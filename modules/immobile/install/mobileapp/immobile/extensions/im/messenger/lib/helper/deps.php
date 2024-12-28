<?php

return [
	'extensions' => [
		'type',
		'assets/icons',
		'device/connection',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/smile-manager',
		'im:messenger/lib/params',
		'im:messenger/lib/utils',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/permission-manager',
	],
	'bundle' => [
		'./src/dialog',
		'./src/user',
		'./src/message',
		'./src/date',
		'./src/worker',
		'./src/soft-loader',
		'./src/file',
		'./src/url',
		'./src/entity-selector',
	],
];
