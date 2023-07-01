<?php

return [
	'extensions' => [
		'type',
		'utils/uuid',
		'utils/object',
		'im:chat/utils',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/cache',
		'im:messenger/lib/logger',
		'im:messenger/lib/params',
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