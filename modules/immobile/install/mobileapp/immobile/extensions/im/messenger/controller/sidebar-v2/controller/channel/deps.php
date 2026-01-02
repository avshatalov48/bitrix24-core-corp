<?php

return [
    'extensions' => [
		'assets/icons',
		'require-lazy',
		'im:messenger/controller/sidebar-v2/controller/base',
		'im:messenger/controller/sidebar-v2/const',
		'im:messenger/controller/sidebar-v2/tabs/links',
		'im:messenger/controller/sidebar-v2/tabs/files',
		'im:messenger/controller/sidebar-v2/tabs/audio',
		'im:messenger/controller/sidebar-v2/tabs/media',
		'im:messenger/controller/sidebar-v2/tabs/participants',
		'im:messenger/controller/sidebar-v2/user-actions/participants',
		'im:messenger/controller/sidebar-v2/user-actions/user',
		'im:messenger/controller/sidebar-v2/user-actions/chat',
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/ui/primary-button/factory',
	],
	'bundle' => [
		'./src/view',
	],
];
