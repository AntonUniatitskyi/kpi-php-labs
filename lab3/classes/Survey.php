<?php

class Survey {
    private array $questions = [];

    public function addQuestion(Question $question): void {
        $this->questions[] = $question;
    }

    public function countByType(string $type): int {
        $count = 0;
        foreach ($this->questions as $question) {
            if ($question->getType() === $type) {
                $count++;
            }
        }
        return $count;
    }

    public function listAll(): array {
        return $this->questions;
    }
}