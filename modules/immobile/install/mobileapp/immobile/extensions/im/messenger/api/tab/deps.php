<?php

return [
	'extensions' => [
		'type',
		'entity-ready',
		'im:messenger/const',
		'im:messenger/api/api-version',
		'im:messenger/lib/utils',
	],
	'bundle' => [
		'./src/v1',
		'./src/v2',
	],
];
