<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use App\Entity\Episode as EpisodeEntity;
use App\Entity\Location as LocationEntity;
use NickBeen\RickAndMortyPhpApi\Character;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use NickBeen\RickAndMortyPhpApi\Dto\Episode as EpisodeDto;
use NickBeen\RickAndMortyPhpApi\Dto\Location as LocationDto;
use App\Service\ApiUtilityService;
use NickBeen\RickAndMortyPhpApi\Episode;
use NickBeen\RickAndMortyPhpApi\Exceptions\NotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'meeseeks:character:episode', description: 'Seek all characters in a specific episode')]
class MeeseeksCharacterEpisodeCommand extends MeeseeksCommand
{
    private SymfonyStyle $io;

    protected function additionalHelpText(): string
    {
        return <<<"moreHelp"
    bin/console {$this->getName()} --name "Lawnmower Dog"
    bin/console {$this->getName()} --code S01E02
    bin/console {$this->getName()} --id 2

This commands only supports one option at a time. 
If none is given, ID will be used.
Otherwise the priority is ID > Code > Name
moreHelp;
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption('code', null, InputOption::VALUE_NONE, 'Seeks by episode code');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        // episodes have pretty simple sanitation, strip everything except alphanumeric characters and spaces
        $searchString = $input->getArgument('searchString');
        $searchString = preg_replace('|[^\w\s]|', '', $searchString);

        match (true) {
            $input->getOption('code') => $this->seekByCode($searchString),
            $input->getOption('name') => $this->seekByName($searchString),
            $input->getOption('id') => $this->seekById($searchString),
            default => $this->seekById($searchString),
        };

        return 1;
    }

    protected function seekByCode(string $search): void
    {
        // attempt to fetch from the database first to prevent load on the API
        if ($this->seekInDatabase('episodeString', $search)) {
            return;
        }

        $result = (new Episode())->withEpisode($search)->get()->results[0];
        $characters = [];
        foreach ($result->characters as $character) {
            $characters[] = $this->fetchCharacterFromUrl($character);
        }

        $this->showOutput($characters);
    }

    protected function seekByName(string $search): void
    {
        // attempt to fetch from the database first to prevent load on the API
        if ($this->seekInDatabase('name', $search)) {
            return;
        }

        // fall back to fetching via API
        $result = (new Episode())->withName($search)->get()->results[0];
        $characters = [];
        foreach ($result->characters as $character) {
            $characters[] = $this->fetchCharacterFromUrl($character);
        }

        $this->showOutput($characters);
    }

    protected function seekByUrl(string $search): void
    {
        $id = ApiUtilityService::getIdFromApiUrl($search);
        $this->seekById($id);
    }

    protected function seekById(int|string $search): void
    {
        if (is_string($search)) {
            $search = (int)$search;
        }

        $result = $this->em->getRepository(EpisodeEntity::class)->findOneBy(['remoteId' => $search]);
        if ($result) {
            $this->showOutput($result->getCharacters());

            return;
        }

        try {
            $result = (new Episode())->get($search);
            $characters = [];
            foreach ($result->characters as $character) {
                $characters[] = $this->fetchCharacterFromUrl($character);
            }

            $this->showOutput($characters);
        } catch (NotFoundException) {
            // episode does not exist at all.
            $this->io->error("Cannot find episode #${search}");
        }
    }

    /**
     * @param iterable<CharacterEntity|CharacterDto> $results
     */
    protected function showOutput(iterable $results): void
    {
        foreach ($results as $result) {
            if ($result instanceof CharacterEntity) {
                $name = $result->getName();
            } elseif ($result instanceof CharacterDto) {
                $name = $result->name;
            } else {
                $this->io->error("An error occurred while showing results: result not supported");
                die;
            }

            $this->io->writeln($name);
        }
    }

    private function seekInDatabase(string $findBy, int|string $search): bool
    {
        $result = $this->em->getRepository(EpisodeEntity::class)->findOneBy([$findBy => $search]);
        if ($result) {
            $this->showOutput($result->getCharacters());

            return true;
        }

        return false;
    }

    private function fetchCharacterFromUrl(string $url): CharacterEntity|CharacterDto
    {
        $id = ApiUtilityService::getIdFromApiUrl($url);
        $result = $this->em->getRepository(CharacterEntity::class)->find($id);
        if ($result) {
            return $result;
        }

        try {
            /** @var CharacterDto $result */
            $result = (new Character())->get($id);

            return $result;
        } catch (NotFoundException) {
            $this->io->error(
                "An error occurred while showing results: Cannot find associated character(s), is the API down?",
            );
            die;
        }
    }
}