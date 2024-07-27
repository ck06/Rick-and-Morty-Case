<?php declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class MeeseeksCommand extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $em,
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
            ->addArgument('searchString', InputArgument::REQUIRED, 'What to seek for')
            ->addOption('name', null, InputOption::VALUE_NONE, 'Seeks by name')
            ->addOption('id', null, InputOption::VALUE_NONE, 'Seeks by remote ID')
        ;
    }

    abstract protected function seekByName(string $search): void;

    abstract protected function seekByUrl(string $search): void;

    abstract protected function seekById(string|int $search): void;

    abstract protected function showOutput(iterable $results): void;
}