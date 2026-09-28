<?php
$errors = [];
$success = false;

$questionText = $_POST['questionText'] ?? '';
$type = $_POST['type'] ?? 'text';
$options = $_POST['options'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (trim($questionText) === '') {
        $errors['questionText'] = 'Текст питання є обов\'язковим.';
    }

    if ($type === 'multiple') {
        $optionsList = array_filter(array_map('trim', explode("\n", $options)));
        if (count($optionsList) < 2) {
            $errors['options'] = 'Для типу "multiple" поле options повинно містити щонайменше два варіанти (кожен з нового рядка).';
        }
    }

    if (empty($errors)) {
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Практична 2 - Варіант 12</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 20px auto; }
        .error { color: red; font-size: 0.9em; margin-bottom: 10px; }
        .success { color: green; font-weight: bold; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>

<h2>Створення нового питання</h2>

<?php if ($success): ?>
    <p class="success">Дані успішно збережено! Питання: <?= htmlspecialchars($questionText) ?></p>
    <script>
        localStorage.removeItem('draft_questionText');
        localStorage.removeItem('draft_type');
        localStorage.removeItem('draft_options');
    </script>
<?php endif; ?>

<form method="post" action="index.php" id="surveyForm">
    <div class="form-group">
        <label for="questionText">Текст питання:</label>
        <input type="text" name="questionText" id="questionText"
               value="<?= htmlspecialchars($questionText) ?>" required>
        <?php if (isset($errors['questionText'])): ?>
            <div class="error"><?= $errors['questionText'] ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="type">Тип питання (select):</label>
        <select name="type" id="type">
            <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>Текст (text)</option>
            <option value="single" <?= $type === 'single' ? 'selected' : '' ?>>Один варіант (single)</option>
            <option value="multiple" <?= $type === 'multiple' ? 'selected' : '' ?>>Декілька варіантів (multiple)</option>
        </select>
    </div>

    <div class="form-group">
        <label for="options">Варіанти відповідей (кожен з нового рядка):</label>
        <textarea name="options" id="options" rows="4"><?= htmlspecialchars($options) ?></textarea>
        <?php if (isset($errors['options'])): ?>
            <div class="error"><?= $errors['options'] ?></div>
        <?php endif; ?>
    </div>

    <button type="submit">Зберегти питання</button>
</form>

<script>
    const form = document.getElementById('surveyForm');
    const questionInput = document.getElementById('questionText');
    const typeSelect = document.getElementById('type');
    const optionsArea = document.getElementById('options');

    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        if (localStorage.getItem('draft_questionText')) questionInput.value = localStorage.getItem('draft_questionText');
        if (localStorage.getItem('draft_type')) typeSelect.value = localStorage.getItem('draft_type');
        if (localStorage.getItem('draft_options')) optionsArea.value = localStorage.getItem('draft_options');
    <?php endif; ?>

    form.addEventListener('input', () => {
        localStorage.setItem('draft_questionText', questionInput.value);
        localStorage.setItem('draft_type', typeSelect.value);
        localStorage.setItem('draft_options', optionsArea.value);
    });

    form.addEventListener('submit', (event) => {
        if (typeSelect.value === 'multiple') {
            const opts = optionsArea.value.split('\n').map(s => s.trim()).filter(s => s !== '');
            if (opts.length < 2) {
                event.preventDefault(); // Блокуємо відправку форми на сервер[cite: 2]
                alert('JS Валідація: Для типу "multiple" потрібно щонайменше 2 варіанти відповідей!');
            }
        }
    });
</script>

</body>
</html>