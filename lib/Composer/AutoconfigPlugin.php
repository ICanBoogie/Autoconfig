<?php

namespace ICanBoogie\Autoconfig\Composer;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use ICanBoogie\Autoconfig\AutoconfigGenerator;

use function realpath;

use const DIRECTORY_SEPARATOR;

final class AutoconfigPlugin implements PluginInterface, EventSubscriberInterface
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

    public static function dump(Event $event): void
    {
        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $generator = $composer->getAutoloadGenerator();
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($composer->getInstallationManager(), $package, $packages);
        $sorted = PackageMapSorter::sort($generator, $packageMap);

        $vendor_dir = $composer->getConfig()->get('vendor-dir');
        $destination = realpath($vendor_dir) . DIRECTORY_SEPARATOR . self::AUTOCONFIG_FILEPATH;
        $config = new AutoconfigGenerator($sorted, $destination);
        $config();
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
