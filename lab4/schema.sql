CREATE DATABASE IF NOT EXISTS practicum4
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE practicum4;

CREATE TABLE IF NOT EXISTS questions (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    `text` VARCHAR(255) NOT NULL,
    `type` ENUM('text', 'multiple') NOT NULL
);

INSERT INTO questions (`text`, `type`) VALUES
    ('Напишіть ваші враження від вивчення PHP:', 'text'),
    ('Вкажіть посилання на ваш GitHub:', 'text'),
    ('Який ваш улюблений фреймворк?', 'multiple'),
    ('Яку ОС ви використовуєте?', 'multiple');
