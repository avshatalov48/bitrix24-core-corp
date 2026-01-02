<?php

return [
	'extensions' => [
		'im:messenger/provider/pull/chat',
		'type',
		'utils/object',
		'im:messenger/loc',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/lib/params',
		'im:messenger/lib/notifier',
		'im:messenger/lib/emitter',
		'im:messenger/lib/uuid-manager',
		'im:messenger/cache/share-dialog',
		'im:messenger/provider/service/sync',
		'im:chat/utils',
		'im:chat/messengercommon',
		'im:chat/dataconverter',
		'im:messenger/provider/pull/lib/new-message-manager/collab',
		'im:messenger/provider/pull/lib/file',
	],
	'bundle' => [
		'./src/dialog',
		'./src/message',
		'./src/file',
		'./src/user',
		'./src/collab-info',
	],
];
