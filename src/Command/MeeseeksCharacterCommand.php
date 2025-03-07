<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use App\Entity\Episode as EpisodeEntity;
use App\Entity\Location as LocationEntity;
use App\Service\ApiUtilityService;
use App\Service\MeeseeksApiService;
use App\Service\MeeseeksDatabaseService;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use NickBeen\RickAndMortyPhpApi\Dto\Episode as EpisodeDto;
use NickBeen\RickAndMortyPhpApi\Dto\Location as LocationDto;
use NickBeen\RickAndMortyPhpApi\Exceptions\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class MeeseeksCharacterCommand extends Command
{
    public const ARGUMENT_SEARCH = 'searchString';
    public const OPTION_NAME = 'name';
    public const OPTION_ID = 'id';

    public function __construct(
        protected readonly MeeseeksDatabaseService $db,
        protected readonly MeeseeksApiService $api,
    ) {
        parent::__construct();
    }

    abstract protected function additionalHelpText(): string;

    abstract protected function getSeekTypes(): array;

    protected function getMappingsForOption(string $option): array
    {
        return match ($option) {
            self::OPTION_NAME => ['db' => $this->db::SEEK_VALUE_ALL_NAME, 'api' => $this->api::SEEK_VALUE_ALL_NAME],
            self::OPTION_ID => ['db' => $this->db::SEEK_VALUE_ALL_ID, 'api' => $this->api::SEEK_VALUE_ALL_ID],

            // no default, throw an error if we get here.
        };
    }

    protected function configure()
    {
        $helpText = <<<"help"
Usage: 
    bin/console {$this->getName()} [--option] searchString
{$this->additionalHelpText()}    
help;

        $this
            ->setHelp($helpText)
            ->addArgument(self::ARGUMENT_SEARCH, InputArgument::REQUIRED, 'What to seek for')
            ->addOption(self::OPTION_NAME, null, InputOption::VALUE_NONE, 'Seeks by name')
            ->addOption(self::OPTION_ID, null, InputOption::VALUE_NONE, 'Seeks by remote ID')
        ;
    }

    protected function seek(string $option, string|int $search): iterable
    {
        $types = $this->getSeekTypes();
        [$dbType, $apiType] = [$types['db'], $types['api']];

        $findBys = $this->getMappingsForOption($option);
        [$dbFindBy, $apiFindBy] = [$findBys['db'], $findBys['api']];

        /** @var null|EpisodeEntity|LocationEntity|CharacterEntity $result */
        $result = $this->db->seekOne($dbType, $dbFindBy, $search);
        if ($result) {
            if ($result instanceof CharacterEntity) {
                return [$result];
            }

            return $result->getCharacters();
        }

        /** @var null|EpisodeDto|LocationDto|CharacterDto $result */
        $result = $this->api->seekOne($apiType, $apiFindBy, $search);
        if (!$result) {
            return [];
        }

        if ($result instanceof CharacterDto) {
            return [$result];
        }

        if ($result instanceof LocationDto) {
            $foundCharacters = $result->residents;
        } else {
            $foundCharacters = $result->characters;
        }

        $characters = [];
        foreach ($foundCharacters as $character) {
            $characters[] = $this->seekCharacterFromUrl($character);
        }

        return $characters;
    }

    /**
     * @param iterable<CharacterEntity|CharacterDto> $results
     */
    protected function showOutput(SymfonyStyle $io, iterable $results): void
    {
        if (count($results) === 0) {
            $io->error('No results found');

            return;
        }

        $tableHeader = ['Name', 'Status', 'Species', 'Type', 'Gender', 'Location of origin', 'Last known location'];
        $tableBody = [];
        foreach ($results as $result) {
            $row = [];
            if ($result instanceof CharacterEntity) {
                $row = [
                    $result->getName(),
                    $result->getStatus(),
                    $result->getSpecies(),
                    $result->getType(),
                    $result->getGender(),
                    $result->getOriginLocation()?->getName() ?? 'unknown',
                    $result->getLocation()?->getName() ?? 'unknown',
                ];
            } elseif ($result instanceof CharacterDto) {
                $origin = 'unknown';
                if ($result->location->url !== '') {
                    $location = $this->seekLocationFromUrl($result->origin->url);
                    $origin = $location instanceof LocationDto ? $location->name : $location->getName();
                }

                $current = 'unknown';
                if ($result->location->url !== '') {
                    $location = $this->seekLocationFromUrl($result->location->url);
                    $current = $location instanceof LocationDto ? $location->name : $location->getName();
                }

                $row = [
                    $result->name,
                    $result->status,
                    $result->species,
                    $result->type,
                    $result->gender,
                    $origin,
                    $current,
                ];

            } else {
                $io->error("An error occurred while showing results: result not supported");
                die;
            }

            $tableBody[] = $row;
        }

        $io->table($tableHeader, $tableBody);
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

    private function seekLocationFromUrl(string $locationUrl): LocationEntity|LocationDto
    {
        $id = ApiUtilityService::getIdFromApiUrl($locationUrl);
        $locationEntity = $this->db->seekOne($this->db::SEEK_LOCATION, $this->db::SEEK_VALUE_ALL_ID, $id);
        if ($locationEntity) {
            return $locationEntity;
        }

        $locationDto = $this->api->seekOne($this->api::SEEK_LOCATION, $this->api::SEEK_VALUE_ALL_ID, $id);
        if ($locationDto) {
            return $locationDto;
        }

        throw new NotFoundException("Unable to find location with ID {$id}");
    }
}