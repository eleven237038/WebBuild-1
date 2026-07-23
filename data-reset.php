<?php
// Fast data-only cache purge. Use after DB-only changes (product attribute edits)
// to invalidate the APCu full-page cache (page_fw) + settings/image caches WITHOUT
// clearing the compiled Twig cache (which forces a slow recompile). For code/Twig
// edits use opcache-reset.php instead.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$is_private = ($ip === '127.0.0.1' || $ip === '::1'
    || preg_match('/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)/', $ip));
if (!$is_private) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain');
$m = [];
if (function_exists('apcu_clear_cache') && apcu_clear_cache()) {
    $m[] = 'apcu cleared';
}
// Bump the version stamps (monotonic counters in the files). Counter (not
// filemtime) avoids 1-second mtime-resolution collisions so rapid successive
// resets always change the page_fw / oc_settings cache keys -> every prefork
// worker misses and re-reads fresh DB data.
$cv = __DIR__ . '/system/storage/cache/content.ver';
$sv = __DIR__ . '/system/storage/cache/settings.ver';
@file_put_contents($cv, ((int)@file_get_contents($cv)) + 1);
@file_put_contents($sv, ((int)@file_get_contents($sv)) + 1);
$m[] = 'content+settings ver bumped';
echo implode('; ', $m) . "\n";
