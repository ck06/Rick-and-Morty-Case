<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'meeseeks:character:location', description: 'Seek all characters in a specific location')]
class MeeseeksCharacterLocationCommand extends MeeseeksCharacterCommand
{
    public const OPTION_DIMENSION = 'dimension';

    protected function additionalHelpText(): string
    {
        return <<<"moreHelp"
    bin/console {$this->getName()} --name "Earth"
    bin/console {$this->getName()} --name "Earth (C-137)"
    bin/console {$this->getName()} --dimension "C-137"
    bin/console {$this->getName()} --id 1

This commands only supports one option at a time. 
When searching by name: 
    If multiple locations are found, characters from all these locations will be returned
    This can happen when a location exists in multiple dimensions 
   
If no option is given, name will be used.
Otherwise the priority is ID > Name > Dimension
moreHelp;
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption(self::OPTION_DIMENSION, null, InputOption::VALUE_NONE, 'Seeks by episode code');
    }

    protected function getSeekTypes(): array
    {
        return [
            'db' => $this->db::SEEK_LOCATION,
            'api' => $this->api::SEEK_LOCATION,
        ];
    }

    protected function getMappingsForOption(string $option): array
    {
        return match ($option) {
            self::OPTION_DIMENSION => ['db' => $this->db::SEEK_VALUE_LOCATION_DIMENSION, 'api' => $this->api::SEEK_VALUE_LOCATION_DIMENSION],
            default => parent::getMappingsForOption($option)
        };
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        // for locations, we only trim whitespace off of the edges.
        // trusting user input is a bad idea, but it'll do for this assignment
        $searchString = trim($input->getArgument(self::ARGUMENT_SEARCH));

        /** @var array<CharacterEntity|CharacterDto> $characters */
        $characters = match (true) {
            $input->getOption(self::OPTION_DIMENSION) => $this->seek(self::OPTION_DIMENSION, $searchString),
            $input->getOption(self::OPTION_NAME) => $this->seek(self::OPTION_NAME, $searchString),
            $input->getOption(self::OPTION_ID) => $this->seek(self::OPTION_ID, (int)$searchString),
            default => $this->seek(self::OPTION_ID, (int)$searchString),
        };

        $this->showOutput($io, $characters);

        return 1;
    }
}