<?php

return [
	'extensions' => [
		'type',
		'loc',
		'im:messenger/const',
		'im:messenger/controller/base',
		'im:messenger/service',
		'im:messenger/lib/logger',
		'im:messenger/lib/converter',
		'im:messenger/lib/page-navigation',
		'im:messenger/lib/helper',
		'im:messenger/service',
		'im:messenger/lib/params',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/renderer',
		'im:messenger/lib/event',
		'im:messenger/lib/counters',
		'im:messenger/cache/share-dialog',
		'im:chat/timer',
		'im:chat/utils',
		'user/profile',
	],
	'bundle' => [
		'./src/item-action',
		'./src/renderer',
		'./src/recent',
	],
];