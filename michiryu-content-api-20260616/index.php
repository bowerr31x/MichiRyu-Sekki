<?php
/**
 * MichiRyu Content API.
 *
 * Deploy outside the public GPL plugin repository.
 */

declare(strict_types=1);

const MICHIRYU_CONTENT_API_VERSION = '0.2.0';

$config_path = __DIR__ . '/config.php';
if (!is_readable($config_path)) {
    michiryu_content_api_json(array('error' => 'Missing content API config.'), 500);
}

$config = require $config_path;
if (!is_array($config)) {
    michiryu_content_api_json(array('error' => 'Invalid content API config.'), 500);
}

$route = michiryu_content_api_route();
$content_root = realpath((string)($config['content_root'] ?? ''));

if ('health' === $route) {
    michiryu_content_api_health($config, $content_root);
}

if (false === $content_root || !is_dir($content_root) || !is_readable($content_root)) {
    michiryu_content_api_json(array('error' => 'Invalid content root.'), 500);
}

$token_hash = (string)($config['basic_token_hash'] ?? '');
if ('' !== $token_hash && !michiryu_content_api_authorized($token_hash)) {
    michiryu_content_api_json(array('error' => 'Unauthorized.'), 401);
}

if ('manifest' === $route || '' === $route) {
    michiryu_content_api_manifest($config);
}

if ('file' === $route) {
    michiryu_content_api_file($config, $content_root);
}

michiryu_content_api_json(array('error' => 'Not found.'), 404);

function michiryu_content_api_route(): string
{
    $route = trim((string)($_GET['route'] ?? ''), '/');
    if ('' !== $route) {
        return $route;
    }

    return trim((string)($_SERVER['PATH_INFO'] ?? ''), '/');
}

function michiryu_content_api_authorized(string $token_hash): bool
{
    $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ('' === $header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = (string)($headers['Authorization'] ?? $headers['authorization'] ?? '');
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return false;
    }

    $token = trim($matches[1]);
    if ('' === $token) {
        return false;
    }

    if (0 === strpos($token_hash, '$2y$') || 0 === strpos($token_hash, '$2a$') || 0 === strpos($token_hash, '$argon')) {
        return password_verify($token, $token_hash);
    }

    return hash_equals($token_hash, hash('sha256', $token));
}

function michiryu_content_api_health(array $config, string|false $content_root): void
{
    $root_readable = false !== $content_root && is_dir($content_root) && is_readable($content_root);

    michiryu_content_api_json(
        array(
            'status' => $root_readable ? 'ok' : 'error',
            'api_version' => MICHIRYU_CONTENT_API_VERSION,
            'library' => (string)($config['library'] ?? 'michiryu-basic'),
            'version' => (string)($config['version'] ?? gmdate('Y.m.d')),
            'content_root_readable' => $root_readable,
            'featured_content_exists' => $root_readable && is_readable($content_root . DIRECTORY_SEPARATOR . 'featured-content.json'),
            'images_index_exists' => $root_readable && is_readable($content_root . DIRECTORY_SEPARATOR . 'images.json'),
            'token_required' => '' !== (string)($config['basic_token_hash'] ?? ''),
        ),
        $root_readable ? 200 : 500
    );
}

function michiryu_content_api_manifest(array $config): void
{
    $base_url = rtrim((string)($config['base_url'] ?? ''), '/');
    $file_url = '' === $base_url ? '' : $base_url . '/index.php?route=file&path=';

    michiryu_content_api_json(
        array(
            'library' => (string)($config['library'] ?? 'michiryu-basic'),
            'version' => (string)($config['version'] ?? gmdate('Y.m.d')),
            'license' => (string)($config['license'] ?? 'MichiRyu Content License'),
            'featured_content' => 'featured-content.json',
            'images' => 'images.json',
            'featured_content_url' => $file_url . rawurlencode('featured-content.json'),
            'images_url' => $file_url . rawurlencode('images.json'),
            'file_base_url' => $file_url,
        )
    );
}

function michiryu_content_api_file(array $config, string $content_root): void
{
    $relative_path = michiryu_content_api_sanitize_path((string)($_GET['path'] ?? ''));
    if ('' === $relative_path) {
        michiryu_content_api_json(array('error' => 'Invalid path.'), 400);
    }

    $extension = strtolower(pathinfo($relative_path, PATHINFO_EXTENSION));
    $allowed_extensions = michiryu_content_api_allowed_extensions($config);
    if (!in_array($extension, $allowed_extensions, true)) {
        michiryu_content_api_json(array('error' => 'File type not allowed.'), 403);
    }

    $path = realpath($content_root . DIRECTORY_SEPARATOR . $relative_path);
    if (false === $path || !is_file($path) || !is_readable($path)) {
        michiryu_content_api_json(array('error' => 'File not found.'), 404);
    }

    $root = rtrim($content_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (0 !== strpos($path, $root)) {
        michiryu_content_api_json(array('error' => 'Forbidden.'), 403);
    }

    $file_size = filesize($path);
    $max_file_bytes = (int)($config['max_file_bytes'] ?? 26214400);
    if (false === $file_size || $file_size > $max_file_bytes) {
        michiryu_content_api_json(array('error' => 'File is too large.'), 413);
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

function michiryu_content_api_allowed_extensions(array $config): array
{
    $extensions = $config['allowed_extensions'] ?? array('json', 'jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf', 'md', 'txt');
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
