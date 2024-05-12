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
		'im:chat/utils',
		'im:chat/messengercommon',
	],
	'bundle' => [
		'./src/pull-handler-base',
		'./src/dialog-base',
		'./src/message-base',
	],
];