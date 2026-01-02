<?php

return [
	'extensions' => [
		'device/connection',
		'assets/icons',
		'im:lib/theme',
		'im:messenger/loc',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/controller/user-add',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/integration/immobile/calls',
		'im:messenger/lib/helper',
		'im:messenger/lib/ui/notification',
		'im:messenger/lib/permission-manager',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/controller/selector/member',
	],
	'bundle' => [
		'./src/buttons',
		'./src/button-configuration',
	],
];
