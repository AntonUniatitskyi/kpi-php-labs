<?php

function formatOptions(array $options): string {
    if (empty($options)) {
        return "<p>Немає варіантів для вибору.</p>";
    }
    $html = "<ul style='margin-top: 5px; margin-bottom: 5px;'>";
    foreach ($options as $option) {
        $html .= "<li>" . htmlspecialchars($option) . "</li>";
    }
    $html .= "</ul>";
    return $html;
}

function validateAnswer(string $answer, array $validOptions): bool {
    return in_array($answer, $validOptions, true);
}

/** Скорочення для безпечного виводу в HTML. */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Допустимі типи питань. */
function questionTypes(): array {
    return [
        Question::TYPE_TEXT     => 'Текстова відповідь',
        Question::TYPE_MULTIPLE => 'З варіантами',
    ];
}

/**
 * Валідація даних форми питання.
 * Повертає масив помилок (порожній, якщо все гаразд).
 */
function validateQuestionForm(string $text, string $type): array {
    $errors = [];

    if ($text === '') {
        $errors['text'] = 'Введіть текст питання.';
    } elseif (mb_strlen($text) > 255) {
        $errors['text'] = 'Текст питання не може бути довшим за 255 символів.';
    }

    if ($type === '') {
        $errors['type'] = 'Оберіть тип питання.';
    } elseif (!validateAnswer($type, array_keys(questionTypes()))) {
        $errors['type'] = 'Недопустимий тип питання.';
    }

    return $errors;
}
