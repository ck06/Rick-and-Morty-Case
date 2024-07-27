<?php

namespace App\Repository;

use App\Entity\Episode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Episode>
 */
class EpisodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Episode::class);
    }

    public function findHighestEpisodeId(): int
    {
        $result = $this
            ->createQueryBuilder('e')
            ->select('e.remoteId')
            ->orderBy('e.remoteId', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->execute()
        ;

        return $result[0]['remoteId'] ?? 0;
    }
}
