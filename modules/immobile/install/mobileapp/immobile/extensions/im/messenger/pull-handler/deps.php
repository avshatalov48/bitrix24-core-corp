<?php

return [
	'extensions' => [
		'type',
		'loc',
		'im/messenger/const',
		'im:messenger/lib/converter',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/lib/params',
		'im:messenger/lib/counters',
		'im:messenger/lib/notifier',
		'im:messenger/lib/event',
		'im:chat/utils',
		'im:chat/messengercommon',
		'im:chat/dataconverter',
	],
	'bundle' => [
		'./src/base',
		'./src/dialog',
		'./src/message',
		'./src/user',
		'./src/desktop',
		'./src/notification',
		'./src/online',
	],
];