<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/controller/search/experimental',
		'im:messenger/lib/date-formatter',
		'im:messenger/lib/element',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest',
		'im:messenger/lib/params',
	],
	'bundle' => [
		'./src/config',
		'./src/manager',
		'./src/provider',
	],
];