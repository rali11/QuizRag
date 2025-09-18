<?php

namespace App\Tests\Quiz\Service;

use App\Quiz\Model\Quiz;
use App\Quiz\Service\QuizGenerator;
use App\RAG\Agent\QuizProvinciasAgent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\RAG\Traits\ProvinciasEmbeddingsLoaderTrait;
use Doctrine\ORM\EntityManagerInterface;

class QuizGeneratorIntegrationTest extends KernelTestCase
{
    use ProvinciasEmbeddingsLoaderTrait;

    private QuizGenerator $generator;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $agent = QuizProvinciasAgent::make($entityManager);
        $this->generator = new QuizGenerator($agent);

        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM document');

        $provincias = json_decode(
            file_get_contents(__DIR__ . '/../../../src/Database/provincias2.json'),
            true
        );

        $this->loadProvinciasEmbeddings($provincias, $agent);
    }

    public function testGenerateQuizReturnsValidQuiz()
    {
        $quiz = $this->generator->generateQuiz();
        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertNotEmpty($quiz->getQuestion());
        $this->assertIsArray($quiz->getChoices());
        $this->assertCount(3, $quiz->getChoices());
        $correctCount = 0;
        $values = [];
        foreach ($quiz->getChoices() as $option) {
            $this->assertArrayHasKey('value', $option);
            $values[] = $option['value'];
            if ($quiz->isCorrectChoice($option['id'])) {
                $correctCount++;
            }
        }

        $this->assertEquals(1, $correctCount, 'Debe haber solo una opción correcta');
        $this->assertCount(3, array_unique($values), 'Las opciones deben ser diferentes entre sí');
    }

    public function testGenerateQuizAvoidsPreviousQuestions()
    {
        $firstQuiz = $this->generator->generateQuiz();
        $secondQuiz = $this->generator->generateQuiz([$firstQuiz]);

        $this->assertNotEquals($firstQuiz->getQuestion(), $secondQuiz->getQuestion(), 'La pregunta debe ser diferente a la anterior');
        
    }
}
