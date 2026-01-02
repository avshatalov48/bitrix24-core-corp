<?php

return [
	'extensions' => [
		'im:messenger/controller/sidebar-v2/controller/base',
		'im:messenger/controller/sidebar-v2/tabs/media',
		'im:messenger/controller/sidebar-v2/tabs/participants',
		'im:messenger/controller/sidebar-v2/tabs/audio',
		'im:messenger/controller/sidebar-v2/tabs/links',
		'im:messenger/controller/sidebar-v2/user-actions/participants',
		'im:messenger/controller/sidebar-v2/user-actions/chat',
		'im:messenger/controller/sidebar-v2/user-actions/user',
		'im:messenger/controller/sidebar-v2/ui/primary-button/factory',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/permission-manager',
		'im:messenger/lib/ui/notification',
		'im:messenger/const',
		'require-lazy',
		'haptics',
		'assets/icons',
	],
	'bundle' => [
		'./src/view',
		'./src/permission-manager',
	],
];
