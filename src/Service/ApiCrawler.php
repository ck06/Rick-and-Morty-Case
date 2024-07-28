<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Character as CharacterEntity;
use App\Entity\Episode as EpisodeEntity;
use App\Entity\Location as LocationEntity;
use Doctrine\ORM\EntityManagerInterface;
use NickBeen\RickAndMortyPhpApi\Character;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use NickBeen\RickAndMortyPhpApi\Dto\Episode as EpisodeDto;
use NickBeen\RickAndMortyPhpApi\Dto\Location as LocationDto;
use NickBeen\RickAndMortyPhpApi\Episode;
use NickBeen\RickAndMortyPhpApi\Location;

class ApiCrawler
{
    private array $supportedClasses = [
        Episode::class,
        Location::class,
        Character::class,
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {

    }

    public function getTotalAmountOf(string $class)
    {
        if (!in_array($class, $this->supportedClasses, true)) {
            return 0;
        }

        return (new $class())->get()->info->count;
    }

    /**
     * @param class-string $class
     * @param array<int|string> $ids
     *
     * @return array<LocationDto|LocationEntity|CharacterDto|CharacterEntity|EpisodeDto|EpisodeEntity>
     */
    public function crawlByIds(string $class, int|array $ids): array
    {
        $repositoryClass = match ($class) {
            Episode::class => EpisodeEntity::class,
            Location::class => LocationEntity::class,
            Character::class => CharacterEntity::class,
        };

        if (is_int($ids)) {
            $ids = [$ids];
        }

        $fetchIds = [];
        $localRelations = [];
        foreach ($ids as $id) {
            if (!is_int($id)) {
                $id = ApiUtilityService::getIdFromApiUrl($id);
            }

            // check if we have the relation in our database already
            $result = $this->em->getRepository($repositoryClass)->findOneBy(['id' => $id]);
            if (!$result) {
                $fetchIds[$id] = $id;
            } else {
                $localRelations[$id] = $result;
            }
        }

        $fetchedRelations = [];
        if ($fetchIds !== []) {
            $type = new $class();
            $fetchedRelations = $type->get(...$fetchIds);
        }

        // if we only fetch 1 id our $fetchedRelations will not be an array.
        if (!is_array($fetchedRelations)) {
            $fetchedRelations = [$fetchedRelations];
        }

        return array_merge($fetchedRelations, $localRelations);
    }
}