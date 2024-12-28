<?php

return [
	'extensions' => [
		'apptheme',
		'loc',
		'utils/object',
		'device/connection',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/element',
		'im:messenger/lib/integration/immobile/calls',
		'im:messenger/lib/helper',
		'im:messenger/lib/permission-manager',
		'im:messenger/lib/utils',
		'im:messenger/lib/params',
		'im:messenger/assets/common',
		'im:messenger/controller/user-add',
	],
	'bundle' => [
		'./src/buttons',
		'./src/title',
		'./src/button-configuration',
	],
];
