<?php

namespace App\Command;

use App\RAG\Agent\QuizProvinciasAgent;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\RAG\DataLoader\StringDataLoader;
use App\RAG\Traits\ProvinciasEmbeddingsLoaderTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:generate-embeddings')]
class GenerateEmbeddingsCommand
{
    use ProvinciasEmbeddingsLoaderTrait;
    
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function __invoke(SymfonyStyle $io)
    {
        try {

            $io->title('Generating embeddings...');

            $agent =  QuizProvinciasAgent::make($this->entityManager);

            $provincias = json_decode(
                file_get_contents(__DIR__ . '/../Database/provincias2.json'),
                true
            );

            $this->loadProvinciasEmbeddings($provincias, $agent, $io);

            $io->success('Embeddings generated successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $io->error($th->getMessage());

            return Command::FAILURE;
        }
    }
}
