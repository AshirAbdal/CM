<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * Shared helpers for the Majestic Marquees raw-PHP admin panel.
 * Pure PHP — no framework. Included once by public/index.php.
 */

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Render a hidden CSRF token field. */
function csrf_field(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

/** Verify CSRF token from POST; call at the top of any POST handler. */
function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';
    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('Invalid request.');
    }
    unset($_SESSION['csrf_token']);
}
