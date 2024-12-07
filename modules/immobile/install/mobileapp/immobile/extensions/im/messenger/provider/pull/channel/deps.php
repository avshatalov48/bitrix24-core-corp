<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/counters',
		'im:messenger/lib/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/emitter',
		'im:messenger/lib/helper',
		'im:messenger/provider/data',
		'im:messenger/provider/pull/base',
		'im:messenger/provider/pull/chat',
		'im:messenger/provider/pull/lib/recent/channel',
	],
	'bundle' => [
		'./src/message',
		'./src/dialog',
		'./src/file',
	],
];