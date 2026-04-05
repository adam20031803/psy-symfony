<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

final class PersistenceService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function remove(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
