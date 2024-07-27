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
use Symfony\Component\Console\Helper\ProgressBar;
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
    /** allow us to write to output anywhere in the command. */
    private ProgressBar $progress;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln("Crawling episodes of Rick and Morty.");
        $this->progress = new ProgressBar($output);

        $lastCrawledEpisode = 1 + $this->em->getRepository(EpisodeEntity::class)->findHighestEpisodeId();
        $this->crawlEpisodes($lastCrawledEpisode);

        $output->writeln('');
        $output->writeln('Crawling completed.');
        $output->writeln('');

        return 0;
    }

    private function crawlEpisodes(int $startAt = 1): void
    {
        // get total amount of episodes (according to the API)
        $totalEpisodes = (new Episode())->get()->info->count;
        $this->progress->setMaxSteps($totalEpisodes);
        $this->progress->setProgress($startAt);

        // for some reason fetching the info earlier messes with fetching individual episodes.
        // to circumvent this, we will use separate Episode objects for the two tasks.
        $episode = new Episode();
        try {
            for ($current = $startAt; true; $current++) {
                $dto = $episode->get($current);
                $this->persist($dto);

                // if we run into performance problems, crank this up to do database stuff in batches.
                if ($current % 1 === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }

                // I couldn't find a requests-per-minute limit in the API documentation, so
                // wait a few seconds between each episode to be respectful towards the API
                sleep(5);
                $this->progress->advance();
            }
        } catch (NotFoundException) {
            // This means either the API is not available, or we ran out of episodes to crawl.
        }

        // one final flush & clear to finish the process
        // will do nothing if we're saving per episode with no issues, but serves as a safety
        // net in case we're doing batches and finish an incomplete batch (i.e. 3/5 episodes)
        $this->em->flush();
        $this->em->clear();

        $this->progress->finish();
    }

    /**
     * @param class-string $class
     * @param array<int|string> $ids
     *
     * @return array<LocationDto|LocationEntity|CharacterDto|CharacterEntity|EpisodeDto|EpisodeEntity>
     */
    private function crawlRelations(string $class, array $ids): array
    {
        $repositoryClass = match ($class) {
            Episode::class => EpisodeEntity::class,
            Location::class => LocationEntity::class,
            Character::class => CharacterEntity::class,
        };

        $fetchIds = [];
        $localRelations = [];
        foreach ($ids as $id) {
            if (!is_int($id)) {
                $id = $this->getIdFromUrl($id);
            }

            // check if we have the relation in our database already
            $result = $this->em->getRepository($repositoryClass)->findOneBy(['remoteId' => $id]);
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

    private function persist(
        EpisodeDto|EpisodeEntity|LocationDto|LocationEntity|CharacterDto|CharacterEntity $data,
    ): EpisodeEntity|LocationEntity|CharacterEntity {
        $entity = match (get_class($data)) {
            EpisodeDto::class => $this->createEpisodeEntity($data),
            LocationDto::class => $this->createLocationEntity($data),
            CharacterDto::class => $this->createCharacterEntity($data),

            // default clause will cover the 3 entities as well as doctrine proxy classes
            default => $data,
        };

        $this->em->persist($entity);

        return $entity;
    }

    private function createEpisodeEntity(EpisodeDto $dto): EpisodeEntity
    {
        $entity = new EpisodeEntity();
        $entity
            ->setRemoteId($dto->id)
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
            ->setRemoteId($dto->id)
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
            ->setRemoteId($dto->id)
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