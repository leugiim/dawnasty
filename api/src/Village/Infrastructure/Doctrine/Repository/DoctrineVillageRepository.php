<?php

declare(strict_types=1);

namespace App\Village\Infrastructure\Doctrine\Repository;

use App\Village\Domain\Village;
use App\Village\Domain\VillageId;
use App\Village\Domain\VillageRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineVillageRepository extends ServiceEntityRepository implements VillageRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Village::class);
    }

    public function save(Village $village): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($village);
        $entityManager->flush();
    }

    public function byId(VillageId $id): ?Village
    {
        return $this->find($id->value);
    }
}
