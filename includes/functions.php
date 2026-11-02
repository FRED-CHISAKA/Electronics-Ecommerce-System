<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float|int|string $amount): string
{
    return 'KSh ' . number_format((float) $amount, 2);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid(?string $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken);
}
