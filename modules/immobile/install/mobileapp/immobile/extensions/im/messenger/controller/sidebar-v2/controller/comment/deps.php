<?php

return [
	'extensions' => [
		'im:messenger/controller/sidebar-v2/controller/base',
		'im:messenger/controller/sidebar-v2/tabs/files',
		'im:messenger/controller/sidebar-v2/tabs/links',
		'im:messenger/controller/sidebar-v2/tabs/audio',
		'im:messenger/controller/sidebar-v2/tabs/media',
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/ui/sidebar-avatar',
		'im:messenger/controller/sidebar-v2/ui/primary-button/factory',
		'im:messenger/controller/sidebar-v2/const',
		'im:messenger/lib/ui/notification',
		'ui-system/typography',
		'assets/icons',
		'device/connection',
		'tokens',
	],
	'bundle' => [
		'./src/view',
	],
];
