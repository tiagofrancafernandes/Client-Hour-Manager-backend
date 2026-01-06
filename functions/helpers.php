<?php

use Illuminate\Support\Carbon;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

if (!function_exists('get_env')) {
    /**
     * get_env function
     *
     * Gets the value of an environment variable, or all
     *
     * @param string|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    function get_env(?string $key = null, mixed $default = null): mixed
    {
        return App\Helpers\Env::init()->getEnv($key, $default);
    }
}

if (!function_exists('base_path')) {
    /**
     * function base_path
     *
     * @param string $path = ''
     *
     * @return string
     */
    function base_path(string $path = ''): ?string
    {
        if (!defined('BASE_PATH')) {
            return null;
        }

        return BASE_PATH . '/' . ltrim($path, '/');
    }
}

if (!function_exists('temp_path')) {
    /**
     * function temp_path
     *
     * @param string $path = ''
     *
     * @return string
     */
    function temp_path(string $path = ''): ?string
    {
        $tempDir = sys_get_temp_dir();

        if (!$tempDir || !$path) {
            return $tempDir ?: null;
        }

        return $tempDir . '/' . ltrim($path, '/');
    }
}

if (!function_exists('request_query_get')) {
    /**
     * function request_query_get
     *
     * @param ?string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    function request_query_get(?string $key, mixed $defaultValue = null): mixed
    {
        return $_GET[$key] ?? $defaultValue;
    }
}

if (!function_exists('request_post_get')) {
    /**
     * function request_post_get
     *
     * @param ?string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    function request_post_get(?string $key, mixed $defaultValue = null): mixed
    {
        return $_POST[$key] ?? $defaultValue;
    }
}

if (!function_exists('request_any_get')) {
    /**
     * function request_any_get
     *
     * @param ?string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    function request_any_get(?string $key, mixed $defaultValue = null): mixed
    {
        return $_REQUEST[$key] ?? $_GET[$key] ?? $_POST[$key] ?? $defaultValue;
    }
}

if (!function_exists('request_uri')) {
    /**
     * function request_uri
     *
     * @param string $defaultValue
     *
     * @return mixed
     */
    function request_uri(string $defaultValue = '/'): string
    {
        return $uri = $_SERVER['REQUEST_URI'] ?? $defaultValue;
    }
}

if (!function_exists('request_cookie_get')) {
    /**
     * function request_cookie_get
     *
     * @param ?string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    function request_cookie_get(?string $key, mixed $defaultValue = null): mixed
    {
        return $_COOKIE[$key] ?? $defaultValue;
    }
}

if (!function_exists('request_server_get')) {
    /**
     * function request_server_get
     *
     * @param ?string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    function request_server_get(?string $key, mixed $defaultValue = null): mixed
    {
        return $_SERVER[$key] ?? $defaultValue;
    }
}

if (!function_exists('request_path')) {
    /**
     * function request_path
     *
     * @param string $defaultValue
     *
     * @return mixed
     */
    function request_path(string $defaultValue = '/'): string
    {
        return $_SERVER['PATH_INFO'] ?? $defaultValue;
    }
}

if (!function_exists('request_header_get')) {
    /**
     * function request_header_get
     *
     * @param ?string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    function request_header_get(?string $key, mixed $defaultValue = null): mixed
    {
        if (!$key) {
            return $defaultValue;
        }

        $key = str_replace(['-', '_'], '_', $key);

        $key = strtoupper("HTTP_{$key}");
        $forwardedkey = strtoupper("HTTP_X_FORWARDED_{$key}");

        return $_SERVER[$key] ?? $_SERVER[$forwardedkey] ?? $defaultValue;
    }
}

if (!function_exists('request_expects_json')) {
    /**
     * function request_expects_json
     *
     * @return bool
     */
    function request_expects_json(): bool
    {
        $acceptHeader = request_header_get('accept', '');

        return str_contains($acceptHeader, 'application/json');
    }
}

if (!function_exists('app_abort')) {
    function app_abort(int $code, string $message = ''): void
    {
        if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && !headers_sent()) {
            $code = $code > 100 && $code <= 500 ? $code : 500;

            $message = $message ?: match ($code) {
                500 => 'Server error',
                404 => 'Not found',
                '' => '',
            };
            header("HTTP/1.1 {$code} {$message}");
        }

        exit((int) $code);
    }
}

if (!function_exists('response_as_json')) {
    /**
     * function response_as_json
     *
     * @param mixed $data
     * @param int $statusCode
     * @param array $headers
     *
     * @return void
     */
    function response_as_json(
        mixed $data,
        int $statusCode = 200,
        array $headers = [],
    ): void {
        foreach ($headers as $key => $value) {
            if (!is_string($key) || !is_string($value) || !trim($key) || !trim($value)) {
                continue;
            }

            header("{$key}: {$value}", true);
        }

        header('Content-Type: application/json', true);
        header('App-Creator: TiagoFranca.com', true);

        $statusCode = $statusCode > 100 && $statusCode <= 500 ? $statusCode : 500;
        http_response_code($statusCode);

        die(json_encode($data, 64));
    }
}

if (!function_exists('on_cli')) {
    /**
     * function on_cli
     *
     * @param
     * @return bool
     */
    function on_cli(): bool
    {
        return \PHP_SAPI === 'cli';
    }
}

if (!function_exists('request_input_bool')) {
    /**
     * function request_input_bool
     *
     * @param ?string $key
     * @param ?bool $defaultValue
     *
     * @return mixed
     */
    function request_input_bool(?string $key, ?bool $defaultValue = null): mixed
    {
        $value = request_any_get($key, null);

        if ($value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaultValue;
    }
}

if (!function_exists('to_bool')) {
    /**
     * function to_bool
     *
     * @param mixed $value
     * @param ?bool $defaultValue
     *
     * @return mixed
     */
    function to_bool(mixed $value, ?bool $defaultValue = null): mixed
    {
        return boolval(filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaultValue);
    }
}

if (!function_exists('is_to_cache')) {
    /**
     * function is_to_cache
     *
     * @return bool
     */
    function is_to_cache(): bool
    {
        $noCacheValue = request_header_get('no-cache', request_any_get('no-cache'));
        $noCacheValue = $noCacheValue === "" ? true : to_bool($noCacheValue);

        if ($noCacheValue) {
            return false;
        }

        $toCacheValue = request_header_get('to-cache', request_any_get('to-cache')) ?? true;
        $toCacheValue = $toCacheValue === "" ? true : to_bool($toCacheValue);

        return to_bool($toCacheValue);
    }
}

if (!function_exists('die_as_json')) {
    /**
     * function die_as_json
     *
     * @param mixed ...$data
     *
     * @return void
     */
    function die_as_json(
        mixed ...$data,
    ): void {
        response_as_json($data, 500);

        die;
    }
}

if (!function_exists('git_latest_log')) {
    /**
     * Get latest git HEAD log entry.
     *
     * @param string|null $key
     * @return array|string
     */
    function git_latest_log(?string $key = null): array|string
    {
        $headFile = base_path('.git/HEAD');
        $headLogFile = base_path('.git/logs/HEAD');

        if (!is_file($headLogFile) || !is_readable($headLogFile)) {
            return $key === null ? [] : '';
        }

        $file = new SplFileObject($headLogFile, 'r');
        $file->seek(PHP_INT_MAX);
        $file->seek($file->key() - 1);
        $file->seek($file->key());

        $line = trim($file->current());

        if ($line === '') {
            return $key === null ? [] : '';
        }

        $pattern = '/^
            (?<oldHash>[a-f0-9]{40})\s
            (?<newHash>[a-f0-9]{40})\s
            (?<author>.+?)\s
            <(?<email>[^>]+)>\s
            (?<timestamp>\d+)\s
            (?<timezone>[+-]\d{4})\t
            (?<action>[^:]+):\s
            (?<message>.+)
        $/x';

        if (!preg_match($pattern, $line, $matches)) {
            return $key === null ? [] : '';
        }

        $data = [
            'old_hash'  => $matches['oldHash'],
            'new_hash'  => $matches['newHash'],
            'author'    => $matches['author'],
            'email'     => $matches['email'],
            'timestamp' => (int) $matches['timestamp'],
            'timezone'  => $matches['timezone'],
            'action'    => $matches['action'],
            'message'   => $matches['message'],
        ];

        $headContent = is_file($headFile) && is_readable($headFile) ? fgets(fopen($headFile, 'r')) : '';
        $data['log_line'] = $line;
        $head = str_replace(['ref: refs/heads/', 'ref: refs/heads', 'ref: ', "\n", "\r"], '', trim($headContent ?: ''));
        $data['branch'] = $data['head'] = $head;

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? '';
    }
}

if (!function_exists('render_view')) {
    /**
     * Summary of render_view
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    function render_view(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();

        include $template;

        return ob_get_clean() ?: '';
    }
}

if (!function_exists('www_content_view')) {
    /**
     * Summary of www_content_view
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    function www_content_view(string $path, array $data = []): string
    {
        return render_view(base_path("www-content/{$path}.view.php"), $data);
    }
}

if (!function_exists('now')) {
    /**
     * function now
     *
     * @param DateTimeInterface|string|null $date
     *
     * @return \Carbon\Carbon|Carbon
     */
    function now(DateTimeInterface|string|null $date = null): \Carbon\Carbon|Carbon
    {
        $date ??= null;

        return new Carbon($date);
    }
}
