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