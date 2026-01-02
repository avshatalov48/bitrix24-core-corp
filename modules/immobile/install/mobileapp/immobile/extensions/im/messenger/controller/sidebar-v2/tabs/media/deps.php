<?php

return [
	'extensions' => [
		'tokens',
		'utils/file',
		'utils/array',
		'utils/validation',
		'utils/date/formats',
		'utils/date/moment',
		'im:messenger/const',
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/controller/sidebar-v2/tabs/base',
		'im:messenger/controller/sidebar-v2/services/file-service',
		'im:messenger/controller/sidebar-v2/const',
		'im:messenger/controller/sidebar-v2/tabs/files',
		'im:messenger/controller/sidebar-v2/tabs/base/src/content',
		'im:messenger/controller/dialog/lib/media-gallery',
		'im:messenger/assets',
	],
	'bundle' => [
		'./src/content',
		'./src/items/model',
	],
];
