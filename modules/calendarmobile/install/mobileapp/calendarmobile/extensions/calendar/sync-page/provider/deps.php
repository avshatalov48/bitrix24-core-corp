<?php

return [
	'extensions' => [
		'apptheme',
		'loc',
		'layout/ui/friendly-date/time-ago-format',
		'utils/date/moment',
		'calendar:sync-page/settings',
		'calendar:sync-page/wizard',
		'calendar:sync-page/icloud-dialog',
		'calendar:model/sync/connection',
	],
	'bundle' => [
		'./factory',
		'./sync-provider',
	],
];