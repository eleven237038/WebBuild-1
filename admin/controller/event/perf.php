<?php
/**
 * Performance cache invalidation events.
 *
 * Bumps the content-version stamp so the APCu full-page / module caches
 * (home page, etc.) are regenerated after content-affecting writes.
 * Registered against admin/model/.../after triggers in oc_event.
 */
class ControllerEventPerf extends Controller {
	public function bump() {
		// Monotonic counter (not filemtime): avoids 1-second mtime-resolution
		// collisions so rapid successive edits always invalidate every worker.
		$f = DIR_CACHE . 'content.ver';
		@file_put_contents($f, ((int)@file_get_contents($f)) + 1);
	}
}
