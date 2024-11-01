<?php

namespace ICanBoogie\Autoconfig;

final class AutoconfigResolver
{
    public const ENV_ICANBOOGIE_INSTANCE = 'ICANBOOGIE_INSTANCE';

    public static function resolve(): Autoconfig
    {
        if (!defined('ICANBOOGIE_AUTOCONFIG')) {
            $tries = [
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'autoconfig.php',
                dirname(
                    __DIR__
                ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'icanboogie' . DIRECTORY_SEPARATOR . 'autoconfig.php',
            ];

            foreach ($tries as $try) {
                if (file_exists($try)) {
                    define('ICANBOOGIE_AUTOCONFIG', $try);
                    break;
                }
            }

            /** @phpstan-ignore-next-line */
            if (!defined('ICANBOOGIE_AUTOCONFIG')) {
                $tries = implode(', ', $tries);

                trigger_error(
                    "The autoconfig file is missing, tried: $tries. Check the `script` section of your composer.json file. https://icanboogie.org/docs/4.0/autoconfig#generating-the-autoconfig-file",
                    E_USER_ERROR
                );
            }
        }

        /** @var Autoconfig $autoconfig */

        $autoconfig = require \ICANBOOGIE_AUTOCONFIG;

        $additional_app_paths = self::resolve_app_paths($autoconfig->app_path);
        $config_paths = $autoconfig->config_paths;

        foreach ($additional_app_paths as $path) {
            $path = $path . 'config';

            if (file_exists($path)) {
                $config_paths[$path] = Autoconfig::CONFIG_WEIGHT_APP;
            }
        }

        $autoconfig = $autoconfig->with([
            Autoconfig::ARG_APP_PATHS => array_merge($autoconfig->app_paths, $additional_app_paths),
            Autoconfig::ARG_CONFIG_PATHS => $config_paths,
        ]);

        foreach ($autoconfig->filters as $filter) {
            $autoconfig = $filter($autoconfig);
        }

        return $autoconfig;
    }

    /**
     * Resolves application instance name.
     */
    private static function resolve_instance_name(): string
    {
        $instance = getenv(self::ENV_ICANBOOGIE_INSTANCE);

        if (!$instance && PHP_SAPI == 'cli') {
            $instance = 'cli';
        }

        if (!$instance && !empty($_SERVER['SERVER_NAME'])) {
            $instance = $_SERVER['SERVER_NAME'];
        }

        return $instance;
    }

    /**
     * Resolves the paths where the application can look for config, locale, modules, and more.
     *
     * @return string[] An array of absolute paths, ordered from the less specific to
     * the most specific.
     *
     * @see https://icanboogie.org/docs/4.0/multi-site
     */
    public static function resolve_app_paths(string $root, ?string $instance = null): array
    {
        $instance ??= self::resolve_instance_name();

        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $parts = explode('.', $instance);
        $paths = [];

        while ($parts) {
            $try = $root . implode('.', $parts);

            if (!file_exists($try)) {
                array_shift($parts);


                continue;
            }

            $paths[] = $try . DIRECTORY_SEPARATOR;

            break;
        }

        if (!$paths && file_exists($root . 'default')) {
            $paths[] = $root . 'default' . DIRECTORY_SEPARATOR;
        }

        if (file_exists($root . 'all')) {
            array_unshift($paths, $root . 'all' . DIRECTORY_SEPARATOR);
        }

        return $paths;
    }
}
