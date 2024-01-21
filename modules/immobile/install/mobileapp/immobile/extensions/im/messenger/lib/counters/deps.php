<?php

return [
	'extensions' => [
		'type',
		'im:messenger/core',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest-manager',
		'im:messenger/const',
		'im:messenger/lib/emitter',
		'im:messenger/lib/params',
		'im:messenger/lib/helper',
	],
	'bundle' => [
		'./src/counters',
		'./src/counter',
	],
];