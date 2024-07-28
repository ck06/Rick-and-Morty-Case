<?php

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function findByName(string $name): array
    {
        // attempt an exact search first
        $directResult = $this->findBy(['name' => $name]);
        if ($directResult) {
            return $directResult;
        }

        // now try a fuzzy search in case only first or last name is given
        return $this
            ->createQueryBuilder('c')
            ->select()
            ->where('c.name like :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult()
        ;
    }
}
