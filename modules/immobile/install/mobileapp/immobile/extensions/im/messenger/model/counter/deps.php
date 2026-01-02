<?php

return [
	'extensions' => [
		'type',
		'utils/array',
		'im:messenger/const',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/default-element',
		'./src/model',
		'./src/validator',
	],
];