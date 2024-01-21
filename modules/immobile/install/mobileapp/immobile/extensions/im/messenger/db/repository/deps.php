<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'im:messenger/lib/settings',
		'im:chat/utils',
		'im:messenger/db/table',
		'im:messenger/lib/logger',
		'im:messenger/lib/utils',
		'im:messenger/lib/helper',
		'im:messenger/const',
	],
	'bundle' => [
		'./src/option',
		'./src/recent',
		'./src/dialog',
		'./src/file',
		'./src/user',
		'./src/message',
		'./src/temp-message',
		'./src/reaction',
		'./src/queue',
		'./src/smile',
	],
];