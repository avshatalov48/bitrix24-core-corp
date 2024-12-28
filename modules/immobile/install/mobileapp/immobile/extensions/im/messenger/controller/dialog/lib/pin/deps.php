<?php

return [
	'extensions' => [
		'loc',
		'type',
		'device/connection',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/parser',
		'im:messenger/lib/converter',
		'im:messenger/lib/params',
		'im:messenger/lib/feature',
		'im:messenger/lib/logger',
		'im:messenger/lib/ui/notification',
		'im:messenger/lib/helper',
		'im:messenger/lib/permission-manager',
	],
	'bundle' => [
		'./src/manager',
	],
];
