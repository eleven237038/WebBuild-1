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
// Bump the settings version stamp (monotonic counter in the file) so APCu
// settings/page caches re-read from DB. Counter (not filemtime) avoids 1-second
// mtime-resolution collisions on rapid successive resets.
$sv = __DIR__ . '/system/storage/cache/settings.ver';
@file_put_contents($sv, ((int)@file_get_contents($sv)) + 1);
$messages[] = 'settings cache invalidated';
// Clear compiled Twig templates. The Twig env runs with auto_reload=false for
// speed, so it never stats source files - edited .twig won't show until the
// 2-char-hex compiled-cache subdirs under storage/cache/ are removed.
$cache_dir = __DIR__ . '/system/storage/cache/';
$cleared = 0;
foreach (glob($cache_dir . '*', GLOB_ONLYDIR) as $dir) {
    $name = basename($dir);
    if (preg_match('/^[0-9a-f]{2}$/', $name)) {
        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($rii as $file) {
            $file->isDir() ? @rmdir($file) : @unlink($file);
        }
        @rmdir($dir);
        $cleared++;
    }
}
if ($cleared) {
    $messages[] = $cleared . ' twig cache dirs cleared';
}
echo implode('; ', $messages) . "\n";
