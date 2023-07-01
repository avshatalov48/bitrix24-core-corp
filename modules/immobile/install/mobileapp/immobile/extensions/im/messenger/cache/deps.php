<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/params',
		'im:chat/utils',
		'utils/function',
		'utils/object',
	],
	'bundle' => [
		'./src/shared-storage/base',
		'./src/shared-storage/recent',
		'./src/shared-storage/messages',
		'./src/shared-storage/users',
		'./src/shared-storage/files',
		'./src/native/share-dialog',
	],
];