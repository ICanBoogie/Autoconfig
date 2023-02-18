<?php

namespace ICanBoogie\Autoconfig\Composer;

use Composer\Autoload\AutoloadGenerator;

final class PackageMapSorter extends AutoloadGenerator
{
    public static function sort(AutoloadGenerator $generator, array $packageMap): array
    {
        return $generator->sortPackageMap($packageMap);
    }
}
