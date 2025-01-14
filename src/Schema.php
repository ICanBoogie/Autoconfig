<?php

namespace ICanBoogie\Autoconfig;

use Composer\Json\JsonFile;
use InvalidArgumentException;
use JsonSchema\Validator;
use Throwable;

use function array_walk;
use function file_get_contents;
use function is_array;
use function is_numeric;
use function is_object;
use function is_scalar;
use function json_decode;
use function key;
use function reset;

use const JSON_THROW_ON_ERROR;

/**
 * A JSON schema.
 *
 * Used to validate other JSON files.
 *
 * @codeCoverageIgnore
 */
final readonly class Schema
{
    /**
     * Read schema data from a JSON file.
     */
    public static function read(string $pathname): object
    {
        $json = file_get_contents($pathname);

        assert(is_string($json));

        JsonFile::parseJson($json, $pathname);

        $decoded = json_decode($json, flags: JSON_THROW_ON_ERROR);

        assert(is_object($decoded));

        return $decoded;
    }

    public static function normalize_data(mixed $data): mixed
    {
        if ($data && is_array($data)) {
            array_walk($data, function (&$data) {
                $data = self::normalize_data($data);
            });

            reset($data);
            $key = key($data);

            if (is_scalar($key) && !is_numeric($key)) { // @phpstan-ignore-line
                $data = (object) $data;
            }
        }

        return $data;
    }

    private Validator $validator;

    /**
     * @param object $schema Schema data as returned by {@link read()}.
     */
    public function __construct(
        private object $schema
    ) {
        $this->validator = new Validator();
    }

    /**
     * Validate some data against the schema.
     *
     * @param mixed $data Data to validate.
     * @param string $pathname The pathname to the file where the data is defined.
     *
     * @throws Throwable when the data is not valid.
     */
    public function validate(mixed $data, string $pathname): void
    {
        $validator = $this->validator;

        $validator->check($data, $this->schema);

        if (!$validator->isValid()) {
            $errors = '';

            foreach ($validator->getErrors() as $error) {
                $errors .= "\n- " . ($error['property'] ? $error['property'] . ': ' : '') . $error['message'];
            }

            throw new InvalidArgumentException("`$pathname` does not match the expected JSON schema:\n$errors");
        }
    }

    /**
     * Validate a JSON file against the schema.
     *
     * @param string $pathname The pathname to the JSON file to validate.
     *
     * @throws Throwable when the data is not valid.
     *
     * @see validate()
     */
    public function validate_file(string $pathname): void
    {
        $data = self::read($pathname);

        $this->validate($data, $pathname);
    }
}
