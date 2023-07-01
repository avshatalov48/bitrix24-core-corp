<?php

return [
	'extensions' => [
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/params',
		'im:messenger/lib/user-manager',
		'im:messenger/lib/rest',
	],
	'bundle' => [
		'./src/chat',
		'./src/message',
		'./src/classes/chat/load',
		'./src/classes/chat/read',
		'./src/classes/message/load',
		'./src/classes/message/reaction',
		'./src/classes/rest-data-extractor',
	],
];