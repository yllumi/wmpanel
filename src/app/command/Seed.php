<?php

namespace Yllumi\Wmpanel\app\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('wmpanel:seed', 'Run a specific seeder from yllumi/wmpanel package.')]
class Seed extends Command
{
    protected function configure(): void
    {
        $this->addArgument('seeder', InputArgument::OPTIONAL, 'Seeder class name', 'WmpanelInitSeeder');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $seedClass = (string)$input->getArgument('seeder');
        $output->writeln('<info>[wmpanel]</info> Running seeder: ' . $seedClass);

        $projectRoot = base_path();
        $migrationDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/migrations';
        $seedDir = $projectRoot . '/vendor/yllumi/wmpanel/src/database/seeds';

        if (!is_file($seedDir . '/' . $seedClass . '.php')) {
            $output->writeln('<error>[wmpanel]</error> Seeder class file not found: ' . $seedClass . '.php');
            return Command::FAILURE;
        }

        $tempConfigFile = $this->buildTempPhinxConfig($output, $migrationDir, $seedDir);
        if ($tempConfigFile === null) {
            return Command::FAILURE;
        }

        $command = sprintf(
            './vendor/bin/phinx seed:run --configuration=%s --seed=%s',
            escapeshellarg($tempConfigFile),
            escapeshellarg($seedClass)
        );

        $success = $this->runCommandAndWriteOutput($command, $output);
        @unlink($tempConfigFile);

        if (!$success) {
            $output->writeln('<error>[wmpanel]</error> Failed running seeder: ' . $seedClass);
            return Command::FAILURE;
        }

        $output->writeln('<info>[wmpanel]</info> Seeder complete.');
        return Command::SUCCESS;
    }

    protected function buildTempPhinxConfig(OutputInterface $output, string $migrationDir, string $seedDir): ?string
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
        $baseConfig['paths']['seeds'] = $seedDir;

        $tempConfigFile = tempnam(sys_get_temp_dir(), 'wmpanel_seed_');
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
}
