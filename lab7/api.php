<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$startTime = microtime(true);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_error_handler(function (int $no, string $str, string $file, int $line) {
    if (!(error_reporting() & $no)) {
        return false;
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});
set_exception_handler(function (Throwable $e) {
    error_log((string)$e);
    fail(500, 'Внутрішня помилка сервера');
});

const ALLOWED_TYPES = ['text', 'rating', 'yesno'];
const ALLOWED_MODES = ['n1', 'group', 'cached'];
const CACHE_TTL     = 60;
const CACHE_KEY     = 'countByType';

function respond(int $code, array $payload): void {
    global $startTime;
    $elapsedMs = (microtime(true) - $startTime) * 1000;
    $peakMb    = memory_get_peak_usage(true) / 1024 / 1024;
    $queries   = CountingPDO::$queries;

    http_response_code($code);
    header(sprintf('X-Time-Ms: %.2f', $elapsedMs));
    header(sprintf('X-Peak-Memory-MB: %.2f', $peakMb));
    header('X-SQL-Queries: ' . $queries);
    error_log(sprintf('Час: %.2f мс, пам\'ять: %.2f МБ, SQL-запитів: %d', $elapsedMs, $peakMb, $queries));

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ok(int $code, $data): void {
    respond($code, ['success' => true, 'data' => $data]);
}

function fail(int $code, string $message): void {
    respond($code, ['success' => false, 'error' => $message]);
}

function readInput(): array {
    $raw = file_get_contents('php://input');
    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (!empty($_POST)) {
            return $_POST;
        }
        fail(400, 'Некоректний JSON у тілі запиту');
    }
    return $_POST;
}

function inputString(array $input, string $key): string {
    $value = $input[$key] ?? '';
    if (!is_string($value)) {
        fail(400, 'Поле "' . $key . '" має бути рядком');
    }
    return trim($value);
}

function normalizeQuestion(array $row): array {
    $row['id'] = (int)$row['id'];
    if (isset($row['type_count'])) {
        $row['type_count'] = (int)$row['type_count'];
    }
    return $row;
}


function cachePath(string $key): string {
    return sys_get_temp_dir() . '/survey_cache_' . md5($key) . '.json';
}

function cached(string $key, int $ttl, callable $compute): array {
    $file = cachePath($key);
    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        $data = json_decode((string)file_get_contents($file), true);
        if (is_array($data)) {
            return $data;
        }
    }
    $result = $compute();
    try {
        $tmp = $file . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, json_encode($result));
        rename($tmp, $file);
    } catch (Throwable $e) {
        error_log('Кеш не записано: ' . $e->getMessage());
    }
    return $result;
}

function invalidateCache(string $key): void {
    @unlink(cachePath($key));
}

function countByTypeFresh(PDO $pdo): array {
    $rows = $pdo->query(
        'SELECT type, COUNT(*) AS count FROM questions GROUP BY type ORDER BY type'
    )->fetchAll();
    foreach ($rows as &$row) {
        $row['count'] = (int)$row['count'];
    }
    unset($row);
    return $rows;
}

function countByType(PDO $pdo): array {
    return cached(CACHE_KEY, CACHE_TTL, fn() => countByTypeFresh($pdo));
}

function listQuestions(PDO $pdo): void {
    $mode = $_GET['mode'] ?? 'cached';
    if (!in_array($mode, ALLOWED_MODES, true)) {
        fail(400, 'Параметр "mode" має бути одним із: ' . implode(', ', ALLOWED_MODES));
    }

    $rows = $pdo->query('SELECT id, text, type FROM questions ORDER BY id')->fetchAll();

    if ($mode === 'n1') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE type = :type');
        foreach ($rows as &$row) {
            $stmt->execute([':type' => $row['type']]);
            $row['type_count'] = $stmt->fetchColumn();
        }
        unset($row);
    } else {
        $stats  = $mode === 'group' ? countByTypeFresh($pdo) : countByType($pdo);
        $counts = array_column($stats, 'count', 'type');
        foreach ($rows as &$row) {
            $row['type_count'] = $counts[$row['type']] ?? 0;
        }
        unset($row);
    }

    ok(200, array_map('normalizeQuestion', $rows));
}

function getQuestion(PDO $pdo, int $id): void {
    $stmt = $pdo->prepare('SELECT id, text, type FROM questions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        fail(404, 'Питання з id=' . $id . ' не знайдено');
    }
    ok(200, normalizeQuestion($row));
}

function createQuestion(PDO $pdo): void {
    $input = readInput();

    $text = inputString($input, 'text');
    $type = inputString($input, 'type');

    if ($text === '') {
        fail(400, 'Поле "text" обов\'язкове');
    }
    if (mb_strlen($text) > 255) {
        fail(400, 'Поле "text" занадто довге (максимум 255 символів)');
    }
    if ($type === '') {
        fail(400, 'Поле "type" обов\'язкове');
    }
    if (!in_array($type, ALLOWED_TYPES, true)) {
        fail(400, 'Поле "type" має бути одним із: ' . implode(', ', ALLOWED_TYPES));
    }

    $stmt = $pdo->prepare('INSERT INTO questions (text, type) VALUES (:text, :type)');
    $stmt->execute([':text' => $text, ':type' => $type]);
    $id = (int)$pdo->lastInsertId();

    invalidateCache(CACHE_KEY);

    header('Location: api.php?resource=questions&id=' . $id);
    ok(201, ['id' => $id, 'text' => $text, 'type' => $type]);
}

function statsQuestions(PDO $pdo): void {
    ok(200, countByType($pdo));
}


$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;
$idParam  = $_GET['id'] ?? null;
$action   = $_GET['action'] ?? null;

try {
    if ($resource !== 'questions') {
        fail(404, 'Невідомий ресурс');
    }

    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        fail(405, 'Метод ' . $method . ' не підтримується для ресурсу questions');
    }

    $pdo = getPdo();
    CountingPDO::$queries = 0;

    if ($method === 'GET') {
        if ($action !== null) {
            fail(400, 'Параметр action підтримується лише в POST');
        }
        if ($idParam === null) {
            listQuestions($pdo);
        }
        $id = filter_var($idParam, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            fail(400, 'Параметр "id" має бути додатним цілим числом');
        }
        getQuestion($pdo, $id);
    }

    if ($action === null) {
        createQuestion($pdo);
    } elseif ($action === 'stats') {
        statsQuestions($pdo);
    } else {
        fail(400, 'Невідома дія: ' . (is_string($action) ? $action : 'некоректне значення'));
    }
} catch (PDOException $e) {
    error_log('PDO: ' . $e->getMessage());
    fail(500, 'Помилка бази даних');
} catch (Throwable $e) {
    error_log((string)$e);
    fail(500, 'Внутрішня помилка сервера');
}