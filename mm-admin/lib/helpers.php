<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * Shared helpers for the Majestic Marquees raw-PHP admin panel.
 * Pure PHP - no framework. Included once by public/index.php.
 */

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Permission keys granted to the signed-in admin (from the login response). */
function current_permissions(): array
{
    return is_array($_SESSION['permissions'] ?? null) ? $_SESSION['permissions'] : [];
}

/** True if the signed-in admin holds the given permission key. */
function can(string $key): bool
{
    return in_array($key, current_permissions(), true);
}

/** True if the signed-in admin holds at least one of the given permission keys. */
function can_any(array $keys): bool
{
    foreach ($keys as $key) {
        if (can($key)) {
            return true;
        }
    }
    return false;
}

/** Display name of the signed-in admin's role. */
function current_role_name(): string
{
    return (string) ($_SESSION['admin_role_name'] ?? $_SESSION['admin_role'] ?? '');
}

/**
 * First admin page the signed-in user is allowed to open, in sidebar order.
 * Used as a safe redirect target when a user reaches a page they cannot access
 * (so we never bounce them back to a page they also lack permission for).
 * Falls back to /change-password, which every authenticated user can open.
 */
function landing_path(): string
{
    if (can('dashboard.view'))                      return '/dashboard';
    if (can_any(['leads.view', 'customers.view']))  return '/customer-info-details';
    if (can('inventory.view'))                      return '/inventory';
    if (can('leads.view'))                          return '/lead-management';
    if (can('posts.view'))                          return '/posts';
    if (can('images.view'))                         return '/images';
    if (can('survey.manage'))                       return '/survey-questions';
    if (can('ai.manage'))                           return '/ai-settings';
    if (can('xero.manage'))                         return '/xero';
    if (can_any(['users.manage', 'roles.manage']))  return '/user-management';
    return '/change-password';
}

