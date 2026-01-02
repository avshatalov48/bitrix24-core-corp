<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/model',
		'./src/validator',
		'./src/default-element',
	],
];