<?php

namespace Test\ICanBoogie\Autoconfig\Composer;

use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase
{
    public function testPlugin(): void
    {
        $this->assertFileExists(dirname(__DIR__, 3) . "/vendor/icanboogie/autoconfig.php");
    }
}
