<?php

return [
	'extensions' => [
		'utils/object',
		'utils/validation',
		'layout/ui/menu',
		'layout/ui/loaders/spinner',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/ui/base/loader',
		'im:messenger/lib/di/service-locator',
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/ui/empty-screen',
		'im:messenger/controller/sidebar-v2/factory',
		'im:messenger/controller/sidebar-v2/controller/base',
	],
	'bundle' => [
		'./src/content',
		'./src/list-content',
		'./src/dummy-content',
		'./src/list-item-model',
	],
];
