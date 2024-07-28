<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use App\Entity\Episode as EpisodeEntity;
use App\Entity\Location as LocationEntity;
use App\Service\ApiCrawler;
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
        private readonly ApiCrawler $crawler,
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
        $totalEpisodes = $this->crawler->getTotalAmountOf(Episode::class);
        $this->progress->setMaxSteps($totalEpisodes);
        $this->progress->setProgress($startAt);

        // for some reason fetching the info earlier messes with fetching individual episodes.
        // to circumvent this, we will use separate Episode objects for the two tasks.
        $episode = new Episode();
        try {
            for ($current = $startAt; true; $current++) {
                $dto = $episode->get($current);
                $this->persist($dto);

                // clear UnitOfWork after every episode to keep performance up.
                $this->em->flush();
                $this->em->clear();

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
        $this->em->flush();

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

        $this->em->persist($entity);

        $characters = $this->crawler->crawlByIds(Character::class, $dto->characters);
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

        $this->em->persist($entity);

        // sometimes the origin is unknown, which we will leave NULL in the database
        if ($dto->origin->url !== '') {
            $originLocation = $this->crawler->crawlByIds(Location::class, [$dto->origin->url])[0];
            $entity->setOriginLocation($this->persist($originLocation));
        }

        // sometimes the last known location is unknown, which we will leave NULL in the database
        if ($dto->location->url !== '') {
            $lastKnownLocation = $this->crawler->crawlByIds(Location::class, [$dto->location->url])[0];
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

        $this->em->persist($entity);

        // We purposely do not fetch any relations for a location - set these via Character instead.
        return $entity;
    }
}