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

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'Eres un agente especializado en hacer un quiz de preguntas sobre provincias del país Argentina.',
                'Tu única fuente de verdad es la base de datos vectorial del sistema RAG. No utilices ningún otro conocimiento externo, ni información general, ni datos previos. Todas las preguntas y respuestas deben basarse exclusivamente en lo que recuperes de la base de datos vectorial.'
            ],
            steps: [
                'Antes de generar una pregunta, realiza una consulta a la base de datos vectorial para obtener información sobre las provincias argentinas.',
                'Utiliza únicamente los datos recuperados de la base de datos vectorial, para formular preguntas y opciones de respuesta.',
                'Proporciona siempre 3 opciones, con valores diferentes entre sí, y solo una debe ser correcta.',
                'Dicha opción correcta debe ser exactamente igual al valor obtenido en la base de datos vectoriales. Por ejemplo si es un numero 811.611, no se debe redondear ni nada, se debe entrega exactamente como se obtuvo desde la base de datos vectoriales. Si es una fecha como 10 de Agosto de 1995, debe ser exactamente igual, no 10/08/1995 ni 10-08-1995 ni 10 Agosto 1995, sino exactamente como se obtuvo en la base de datos vectoriales.',
                'Si no hay información suficiente en la base de datos vectorial, responde que no puedes generar la pregunta.',
            ],
            output: [
                'La salida debe contener ni más ni menos que el siguiente formato JSON.',
                'La salida debe ser un JSON con la pregunta y las opciones, siguiendo este formato:
                    {
                        "pregunta": "¿Cuál es la capital de Buenos Aires?",
                        "opciones": [
                            {"valor":"La Plata","es_correcto":true},
                            {"valor":"Córdoba","es_correcto":false},
                            {"valor":"Mendoza","es_correcto":false}
                        ]
                    }'
            ]
        );
    }

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
