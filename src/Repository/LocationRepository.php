<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findByName(string $name): array
    {
        // attempt an exact search first
        $directResult = $this->findBy(['name' => $name]);
        if ($directResult) {
            return $directResult;
        }

        // now try a leading search, in case the location has dimension specifiers after it.
        return $this
            ->createQueryBuilder('l')
            ->select()
            ->where('l.name like :name')
            ->setParameter('name', $name . '%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByDimension(string $name): array
    {
        // attempt an exact search first
        $directResult = $this->findBy(['dimension' => $name]);
        if ($directResult) {
            return $directResult;
        }

        // now try a fuzzy search in case only first or last name is given
        return $this
            ->createQueryBuilder('l')
            ->select()
            ->where('l.name = :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult()
        ;
    }
}
