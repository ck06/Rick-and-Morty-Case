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
        $classes = [
            Character::class,
            Location::class,
            Episode::class,
        ];

        foreach ($classes as $class) {
            $type = new $class();
            $page = 1;
            try {
                while ($page++) {
                    foreach ($type->page($page)->get()->results as $result) {
                        $this->persist($result);
                    }

                    // for doctrine performance reasons, we want to flush and clear periodically
                    // We'll do this every 5 pages, as well as once at the end.
                    if ($page % 5 === 0) {
                        $this->em->flush();
                        $this->em->clear();
                    }
                }
            } catch (NotFoundException) {
                // This means either the API is not available, or we ran out of pages to crawl.
            }

            // Do a flush before continuing in case we have any unprocessed entities
            $this->em->flush();
            $this->em->clear();
        }

        return 0;
    }

    private function persist(EpisodeDto|LocationDto|CharacterDto $data): void
    {
        $entity = match (get_class($data)) {
            EpisodeDto::class => $this->createEpisodeEntity($data),
            LocationDto::class => $this->createLocationEntity($data),
            CharacterDto::class => $this->createCharacterEntity($data),
        };

        $this->em->persist($entity);
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

        // TODO add characters
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

        // TODO relations (characters, originCharacters)
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

        // TODO relations (origin, location, episodes)
        return $entity;
    }
}