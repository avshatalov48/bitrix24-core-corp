<?php

return [
	'extensions' => [
		'type',
		'utils/date',
		'layout/ui/friendly-date',
		'im:lib/theme',
		'im:messenger/loc',
		'im:messenger/assets/common',
		'im:messenger/cache',
		'im:messenger/const',
		'im:messenger/controller/user-profile',
		'im:messenger/lib/element/chat-title',
		'im:messenger/lib/element/chat-avatar',
		'im:messenger/lib/logger',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/ui/base/loader',
		'im:messenger/lib/ui/base/item',
		'im:messenger/lib/helper/dialog',
		'im:messenger/lib/params',
		'im:messenger/lib/rest',

	],
	'bundle' => [
		'./src/view',
		'./src\view',
	],
];
