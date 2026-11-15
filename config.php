<?php
require_once __DIR__ . '/lib/env.php';
load_env(__DIR__ . '/.env');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/lib/supabase.php';

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function redirect_with(string $location, string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
    header('Location: ' . $location);
    exit;
}

function valid_uuid(string $value): bool
{
    return (bool) preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        $value
    );
}

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}