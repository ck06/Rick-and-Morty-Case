<?php declare(strict_types=1);

namespace App\Command;

use App\Service\MeeseeksApiService;
use App\Service\MeeseeksDatabaseService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class MeeseeksCommand extends Command
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
}