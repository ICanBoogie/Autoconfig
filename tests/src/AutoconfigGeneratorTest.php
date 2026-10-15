<?php

namespace Test\ICanBoogie\Autoconfig;

use Composer\Package\Package;
use Composer\Package\RootPackage;
use ICanBoogie\Autoconfig\Autoconfig;
use ICanBoogie\Autoconfig\AutoconfigGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function basename;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class AutoconfigGeneratorTest extends TestCase
{
    private string $directory;
    private string $destination;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/autoconfig-' . uniqid();
        $this->destination = $this->directory . '/autoconfig.php';

        mkdir($this->directory . '/a', recursive: true);
        mkdir($this->directory . '/b');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->destination)) {
            unlink($this->destination);
        }

        if (is_dir($this->directory . '/a')) {
            rmdir($this->directory . '/a');
            rmdir($this->directory . '/b');
            rmdir($this->directory);
        }
    }

    public function test_boot_callables_are_collected_in_package_order(): void
    {
        $this->dump([
            $this->directory . '/a' => [ 'autoboot' => 'A\Boot::boot' ],
            $this->directory . '/b' => [ 'autoboot' => [ 'B\Boot::first', 'B\Boot::second' ] ],
        ]);

        $autoconfig = require $this->destination;

        $this->assertInstanceOf(Autoconfig::class, $autoconfig);
        $this->assertSame(
            [ 'A\Boot::boot', 'B\Boot::first', 'B\Boot::second' ],
            $autoconfig->boot_callables
        );
    }

    public function test_boot_callables_default_to_empty(): void
    {
        $this->dump([ $this->directory . '/a' => [ 'config-path' => 'config' ] ]);

        $autoconfig = require $this->destination;

        $this->assertInstanceOf(Autoconfig::class, $autoconfig);
        $this->assertSame([], $autoconfig->boot_callables);
    }

    public function test_invalid_boot_callable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectOutputRegex('/Invalid autoboot callable/');

        $this->dump([ $this->directory . '/a' => [ 'autoboot' => 'not a callable' ] ]);
    }

    /**
     * @param array<string, array<string, mixed>> $fragments
     *     Where _key_ is a package pathname.
     */
    private function dump(array $fragments): void
    {
        $packages = [];

        foreach ($fragments as $pathname => $fragment) {
            $package = new Package('test/' . basename($pathname), '1.0.0.0', '1.0.0');
            $package->setExtra([ 'icanboogie' => $fragment ]);
            $packages[] = [ $package, $pathname ];
        }

        $root = new RootPackage('test/root', '1.0.0.0', '1.0.0');

        new AutoconfigGenerator($root, $packages, $this->destination)->dump();
    }
}
