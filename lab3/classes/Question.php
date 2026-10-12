<?php

class Question {
    public const TYPE_TEXT = 'text';
    public const TYPE_MULTIPLE = 'multiple';

    protected string $text;
    protected string $type;

    public function __construct(string $text, string $type) {
        $this->text = $text;
        $this->type = $type;
    }

    public function getInfo(): string {
        return "Питання: '{$this->text}' (Тип: {$this->type})";
    }

    public function getText(): string {
        return $this->text;
    }

    public function getType(): string {
        return $this->type;
    }
}