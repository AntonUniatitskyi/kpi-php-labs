<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'classes/Question.php';
require_once 'classes/QuestionRepository.php';
require_once 'lib/functions.php';

$pdo = require_once 'db.php';
$repo = new QuestionRepository($pdo);

$text = '';
$type = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = is_string($_POST['text'] ?? null) ? trim($_POST['text']) : '';
    $type = is_string($_POST['type'] ?? null) ? trim($_POST['type']) : '';

    $errors = validateQuestionForm($text, $type);

    if (empty($errors)) {
        $repo->addQuestion($text, $type);
        header('Location: index.php?msg=added');
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
<title>Додати питання</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Нове питання</h1>

    <form method="post" action="add.php">
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

        <button type="submit" class="btn">Зберегти</button>
        <a href="index.php">Скасувати</a>
    </form>
</div>
</body>
</html>
