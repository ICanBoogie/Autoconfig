<?php

namespace ICanBoogie\Autoconfig;

use const DIRECTORY_SEPARATOR;

/**
 * @WIP
 */
final class AutoconfigBuilder
{
    private array $config_paths = [];

    public function add_config(string $path): self
    {
        $this->config_paths[] = $this->ensure_directory_separator_terminated($path);

        return $this;
    }

    /**
     * @var array<class-string, class-string>
     *     Where _key_ is a config class and _value_ its builder class.
     */
    private array $config_builders = [];

    /**
     * @param class-string $config
     * @param class-string $builder
     *
     * @return $this
     */
    public function add_config_builder(string $config, string $builder): self
    {
        $this->config_builders[$config] = $builder;

        return $this;
    }

    /**
     * @var string[]
     */
    private array $app_paths = [];

    /**
     * @return $this
     */
    public function add_app_path(string $path): self
    {
        $this->app_paths[] = $this->ensure_directory_separator_terminated($path);

        return $this;
    }

    /**
     * @var callable-string[]
     */
    private array $autoconfig_filter = [];

    /**
     * @phpstan-param callable-string $filter
     *
     * @return $this
     */
    public function add_autoconfig_filter(callable $filter): self
    {
        $this->autoconfig_filter[] = $filter;

        return $this;
    }

    private function ensure_directory_separator_terminated(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
