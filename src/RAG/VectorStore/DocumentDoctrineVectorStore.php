<?php

namespace App\RAG\VectorStore;

use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\RAG\Document;
use App\Entity\Document as DocumentEntity;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Pgvector\Vector;

class DocumentDoctrineVectorStore implements VectorStoreInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function addDocument(Document $document): VectorStoreInterface
    {
        $this->addDocuments([$document]);

        return $this;
    }

    /**
     * @param Document[] $documents
     */
    public function addDocuments(array $documents): VectorStoreInterface
    {
        foreach ($documents as $document) {
            $documentEntity = DocumentEntity::fromArray($document->jsonSerialize());
            $this->entityManager->persist($documentEntity);
        }

        $this->entityManager->flush();

        return $this;
    }

    public function deleteBySource(string $sourceName, string $sourceType): VectorStoreInterface
    {
        return $this;
    }

    /**
     * Return docs most similar to the embedding.
     *
     * @param  float[]  $embedding
     * @return Document[]
     */
    public function similaritySearch(array $embedding, int $k = 4): iterable
    {
        $sql = '
            SELECT 
                id, 
                metadata, 
                embedding, 
                1 - (embedding <=> :embedding) as cosine_similarity
            FROM document
            ORDER BY embedding <=> :embedding
            LIMIT :limit
        ';

        $databaseConnection = $this->entityManager->getConnection();
        $results = $databaseConnection
            ->executeQuery($sql, ['embedding' => new Vector($embedding), 'limit' => $k])
            ->fetchAllAssociative();

        return array_map(function ($row) {
            $document = new Document();
            $document->id = $row['id'];
            $document->embedding = new Vector($row['embedding'])->toArray();
            $document->setScore($row['cosine_similarity']);

            $metadata = json_decode($row['metadata'], true);
            foreach ($metadata as $key => $value) {
                $document->addMetadata($key, $value);
            }

            return $document;
        }, $results);
    }
}
