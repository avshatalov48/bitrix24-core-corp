<?php

return [
	'extensions' => [
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/tabs/base',
		'im:messenger/controller/sidebar-v2/services/file-service',
		'im:messenger/controller/sidebar-v2/ui/layout/list-item',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/ui/avatar',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/helper/dialog',
		'im:messenger/controller/sidebar-v2/const',
		'im:messenger/controller/sidebar-v2/utils/file',

		'toast',
		'tokens',
		'notify-manager',
		'utils/url',
		'assets/icons',
		'layout/ui/menu',
		'ui-system/typography/text',
		'ui-system/blocks/icon',
		'utils/date',
		'utils/date/formats',

		'native/filesystem',
	],
	'bundle' => [
		'./src/content',
		'./src/items/file',
		'./src/action-menu/file',
	],
];
