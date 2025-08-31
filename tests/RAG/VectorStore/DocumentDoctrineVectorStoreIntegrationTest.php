<?php

namespace App\Tests\Integration\RAG\VectorStore;

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

        PgvectorSetup::registerTypes($this->entityManager);
        $this->vectorStore = new DocumentDoctrineVectorStore($this->entityManager);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM document');
    }

    public function testAddDocumentAndSimilaritySearch()
    {
        $ollamaEmbedding = new OllamaEmbeddingsProvider('nomic-embed-text');
        $embedding = $ollamaEmbedding->embedText('prueba de documento.');

        $doc = new Document();
        $doc->id = uniqid();
        $doc->embedding = $embedding;
        $doc->addMetadata('name', 'Test');

        $this->vectorStore->addDocument($doc);

        $results = $this->vectorStore->similaritySearch($embedding, 1);
        $docs = iterator_to_array($results);

        $this->assertCount(1, $docs);
        $this->assertEquals('Test', $docs[0]->metadata['name']);
        $this->assertEquals($embedding, $docs[0]->embedding);
    }
}
