<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'classes/Question.php';
require_once 'classes/MultipleChoiceQuestion.php';
require_once 'classes/Survey.php';
require_once 'lib/functions.php';

// --- Створення менеджера та об'єктів ---
$survey = new Survey();

$survey->addQuestion(new Question("Напишіть ваші враження від вивчення PHP:", Question::TYPE_TEXT));
$survey->addQuestion(new Question("Вкажіть посилання на ваш GitHub:", Question::TYPE_TEXT));
$survey->addQuestion(new MultipleChoiceQuestion("Який ваш улюблений фреймворк?", ["Laravel", "Symfony", "Yii2", "Немає"]));
$survey->addQuestion(new MultipleChoiceQuestion("Яку ОС ви використовуєте?", ["NixOS", "Ubuntu", "Windows", "macOS"]));

$questions = $survey->listAll();
$totalQuestions = count($questions);
$textCount = $survey->countByType(Question::TYPE_TEXT);
$multipleCount = $survey->countByType(Question::TYPE_MULTIPLE);

// --- Обробка форми ---
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$answers = $_POST['answers'] ?? [];
$values = [];
$errors = [];

foreach ($questions as $i => $question) {
    $values[$i] = (isset($answers[$i]) && is_string($answers[$i])) ? trim($answers[$i]) : '';
}

if ($submitted) {
    foreach ($questions as $i => $question) {
        if ($question instanceof MultipleChoiceQuestion) {
            if ($values[$i] === '') {
                $errors[$i] = 'Оберіть варіант відповіді.';
            } elseif (!validateAnswer($values[$i], $question->getOptions())) {
                $errors[$i] = 'Недопустимий варіант відповіді.';
            }
        } elseif ($values[$i] === '') {
            $errors[$i] = 'Введіть відповідь.';
        }
    }
}
$success = $submitted && empty($errors);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Опитування (Практична 3 - Варіант 12)</title>
<style>
body { font-family: system-ui, -apple-system, sans-serif; margin: 20px; background: #f8f9fa; color: #212529; }
.container { max-width: 700px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.summary { background: #f1f5f9; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95em; }
.card { border: 1px solid #e9ecef; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
.card.has-error { border-color: #dc3545; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.75em; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; }
.badge-multiple { background: #e0f2fe; color: #0369a1; }
.badge-text { background: #f1f5f9; color: #475569; }
.q-title { font-size: 1.1em; font-weight: 600; margin-bottom: 4px; }
.q-info { font-size: 0.8em; color: #6c757d; margin-bottom: 12px; }
.options-group { display: flex; flex-direction: column; gap: 10px; }
.radio-label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.text-input { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
.text-input:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2,132,199,0.1); }
.error { color: #dc3545; font-size: 0.9em; margin-top: 8px; }
.success { background: #ecfdf5; border: 1px solid #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
button { background: #0284c7; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 1em; font-weight: 600; width: 100%; }
</style>
</head>
<body>
<div class="container">
    <h1>Онлайн-опитування</h1>

    <div class="summary">
        Всього питань: <strong><?= $totalQuestions ?></strong> |
        Текстових: <strong><?= $textCount ?></strong> |
        З вибором: <strong><?= $multipleCount ?></strong>
    </div>

    <?php if ($success): ?>
        <div class="success">
            <strong>Дякуємо! Усі відповіді прийнято:</strong>
            <ul>
                <?php foreach ($questions as $i => $question): ?>
                    <li><?= htmlspecialchars($question->getText()) ?> →
                        <strong><?= htmlspecialchars($values[$i]) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php">
        <?php foreach ($questions as $i => $question): ?>
            <?php $isMultiple = $question instanceof MultipleChoiceQuestion; ?>
            <div class="card <?= isset($errors[$i]) ? 'has-error' : '' ?>">
                <span class="badge <?= $isMultiple ? 'badge-multiple' : 'badge-text' ?>">
                    <?= $isMultiple ? 'З варіантами' : 'Текстова відповідь' ?>
                </span>
                <div class="q-title"><?= htmlspecialchars($question->getText()) ?></div>
                <div class="q-info"><?= htmlspecialchars($question->getInfo()) ?></div>

                <?php if ($isMultiple): ?>
                    <div class="options-group">
                        <?php foreach ($question->getOptions() as $option): ?>
                            <label class="radio-label">
                                <input type="radio" name="answers[<?= $i ?>]"
                                       value="<?= htmlspecialchars($option) ?>"
                                       <?= $values[$i] === $option ? 'checked' : '' ?>>
                                <?= htmlspecialchars($option) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <input type="text" class="text-input" name="answers[<?= $i ?>]"
                           value="<?= htmlspecialchars($values[$i]) ?>"
                           placeholder="Напишіть вашу відповідь тут...">
                <?php endif; ?>

                <?php if (isset($errors[$i])): ?>
                    <div class="error"><?= htmlspecialchars($errors[$i]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit">Відправити відповіді</button>
    </form>
</div>
</body>
</html>