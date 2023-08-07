<?php

return [
	'extensions' => [
		'uploader/client',
		'utils/uuid',
		'utils/file',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/params',
		'im:messenger/lib/user-manager',
		'im:messenger/lib/rest',
		'im:messenger/lib/uploader',
        'im/messenger/core',
	],
	'bundle' => [
		'./src/chat',
		'./src/message',
		'./src/sending',
		'./src/disk',
		'./src/classes/chat/load',
		'./src/classes/chat/read',
		'./src/classes/chat/mute',
		'./src/classes/message/load',
		'./src/classes/message/reaction',
		'./src/classes/sending/file',
		'./src/classes/sending/upload-task',
		'./src/classes/sending/upload-manager',
		'./src/classes/rest-data-extractor',
	],
];