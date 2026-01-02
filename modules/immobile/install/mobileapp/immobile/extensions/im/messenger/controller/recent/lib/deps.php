<?php

return [
	'extensions' => [
		'type',
		'alert',
		'utils/object',
		'utils/array',
		'device/connection',
		'user-profile',

		'im:messenger/loc',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/cache/share-dialog',
		'im:messenger/lib/converter/ui/recent',
		'im:messenger/lib/helper',
		'im:messenger/lib/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/lib/permission-manager',
		'im:messenger/lib/notifier',
		'im:messenger/lib/emitter',
		'im:messenger/lib/uuid-manager',
		'im:messenger/provider/services/recent',
		'im:messenger/lib/ui/notification',
		'im:messenger/lib/helper',
		'im:messenger/lib/rest-manager',
		'im:chat/utils',
		'im:chat/messengercommon',
	],
	'bundle' => [
		'./src/renderer',
		'./src/recent-base',
		'./src/item-action',
	],
];
