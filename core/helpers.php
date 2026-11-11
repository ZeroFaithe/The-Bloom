<?php
declare(strict_types=1);

namespace Core;

/** Escape output in views: <?= e($name) ?> */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Build a full URL from BASE_URL: url('/guides') */
function url(string $path = '/'): string
{
    return rtrim((string) Config::get('app.base_url', ''), '/') . '/' . ltrim($path, '/');
}

/**
 * Link to a file inside public/: asset('css/app.css') -> .../css/app.css?v=1731250000
 * The ?v= number is the file's last-changed time, so browsers download it again after
 * every edit instead of showing an old cached copy.
 */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $version;
}

/** Hidden input every POST form must include. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Session::token()) . '">';
}

/** Read (and clear) a flash message. */
function flash(string $key): mixed
{
    return Session::pull($key);
}

/** What the user typed last time, for refilling a form after an error. */
function old(string $key, mixed $default = ''): mixed
{
    return Session::old($key, $default);
}

/** Error message for one form field ('' when there is none). */
function field_error(string $field): string
{
    return Session::errors()[$field] ?? '';
}

/** "Hario V60 Basics!" -> "hario-v60-basics" */
function slugify(string $text): string
{
    return strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
}
