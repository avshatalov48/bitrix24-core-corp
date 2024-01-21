<?php

return [
	'extensions' => [
		'type',
		'date',
		'utils/uuid',
		'utils/object',
		'im:chat/utils',
		'im:messenger/const',
		'im:messenger/lib/date-formatter',
		'im:messenger/lib/helper',
		'im:messenger/cache',
		'im:messenger/lib/logger',
		'im:messenger/lib/params',
		'im:messenger/lib/utils',
	],
	'bundle' => [
		'./src/application',
		'./src/recent',
		'./src/recent/search',
		'./src/messages',
		'./src/users',
		'./src/dialogues',
		'./src/files',
		'./src/sidebar',
		'./src/draft',
		'./src/queue',
		'./src/messages/reactions',
	],
];