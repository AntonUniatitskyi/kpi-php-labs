#!/usr/bin/env bash
# Автоматична перевірка практичної №7: виконує всі команди послідовно,
# показує кожну команду та її вивід, пише лог і зведену таблицю «до / після».
#
#   bash run_checks.sh                 # 1000 питань, ab -n 200 -c 20
#   ROWS=500 AB_N=100 bash run_checks.sh
#
# Потрібні: php, curl, ab (NixOS: nix-shell -p php apacheHttpd).
set -u
cd "$(dirname "$0")"

PORT="${PORT:-8089}"
ROWS="${ROWS:-1000}"
AB_N="${AB_N:-200}"
AB_C="${AB_C:-20}"
BASE="http://127.0.0.1:$PORT"
API="$BASE/api.php?resource=questions"

mkdir -p results
STAMP=$(date +%Y%m%d_%H%M%S)
LOG="results/run_$STAMP.log"
SUMMARY="results/summary_$STAMP.md"
exec > >(tee -a "$LOG") 2>&1

for tool in php curl ab; do
    command -v "$tool" >/dev/null 2>&1 || { echo "Не знайдено програму: $tool"; exit 1; }
done


step() {
    echo
    echo "================================================================"
    echo "$*"
    echo "================================================================"
}

run() {                       # показати команду і виконати її
    echo
    echo "\$ $*"
    "$@"
}

T=""; M=""; Q=""; RPS=""; TPR=""; FAIL=""

measure_one() {               # один запит: час, пам'ять, кількість SQL (з заголовків X-*)
    local url="$API&mode=$1"
    echo
    echo "\$ curl -s -o /dev/null -D - \"$url\" | grep '^X-'"
    local h
    h=$(curl -s -o /dev/null -D - "$url" | tr -d '\r')
    echo "$h" | grep '^X-'
    T=$(echo "$h" | awk -F': ' '/^X-Time-Ms/{print $2}')
    M=$(echo "$h" | awk -F': ' '/^X-Peak-Memory-MB/{print $2}')
    Q=$(echo "$h" | awk -F': ' '/^X-SQL-Queries/{print $2}')
}

measure_ab() {                # навантажувальний тест Apache Bench
    local url="$API&mode=$1"
    echo
    echo "\$ ab -n $AB_N -c $AB_C -q \"$url\""
    local out
    out=$(ab -n "$AB_N" -c "$AB_C" -q "$url" 2>&1)
    echo "$out" | grep -E 'Complete requests|Failed requests|Requests per second|Time per request'
    RPS=$(echo "$out" | awk '/Requests per second/{print $4}')
    TPR=$(echo "$out" | awk '/Time per request/ && /\(mean\)$/{print $4}')
    FAIL=$(echo "$out" | awk '/Failed requests/{print $3}')
}

scenario() {                  # label mode
    step "$1  (mode=$2)"
    measure_one "$2"
    measure_ab "$2"
    echo "| $1 | $T | $M | $Q | $RPS | $TPR | $FAIL |" >> "$SUMMARY"
}

SERVER_PID=""
cleanup() {
    if [ -n "$SERVER_PID" ]; then
        pkill -P "$SERVER_PID" 2>/dev/null
        kill "$SERVER_PID" 2>/dev/null
    fi
}
trap cleanup EXIT


step "0. Підготовка: чиста БД, $ROWS питань, без індексу, запуск сервера"
if curl -s -o /dev/null --max-time 1 "$BASE/api.php?resource=foo"; then
    echo "Порт $PORT уже зайнятий. Зупиніть сервер або задайте інший: PORT=8090 bash run_checks.sh"
    exit 1
fi
run php tools.php reset
run php tools.php seed "$ROWS"
run php tools.php index drop

echo
echo "\$ PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:$PORT   (у фоні)"
PHP_CLI_SERVER_WORKERS=4 php -S "127.0.0.1:$PORT" > results/server.log 2>&1 &
SERVER_PID=$!
for _ in $(seq 1 50); do
    curl -s -o /dev/null --max-time 1 "$BASE/api.php?resource=foo" && break
    sleep 0.2
done

{
    echo "# Зведені результати (практична №7)"
    echo
    echo "- Дата: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "- PHP: $(php -r 'echo PHP_VERSION;')"
    echo "- Питань у таблиці: $ROWS (+8 початкових)"
    echo "- ab: -n $AB_N -c $AB_C"
    echo
    echo "| Сценарій | Час, мс | Пам'ять, МБ | SQL-запитів | Req/s | Time per request, мс | Failed |"
    echo "|----------|--------:|------------:|------------:|------:|---------------------:|-------:|"
} > "$SUMMARY"


scenario "ДО: N+1, без індексу" n1

step "4. Індекс: EXPLAIN до і після"
run php tools.php explain
run php tools.php index create
run php tools.php explain
scenario "N+1, з індексом" n1

scenario "GROUP BY (без N+1), з індексом" group

step "6. Кеш: холодний і теплий запит"
run php tools.php cache clear
echo "(холодний запит: кеш порожній -> список + GROUP BY = 2 запити)"
measure_one cached
echo "(теплий запит: результат countByType() з кешу -> лише список = 1 запит)"
measure_one cached

scenario "ПІСЛЯ: GROUP BY + кеш, з індексом" cached

step "6.1 Інвалідація кешу після додавання питання"
echo "(статистика до додавання)"
run curl -s -X POST "$API&action=stats"; echo
echo "(додаємо питання типу text)"
run curl -s -X POST "$API" -H "Content-Type: application/json" -d '{"text":"Тест інвалідації кешу","type":"text"}'; echo
echo "(статистика після: count для text має зрости на 1 одразу, а не через 60 с)"
run curl -s -X POST "$API&action=stats"; echo

step "7. Перевірка обробки некоректного вводу (без Warning, лише JSON)"
run curl -s -i -X POST "$API" -d 'text[]=x&type=text'; echo
run curl -s -i -g -X POST "$API&action[]=x"; echo

step "Готово"
echo "Лог:             $LOG"
echo "Лог сервера:     results/server.log"
echo "Зведена таблиця: $SUMMARY"
echo
cat "$SUMMARY"
sleep 0.3