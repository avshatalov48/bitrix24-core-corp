<?php

return [
	'extension' => [
		'type',
		'im:messenger/lib/params',
		'im:chat/utils',
	],
	'bundle' => [
		'./src/shared-storage/base',
		'./src/shared-storage/recent',
		'./src/shared-storage/messages',
		'./src/shared-storage/users',
	],
];