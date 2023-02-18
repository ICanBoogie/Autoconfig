<?php

namespace ICanBoogie\Autoconfig\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use ICanBoogie\Autoconfig\AutoconfigGenerator;
use Throwable;

use function realpath;

use const DIRECTORY_SEPARATOR;

final class Plugin implements PluginInterface, EventSubscriberInterface
{
    public const PRIORITY = 0;

    /**
     * Autoconfig file path, relative to the vendor folder.
     */
    public const AUTOCONFIG_FILEPATH = 'icanboogie/autoconfig.php';

    public static function getSubscribedEvents(): array
    {
        return [
            'post-autoload-dump' => [ 'dump', self::PRIORITY ],
        ];
    }

    /**
     * @throws Throwable
     */
    public static function dump(ScriptEvent $event): void
    {
        $log = $event->getIO()->write(...);
        $log("<info>Generating ICanBoogie's Autoconfig</info>");

        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $generator = $composer->getAutoloadGenerator();
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($composer->getInstallationManager(), $package, $packages);
        $sorted = PackageMapSorter::sort($generator, $packageMap);

        $vendor_dir = $composer->getConfig()->get('vendor-dir');
        assert(is_string($vendor_dir));
        $destination = realpath($vendor_dir) . DIRECTORY_SEPARATOR . self::AUTOCONFIG_FILEPATH;

        $dumper = new AutoconfigGenerator($package, $sorted, $destination);
        $dumper->dump();

        $log("<info>Generated ICanBoogie's Autoconfig in $destination</info>");
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        // nothing to do
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // nothing to do
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // nothing to do
    }
}
