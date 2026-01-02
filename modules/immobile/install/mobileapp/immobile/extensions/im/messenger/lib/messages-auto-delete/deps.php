<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/lib/permission-manager',
		'im:messenger/lib/ui/context-menu/messages-auto-delete',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/ui/notification',
		'im:messenger/provider/services/chat/service',
	],
	'bundle' => [
		'./src/notification',
	],
];
