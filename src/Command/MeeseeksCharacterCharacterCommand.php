<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Character as CharacterEntity;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'meeseeks:character:character', description: 'Seek for a specific character')]
class MeeseeksCharacterCharacterCommand extends MeeseeksCharacterCommand
{
    protected function additionalHelpText(): string
    {
        return <<<"moreHelp"
    bin/console {$this->getName()} --name "Rick Sanchez"
    bin/console {$this->getName()} --name "Morty"
    bin/console {$this->getName()} --id 1

This commands only supports one option at a time. 
When searching by name: 
    If a direct result is not found, will return all characters with the search query in the name
    This lets you do something like searching by first or last name, or search variations of a certain character.
   
If no option is given, name will be used.
Otherwise the priority is ID > Name
moreHelp;
    }

    protected function getSeekTypes(): array
    {
        return [
            'db' => $this->db::SEEK_CHARACTER,
            'api' => $this->api::SEEK_CHARACTER,
        ];
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // for characters, we only trim whitespace off of the edges.
        // trusting user input is a bad idea, but it'll do for this assignment
        $searchString = trim($input->getArgument(self::ARGUMENT_SEARCH));

        /** @var array<CharacterEntity|CharacterDto> $characters */
        $characters = match (true) {
            $input->getOption(self::OPTION_ID) => $this->seek(self::OPTION_ID, (int)$searchString),
            $input->getOption(self::OPTION_NAME) => $this->seek(self::OPTION_NAME, $searchString),
            default => $this->seek(self::OPTION_NAME, $searchString),
        };

        $io = new SymfonyStyle($input, $output);
        $this->showOutput($io, $characters);

        return 1;
    }
}
