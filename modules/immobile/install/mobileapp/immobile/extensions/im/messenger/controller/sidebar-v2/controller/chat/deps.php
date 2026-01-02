<?php

return [
	'extensions' => [
		'tokens',
		'ui-system/blocks/icon',
		'assets/icons',
		'require-lazy',
		'ui-system/typography',
		'im:messenger/lib/utils',
		'im:messenger/controller/sidebar-v2/controller/base',
		'im:messenger/controller/sidebar-v2/tabs/common-chats',
		'im:messenger/controller/sidebar-v2/tabs/audio',
		'im:messenger/controller/sidebar-v2/tabs/files',
		'im:messenger/controller/sidebar-v2/tabs/links',
		'im:messenger/controller/sidebar-v2/tabs/participants',
		'im:messenger/controller/sidebar-v2/tabs/media',
		'im:messenger/controller/sidebar-v2/const',
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/tabs/common-chats',
		'im:messenger/controller/user-profile',
		'im:messenger/controller/sidebar-v2/ui/layout/work-position',
		'im:messenger/controller/sidebar-v2/services/user-department-service',
		'im:messenger/controller/sidebar-v2/user-actions/participants',
		'im:messenger/controller/sidebar-v2/ui/primary-button/factory',
		'im:messenger/controller/sidebar-v2/user-actions/chat',
	],
	'bundle' => [
		'./src/view',
		'./src/permission-manager',
	],
];
