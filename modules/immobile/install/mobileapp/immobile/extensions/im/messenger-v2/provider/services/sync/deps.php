<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/provider/data',
		'im:messenger/provider/services/sync/fillers/base',
		'im:messenger/provider/services/sync/fillers/database',
	],
	'bundle' => [
		'./src/fillers/store',
		'./src/loader',
		'./src/filler',
		'./src/service',
	],
];
