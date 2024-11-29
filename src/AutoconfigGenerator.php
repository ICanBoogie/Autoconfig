<?php

namespace ICanBoogie\Autoconfig;

use Composer\Package\PackageInterface;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Composer\Util\Filesystem;
use ICanBoogie\Config\Builder;
use Throwable;

use function array_keys;
use function array_map;
use function array_merge;
use function assert;
use function file_put_contents;
use function getcwd;
use function implode;
use function is_string;
use function ksort;
use function realpath;

/**
 * @codeCoverageIgnore
 */
final class AutoconfigGenerator
{
    private Filesystem $filesystem;

    /**
     * @var array<string, array<SchemaOptions::*, mixed>>
     */
    private array $fragments = [];

    /**
     * @var array<string, int>
     */
    private array $weights = [];

    /**
     * @var ExtensionAbstract[]
     */
    private array $extensions = [];

    /**
     * @var array<string, PackageInterface>
     *     Where _key_ is a pathname.
     */
    public readonly array $packages;

    /**
     * @param array<int, array{0: PackageInterface, 1: string|null}> $packages
     */
    public function __construct(
        public readonly RootPackageInterface $root_package,
        array $packages,
        public readonly string $destination,
    ) {
        $this->filesystem = new Filesystem();
        $this->packages = $this->index_packages($packages);
    }

    /**
     * @param array<int, array{0: PackageInterface, 1: string|null}> $packages
     *
     * @return array<string, PackageInterface>
     */
    private function index_packages(array $packages): array
    {
        $byDir = [];

        foreach ($packages as [$package, $pathname]) {
            if (!$pathname) {
                $pathname = getcwd();
            }

            assert(is_string($pathname));

            $byDir[$pathname] = $package;
        }

        return $byDir;
    }

    /**
     * @throws Throwable
     */
    public function dump(): void
    {
        [ $fragments, $weights ] = $this->collect_fragments();

        $this->fragments = $fragments;
        $this->weights = $weights;
        $this->extensions = $this->collect_extensions($fragments);

        $this->validate_fragments($fragments);
        $this->write();
    }

    public function find_shortest_path_code(string $to): string
    {
        return $this->filesystem->findShortestPathCode($this->destination, $to);
    }

    public function render_entry(string $key, string $value): string
    {
        return <<<EOT
    '$key' => $value,
EOT;
    }

    /**
     * @param array<string, mixed> $items
     */
    public function render_array_entry(string $key, array $items, callable $renderer): string
    {
        $rendered_items = implode(
            array_map(
                fn($item, $key) => "\t\t" . $renderer($item, $key) . ",\n",
                $items,
                array_keys($items)
            )
        );

        return <<<EOT
    '$key' => [

$rendered_items
    ],
EOT;
    }

    /**
     * @param array<string, mixed> $items
     */
    public function render_array(array $items, callable $renderer): string
    {
        $rendered_items = implode(
            array_map(
                fn($item, $key) => "\t\t" . $renderer($item, $key) . ",\n",
                $items,
                array_keys($items)
            )
        );

        return <<<EOT
[

$rendered_items
    ]
EOT;
    }

    /**
     * Collect autoconfig fragments from packages.
     *
     * @return array{ array<string, array<SchemaOptions::*, mixed>>, array<string, int> }
     *     An array with the collected fragments and their weights.
     */
    private function collect_fragments(): array
    {
        $fragments = [];
        $weights = [];

        foreach ($this->packages as $pathname => $package) {
            $pathname = realpath($pathname);

            assert(is_string($pathname));

            $fragment = $this->find_fragment($package);

            if (!$fragment) {
                continue;
            }

            $fragments[$pathname] = $fragment;
            /** @phpstan-ignore-next-line */
            $weights[$pathname] = $this->resolve_config_weight($package, $fragment);
        }

        return [ $fragments, $weights ];
    }

    /**
     * Try to find autoconfig fragment of package.
     *
     * @return array<SchemaOptions::*, mixed>|null The autoconfig fragment, or `null` if the package doesn't define one.
     */
    private function find_fragment(PackageInterface $package): ?array
    {
        /** @phpstan-ignore-next-line */
        return $package->getExtra()['icanboogie'] ?? null;
    }

    /**
     * @param array<string, array<SchemaOptions::*, mixed>> $fragments
     *
     * @return ExtensionAbstract[]
     */
    private function collect_extensions(array $fragments): array
    {
        $extensions = [];

        foreach ($fragments as $fragment) {
            if (empty($fragment[SchemaOptions::AUTOCONFIG_EXTENSION])) {
                continue;
            }

            /** @var class-string<ExtensionAbstract> $class */
            $class = $fragment[SchemaOptions::AUTOCONFIG_EXTENSION];
            $extensions[] = new $class($this);
        }

        return $extensions;
    }

    /**
     * Validate fragments against schema.
     *
     * @param array<string, mixed> $fragments
     *
     * @throws Throwable
     */
    private function validate_fragments(array $fragments): void
    {
        $data = Schema::read(__DIR__ . '/schema.json');
        $properties = &$data->properties;
        $set_property = function (string $property, array $data) use (&$properties): void {
            $properties->$property = (object)$data;
        };

        foreach ($this->extensions as $extension) {
            $extension->alter_schema($set_property);
        }

        $schema = new Schema($data);

        foreach ($fragments as $pathname => $fragment) {
            $schema->validate(Schema::normalize_data($fragment), $pathname);
        }
    }

    /**
     * @param array{ config-weight?: int } $fragment
     */
    private function resolve_config_weight(PackageInterface $package, array $fragment): int
    {
        if (isset($fragment[SchemaOptions::CONFIG_WEIGHT])) {
            return $fragment[SchemaOptions::CONFIG_WEIGHT];
        }

        if ($package instanceof RootPackage) {
            return Autoconfig::CONFIG_WEIGHT_APP;
        }

        return Autoconfig::CONFIG_WEIGHT_FRAMEWORK;
    }

    private const SCHEMA_MAPPING = [

        SchemaOptions::APP_PATHS => Autoconfig::ARG_APP_PATHS,
        SchemaOptions::CONFIG_PATH => Autoconfig::ARG_CONFIG_PATHS,
        SchemaOptions::CONFIG_CONSTRUCTOR => Autoconfig::ARG_CONFIG_BUILDERS,
        SchemaOptions::LOCALE_PATH => Autoconfig::ARG_LOCALE_PATHS,
        SchemaOptions::AUTOCONFIG_FILTERS => Autoconfig::ARG_FILTERS,

    ];

    /**
     * Synthesize the autoconfig fragments into a single array.
     *
     * @return array{
     *     app_paths: array<string>,
     *     config_paths: array<string, int>,
     *     config_builders: array<class-string, class-string<Builder<object>>>,
     *     locale_paths: array<string>,
     *     filters: array<callable-string>
     * }
     */
    private function synthesize(): array
    {
        $config = [

            Autoconfig::ARG_APP_PATHS => [],
            Autoconfig::ARG_CONFIG_BUILDERS => [],
            Autoconfig::ARG_CONFIG_PATHS => [],
            Autoconfig::ARG_LOCALE_PATHS => [],
            Autoconfig::ARG_FILTERS => [],

        ];

        foreach ($this->fragments as $path => $fragment) {
            foreach ($fragment as $key => $value) {
                switch ($key) {
                    case SchemaOptions::APP_PATHS:
                    case SchemaOptions::CONFIG_CONSTRUCTOR:
                    case SchemaOptions::AUTOCONFIG_FILTERS:
                        $key = self::SCHEMA_MAPPING[$key];
                        $config[$key] = array_merge($config[$key], (array)$value);
                        break;

                    case SchemaOptions::CONFIG_PATH:
                        foreach ((array)$value as $v) {
                            assert(is_string($v));

                            $config[Autoconfig::ARG_CONFIG_PATHS][] = [

                                $this->find_shortest_path_code("$path/$v"),
                                $this->weights[$path]

                            ];
                        }
                        break;

                    case SchemaOptions::LOCALE_PATH:
                        $key = self::SCHEMA_MAPPING[$key];

                        foreach ((array)$value as $v) {
                            assert(is_string($v));

                            $config[$key][] = $this->find_shortest_path_code("$path/$v");
                        }

                        break;
                }
            }
        }

        foreach ($this->extensions as $extension) {
            $extension->synthesize($config);
        }

        return $config; // @phpstan-ignore return.type
    }

    /**
     * Render the synthesized autoconfig into a string.
     *
     * @param array{
     *     app_paths: array<string>,
     *     config_paths: array<string, int>,
     *     config_builders: array<class-string, class-string<Builder<object>>>,
     *     locale_paths: array<string>,
     *     filters: array<callable-string>
     * } $config
     */
    private function render(array $config): string
    {
        $generator = __CLASS__;

        $rendered_app_paths = $this->render_app_paths_array($config);
        $rendered_config_path = $this->render_config_path($config);
        $rendered_config_builders = $this->render_config_builders_array($config);
        $rendered_locale_paths = $this->render_locale_paths_array($config);
        $rendered_filters = $this->render_filters($config);

        return <<<PHP
        <?php

        /**
         * DO NOT EDIT THIS FILE
         *
         * @generated by {@see \\$generator}
         * @link https://icanboogie.org/docs/6.0/autoconfig
         */

        namespace ICanBoogie\Autoconfig;

        use function assert;
        use function dirname;
        use function getcwd;
        use function is_string;

        use const DIRECTORY_SEPARATOR;

        \$cwd = getcwd();

        assert(is_string(\$cwd));

        return new Autoconfig(
            base_path: \$cwd . DIRECTORY_SEPARATOR,
            app_path: \$cwd . DIRECTORY_SEPARATOR . Autoconfig::DEFAULT_APP_DIRECTORY . DIRECTORY_SEPARATOR,
            app_paths: $rendered_app_paths,
            config_paths: $rendered_config_path,
            config_builders: $rendered_config_builders,
            locale_paths: $rendered_locale_paths,
            filters: $rendered_filters,
        );
        PHP;
    }

    /**
     * @param array{ app_paths: array<string> } $config
     */
    private function render_app_paths_array(array $config): string
    {
        return $this->render_array(
            $config[Autoconfig::ARG_APP_PATHS],
            function ($item): string {
                return (string)$item;
            }
        );
    }

    /**
     * @param array{ locale_paths: array<string> } $config
     */
    private function render_locale_paths_array(array $config): string
    {
        return $this->render_array(
            $config[Autoconfig::ARG_LOCALE_PATHS],
            function ($item): string {
                return (string)$item;
            }
        );
    }

    /**
     * @param array{ config_builders: array<class-string, class-string> } $config
     */
    private function render_config_builders_array(array $config): string
    {
        $builders = $config[Autoconfig::ARG_CONFIG_BUILDERS];
        ksort($builders);

        return $this->render_array(
            $builders,
            function ($builder_class, $name): string {
                return "\\$name::class => \\$builder_class::class";
            }
        );
    }

    /**
     * @param array{ config_paths: array<string, int> } $config
     */
    private function render_config_path(array $config): string
    {
        return $this->render_array(
            $config[Autoconfig::ARG_CONFIG_PATHS],
            function ($item): string {
                [ $path_code, $weight ] = $item;

                return "$path_code => $weight";
            }
        );
    }

    /**
     * @param array{ filters: array<callable-string> } $config
     */
    private function render_filters(array $config): string
    {
        $data = $config[Autoconfig::ARG_FILTERS];

        return $this->render_array(
            $data,
            function ($callable): string {
                return "'$callable'";
            }
        );
    }

    /**
     * Write the autoconfig file.
     *
     * @throws Throwable
     */
    private function write(): void
    {
        try {
            $autoconfig = $this->synthesize();

            file_put_contents(
                $this->destination,
                $this->render($autoconfig)
            );
        } catch (Throwable $e) {
            echo $e;

            throw $e;
        }
    }
}
