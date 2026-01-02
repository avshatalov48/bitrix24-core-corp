<?php

return [
	'extensions' => [
		'type',
		'utils/function',
		'utils/object',
		'utils/array',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/lib/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/element/chat-title',
		'im:messenger/lib/element/chat-avatar',
	],
	'bundle' => [
		'./src/shared-storage/base',
		'./src/shared-storage/recent',
		'./src/native/share-dialog',
		'./src/simple-wrapper/map-cache',
	],
];
