<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use App\Entity\Episode as EpisodeEntity;
use App\Entity\Location as LocationEntity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use NickBeen\RickAndMortyPhpApi\Character;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use NickBeen\RickAndMortyPhpApi\Dto\Episode as EpisodeDto;
use NickBeen\RickAndMortyPhpApi\Dto\Location as LocationDto;
use NickBeen\RickAndMortyPhpApi\Episode;
use NickBeen\RickAndMortyPhpApi\Exceptions\NotFoundException;
use NickBeen\RickAndMortyPhpApi\Location;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:initialize',
    description: 'Crawl the API to fill a local database',
    aliases: ['app:crawl'],
    hidden: false
)]
class InitializeCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lastCrawledEpisode = $this->em->getRepository(EpisodeEntity::class)->findHighestEpisodeId();
        $this->crawlEpisodes(++$lastCrawledEpisode);

        return 0;
    }

    private function crawlEpisodes(int $startAt = 1): void
    {
        $episode = new Episode();
        try {
            for ($current = $startAt; true; $current++) {
                $dto = $episode->get($current);
                $this->persist($dto);

                // for performance reasons, we will flush and clear all our entities every 50 episodes.
                if ($current % 50 === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }
        } catch (NotFoundException) {
            // This means either the API is not available, or we ran out of episodes to crawl.
        }

        // one final flush & clear to finish the process
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @param class-string $class
     * @param array<int|string> $ids
     *
     * @return array<LocationDto|CharacterDto|EpisodeDto>
     */
    private function crawlRelations(string $class, array $ids): array
    {
        $ids = array_map(fn($id) => is_int($id) ? $id : $this->getIdFromUrl($id), $ids);
        $type = new $class();

        $relations = [];
        $fetchedRelations = $type->get(...$ids);

        // if we only get 1 result, it will not be an array. Wrap it here to avoid breaking the rest of the logic.
        return is_array($fetchedRelations) ? $fetchedRelations : [$fetchedRelations];
    }

    private function persist(EpisodeDto|LocationDto|CharacterDto $data): EpisodeEntity|LocationEntity|CharacterEntity
    {
        $entity = match (get_class($data)) {
            EpisodeDto::class => $this->createEpisodeEntity($data),
            LocationDto::class => $this->createLocationEntity($data),
            CharacterDto::class => $this->createCharacterEntity($data),
        };

        $this->em->persist($entity);

        return $entity;
    }

    private function createEpisodeEntity(EpisodeDto $dto): EpisodeEntity
    {
        $entity = new EpisodeEntity();
        $entity
            ->setId($dto->id)
            ->setName($dto->name)
            ->setEpisodeString($dto->episode)
            ->setUrl($dto->url)
            ->setAirDate(new DateTimeImmutable($dto->air_date))
            ->setCreatedAt(new DateTimeImmutable($dto->created))
        ;

        $characters = $this->crawlRelations(Character::class, $dto->characters);
        foreach ($characters as $character) {
            $entity->addCharacter($this->persist($character));
        }

        return $entity;
    }

    private function createCharacterEntity(CharacterDto $dto): CharacterEntity
    {
        $entity = new CharacterEntity();
        $entity
            ->setId($dto->id)
            ->setName($dto->name)
            ->setStatus($dto->status)
            ->setSpecies($dto->species)
            ->setType($dto->type)
            ->setGender($dto->gender)
            ->setImage($dto->image)
            ->setUrl($dto->url)
            ->setCreatedAt(new DateTimeImmutable($dto->created))
        ;

        // sometimes the origin is unknown, which we will leave NULL in the database
        if ($dto->origin->url !== '') {
            $originLocation = $this->crawlRelations(Location::class, [$dto->origin->url])[0];
            $entity->setOriginLocation($this->persist($originLocation));
        }

        // sometimes the last known location is unknown, which we will leave NULL in the database
        if ($dto->location->url !== '') {
            $lastKnownLocation = $this->crawlRelations(Location::class, [$dto->location->url])[0];
            $entity->setLocation($this->persist($lastKnownLocation));
        }

        // To prevent infinite loops we purposely do not fetch Episodes here.
        return $entity;
    }

    private function createLocationEntity(LocationDto $dto): LocationEntity
    {
        $entity = new LocationEntity();
        $entity
            ->setId($dto->id)
            ->setName($dto->name)
            ->setType($dto->type)
            ->setDimension($dto->dimension)
            ->setUrl($dto->url)
            ->setCreatedAt(new DateTimeImmutable($dto->created))
        ;

        // We purposely do not fetch any relations for a location - set these via Character instead.
        return $entity;
    }

    private function getIdFromUrl(string $url): int
    {
        return (int)substr($url, 1 + (int)strrpos($url, '/'));
    }
}