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
        $rootConfigFile = $projectRoot . '/config/migration.php';
        $pluginConfigDir = $projectRoot . '/config/plugin/yllumi/wmpanel';
        $migrationDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/migrations';
        $pluginConfigFile = $pluginConfigDir . '/migration.php';
        $configFile = is_file($rootConfigFile) ? $rootConfigFile : $pluginConfigFile;

        if (!is_file($configFile)) {
            $output->writeln('<error>[wmpanel]</error> migration config not found. Checked: ' . $rootConfigFile . ' and ' . $pluginConfigFile);
            return false;
        }

        $migrationFiles = glob($migrationDir . '/*.php') ?: [];
        if (!$migrationFiles) {
            $output->writeln('<comment>[wmpanel]</comment> No package migration files found.');
            return true;
        }

        $baseConfig = include $configFile;
        if (!is_array($baseConfig)) {
            $output->writeln('<error>[wmpanel]</error> Invalid migration config format.');
            return false;
        }

        if (!isset($baseConfig['paths']) || !is_array($baseConfig['paths'])) {
            $baseConfig['paths'] = [];
        }
        $baseConfig['paths']['migrations'] = $migrationDir;

        $tempConfigFile = tempnam(sys_get_temp_dir(), 'wmpanel_migration_');
        if ($tempConfigFile === false) {
            $output->writeln('<error>[wmpanel]</error> Unable to create temporary migration config.');
            return false;
        }

        file_put_contents($tempConfigFile, "<?php\nreturn " . var_export($baseConfig, true) . ";\n");

        $command = sprintf(
            './vendor/bin/phinx migrate --configuration=%s',
            escapeshellarg($tempConfigFile)
        );

        exec($command, $outputLines, $returnVar);
        @unlink($tempConfigFile);

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
