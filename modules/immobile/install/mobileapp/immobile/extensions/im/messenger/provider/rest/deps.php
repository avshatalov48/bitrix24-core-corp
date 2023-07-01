<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/logger',
		'im:messenger/lib/page-navigation',
		'im:messenger/lib/helper',
		'im:messenger/const',
		'im:messenger/provider/rest',
	],
	'bundle' => [
		'./src/dialog',
		'./src/chat',
		'./src/message',
		'./src/recent',
		'./src/promotion',
		'./src/user',
		'./src/openlines',
	],
];