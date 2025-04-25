<?php

namespace App\Command;

use App\Services\Import\ImportDefaultServices;
use App\Services\UtilityServices;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-default-data',
    description: 'Add a short description for your command',
)]
class AddDefaultDataCommand extends Command
{
    private UtilityServices $utilities;

    public function __construct(
        private ImportDefaultServices $defaultServices
        )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'entity')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'file')
            ->addOption('purge', null, InputOption::VALUE_OPTIONAL, 'purge')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entity = $input->getOption('entity');
        $data = $input->getOption('file');
        $purge = $input->getOption('purge');

        if ($entity && $data) {
            $this->defaultServices->importEntity($entity, $data, $io, $purge);

            return Command::SUCCESS;
        }

        $io->warning('invalid Parameter');
        return Command::INVALID;
    }
}
