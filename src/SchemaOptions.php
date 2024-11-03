<?php

namespace ICanBoogie\Autoconfig;

/**
 * Keys of the ICanBoogie section in the composer.json file.
 *
 * @see schema.json
 */
interface SchemaOptions
{
    public const string AUTOCONFIG_EXTENSION = 'autoconfig-extension';
    public const string AUTOCONFIG_FILTERS = 'autoconfig-filters';
    public const string CONFIG_CONSTRUCTOR = 'config-constructor';
    public const string CONFIG_PATH = 'config-path';
    public const string CONFIG_WEIGHT = 'config-weight';
    public const string LOCALE_PATH = 'locale-path';
    public const string APP_PATH = 'app-path';
    public const string APP_PATHS = 'app-paths';
}
