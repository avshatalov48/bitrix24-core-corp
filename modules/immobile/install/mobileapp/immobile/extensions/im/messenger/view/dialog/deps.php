<?php

return [
	'extensions' => [
		'apptheme',
		'type',
		'loc',
		'im:messenger/lib/di/service-locator',
		'im:messenger/view/base',
		'im:messenger/const',
		'im:messenger/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/visibility-manager',
	],
	'bundle' => [
		'./src/dialog',
	],
];