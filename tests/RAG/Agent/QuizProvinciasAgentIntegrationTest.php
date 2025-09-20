<?php

namespace App\Tests\RAG\Agent;

use App\RAG\Agent\QuizProvinciasAgent;
use App\RAG\Traits\ProvinciasEmbeddingsLoaderTrait;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QuizProvinciasAgentIntegrationTest extends KernelTestCase
{
    use ProvinciasEmbeddingsLoaderTrait;

    private QuizProvinciasAgent $agent;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM document');

        $this->agent = QuizProvinciasAgent::make($entityManager);

        $provincias = json_decode(
            file_get_contents(__DIR__ . '/../../../src/Database/provincias2.json'),
            true
        );

        $this->loadProvinciasEmbeddings($provincias, $this->agent);
    }

    public function testChatResponseFormatIsCorrect()
    {
        $response = $this->agent->chat(
            new UserMessage('Haz una pregunta sobre la informacion que tengas de tu base de datos vectoriales. Debes responder unicamente con el JSON que tiene la pregunta y las opciones, ni mas ni menos.')
        );

        $json = json_decode($response->getContent(), true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('pregunta', $json);
        $this->assertArrayHasKey('opciones', $json);
        $this->assertIsArray($json['opciones']);
        $this->assertCount(3, $json['opciones']);

        $correctCount = 0;
        $valores = [];
        foreach ($json['opciones'] as $opcion) {
            $this->assertArrayHasKey('valor', $opcion);
            $this->assertArrayHasKey('es_correcto', $opcion);
            $valores[] = $opcion['valor'];
            if ($opcion['es_correcto']) {
                $correctCount++;
            }
        }
        $this->assertEquals(1, $correctCount, 'Debe haber solo una opción correcta');
        $this->assertCount(3, array_unique($valores), 'Las opciones deben ser diferentes entre sí');
    }

    public function testChatResponseIsCorrect()
    {
        $response = $this->agent->chat(
            new UserMessage('Haz una pregunta sobre la fecha de autonomia de La Pampa con la informacion que obtengas de tu base de datos vectoriales. Debes responder unicamente con el JSON que tiene la pregunta y las opciones, ni mas ni menos.')
        );

        $json = json_decode($response->getContent(), true);

        $correctResponse = "";
        foreach ($json['opciones'] as $opcion) {
            if ($opcion['es_correcto']) {
                $correctResponse = $opcion['valor'];
                break;
            }
        }

        $this->assertStringContainsString('8 de Agosto de 1951', $correctResponse);
    }
}
