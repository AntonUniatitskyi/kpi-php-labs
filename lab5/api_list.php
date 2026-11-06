<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $q = trim($_GET['q'] ?? '');

    $sql = 'SELECT q.id, q.text, q.type,
                   EXISTS(SELECT 1 FROM answers a WHERE a.question_id = q.id) AS answered
            FROM questions q';
    $params = [];

    if ($q !== '') {
        $sql .= ' WHERE q.type LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY q.id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['answered'] = (bool)$row['answered'];
    }
    unset($row);

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Помилка бази даних'], JSON_UNESCAPED_UNICODE);
}