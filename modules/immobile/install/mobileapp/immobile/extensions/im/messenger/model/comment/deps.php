<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'im:messenger/lib/logger',

		'im:messenger/const',
		'im:messenger/lib/helper',
	],
	'bundle' => [
		'./src/model',
		'./src/validator',
		'./src/default-element',
	],
];