<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/utils',
		'im:messenger/provider/services/analytics',
	],
	'bundle' => [
		'./src/controller',
		'./src/result',
		'./src/api-handler',
		'./src/tab-switcher',
		'./src/helper',
	],
];