<?php

namespace App\Quiz\Service;

use App\Quiz\Model\Quiz;
use Symfony\Component\HttpFoundation\RequestStack;

class GenerateQuiz
{
    public function __construct(
        private QuizGeneratorInterface $quizGenerator,
        private RequestStack $requestStack    
    ) {}

    public function __invoke(): Quiz
    {
        $session = $this->requestStack->getSession();
        $previousQuizzes = $session->get('previous_quizzes', []);

        $quiz = $this->quizGenerator->generateQuiz($previousQuizzes);
        $previousQuizzes[] = $quiz;
        
        $session->set('previous_quizzes', $previousQuizzes);

        return $quiz;
    }
}