<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Дозволений лише метод POST']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$questionId = filter_var($input['question_id'] ?? null, FILTER_VALIDATE_INT);
$answerText = trim((string)($input['answer_text'] ?? ''));

if (!$questionId) {
    respond(400, ['error' => 'Не вказано question_id']);
}
if ($answerText === '') {
    respond(400, ['error' => 'Відповідь не може бути порожньою']);
}
if (mb_strlen($answerText) > 500) {
    respond(400, ['error' => 'Відповідь занадто довга (максимум 500 символів)']);
}

try {
    $check = $pdo->prepare('SELECT id FROM questions WHERE id = :id');
    $check->execute([':id' => $questionId]);
    if (!$check->fetch()) {
        respond(404, ['error' => 'Питання не знайдено']);
    }

    $dup = $pdo->prepare('SELECT id FROM answers WHERE question_id = :id');
    $dup->execute([':id' => $questionId]);
    if ($dup->fetch()) {
        respond(409, ['error' => 'На це питання вже є відповідь']);
    }

    $insert = $pdo->prepare(
        'INSERT INTO answers (question_id, answer_text) VALUES (:qid, :text)'
    );
    $insert->execute([':qid' => $questionId, ':text' => $answerText]);
    $newId = (int)$pdo->lastInsertId();

    $answered = (int)$pdo->query('SELECT COUNT(DISTINCT question_id) FROM answers')->fetchColumn();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();

    respond(201, [
        'id' => $newId,
        'question_id' => $questionId,
        'answer_text' => $answerText,
        'answered' => $answered,
        'total' => $total,
    ]);
} catch (PDOException $e) {
    respond(500, ['error' => 'Помилка бази даних']);
}