<?php

return [
	'extensions' => [
		'apptheme',
		'type',
		'loc',
		'module',
		'ui-system/blocks/icon',
		'im:messenger/lib/di/service-locator',
		'im:messenger/view/base',
		'im:messenger/const',
		'im:messenger/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/visibility-manager',
		'im:messenger/lib/feature',
		'im:messenger/provider/service/analytics',
	],
	'bundle' => [
		'./src/dialog',
	],
];
