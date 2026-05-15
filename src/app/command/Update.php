<?php

namespace Yllumi\Wmpanel\app\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('wmpanel:update', 'Update yllumi/wmpanel: publish missing config files and run package migrations.')]
class Update extends Install
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[wmpanel]</info> Starting update...');

        $this->publishFiles($output);

        if (!$this->runPackageMigrations($output)) {
            return Command::FAILURE;
        }

        $output->writeln('<info>[wmpanel]</info> Update complete.');
        return Command::SUCCESS;
    }

    protected function runPackageMigrations(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $migrationDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/migrations';

        $migrationFiles = glob($migrationDir . '/*.php') ?: [];
        if (!$migrationFiles) {
            $output->writeln('<comment>[wmpanel]</comment> No package migration files found.');
            return true;
        }

        $command = 'PLUGIN_PATH=' . escapeshellarg('vendor/yllumi/wmpanel/src/')
            . ' ./vendor/bin/phinx migrate --configuration=vendor/yllumi/wmpanel/src/config/migration.php';

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        if ($returnVar !== 0) {
            $output->writeln('<error>[wmpanel]</error> Failed running package migrations.');
            return false;
        }

        $output->writeln('<info>[wmpanel]</info> Package migrations executed.');
        return true;
    }
}
