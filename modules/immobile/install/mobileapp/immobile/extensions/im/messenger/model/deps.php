<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/const',
		'im:messenger/lib/helper',
		'im:messenger/cache',
	],
	'bundle' => [
		'./src/application',
		'./src/recent',
		'./src/messages',
		'./src/users',
		'./src/dialogues',
		'./src/files',
	],
];