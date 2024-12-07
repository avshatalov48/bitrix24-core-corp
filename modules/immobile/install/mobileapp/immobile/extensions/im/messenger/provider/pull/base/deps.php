<?php

return [
	'extensions' => [
		'type',
		'loc',
		'utils/object',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/cache/share-dialog',
		'im:messenger/lib/converter',
		'im:messenger/lib/element',
		'im:messenger/lib/helper',
		'im:messenger/lib/params',
		'im:messenger/lib/counters',
		'im:messenger/lib/notifier',
		'im:messenger/lib/emitter',
		'im:messenger/lib/uuid-manager',
		'im:messenger/lib/logger',
		'im:messenger/provider/service/sync',
		'im:messenger/provider/pull/lib/recent/base',
		'im:messenger/provider/pull/lib/file',
		'im:chat/utils',
		'im:chat/messengercommon',
		'im:messenger/provider/data',
	],
	'bundle' => [
		'./src/pull-handler',
		'./src/dialog',
		'./src/message',
	],
];
