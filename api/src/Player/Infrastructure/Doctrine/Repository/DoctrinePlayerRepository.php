<?php

declare(strict_types=1);

namespace App\Player\Infrastructure\Doctrine\Repository;

use App\Player\Domain\Player;
use App\Player\Domain\PlayerId;
use App\Player\Domain\PlayerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrinePlayerRepository extends ServiceEntityRepository implements PlayerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function save(Player $player): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($player);
        $entityManager->flush();
    }

    public function byId(PlayerId $id): ?Player
    {
        return $this->find($id->value);
    }
}
