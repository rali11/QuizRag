<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Pgvector\Vector;

#[ORM\Entity]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'vector', length:768)]
    private ?Vector $embedding = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $sourceType = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $sourceName = null;

    #[ORM\Column(nullable: true)]
    private ?int $chunkNumber = null;

    #[ORM\Column]
    private array $metadata = [];

    static public function fromArray(array $data): self
    {
        $document = new self();
        $document->content = ($data['content'] ?? null);
        $document->embedding = isset($data['embedding']) ? new Vector($data['embedding']) : null;
        $document->sourceType = $data['sourceType'] ?? null;
        $document->sourceName = $data['sourceName'] ?? null;
        $document->chunkNumber = $data['chunkNumber'] ?? null;
        $document->metadata = $data['metadata'] ?? [];

        return $document;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }
}
