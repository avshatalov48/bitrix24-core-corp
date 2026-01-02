<?php

return [
	'extensions' => [
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/element/chat-title',
		'im:messenger/lib/element/chat-avatar',
		'im:messenger/lib/feature',
		'im:messenger/lib/helper/dialog',
		'im:messenger/const',
		'im:messenger/lib/date-formatter',
		'im:messenger/controller/sidebar-v2/search/src/analytics',
		'im:messenger/const/rest',
		'im:messenger/lib/logger',
		'im:messenger/const/analytics',
		'im:messenger/lib/feature',
		'im:messenger/loc',
		'tokens',
		'type',
		'utils/array',
		'utils/function',
		'native/memorystore',
		'bbcode/parser',
		'analytics',
	],
	'bundle' => [
		'./src/utils',
		'./src/analytics',
		'./src/memory-storage',
	],
];
