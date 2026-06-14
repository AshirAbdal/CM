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
