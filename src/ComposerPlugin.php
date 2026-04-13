<?php

namespace Yllumi\Wmpanel;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    protected Composer $composer;
    protected IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE  => 'onPostPackageUpdate',
        ];
    }

    public function onPostPackageInstall(PackageEvent $event): void
    {
        $package = $event->getOperation()->getPackage();
        if ($package->getName() === 'yllumi/wmpanel') {
            $this->publishFiles();
        }
    }

    public function onPostPackageUpdate(PackageEvent $event): void
    {
        $package = $event->getOperation()->getTargetPackage();
        if ($package->getName() === 'yllumi/wmpanel') {
            $this->publishFiles();
        }
    }

    protected function publishFiles(): void
    {
        // Root project directory (where composer.json of the project lives)
        $projectRoot = $this->composer->getConfig()->get('vendor-dir') . '/..';
        $projectRoot = realpath($projectRoot) ?: rtrim($projectRoot, '/');

        $targetDir = $projectRoot . '/config/plugin/panel';
        $packageSrc = __DIR__;

        // Create target directory if it does not exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            $this->io->write('<info>[yllumi/wmpanel]</info> Created directory: config/plugin/panel/');
        }

        // Copy menu.yml
        $this->copyFile($packageSrc . '/menu.yml', $targetDir . '/menu.yml');

        // Copy privileges.yml
        $this->copyFile($packageSrc . '/privileges.yml', $targetDir . '/privileges.yml');

        // Copy settings/ folder recursively
        $this->copyDirectory($packageSrc . '/settings', $targetDir . '/settings');

        // Publish all config files to config/plugin/yllumi/wmpanel/
        $pluginConfigDir = $projectRoot . '/config/plugin/yllumi/wmpanel';
        if (!is_dir($pluginConfigDir)) {
            mkdir($pluginConfigDir, 0755, true);
            $this->io->write('<info>[yllumi/wmpanel]</info> Created directory: config/plugin/yllumi/wmpanel/');
        }
        $this->copyDirectory($packageSrc . '/config', $pluginConfigDir);
    }

    protected function copyFile(string $src, string $dest): void
    {
        if (!is_file($src)) {
            return;
        }

        // Do not overwrite existing files to preserve user customisations
        if (is_file($dest)) {
            $this->io->write('<comment>[yllumi/wmpanel]</comment> Skipped (already exists): ' . basename($dest));
            return;
        }

        copy($src, $dest);
        $this->io->write('<info>[yllumi/wmpanel]</info> Published: config/plugin/panel/' . basename($dest));
    }

    protected function copyDirectory(string $src, string $dest): void
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

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath);
            } else {
                $this->copyFile($srcPath, $destPath);
            }
        }
    }
}
