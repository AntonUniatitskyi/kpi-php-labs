<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'classes/Question.php';
require_once 'classes/QuestionRepository.php';
require_once 'lib/functions.php';

$pdo = require_once 'db.php';
$repo = new QuestionRepository($pdo);

$questions = $repo->listAll();
$counts = $repo->countByType();
$total = count($questions);

// Повідомлення після redirect (дозволені лише відомі значення)
$messages = [
    'added'     => ['ok',  'Питання додано.'],
    'updated'   => ['ok',  'Питання оновлено.'],
    'deleted'   => ['ok',  'Питання видалено.'],
    'not_found' => ['err', 'Питання не знайдено.'],
];
$flash = $messages[$_GET['msg'] ?? ''] ?? null;
$types = questionTypes();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Опитування (Практична 4 - Варіант 12)</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Питання опитування</h1>

    <?php if ($flash): ?>
        <div class="flash flash-<?= $flash[0] ?>"><?= e($flash[1]) ?></div>
    <?php endif; ?>

    <div class="summary">
        Всього питань: <strong><?= $total ?></strong> |
        Текстових: <strong><?= $counts[Question::TYPE_TEXT] ?></strong> |
        З вибором: <strong><?= $counts[Question::TYPE_MULTIPLE] ?></strong>
    </div>

    <p><a class="btn" href="add.php">+ Додати питання</a></p>

    <?php if ($total === 0): ?>
        <p class="empty">Питань поки немає.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Текст</th><th>Тип</th><th>Дії</th></tr>
            </thead>
            <tbody>
            <?php foreach ($questions as $q): ?>
                <tr>
                    <td><?= (int)$q['id'] ?></td>
                    <td><?= e($q['text']) ?></td>
                    <td>
                        <span class="badge <?= $q['type'] === Question::TYPE_MULTIPLE ? 'badge-multiple' : 'badge-text' ?>">
                            <?= e($types[$q['type']] ?? $q['type']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="edit.php?id=<?= (int)$q['id'] ?>">Редагувати</a>
                            <form method="post" action="delete.php"
                                  onsubmit="return confirm('Видалити це питання?');">
                                <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                                <button type="submit" class="btn-link">Видалити</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
