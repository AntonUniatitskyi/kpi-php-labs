<?php
//   php tools.php seed 1000        — додати N тестових питань (для помітної різниці в замірах)
//   php tools.php explain          — EXPLAIN QUERY PLAN для запиту з фільтром і для GROUP BY
//   php tools.php index create     — CREATE INDEX idx_questions_type
//   php tools.php index drop       — DROP INDEX
//   php tools.php cache clear      — видалити файли кешу
//   php tools.php reset            — видалити survey.db і кеш (БД створиться заново з 8 питаннями)
if (PHP_SAPI !== 'cli') {
    exit('Лише для командного рядка');
}
require_once __DIR__ . '/db.php';

$cmd = $argv[1] ?? '';

function clearCache(): int {
    $n = 0;
    foreach (glob(sys_get_temp_dir() . '/survey_cache_*') ?: [] as $file) {
        if (@unlink($file)) {
            $n++;
        }
    }
    return $n;
}

function explain(PDO $pdo, string $sql): void {
    echo "\n> $sql\n";
    foreach ($pdo->query('EXPLAIN QUERY PLAN ' . $sql)->fetchAll() as $r) {
        echo '  ' . $r['detail'] . "\n";
    }
}

if ($cmd === 'reset') {
    @unlink(__DIR__ . '/survey.db');
    echo 'survey.db видалено, файлів кешу видалено: ' . clearCache() . "\n";
    exit;
}
if ($cmd === 'cache') {
    if (($argv[2] ?? '') === 'clear') {
        echo 'Файлів кешу видалено: ' . clearCache() . "\n";
    } else {
        echo "Використання: php tools.php cache clear\n";
    }
    exit;
}

$pdo = getPdo();

switch ($cmd) {
    case 'seed':
        $n = max(1, (int)($argv[2] ?? 1000));
        $types = ['text', 'rating', 'yesno'];
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO questions (text, type) VALUES (:text, :type)');
        for ($i = 1; $i <= $n; $i++) {
            $stmt->execute([':text' => "Тестове питання #$i", ':type' => $types[array_rand($types)]]);
        }
        $pdo->commit();
        $total = $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
        echo "Додано $n. Усього питань: $total\n";
        break;

    case 'explain':
        explain($pdo, "SELECT * FROM questions WHERE type = 'text'");
        explain($pdo, "SELECT COUNT(*) FROM questions WHERE type = 'text'");
        explain($pdo, 'SELECT type, COUNT(*) FROM questions GROUP BY type');
        break;

    case 'index':
        $action = $argv[2] ?? '';
        if ($action === 'create') {
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_questions_type ON questions (type)');
            echo "Індекс створено\n";
        } elseif ($action === 'drop') {
            $pdo->exec('DROP INDEX IF EXISTS idx_questions_type');
            echo "Індекс видалено\n";
        } else {
            echo "Використання: php tools.php index create|drop\n";
        }
        break;

    default:
        echo "Команди: seed [N] | explain | index create|drop | cache clear | reset\n";
}