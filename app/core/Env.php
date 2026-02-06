<?php
// app/Env.php

class Env {
    public static function load($path = null) {
        if ($path === null) {
            // Tenta localizar automaticamente o .env
            $paths = [
                __DIR__ . '/../../.env',
                __DIR__ . '/../../../.env',
                getcwd() . '/.env'
            ];

            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $path = $p;
                    break;
                }
            }

            if ($path === null) {
                throw new \Exception("Arquivo .env não encontrado");
            }
        }

        if (!file_exists($path)) return false;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $value = trim($value, "\"'");

                if (!empty($name)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
        return true;
    }

    public static function get($key, $default = null) {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? null;
        }

        if ($value === false || $value === null) {
            return $default;
        }

        // Converte strings booleanas
        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            default:
                return $value;
        }
    }
}