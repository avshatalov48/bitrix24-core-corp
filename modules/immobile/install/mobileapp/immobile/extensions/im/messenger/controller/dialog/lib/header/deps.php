<?php

return [
	'extensions' => [
		'apptheme',
		'loc',
		'utils/object',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/lib/element',
		'im:messenger/lib/logger',
		'im:messenger/lib/integration/immobile/calls',
		'im:messenger/lib/helper',
		'im:messenger/lib/permission-manager',
		'im:messenger/lib/utils',
	],
	'bundle' => [
		'./src/buttons',
		'./src/title',
	],
];