<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/feature',
		'im:messenger/lib/utils',
	],
	'bundle' => [
		'./src/model',
		'./src/validator',
		'./src/default-element',
		'./src/transcript/model',
		'./src/transcript/validator',
		'./src/transcript/default-element',
	],
];
