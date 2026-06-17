<?php
/**
 * MichiRyu Content API.
 *
 * Deploy outside the public GPL plugin repository.
 */

declare(strict_types=1);

ini_set('display_errors', '0');

const MICHIRYU_CONTENT_API_VERSION = '0.3.0';

set_error_handler(
    static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }
);

set_exception_handler(
    static function (Throwable $error): void {
        michiryu_content_api_json(
            array(
                'error' => 'server_error',
                'message' => 'The content API could not complete the request.',
            ),
            500
        );
    }
);

$config_path = __DIR__ . '/config.php';
if (!is_readable($config_path)) {
    michiryu_content_api_error('missing_config', 'Missing content API config.', 500);
}

$config = require $config_path;
if (!is_array($config)) {
    michiryu_content_api_error('invalid_config', 'Invalid content API config.', 500);
}

$config = michiryu_content_api_normalize_config($config);
$route = michiryu_content_api_route();

if ('health' === $route) {
    michiryu_content_api_health($config);
}

if (!in_array($route, array('', 'manifest', 'file'), true)) {
    michiryu_content_api_error('not_found', 'Not found.', 404);
}

$library_key = michiryu_content_api_library_key($config);
$library = michiryu_content_api_library($config, $library_key);
$content_root = michiryu_content_api_content_root($library);

if (false === $content_root) {
    michiryu_content_api_error('invalid_content_root', 'The requested content library is not readable.', 500);
}

$token_hash = (string)($library['token_hash'] ?? '');
if ('' === $token_hash && ('manifest' === $route || 'file' === $route || '' === $route)) {
    michiryu_content_api_error('token_not_configured', 'Content API token is not configured for this library.', 500);
}

if ('' !== $token_hash && !michiryu_content_api_authorized($token_hash)) {
    michiryu_content_api_error('unauthorized', 'Unauthorized.', 401);
}

if ('manifest' === $route || '' === $route) {
    $manifest_key = michiryu_content_api_manifest_key($library);
    $manifest = michiryu_content_api_manifest_config($library, $manifest_key);
    michiryu_content_api_manifest($config, $library_key, $library, $manifest_key, $manifest);
}

if ('file' === $route) {
    michiryu_content_api_file($config, $library, $content_root);
}

michiryu_content_api_error('not_found', 'Not found.', 404);

function michiryu_content_api_normalize_config(array $config): array
{
    if (isset($config['libraries']) && is_array($config['libraries'])) {
        return $config;
    }

    $manifest = array(
        'featured_content' => (string)($config['featured_content'] ?? 'featured-content.json'),
        'images' => (string)($config['images'] ?? 'images.json'),
    );

    $config['default_library'] = (string)($config['default_library'] ?? 'basic');
    $config['libraries'] = array(
        $config['default_library'] => array(
            'library' => (string)($config['library'] ?? 'michiryu-basic'),
            'version' => (string)($config['version'] ?? gmdate('Y.m.d')),
            'license' => (string)($config['license'] ?? 'MichiRyu Content License'),
            'content_root' => (string)($config['content_root'] ?? ''),
            'token_hash' => (string)($config['basic_token_hash'] ?? $config['token_hash'] ?? ''),
            'manifests' => array(
                'default' => $manifest,
            ),
        ),
    );

    return $config;
}

function michiryu_content_api_route(): string
{
    $route = trim((string)($_GET['route'] ?? ''), '/');
    if ('' !== $route) {
        return $route;
    }

    return trim((string)($_SERVER['PATH_INFO'] ?? ''), '/');
}

function michiryu_content_api_library_key(array $config): string
{
    $default = (string)($config['default_library'] ?? 'basic');
    $library = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($_GET['library'] ?? $default)));

    return '' !== $library ? $library : $default;
}

function michiryu_content_api_library(array $config, string $library_key): array
{
    $libraries = $config['libraries'] ?? array();
    if (!is_array($libraries) || !isset($libraries[$library_key]) || !is_array($libraries[$library_key])) {
        michiryu_content_api_error('unknown_library', 'Unknown content library.', 404);
    }

    return $libraries[$library_key];
}

function michiryu_content_api_manifest_key(array $library): string
{
    $manifests = $library['manifests'] ?? array();
    $default = '';
    if (is_array($manifests)) {
        if (isset($manifests['default'])) {
            $default = 'default';
        } else {
            foreach ($manifests as $key => $manifest) {
                $default = (string)$key;
                break;
            }
        }
    }

    $manifest = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($_GET['manifest'] ?? $default)));

    return '' !== $manifest ? $manifest : $default;
}

function michiryu_content_api_manifest_config(array $library, string $manifest_key): array
{
    $manifests = $library['manifests'] ?? array();
    if (!is_array($manifests) || !isset($manifests[$manifest_key]) || !is_array($manifests[$manifest_key])) {
        michiryu_content_api_error('unknown_manifest', 'Unknown content manifest.', 404);
    }

    $manifest = $manifests[$manifest_key];
    if (empty($manifest['featured_content']) || empty($manifest['images'])) {
        michiryu_content_api_error('invalid_manifest_config', 'Content manifest config must include featured content and images paths.', 500);
    }

    return $manifest;
}

function michiryu_content_api_content_root(array $library): string|false
{
    $content_root = realpath((string)($library['content_root'] ?? ''));
    if (false === $content_root || !is_dir($content_root) || !is_readable($content_root)) {
        return false;
    }

    return $content_root;
}

function michiryu_content_api_authorized(string $token_hash): bool
{
    $token = (string)($_SERVER['HTTP_X_MICHIRYU_CONTENT_TOKEN'] ?? $_SERVER['REDIRECT_HTTP_X_MICHIRYU_CONTENT_TOKEN'] ?? '');
    if ('' === $token && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $token = (string)($headers['X-MichiRyu-Content-Token'] ?? $headers['x-michiryu-content-token'] ?? '');
    }

    $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ('' === $header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = (string)($headers['Authorization'] ?? $headers['authorization'] ?? '');
    }

    if ('' === $token && preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        $token = $matches[1];
    }

    $token = trim($token);
    if ('' === $token) {
        return false;
    }

    if (0 === strpos($token_hash, '$2y$') || 0 === strpos($token_hash, '$2a$') || 0 === strpos($token_hash, '$argon')) {
        return password_verify($token, $token_hash);
    }

    return hash_equals($token_hash, hash('sha256', $token));
}

function michiryu_content_api_health(array $config): void
{
    $libraries = array();
    foreach ((array)($config['libraries'] ?? array()) as $key => $library) {
        if (!is_array($library)) {
            continue;
        }

        $content_root = michiryu_content_api_content_root($library);
        $manifest_keys = is_array($library['manifests'] ?? null) ? array_keys($library['manifests']) : array();
        $libraries[$key] = array(
            'library' => (string)($library['library'] ?? $key),
            'version' => (string)($library['version'] ?? gmdate('Y.m.d')),
            'content_root_readable' => false !== $content_root,
            'manifest_count' => count($manifest_keys),
            'token_required' => '' !== (string)($library['token_hash'] ?? ''),
        );
    }

    michiryu_content_api_json(
        array(
            'status' => !empty($libraries) ? 'ok' : 'error',
            'api_version' => MICHIRYU_CONTENT_API_VERSION,
            'default_library' => (string)($config['default_library'] ?? 'basic'),
            'libraries' => $libraries,
        ),
        !empty($libraries) ? 200 : 500
    );
}

function michiryu_content_api_manifest(array $config, string $library_key, array $library, string $manifest_key, array $manifest): void
{
    $base_url = rtrim((string)($config['base_url'] ?? ''), '/');
    if ('' === $base_url) {
        michiryu_content_api_error('missing_base_url', 'Content API base URL is not configured.', 500);
    }

    $file_url = $base_url . '/index.php?route=file&library=' . rawurlencode($library_key) . '&path=';
    $featured_content = michiryu_content_api_sanitize_path((string)$manifest['featured_content']);
    $images = michiryu_content_api_sanitize_path((string)$manifest['images']);
    if ('' === $featured_content || '' === $images) {
        michiryu_content_api_error('invalid_manifest_paths', 'Content manifest paths are invalid.', 500);
    }

    michiryu_content_api_json(
        array(
            'library' => (string)($library['library'] ?? $library_key),
            'version' => (string)($library['version'] ?? gmdate('Y.m.d')),
            'license' => (string)($library['license'] ?? 'MichiRyu Content License'),
            'manifest' => $manifest_key,
            'featured_content' => $featured_content,
            'images' => $images,
            'featured_content_url' => $file_url . rawurlencode($featured_content),
            'images_url' => $file_url . rawurlencode($images),
            'file_base_url' => $file_url,
        )
    );
}

function michiryu_content_api_file(array $config, array $library, string $content_root): void
{
    $relative_path = michiryu_content_api_sanitize_path((string)($_GET['path'] ?? ''));
    if ('' === $relative_path) {
        michiryu_content_api_error('invalid_path', 'Invalid path.', 400);
    }

    $extension = strtolower(pathinfo($relative_path, PATHINFO_EXTENSION));
    $allowed_extensions = michiryu_content_api_allowed_extensions($config, $library);
    if (!in_array($extension, $allowed_extensions, true)) {
        michiryu_content_api_error('file_type_not_allowed', 'File type not allowed.', 403);
    }

    $path = realpath($content_root . DIRECTORY_SEPARATOR . $relative_path);
    if (false === $path || !is_file($path) || !is_readable($path)) {
        michiryu_content_api_error('file_not_found', 'File not found.', 404);
    }

    $root = rtrim($content_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (0 !== strpos($path, $root)) {
        michiryu_content_api_error('forbidden', 'Forbidden.', 403);
    }

    $file_size = filesize($path);
    $max_file_bytes = (int)($library['max_file_bytes'] ?? $config['max_file_bytes'] ?? 26214400);
    if (false === $file_size || $file_size > $max_file_bytes) {
        michiryu_content_api_error('file_too_large', 'File is too large.', 413);
    }

    michiryu_content_api_security_headers();
    header('Cache-Control: private, max-age=300');
    header('Content-Type: ' . michiryu_content_api_mime_type($path));
    header('Content-Length: ' . (string)$file_size);
    readfile($path);
    exit;
}

function michiryu_content_api_sanitize_path(string $path): string
{
    $path = ltrim(str_replace('\\', '/', trim($path)), '/');
    if ('' === $path || preg_match('#^https?://#i', $path)) {
        return '';
    }

    foreach (explode('/', $path) as $segment) {
        if ('' === $segment || '..' === $segment || '.' === $segment) {
            return '';
        }
    }

    return $path;
}

function michiryu_content_api_allowed_extensions(array $config, array $library): array
{
    $extensions = $library['allowed_extensions'] ?? $config['allowed_extensions'] ?? array('json', 'jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf', 'md', 'txt');
    if (!is_array($extensions)) {
        return array('json', 'jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf', 'md', 'txt');
    }

    return array_values(
        array_filter(
            array_map(
                static function ($extension): string {
                    return strtolower(trim((string)$extension));
                },
                $extensions
            )
        )
    );
}

function michiryu_content_api_mime_type(string $path): string
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = array(
        'json' => 'application/json; charset=utf-8',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'svg' => 'image/svg+xml; charset=utf-8',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'md' => 'text/markdown; charset=utf-8',
        'txt' => 'text/plain; charset=utf-8',
    );

    return $types[$extension] ?? 'application/octet-stream';
}

function michiryu_content_api_error(string $code, string $message, int $status): void
{
    michiryu_content_api_json(
        array(
            'error' => $code,
            'message' => $message,
        ),
        $status
    );
}

function michiryu_content_api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    michiryu_content_api_security_headers();
    header('Cache-Control: no-store');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function michiryu_content_api_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
}
