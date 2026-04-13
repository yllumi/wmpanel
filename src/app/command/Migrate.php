<?php

namespace Yllumi\Wmpanel\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('migrate', 'Migrate the database')]
class Migrate extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // run ./vendor/bin/phinx migrate
        $command = './vendor/bin/phinx migrate --configuration=config/plugin/yllumi/wmpanel/migration.php';
        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }
        return $returnVar === 0 ? self::SUCCESS : self::FAILURE;
    }

}
