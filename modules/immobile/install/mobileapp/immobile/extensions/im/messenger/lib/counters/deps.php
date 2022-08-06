<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest-manager',
		'im:messenger/const',
		'im:messenger/lib/event',
		'im:messenger/lib/params',
	],
	'bundle' => [
		'./src/counters',
		'./src/counter',
	],
];