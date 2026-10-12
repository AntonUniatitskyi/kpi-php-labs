<?php

class MultipleChoiceQuestion extends Question {
    protected array $options;

    public function __construct(string $text, array $options) {
        parent::__construct($text, self::TYPE_MULTIPLE);
        $this->options = $options;
    }

    public function getInfo(): string {
        $baseInfo = parent::getInfo();
        $optionsCount = count($this->options);
        $optionsList = implode(', ', $this->options);
        return $baseInfo . " | Варіанти ({$optionsCount}): {$optionsList}";
    }

    public function getOptions(): array {
        return $this->options;
    }
}