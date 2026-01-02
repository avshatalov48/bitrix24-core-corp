<?php

return [
	'extensions' => [
		'utils/file',
		'tokens',
		'layout/ui/context-menu',
		'ui-system/typography',
		'ui-system/blocks/icon',
		'im:messenger/loc',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/provider/services/disk',
		'im:messenger/lib/ui/notification',
		'im:messenger/lib/di/service-locator',
	],
	'bundle' => [
		'./src/file-download',
		'./src/server-download',
	],
];
