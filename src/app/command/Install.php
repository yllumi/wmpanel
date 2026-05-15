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

        $command = 'PLUGIN_PATH=' . escapeshellarg('vendor/yllumi/wmpanel/src/')
            . ' ./vendor/bin/phinx migrate --configuration=vendor/yllumi/wmpanel/src/config/migration.php'
            . ' --target=' . escapeshellarg($targetVersion);

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        if ($returnVar !== 0) {
            $output->writeln('<error>[wmpanel]</error> Failed running install_plugin migration.');
            return false;
        }

        $output->writeln('<info>[wmpanel]</info> install_plugin migration executed.');
        return true;
    }

    protected function runInstallSeeder(OutputInterface $output): bool
    {
        $projectRoot = base_path();
        $seedDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/seeds';
        $seedClass = 'WmpanelInitSeeder';

        if (!is_file($seedDir . '/' . $seedClass . '.php')) {
            $output->writeln('<comment>[wmpanel]</comment> Seeder not found, skipping.');
            return true;
        }

        $command = 'PLUGIN_PATH=' . escapeshellarg('vendor/yllumi/wmpanel/src/')
            . ' ./vendor/bin/phinx seed:run --configuration=vendor/yllumi/wmpanel/src/config/migration.php'
            . ' --seed=' . escapeshellarg($seedClass);

        exec($command, $outputLines, $returnVar);
        foreach ($outputLines as $line) {
            $output->writeln($line);
        }

        if ($returnVar !== 0) {
            $output->writeln('<error>[wmpanel]</error> Failed running install seeder.');
            return false;
        }

        $output->writeln('<info>[wmpanel]</info> install seeder executed.');
        return true;
    }

    protected function publishFiles(OutputInterface $output): void
    {
        $projectRoot = base_path();
        $targetDir   = $projectRoot . '/config/plugin/panel';
        $packageSrc  = dirname(__DIR__, 2);
        $themeSrcDir = dirname($packageSrc) . '/theme';
        $publicThemeDir = $projectRoot . '/public/theme';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            $output->writeln('<info>[wmpanel]</info> Created: config/plugin/panel/');
        }

        $this->copyFile($packageSrc . '/menu.yml', $targetDir . '/menu.yml', $output);
        $this->copyFile($packageSrc . '/privileges.yml', $targetDir . '/privileges.yml', $output);
        $this->copyDirectory($packageSrc . '/settings', $targetDir . '/settings', $output);

        $this->copyDirectory($themeSrcDir, $publicThemeDir, $output);

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
