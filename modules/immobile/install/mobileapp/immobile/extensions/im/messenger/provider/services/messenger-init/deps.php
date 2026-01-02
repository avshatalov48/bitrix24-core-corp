<?php

return [
	'extensions' => [
		'native/memorystore',
		'entity-ready',
		'im:messenger/const',
		'im:messenger/lib/feature',
		'im:messenger/lib/rest',
		'im:messenger/lib/params',
	],
	'bundle' => [
		'./src/service',
	],
];
