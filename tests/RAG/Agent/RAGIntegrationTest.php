<?php

namespace App\Tests\RAG\Agent;

use Doctrine\ORM\EntityManagerInterface;
use App\RAG\Traits\ProvinciasEmbeddingsLoaderTrait;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\RAG;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\RAG\VectorStore\DocumentDoctrineVectorStore;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\SystemPrompt;
use Pgvector\Doctrine\PgvectorSetup;

class RAGIntegrationTest extends KernelTestCase
{
    use ProvinciasEmbeddingsLoaderTrait;

    private RAG $agent;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM document');

        $this->agent = RAG::make()
            ->setVectorStore(
                new DocumentDoctrineVectorStore(
                    entityManager: $entityManager
                )
            )
            ->setEmbeddingsProvider(
                new OllamaEmbeddingsProvider('nomic-embed-text')
            )
            ->withProvider(
                new Ollama(
                    url: 'http://localhost:11434/api',
                    model: 'llama3.1:8b'
                )
            )
            ->withInstructions(
                (string) new SystemPrompt(
                    background: [
                        'Eres un agente especializado en provincias argentinas.',
                        'Tu única fuente de verdad es la base de datos vectorial del sistema RAG. No utilices ningún otro conocimiento externo, ni información general, ni datos previos. Todas tus respuestas deben basarse exclusivamente en lo que recuperes de la base de datos vectorial.'
                    ],
                )
            );

        $provincias = json_decode(
            file_get_contents(__DIR__ . '/../../../src/Database/provincias2.json'),
            true
        );

        $this->loadProvinciasEmbeddings($provincias, $this->agent);
    }

    public function testChatResponseIsCorrect()
    {
        $response = $this->agent->chat(
            new UserMessage('¿Cual es la fecha de autonomia de la provincia Tierra del Fuego?')
        );
        $this->assertStringContainsString('26 de abril de 1990', strtolower($response->getContent()));

        $response = $this->agent->chat(
            new UserMessage('¿Cual es la cantidad de poblacion de jujuy?')
        );
        $this->assertStringContainsString('811611', str_replace('.', '', $response->getContent()));
    }
}
