<?php

namespace App\RAG\Traits;

use NeuronAI\RAG\DataLoader\StringDataLoader;

trait ProvinciasEmbeddingsLoaderTrait
{
    public function loadProvinciasEmbeddings($provincias, $agent, $io = null)
    {
        $progress = $io ? $io->progressIterate($provincias['provincias']) : $provincias['provincias'];
        foreach ($progress as $provincia) {
            $provinciaNombre = $provincia['provincia'];
            foreach ($provincia['atributos'] as $atributo) {
                $documents = StringDataLoader::for($atributo['content'])->getDocuments();
                foreach ($documents as $document) {
                    $document->addMetadata('provincia', $provinciaNombre);
                    $document->addMetadata($atributo['nombre_campo'], $atributo['content']);
                }
                $agent->addDocuments($documents);
            }
        }
    }
}
