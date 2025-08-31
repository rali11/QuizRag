<?php
namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Pgvector\Doctrine\PgvectorSetup;

final class PgVectorSetupConsoleListener
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function onConsoleCommand(): void
    {
        PgvectorSetup::registerTypes($this->entityManager);
    }
}