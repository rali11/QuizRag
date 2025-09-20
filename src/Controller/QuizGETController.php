<?php

namespace App\Controller;

use App\Quiz\Service\GenerateQuiz;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quiz', name: 'get_quiz', methods: ['GET'])]
class QuizGETController
{
    public function __construct(private GenerateQuiz $generateQuiz) {}

    public function __invoke(): Response
    {
        $quiz = $this->generateQuiz->__invoke();

        echo $quiz->getQuestion().'<br>';
        echo '<ul>';
        foreach ($quiz->getChoices() as $choice) {
            echo '<li>'.$choice['value'].'</li>';
        }
        echo '</ul>';

        return new Response(
            content: '',
            status: Response::HTTP_OK,
            headers: ['Content-Type' => 'text/html']
        );
    }
}
