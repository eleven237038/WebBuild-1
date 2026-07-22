<?php
// Dev-only cache purge. Restricted to private/local IPs so it can't be abused
// if the box is briefly exposed. Hit http://localhost:8081/opcache-reset.php
// after editing PHP/Twig to pick up changes immediately (bypasses revalidate_freq),
// or to flush APCu (settings/image caches) after data changes.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$is_private = ($ip === '127.0.0.1' || $ip === '::1'
    || preg_match('/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)/', $ip));
if (!$is_private) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain');
$messages = [];
if (function_exists('opcache_reset') && opcache_reset()) {
    $messages[] = 'opcache reset';
}
if (function_exists('apcu_clear_cache') && apcu_clear_cache()) {
    $messages[] = 'apcu cleared';
}
// Bump the settings version stamp so APCu settings cache re-reads from DB.
@touch(__DIR__ . '/system/storage/cache/settings.ver');
$messages[] = 'settings cache invalidated';
echo implode('; ', $messages) . "\n";
