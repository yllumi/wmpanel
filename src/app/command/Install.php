<?php

namespace Yllumi\Wmpanel\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('wmpanel:install', 'Install yllumi/wmpanel: run plugin migration and publish config files.')]
class Install extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[wmpanel]</info> Starting installation...');

        if (!$this->runInstallMigration($output)) {
            return Command::FAILURE;
        }

        if (!$this->runInstallSeeder($output)) {
            return Command::FAILURE;
        }

        $this->publishFiles($output);

        $output->writeln('<info>[wmpanel]</info> Installation complete.');
        return Command::SUCCESS;
    }

    protected function runInstallMigration(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $migrationDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/migrations';

        $migrationFiles = glob($migrationDir . '/*_install_plugin.php') ?: [];
        if (!$migrationFiles) {
            $output->writeln('<error>[wmpanel]</error> install_plugin migration file not found.');
            return false;
        }

        usort($migrationFiles, static function (string $left, string $right): int {
            return strcmp($left, $right);
        });

        $migrationFile = end($migrationFiles);
        $migrationBaseName = basename($migrationFile ?: '');
        preg_match('/^(\d+)_install_plugin\.php$/', $migrationBaseName, $matches);
        $targetVersion = $matches[1] ?? null;

        if (!$targetVersion) {
            $output->writeln('<error>[wmpanel]</error> Unable to resolve install_plugin migration version.');
            return false;
        }

        $tempConfigFile = $this->buildTempPhinxConfig($output, $migrationDir, null);
        if ($tempConfigFile === null) {
            return false;
        }

        $command = sprintf(
            './vendor/bin/phinx migrate --configuration=%s --target=%s',
            escapeshellarg($tempConfigFile),
            escapeshellarg($targetVersion)
        );

        $success = $this->runCommandAndWriteOutput($command, $output);
        @unlink($tempConfigFile);

        if (!$success) {
            $output->writeln('<error>[wmpanel]</error> Failed running install_plugin migration.');
            return false;
        }

        $output->writeln('<info>[wmpanel]</info> install_plugin migration executed.');
        return true;
    }

    protected function runInstallSeeder(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $migrationDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/migrations';
        $seedDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/seeds';
        $seedClass = 'WmpanelInitSeeder';

        if (!is_file($seedDir . '/' . $seedClass . '.php')) {
            $output->writeln('<comment>[wmpanel]</comment> Seeder not found, skipping.');
            return true;
        }

        $tempConfigFile = $this->buildTempPhinxConfig($output, $migrationDir, $seedDir);
        if ($tempConfigFile === null) {
            return false;
        }

        $command = sprintf(
            './vendor/bin/phinx seed:run --configuration=%s --seed=%s',
            escapeshellarg($tempConfigFile),
            escapeshellarg($seedClass)
        );

        $success = $this->runCommandAndWriteOutput($command, $output);
        @unlink($tempConfigFile);

        if (!$success) {
            $output->writeln('<error>[wmpanel]</error> Failed running install seeder.');
            return false;
        }

        $output->writeln('<info>[wmpanel]</info> install seeder executed.');
        return true;
    }

    protected function buildTempPhinxConfig(OutputInterface $output, string $migrationDir, ?string $seedDir): ?string
    {
        $projectRoot = base_path();
        $rootConfigFile = $projectRoot . '/config/migration.php';
        $pluginConfigDir = $projectRoot . '/config/plugin/yllumi/wmpanel';
        $pluginConfigFile = $pluginConfigDir . '/migration.php';
        $configFile = is_file($rootConfigFile) ? $rootConfigFile : $pluginConfigFile;

        if (!is_file($configFile)) {
            $output->writeln('<error>[wmpanel]</error> migration config not found. Checked: ' . $rootConfigFile . ' and ' . $pluginConfigFile);
            return null;
        }

        $baseConfig = include $configFile;
        if (!is_array($baseConfig)) {
            $output->writeln('<error>[wmpanel]</error> Invalid migration config format.');
            return null;
        }

        if (!isset($baseConfig['paths']) || !is_array($baseConfig['paths'])) {
            $baseConfig['paths'] = [];
        }
        $baseConfig['paths']['migrations'] = $migrationDir;
        if ($seedDir !== null) {
            $baseConfig['paths']['seeds'] = $seedDir;
        }

        $tempConfigFile = tempnam(sys_get_temp_dir(), 'wmpanel_migration_');
        if ($tempConfigFile === false) {
            $output->writeln('<error>[wmpanel]</error> Unable to create temporary migration config.');
            return null;
        }

        file_put_contents($tempConfigFile, "<?php\nreturn " . var_export($baseConfig, true) . ";\n");

        return $tempConfigFile;
    }

    protected function runCommandAndWriteOutput(string $command, OutputInterface $output): bool
    {
        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        return $returnVar === 0;
    }

    protected function publishFiles(OutputInterface $output): void
    {
        $projectRoot = base_path();
        $targetDir   = $projectRoot . '/config/plugin/panel';
        $packageSrc  = dirname(__DIR__, 2);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            $output->writeln('<info>[wmpanel]</info> Created: config/plugin/panel/');
        }

        $this->copyFile($packageSrc . '/menu.yml', $targetDir . '/menu.yml', $output);
        $this->copyFile($packageSrc . '/privileges.yml', $targetDir . '/privileges.yml', $output);
        $this->copyDirectory($packageSrc . '/settings', $targetDir . '/settings', $output);

        // Copy all config files to config/plugin/yllumi/wmpanel/
        $pluginConfigDir = $projectRoot . '/config/plugin/yllumi/wmpanel';
        if (!is_dir($pluginConfigDir)) {
            mkdir($pluginConfigDir, 0755, true);
            $output->writeln('<info>[wmpanel]</info> Created: config/plugin/yllumi/wmpanel/');
        }
        $this->copyDirectory($packageSrc . '/config', $pluginConfigDir, $output);
    }

    protected function copyFile(string $src, string $dest, OutputInterface $output): void
    {
        if (!is_file($src)) {
            return;
        }

        if (is_file($dest)) {
            $output->writeln('<comment>[wmpanel]</comment> Skipped (exists): ' . basename($dest));
            return;
        }

        copy($src, $dest);
        $output->writeln('<info>[wmpanel]</info> Published: ' . $dest);
    }

    protected function copyDirectory(string $src, string $dest, OutputInterface $output): void
    {
        if (!is_dir($src)) {
            return;
        }

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath  = $src  . '/' . $item;
            $destPath = $dest . '/' . $item;

            is_dir($srcPath)
                ? $this->copyDirectory($srcPath, $destPath, $output)
                : $this->copyFile($srcPath, $destPath, $output);
        }
    }
}
