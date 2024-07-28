<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use App\Entity\Episode as EpisodeEntity;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use NickBeen\RickAndMortyPhpApi\Dto\Episode as EpisodeDto;
use App\Service\ApiUtilityService;
use NickBeen\RickAndMortyPhpApi\Exceptions\NotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'meeseeks:character:episode', description: 'Seek all characters in a specific episode')]
class MeeseeksCharacterEpisodeCommand extends MeeseeksCommand
{
    public const OPTION_CODE = 'code';

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
        $this->addOption(self::OPTION_CODE, null, InputOption::VALUE_NONE, 'Seeks by episode code');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        // episodes have pretty simple sanitation, strip everything except alphanumeric characters and spaces
        $searchString = $input->getArgument(self::ARGUMENT_SEARCH);
        $searchString = preg_replace('|[^\w\s]|', '', $searchString);

        /** @var array<CharacterEntity|CharacterDto> $characters */
        $characters = match (true) {
            $input->getOption(self::OPTION_CODE) => $this->seek(self::OPTION_CODE, $searchString),
            $input->getOption(self::OPTION_NAME) => $this->seek(self::OPTION_NAME, $searchString),
            $input->getOption(self::OPTION_ID) => $this->seek(self::OPTION_ID, (int) $searchString),
            default => $this->seek(self::OPTION_ID, (int) $searchString),
        };

        $this->showOutput($characters);

        return 1;
    }

    /**
     * @param iterable<CharacterEntity|CharacterDto> $results
     */
    protected function showOutput(iterable $results): void
    {
        // TODO prettier output than just character names
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

    protected function seek(string $type, string|int $search)
    {
        $typeMap = [
            self::OPTION_CODE => ['db' => $this->db::SEEK_VALUE_EPISODE_CODE, 'api' => $this->api::SEEK_VALUE_EPISODE_CODE],
            self::OPTION_NAME => ['db' => $this->db::SEEK_VALUE_ALL_NAME, 'api' => $this->api::SEEK_VALUE_ALL_NAME],
            self::OPTION_ID => ['db' => $this->db::SEEK_VALUE_ALL_ID, 'api' => $this->api::SEEK_VALUE_ALL_ID],
        ];

        /** @var null|EpisodeEntity $episode */
        $episode = $this->db->seekOne($this->db::SEEK_EPISODE, $typeMap[$type]['db'], $search);
        if ($episode) {
            return $episode->getCharacters();
        }

        /** @var null|EpisodeDto $episode */
        $episode = $this->api->seekOne($this->api::SEEK_EPISODE, $typeMap[$type]['api'], $search);
        $characters = [];
        foreach ($episode->characters as $character) {
            $characters[] = $this->seekCharacterFromUrl($character);
        }

        return $characters;
    }

    private function seekCharacterFromUrl(string $characterUrl): CharacterEntity|CharacterDto
    {
        $id = ApiUtilityService::getIdFromApiUrl($characterUrl);
        $characterEntity = $this->db->seekOne($this->db::SEEK_CHARACTER, $this->db::SEEK_VALUE_ALL_ID, $id);
        if ($characterEntity) {
            return $characterEntity;
        }

        $characterDto = $this->api->seekOne($this->api::SEEK_CHARACTER, $this->api::SEEK_VALUE_ALL_ID, $id);
        if ($characterDto) {
            return $characterDto;
        }

        throw new NotFoundException("Unable to find character with ID {$id}");
    }
}