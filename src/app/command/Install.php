<?php

namespace Yllumi\Wmpanel\app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use support\Db;
use Throwable;

#[AsCommand('wmpanel:install', 'Install yllumi/wmpanel: run SQL migrations and publish config files.')]
class Install extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>[wmpanel]</info> Starting installation...');

        $this->installSql($output);
        $this->publishFiles($output);

        $output->writeln('<info>[wmpanel]</info> Installation complete.');
        return Command::SUCCESS;
    }

    protected function installSql(OutputInterface $output): void
    {
        $sqlFile = dirname(__DIR__, 2) . '/install.sql';

        if (!is_file($sqlFile)) {
            $output->writeln('<comment>[wmpanel]</comment> install.sql not found, skipping.');
            return;
        }

        $sql = file_get_contents($sqlFile);
        // Tambahkan IF NOT EXISTS agar aman dijalankan ulang
        $sql = preg_replace('/CREATE TABLE\s+`/i', 'CREATE TABLE IF NOT EXISTS `', $sql);

        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            try {
                Db::connection('default')->statement($statement);
            } catch (Throwable $e) {
                $output->writeln('<error>[wmpanel]</error> SQL Error: ' . $e->getMessage());
            }
        }

        $output->writeln('<info>[wmpanel]</info> Database tables installed.');
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
