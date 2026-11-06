CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(255) NOT NULL,
    type VARCHAR(20) NOT NULL          -- text | rating | yesno
) CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4;

INSERT INTO questions (text, type) VALUES
('Як вас звати?', 'text'),
('Яка ваша улюблена мова програмування?', 'text'),
('Оцініть зручність курсу (1-5)', 'rating'),
('Оцініть складність практикумів (1-5)', 'rating'),
('Оцініть якість методичних матеріалів (1-5)', 'rating'),
('Чи користувалися ви раніше PHP?', 'yesno'),
('Чи хотіли б ви продовжити вивчення веб-розробки?', 'yesno'),
('Що б ви змінили в курсі?', 'text');