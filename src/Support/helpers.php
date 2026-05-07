<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

function app_env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : (string) $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function app_base_path(): string
{
    $appUrlPath = parse_url(app_env('APP_URL', ''), PHP_URL_PATH);

    if (!$appUrlPath) {
        return '';
    }

    return rtrim($appUrlPath, '/');
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = rtrim($uri ?: '/', '/') ?: '/';

    $basePath = app_base_path();

    if ($basePath !== '' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
        $uri = rtrim($uri ?: '/', '/') ?: '/';
    }

    return $uri;
}

function current_query(): array
{
    $result = [];
    parse_str((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY), $result);
    return $result;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path, array $query = []): never
{
    $url = app_url($path);

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function remember_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function active_menu(array $paths): string
{
    $path = current_path();
    foreach ($paths as $item) {
        if ($path === $item || str_starts_with($path . '/', rtrim($item, '/') . '/')) {
            return 'active';
        }
    }

    return '';
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $contentView = __DIR__ . '/../Views/' . $template . '.php';
    require __DIR__ . '/../Views/layout.php';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function auth_user(): ?array
{
    return Auth::user();
}

function has_role(string ...$roles): bool
{
    return Auth::hasRole($roles);
}

function request_str(string $key, string $default = ''): string
{
    return trim((string) ($_REQUEST[$key] ?? $default));
}

function request_int(string $key, ?int $default = null): ?int
{
    if (!isset($_REQUEST[$key]) || $_REQUEST[$key] === '') {
        return $default;
    }

    return (int) $_REQUEST[$key];
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}

function selected(mixed $left, mixed $right): string
{
    return (string) $left === (string) $right ? 'selected' : '';
}

function bool_icon(mixed $value): string
{
    return $value ? 'Sí' : 'No';
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function app_url(string $path = ''): string
{
    return rtrim(app_env('APP_URL', 'http://localhost:8000'), '/') . '/' . ltrim($path, '/');
}

function current_ip(): ?string
{
    return $_SERVER['REMOTE_ADDR'] ?? null;
}
