<?php

return [
	'extensions' => [
		'im:messenger/controller/sidebar-v2/controller/base',
		'im:messenger/controller/sidebar-v2/tabs/participants',
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/const',
		'im:messenger/controller/sidebar-v2/user-actions/chat',
		'im:messenger/controller/sidebar-v2/user-actions/participants',
		'im:messenger/controller/sidebar-v2/ui/primary-button/factory',
		'assets/icons',
		'require-lazy',
	],
	'bundle' => [
		'./src/view',
		'./src/permission-manager',
	],
];
