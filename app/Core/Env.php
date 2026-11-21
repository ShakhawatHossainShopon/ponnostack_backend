<?php

declare(strict_types=1);

namespace App\Core {

    /**
     * Lightweight Zero-Dependency Environment Variable Loader
     * 
     * Securely loads and parses key=value pairs from the .env file.
     */
    class Env
    {
        private static bool $loaded = false;

        /**
         * Load environment variables from a given file path
         */
        public static function load(string $filePath): void
        {
            if (!file_exists($filePath) || !is_readable($filePath)) {
                return;
            }

            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                return;
            }

            foreach ($lines as $line) {
                $line = trim($line);

                // Skip empty lines and comment lines
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                // Must contain key=value delimiter
                if (!str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if ($key === '') {
                    continue;
                }

                // Remove surrounding single or double quotes
                $len = strlen($value);
                if ($len >= 2) {
                    if (($value[0] === '"' && $value[$len - 1] === '"') ||
                        ($value[0] === "'" && $value[$len - 1] === "'")) {
                        $value = substr($value, 1, -1);
                    }
                }

                // Convert standard literals to actual PHP types
                $lower = strtolower($value);
                $parsedValue = match ($lower) {
                    'true', '(true)' => true,
                    'false', '(false)' => false,
                    'null', '(null)' => null,
                    'empty', '(empty)' => '',
                    default => $value,
                };

                $_ENV[$key] = $parsedValue;
                $_SERVER[$key] = $parsedValue;

                if (is_string($value)) {
                    putenv("{$key}={$value}");
                }
            }

            self::$loaded = true;
        }

        /**
         * Get an environment variable value with fallback
         */
        public static function get(string $key, mixed $default = null): mixed
        {
            if (array_key_exists($key, $_ENV)) {
                return $_ENV[$key];
            }

            if (array_key_exists($key, $_SERVER)) {
                return $_SERVER[$key];
            }

            $val = getenv($key);
            if ($val !== false) {
                return $val;
            }

            return $default;
        }

        /**
         * Check if environment has been loaded
         */
        public static function isLoaded(): bool
        {
            return self::$loaded;
        }
    }
}

namespace {
    // Global helper function in root namespace for concise syntax: env('KEY', 'default')
    if (!function_exists('env')) {
        function env(string $key, mixed $default = null): mixed
        {
            return \App\Core\Env::get($key, $default);
        }
    }
}
