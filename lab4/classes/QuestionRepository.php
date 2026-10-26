<?php

class QuestionRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /** Усі питання. */
    public function listAll(): array {
        return $this->pdo->query('SELECT * FROM questions ORDER BY id')->fetchAll();
    }

    /** Кількість питань за типом: ['text' => N, 'multiple' => M]. */
    public function countByType(): array {
        $rows = $this->pdo
            ->query('SELECT `type`, COUNT(*) AS cnt FROM questions GROUP BY `type`')
            ->fetchAll();

        $counts = ['text' => 0, 'multiple' => 0];
        foreach ($rows as $row) {
            $counts[$row['type']] = (int)$row['cnt'];
        }
        return $counts;
    }

    /** Одне питання за id або null. */
    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM questions WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** INSERT. Повертає id нового запису. */
    public function addQuestion(string $text, string $type): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO questions (`text`, `type`) VALUES (:text, :type)'
        );
        $stmt->execute([':text' => $text, ':type' => $type]);
        return (int)$this->pdo->lastInsertId();
    }

    /** UPDATE. Повертає кількість змінених рядків. */
    public function updateQuestion(int $id, string $text, string $type): int {
        $stmt = $this->pdo->prepare(
            'UPDATE questions SET `text` = :text, `type` = :type WHERE id = :id'
        );
        $stmt->execute([':text' => $text, ':type' => $type, ':id' => $id]);
        return $stmt->rowCount();
    }

    /** DELETE. Повертає кількість видалених рядків. */
    public function deleteQuestion(int $id): int {
        $stmt = $this->pdo->prepare('DELETE FROM questions WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }
}
