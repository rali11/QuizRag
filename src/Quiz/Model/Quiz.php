<?php

namespace App\Quiz\Model;

class Quiz
{
    private string $question;
    private array $choices;

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getChoices(): array
    {
        return array_map(fn($opcion) => [
            'id' => $opcion['id'],
            'value' => $opcion['value']
        ], $this->choices);
    }

    public function isCorrectChoice(string $choiceId): bool
    {
        foreach ($this->choices as $choice) {
            if ($choice['id'] === $choiceId) {
                return $choice['is_correct'];
            }
        }

        throw new \InvalidArgumentException("Invalid choice ID: $choiceId");
    }

    static function fromArray(array $data): self
    {
        $quiz = new self();
        $quiz->question = $data['pregunta'];
        $quiz->choices = array_map(fn($opcion) => [
                'id' => uniqid(),
                'value' => $opcion['valor'],
                'is_correct' => $opcion['es_correcto']
            ], $data['opciones']
        );

        return $quiz;
    }
}
