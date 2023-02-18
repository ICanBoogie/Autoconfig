<?php

namespace Test\ICanBoogie\Autoconfig;

use ICanBoogie\Autoconfig\Autoconfig;
use ICanBoogie\Autoconfig\AutoconfigResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AutoconfigResolverTest extends TestCase
{
    /**
     * @param string[] $expected
     */
    #[DataProvider("provide_test_resolve_app_paths")]
    public function test_resolve_app_paths(string $root, string $server_name, array $expected): void
    {
        $this->assertEquals($expected, AutoconfigResolver::resolve_app_paths($root, $server_name));
    }

    /**
     * @return array<array{ string, string, string[] }>
     */
    public static function provide_test_resolve_app_paths(): array
    {
        $root = __DIR__ . '/../cases/resolve_app_paths/';
        $root0 = $root . 'app_0' . DIRECTORY_SEPARATOR;
        $root1 = $root . 'app_1' . DIRECTORY_SEPARATOR;

        return [

            [ $root0, 'www.icanboogie.org',       [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'org' . DIRECTORY_SEPARATOR ] ],
            [ $root0, 'icanboogie.org',           [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'org' . DIRECTORY_SEPARATOR ] ],
            [ $root0, 'icanboogie.localhost',     [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'localhost' . DIRECTORY_SEPARATOR ] ],
            [ $root0, 'www.icanboogie.localhost', [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'localhost' . DIRECTORY_SEPARATOR ] ],
            [ $root0, 'icanboogie.fr',            [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'icanboogie.fr' . DIRECTORY_SEPARATOR ] ],
            [ $root0, 'www.icanboogie.fr',        [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'icanboogie.fr' . DIRECTORY_SEPARATOR ] ],
            [ $root0, 'cli',                      [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'cli' . DIRECTORY_SEPARATOR ] ],
            [ $root0, 'undefined',                [ $root0 . 'all' . DIRECTORY_SEPARATOR, $root0 . 'default' . DIRECTORY_SEPARATOR ] ],

            [ $root1, 'www.icanboogie.org',       [ $root1 . 'org' . DIRECTORY_SEPARATOR ] ],
            [ $root1, 'icanboogie.org',           [ $root1 . 'org' . DIRECTORY_SEPARATOR ] ],
            [ $root1, 'icanboogie.localhost',     [ $root1 . 'localhost' . DIRECTORY_SEPARATOR ] ],
            [ $root1, 'www.icanboogie.localhost', [ $root1 . 'localhost' . DIRECTORY_SEPARATOR ] ],
            [ $root1, 'icanboogie.fr',            [ $root1 . 'icanboogie.fr' . DIRECTORY_SEPARATOR ] ],
            [ $root1, 'www.icanboogie.fr',        [ $root1 . 'icanboogie.fr' . DIRECTORY_SEPARATOR ] ],
            [ $root1, 'cli',                      [ $root1 . 'cli' . DIRECTORY_SEPARATOR ] ],
            [ $root1, 'undefined',                [ ] ],

        ];
    }

    public function test_get(): void
    {
        $cwd = getcwd();
        assert(is_string($cwd));
        $package_root = $cwd;

        $expected = new Autoconfig(
            base_path: "$cwd/",
            app_path: "$cwd/app/",
            app_paths: [

            ],
            config_paths: [

                "$package_root/vendor/icanboogie/bind-event/config" => Autoconfig::CONFIG_WEIGHT_FRAMEWORK,
                "$package_root/vendor/icanboogie/icanboogie/config" => Autoconfig::CONFIG_WEIGHT_FRAMEWORK,
                "$package_root/vendor/icanboogie/bind-http/config" => Autoconfig::CONFIG_WEIGHT_FRAMEWORK,
                "$package_root/vendor/icanboogie/bind-prototype/config" => Autoconfig::CONFIG_WEIGHT_FRAMEWORK,
                "$package_root/vendor/icanboogie/bind-routing/config" => Autoconfig::CONFIG_WEIGHT_FRAMEWORK,
                "$package_root/vendor/icanboogie/bind-symfony-dependency-injection/config" => Autoconfig::CONFIG_WEIGHT_FRAMEWORK,
                "$package_root/vendor/icanboogie/console/config" => Autoconfig::CONFIG_WEIGHT_FRAMEWORK,

            ],
            config_builders: [

                'ICanBoogie\AppConfig' => 'ICanBoogie\AppConfigBuilder',
                'ICanBoogie\Event\Config' => 'ICanBoogie\Binding\Event\ConfigBuilder',
                'ICanBoogie\Prototype\Config' => 'ICanBoogie\Binding\Prototype\ConfigBuilder',
                'ICanBoogie\Routing\RouteProvider' => 'ICanBoogie\Binding\Routing\ConfigBuilder',
                'ICanBoogie\Binding\SymfonyDependencyInjection\Config' => 'ICanBoogie\Binding\SymfonyDependencyInjection\ConfigBuilder',
                'ICanBoogie\Debug\Config' => 'ICanBoogie\Debug\ConfigBuilder',

            ],
            locale_paths: [

            ],
            filters: [

            ],
        );

        $actual = Autoconfig::get();

        $this->assertEquals($expected, $actual);
    }
}
