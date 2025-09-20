<?php

namespace App\Quiz\Service;

use App\Quiz\Model\Quiz;

interface QuizGeneratorInterface
{
    /**
     * Generate a quiz question with multiple choices.
     *
     * @param  Quiz[]  $previousQuizzes  An array of previously generated quizzes to avoid repetition.
     * @return Quiz
     */
    public function generateQuiz(array $previousQuizzes = []): Quiz;
}