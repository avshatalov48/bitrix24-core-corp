<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/logger',
		'im:messenger/db/repository',
		'im:messenger/db/table',
	],
	'bundle' => [
		'./src/db-updater',
		'./src/version',
		'./src/update',
		'./src/version/1',
		'./src/version/2',
		'./src/version/3',
		'./src/version/4',
		'./src/version/5',
		'./src/version/6',
		'./src/version/7',
		'./src/version/8',
		'./src/version/9',
		'./src/version/10',
		'./src/version/11',
		'./src/version/12',
		'./src/version/13',
	],
];
