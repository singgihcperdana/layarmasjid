<?php
/**
 * Lightweight .env Loader & Helper
 * File: api/env.php
 */

if (!function_exists('load_env')) {
    function load_env($file_path = null) {
        if ($file_path === null) {
            $file_path = dirname(__DIR__) . '/.env';
        }

        if (!file_exists($file_path)) {
            return;
        }

        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Lewati baris komentar atau kosong
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                // Bersihkan kutip tunggal atau ganda
                if (
                    (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))
                ) {
                    $val = substr($val, 1, -1);
                }

                $_ENV[$key] = $val;
                putenv("{$key}={$val}");
            }
        }
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $default;
        }
        return $val;
    }
}

// Otomatis muat .env jika belum dimuat
load_env();
