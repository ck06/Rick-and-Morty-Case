<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use App\Service\ApiUtilityService;
use App\Service\MeeseeksApiService;
use App\Service\MeeseeksDatabaseService;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
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

    abstract protected function seek(string $type, string $search);

    /**
     * @param iterable<CharacterEntity|CharacterDto> $results
     */
    protected function showOutput(SymfonyStyle $io, iterable $results): void
    {
        // TODO prettier output than just character names
        foreach ($results as $result) {
            if ($result instanceof CharacterEntity) {
                $name = $result->getName();
            } elseif ($result instanceof CharacterDto) {
                $name = $result->name;
            } else {
                $io->error("An error occurred while showing results: result not supported");
                die;
            }

            $io->writeln($name);
        }
    }

    protected function seekCharacterFromUrl(string $characterUrl): CharacterEntity|CharacterDto
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