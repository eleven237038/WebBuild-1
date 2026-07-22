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
		@touch(DIR_CACHE . 'content.ver');
	}
}
