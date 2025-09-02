<?php

namespace App\RAG\Agent;

use App\RAG\VectorStore\DocumentDoctrineVectorStore;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\SystemPrompt;

class QuizProvinciasAgent extends RAG
{
    protected function __construct(private EntityManagerInterface $entityManager) {}

    protected function provider(): AIProviderInterface
    {
        return new Ollama(
            url: 'http://localhost:11434/api',
            model: 'llama3.1:8b'
        );
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        return new OllamaEmbeddingsProvider(
            model: 'nomic-embed-text'
        );
    }

    protected function vectorStore(): VectorStoreInterface
    {
        return new DocumentDoctrineVectorStore(
            entityManager: $this->entityManager
        );
    }
}