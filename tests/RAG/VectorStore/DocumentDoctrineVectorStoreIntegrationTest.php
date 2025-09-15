<?php

namespace App\Tests\RAG\VectorStore;

use App\RAG\VectorStore\DocumentDoctrineVectorStore;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Pgvector\Doctrine\PgvectorSetup;

class DocumentDoctrineVectorStoreIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DocumentDoctrineVectorStore $vectorStore;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        // Registrar los tipos de Pgvector solo si no existen
        try {
            PgvectorSetup::registerTypes($this->entityManager);
        } catch (\Exception $e) {
            // Los tipos ya están registrados, no hacer nada
        }
        $this->vectorStore = new DocumentDoctrineVectorStore($this->entityManager);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM document');
    }

    public function testAddDocumentAndSimilaritySearch()
    {
        $ollamaEmbedding = new OllamaEmbeddingsProvider('nomic-embed-text');
        $contents = [
            ['text' => 'Raúl Eduardo Correa nació el 10 de Septiembre de 1995.','metadata' => ['birthdate' => '10 de Septiembre de 1995']],
            ['text' => 'Raúl Eduardo Correa vive en la ciudad de Temperley, provincia de Buenos Aires, país de Argentina.','metadata' => ['location' => 'Temperley, Buenos Aires, Argentina']],
            ['text' => 'Raúl Eduardo Correa trabaja como programador en Mercado Libre.','metadata' => ['job' => 'programador en Mercado Libre']]
        ];
        $documents = [];

        foreach ($contents as $content) {
            $doc = new Document();
            $doc->embedding = $ollamaEmbedding->embedText($content['text']);
            $doc->content = $content['text'];
            foreach ($content['metadata'] as $key => $value) {
                $doc->addMetadata($key, $value);
            }
            $documents[] = $doc;
        }

        foreach ($documents as $document) {
            $this->vectorStore->addDocument($document);
        }

        $embeddingQuestion = $ollamaEmbedding->embedText('¿Dónde trabaja Raúl Eduardo Correa?');
        $results = $this->vectorStore->similaritySearch($embeddingQuestion, 1);
        $docsResults = iterator_to_array($results);

        $this->assertCount(1, $docsResults);
        $this->assertEquals('programador en Mercado Libre', $docsResults[0]->metadata['job']);
        $this->assertEquals($documents[2]->embedding, $docsResults[0]->embedding);
    }
}
