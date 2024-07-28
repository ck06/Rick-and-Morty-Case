<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'meeseeks:character:episode', description: 'Seek all characters in a specific episode')]
class MeeseeksCharacterEpisodeCommand extends MeeseeksCharacterCommand
{
    public const OPTION_CODE = 'code';

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

    protected function getSeekTypes(): array
    {
        return [
            'db' => $this->db::SEEK_EPISODE,
            'api' => $this->api::SEEK_EPISODE,
        ];
    }

    protected function getMappingsForOption(string $option): array
    {
        return match ($option) {
            self::OPTION_CODE => ['db' => $this->db::SEEK_VALUE_EPISODE_CODE, 'api' => $this->api::SEEK_VALUE_EPISODE_CODE],
            default => parent::getMappingsForOption($option)
        };
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // episodes have pretty simple sanitation, strip everything except alphanumeric characters and spaces
        $searchString = $input->getArgument(self::ARGUMENT_SEARCH);
        $searchString = preg_replace('|[^\w\s]|', '', $searchString);

        /** @var array<CharacterEntity|CharacterDto> $characters */
        $characters = match (true) {
            $input->getOption(self::OPTION_CODE) => $this->seek(self::OPTION_CODE, $searchString),
            $input->getOption(self::OPTION_NAME) => $this->seek(self::OPTION_NAME, $searchString),
            $input->getOption(self::OPTION_ID) => $this->seek(self::OPTION_ID, (int)$searchString),
            default => $this->seek(self::OPTION_ID, (int)$searchString),
        };

        $io = new SymfonyStyle($input, $output);
        $this->showOutput($io, $characters);

        return 1;
    }
}
