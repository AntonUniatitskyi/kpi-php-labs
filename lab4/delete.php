<?php
require_once 'classes/Question.php';
require_once 'classes/QuestionRepository.php';
require_once 'lib/functions.php';

$pdo = require_once 'db.php';
$repo = new QuestionRepository($pdo);

// Видалення — лише через POST (посилання GET можуть спрацювати випадково)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    header('Location: index.php?msg=not_found');
    exit;
}

$deleted = $repo->deleteQuestion($id);

header('Location: index.php?msg=' . ($deleted > 0 ? 'deleted' : 'not_found'));
exit;
