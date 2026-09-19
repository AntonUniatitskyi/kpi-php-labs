<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Строго за умовою: text, type, answersCount. 
// Питання трохи наближені до реалій розробника :)
$questions = [
    [
        'text' => 'Яку операційну систему ви використовуєте як основну для розробки?',
        'type' => 'multiple',
        'answersCount' => 125
    ],
    [
        'text' => 'Опишіть ваш досвід налаштування кастомних shell-середовищ:',
        'type' => 'text',
        'answersCount' => 42
    ],
    [
        'text' => 'Який бекенд-фреймворк ви обираєте для нових проєктів?',
        'type' => 'multiple',
        'answersCount' => 98
    ],
    [
        'text' => 'Як ви ставитесь до трекпоїнтів на клавіатурі ноутбука?',
        'type' => 'multiple',
        'answersCount' => 30
    ],
    [
        'text' => 'Залиште посилання на ваш GitHub:',
        'type' => 'text',
        'answersCount' => 150
    ]
];

// Типізована функція форматування
function formatQuestion(array $question): string {
    return "<div class='q-title'>{$question['text']}</div> <div class='q-stats'>(Зібрано відповідей: {$question['answersCount']})</div>";
}

// Агрегатний показник
$totalAnswers = 0;
foreach ($questions as $question) {
    $totalAnswers += $question['answersCount'];
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Опитування розробників</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; margin: 20px; background-color: #f8f9fa; color: #212529; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card { border: 1px solid #e9ecef; padding: 20px; margin-bottom: 20px; border-radius: 8px; transition: border-color 0.2s; }
        .card:hover { border-color: #ced4da; }
        
        .q-title { font-size: 1.1em; font-weight: 600; margin-bottom: 5px; }
        .q-stats { font-size: 0.85em; color: #6c757d; margin-bottom: 15px; }
        
        .badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.75em; font-weight: 700; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-multiple { background-color: #e0f2fe; color: #0369a1; }
        .badge-text { background-color: #f1f5f9; color: #475569; }
        
        /* Стилі для інтерактивних елементів */
        .options-group { display: flex; flex-direction: column; gap: 10px; }
        .radio-label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95em; }
        .text-input { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 0.95em; box-sizing: border-box; font-family: inherit; }
        .text-input:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        
        .summary { background: #ecfdf5; padding: 20px; border-radius: 8px; margin-top: 30px; border: 1px solid #d1fae5; color: #065f46; text-align: center;}
        .summary h2 { margin: 0 0 10px 0; font-size: 1.2em; }
        .summary .big-number { font-size: 2em; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h1>Опитування розробників</h1>

    <form action="#" method="GET" onsubmit="event.preventDefault(); alert('Це лише демо форми для Практичної №1!');">
        <?php foreach ($questions as $index => $question): ?>
            <?php 
                // Умовна логіка
                $isMultiple = ($question['type'] === 'multiple');
                $typeLabel = $isMultiple ? 'Питання з варіантами відповіді' : 'Текстова відповідь';
                $badgeClass = $isMultiple ? 'badge-multiple' : 'badge-text';
            ?>
            
            <div class="card">
                <span class="badge <?= $badgeClass ?>"><?= $typeLabel ?></span>
                
                <!-- Вивід форматованого тексту питання -->
                <?= formatQuestion($question) ?>
                
                <!-- Інтерактивна частина -->
                <?php if ($isMultiple): ?>
                    <div class="options-group">
                        <label class="radio-label">
                            <input type="radio" name="q_<?= $index ?>" value="1"> Варіант відповіді 1
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="q_<?= $index ?>" value="2"> Варіант відповіді 2
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="q_<?= $index ?>" value="3"> Інше...
                        </label>
                    </div>
                <?php else: ?>
                    <input type="text" class="text-input" name="q_<?= $index ?>" placeholder="Напишіть вашу відповідь тут...">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <button type="submit" style="background: #0284c7; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 1em; font-weight: 600; width: 100%;">
            Відправити відповіді
        </button>
    </form>

    <div class="summary">
        <h2>Загальна активність</h2>
        <p>Сумарна кількість зібраних відповідей у базі:</p>
        <div class="big-number"><?= $totalAnswers ?></div>
    </div>
</div>

</body>
</html>