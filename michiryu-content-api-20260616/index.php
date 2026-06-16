<?php
/**
 * MichiRyu Content API prototype.
 *
 * Deploy outside the public GPL plugin repository.
 */

declare(strict_types=1);

$config_path = __DIR__ . '/config.php';
if (!is_readable($config_path)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Missing content API config.'));
    exit;
}

$config = require $config_path;
$content_root = realpath((string)($config['content_root'] ?? ''));

if (false === $content_root || !is_dir($content_root) || !is_readable($content_root)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Invalid content root.'));
    exit;
}

$token_hash = (string)($config['basic_token_hash'] ?? '');
if ('' !== $token_hash && !michiryu_content_api_authorized($token_hash)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Unauthorized.'));
    exit;
}

$route = trim((string)($_GET['route'] ?? ''), '/');
if ('' === $route) {
    $route = trim((string)($_SERVER['PATH_INFO'] ?? ''), '/');
}

if ('manifest' === $route || '' === $route) {
    michiryu_content_api_manifest($config);
}

if ('file' === $route) {
    michiryu_content_api_file($content_root);
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('error' => 'Not found.'));
exit;

function michiryu_content_api_authorized(string $token_hash): bool
{
    $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
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

    if (str_starts_with($token_hash, '$2y$') || str_starts_with($token_hash, '$argon')) {
        return password_verify($token, $token_hash);
    }

    return hash_equals($token_hash, hash('sha256', $token));
}

function michiryu_content_api_manifest(array $config): void
{
    $base_url = rtrim((string)($config['base_url'] ?? ''), '/');
    $file_url = '' === $base_url ? '' : $base_url . '/file?path=';

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array(
            'library' => (string)($config['library'] ?? 'michiryu-basic'),
            'version' => (string)($config['version'] ?? gmdate('Y.m.d')),
            'license' => (string)($config['license'] ?? 'MichiRyu Content License'),
            'featured_content' => 'featured-content.json',
            'images' => 'images.json',
            'featured_content_url' => $file_url . rawurlencode('featured-content.json'),
            'images_url' => $file_url . rawurlencode('images.json'),
            'file_base_url' => $file_url,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function michiryu_content_api_file(string $content_root): void
{
    $relative_path = michiryu_content_api_sanitize_path((string)($_GET['path'] ?? ''));
    if ('' === $relative_path) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'Invalid path.'));
        exit;
    }

    $path = realpath($content_root . DIRECTORY_SEPARATOR . $relative_path);
    if (false === $path || !is_file($path) || !is_readable($path)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'File not found.'));
        exit;
    }

    $root = rtrim($content_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (0 !== strpos($path, $root)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'Forbidden.'));
        exit;
    }

    $mime = michiryu_content_api_mime_type($path);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
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
        if ('' === $segment || '..' === $segment) {
            return '';
        }
    }

    return $path;
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
