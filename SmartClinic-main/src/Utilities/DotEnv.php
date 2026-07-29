<?php

namespace Utilities;
class DotEnv
{
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected $path;


    public function __construct(string $path)
    {
        if(!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    /**
     * Loads the configured file and returns the effective values.
     *
     * By default, variables supplied by the hosting environment take
     * precedence over values committed to the file. A machine-specific
     * override file can explicitly replace those values when $overrideExisting
     * is true.
     */
    public function load(bool $overrideExisting = false): array
    {
        $returnEnv = array();
        if (!is_readable($this->path)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            $existingValue = null;
            if (array_key_exists($name, $_SERVER)) {
                $existingValue = (string)$_SERVER[$name];
            } elseif (array_key_exists($name, $_ENV)) {
                $existingValue = (string)$_ENV[$name];
            } else {
                $processValue = getenv($name);
                if ($processValue !== false) {
                    $existingValue = (string)$processValue;
                }
            }

            $effectiveValue = (!$overrideExisting && $existingValue !== null)
                ? $existingValue
                : $value;

            if ($overrideExisting || $existingValue === null) {
                putenv(sprintf('%s=%s', $name, $effectiveValue));
                $_ENV[$name] = $effectiveValue;
                $_SERVER[$name] = $effectiveValue;
            }
            $returnEnv[$name] = $effectiveValue;
        }
        return $returnEnv;
    }
}

?>
