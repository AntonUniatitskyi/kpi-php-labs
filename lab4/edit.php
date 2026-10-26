<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'classes/Question.php';
require_once 'classes/QuestionRepository.php';
require_once 'lib/functions.php';

$pdo = require_once 'db.php';
$repo = new QuestionRepository($pdo);

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

// id: з POST (при збереженні) або з GET (при відкритті форми)
$rawId = $isPost ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    header('Location: index.php?msg=not_found');
    exit;
}

$question = $repo->find($id);
if ($question === null) {
    header('Location: index.php?msg=not_found');
    exit;
}

// Початкові значення форми — поточні дані із БД
$text = $question['text'];
$type = $question['type'];
$errors = [];

if ($isPost) {
    $text = is_string($_POST['text'] ?? null) ? trim($_POST['text']) : '';
    $type = is_string($_POST['type'] ?? null) ? trim($_POST['type']) : '';

    $errors = validateQuestionForm($text, $type);

    if (empty($errors)) {
        // rowCount() = 0, якщо дані не змінилися — це не помилка
        $repo->updateQuestion($id, $text, $type);
        header('Location: index.php?msg=updated');
        exit;
    }
}

$types = questionTypes();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Редагувати питання</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Редагування питання #<?= (int)$id ?></h1>

    <form method="post" action="edit.php">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="field <?= isset($errors['text']) ? 'has-error' : '' ?>">
            <label for="text">Текст питання</label>
            <input type="text" id="text" name="text" class="text-input"
                   value="<?= e($text) ?>" maxlength="255">
            <?php if (isset($errors['text'])): ?>
                <div class="error"><?= e($errors['text']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['type']) ? 'has-error' : '' ?>">
            <label for="type">Тип</label>
            <select id="type" name="type" class="text-input">
                <option value="">— оберіть —</option>
                <?php foreach ($types as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['type'])): ?>
                <div class="error"><?= e($errors['type']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn">Зберегти зміни</button>
        <a href="index.php">Скасувати</a>
    </form>
</div>
</body>
</html>
