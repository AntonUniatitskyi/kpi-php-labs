<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/survey.db', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec('CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    text TEXT NOT NULL,
    type TEXT NOT NULL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    answer_text TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
)');

if ((int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn() === 0) {
    $seed = $pdo->prepare('INSERT INTO questions (text, type) VALUES (:text, :type)');
    $questions = [
        ['Як вас звати?', 'text'],
        ['Яка ваша улюблена мова програмування?', 'text'],
        ['Оцініть зручність курсу (1-5)', 'rating'],
        ['Оцініть складність практикумів (1-5)', 'rating'],
        ['Оцініть якість методичних матеріалів (1-5)', 'rating'],
        ['Чи користувалися ви раніше PHP?', 'yesno'],
        ['Чи хотіли б ви продовжити вивчення веб-розробки?', 'yesno'],
        ['Що б ви змінили в курсі?', 'text'],
    ];
    foreach ($questions as [$text, $type]) {
        $seed->execute([':text' => $text, ':type' => $type]);
    }
}