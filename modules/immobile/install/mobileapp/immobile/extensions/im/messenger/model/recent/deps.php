<?php

return [
	'extensions' => [
		'type',
		'utils/uuid',
		'utils/object',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/date-formatter',
	],
	'bundle' => [
		'./src/model',
		'./src/validator',
		'./src/default-element',
		'./src/search/model',
		'./src/search/validator',
	],
];
