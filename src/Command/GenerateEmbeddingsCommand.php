<?php

namespace App\Command;

use App\RAG\Agent\QuizProvinciasAgent;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\RAG\DataLoader\StringDataLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:generate-embeddings')]
class GenerateEmbeddingsCommand
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function __invoke(SymfonyStyle $io)
    {
        try {
            $io->title('Generating embeddings...');

            $provincias = json_decode(
                file_get_contents(__DIR__ . '/../Database/provincias.json'), 
                true
            );

            foreach ($io->progressIterate($provincias['provincias']) as $provincia) {
                $content = [
                    'Nombre: '.$provincia['nombre'],
                    'Capital: '.$provincia['capital'],
                    'Poblacion: '.$provincia['poblacion'],
                    'Superficie: '.$provincia['superficie'],
                    'Gentilicio: '.$provincia['gentilicio'],
                    'Fecha Autonomia: '.$provincia['fecha_autonomia'],
                    'Abreviatura: '.$provincia['abreviatura'],
                    'Ciudad mas poblada: '.
                        'Nombre: '.$provincia['ciudad_mas_poblada']['nombre'].
                        ', Habitantes: '.$provincia['ciudad_mas_poblada']['habitantes']
                ];
                $content = implode("|", $content);

                $documents = StringDataLoader::for($content)->getDocuments();

                foreach ($documents as $document) {
                    $document->addMetadata('nombre', $provincia['nombre']);
                    $document->addMetadata('capital', $provincia['capital']);
                    $document->addMetadata('poblacion', $provincia['poblacion']);
                    $document->addMetadata('superficie', $provincia['superficie']);
                    $document->addMetadata('gentilicio', $provincia['gentilicio']);
                    $document->addMetadata('fecha_autonomia', $provincia['fecha_autonomia']);
                    $document->addMetadata('abreviatura', $provincia['abreviatura']);
                    $document->addMetadata('ciudad_mas_poblada', json_encode($provincia['ciudad_mas_poblada']));
                }

                QuizProvinciasAgent::make($this->entityManager)->addDocuments($documents);
            }

            $io->success('Embeddings generated successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $io->error($th->getMessage());
            
            return Command::FAILURE;
        }
    }
}
