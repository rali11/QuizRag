<?php

namespace App\Quiz\Service;

use App\Quiz\Model\Quiz;
use App\RAG\Agent\QuizProvinciasAgent;
use NeuronAI\Chat\Messages\UserMessage;

class QuizGenerator implements QuizGeneratorInterface
{
    public function __construct(
        private QuizProvinciasAgent $agent
    ) {}

    public function generateQuiz(array $previousQuizzes = []): Quiz
    {
        $previousQuestions = array_map(fn(Quiz $quiz) => $quiz->getQuestion(), $previousQuizzes);

        $prompt = 'Haz una pregunta sobre la informacion que tengas de tu base de datos vectoriales. Debes responder unicamente con el JSON que tiene la pregunta y las opciones, ni mas ni menos. Asegurate que la pregunta no se haya hecho antes. Las preguntas anteriores son:' . implode(', ', $previousQuestions);
        $response = $this->agent->chat(
            new UserMessage($prompt)
        );

        $data = json_decode($response->getContent(), true);

        return Quiz::fromArray($data);
    }
}
