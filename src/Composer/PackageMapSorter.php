<?php

namespace ICanBoogie\Autoconfig\Composer;

use Composer\Autoload\AutoloadGenerator;
use Composer\Package\PackageInterface;

final class PackageMapSorter extends AutoloadGenerator
{
    /**
     * @param non-empty-array<int, array{0: PackageInterface, 1: string|null}> $packageMap
     *
     * @return array<int, array{0: PackageInterface, 1: string|null}>
     */
    public static function sort(AutoloadGenerator $generator, array $packageMap): array
    {
        return $generator->sortPackageMap($packageMap);
    }
}
