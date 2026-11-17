<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

const ALLOWED_TYPES = ['text', 'rating', 'yesno'];

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ok(int $code, $data): void {
    respond($code, ['success' => true, 'data' => $data]);
}

function fail(int $code, string $message): void {
    respond($code, ['success' => false, 'error' => $message]);
}

/** Читає тіло запиту: JSON з php://input, а якщо його немає - $_POST. */
function readInput(): array {
    $raw = file_get_contents('php://input');
    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // не JSON - можливо, це звичайна форма
        if (!empty($_POST)) {
            return $_POST;
        }
        fail(400, 'Некоректний JSON у тілі запиту');
    }
    return $_POST;
}

function normalizeQuestion(array $row): array {
    $row['id'] = (int)$row['id'];
    return $row;
}

// ---------- обробники ----------

function listQuestions(PDO $pdo): void {
    $rows = $pdo->query('SELECT id, text, type FROM questions ORDER BY id')->fetchAll();
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

    $text = trim((string)($input['text'] ?? ''));
    $type = trim((string)($input['type'] ?? ''));

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

    header('Location: api.php?resource=questions&id=' . $id);
    ok(201, ['id' => $id, 'text' => $text, 'type' => $type]);
}

function statsQuestions(PDO $pdo): void {
    $rows = $pdo->query(
        'SELECT type, COUNT(*) AS count FROM questions GROUP BY type ORDER BY type'
    )->fetchAll();

    foreach ($rows as &$row) {
        $row['count'] = (int)$row['count'];
    }
    unset($row);

    ok(200, $rows);
}

// ---------- єдина точка входу + маршрутизація ----------

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

    // POST
    if ($action === null) {
        createQuestion($pdo);
    } elseif ($action === 'stats') {
        statsQuestions($pdo);
    } else {
        fail(400, 'Невідома дія: ' . $action);
    }
} catch (PDOException $e) {
    fail(500, 'Помилка бази даних');
}