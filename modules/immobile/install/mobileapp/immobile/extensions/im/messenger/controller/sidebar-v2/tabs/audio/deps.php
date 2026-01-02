<?php

return [
	'extensions' => [
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/tabs/base',
		'im:messenger/controller/sidebar-v2/services/file-service',
		'im:messenger/controller/sidebar-v2/const',
		'im:messenger/controller/sidebar-v2/ui/layout/list-item',
		'im:messenger/controller/sidebar-v2/utils/file',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/ui/avatar',
		'im:messenger/lib/helper/dialog',
		'im:messenger/lib/logger',

		'tokens',
		'assets/icons',
		'utils/date',
		'utils/date/formats',
		'layout/ui/menu',
		'ui-system/typography/text',
		'ui-system/blocks/icon',
		'layout/ui/loaders/spinner',
		'layout/pure-component',

		'native/media',
	],
	'bundle' => [
		'./src/content',
		'./src/item',
		'./src/action-menu',
		'./src/const',
		'./src/ui/playback-time-indicator',
		'./src/ui/title-date',
		'./src/ui/icon-loader',
		'./src/ui/audio-icon',
		'./src/ui/owner',
	],
];
