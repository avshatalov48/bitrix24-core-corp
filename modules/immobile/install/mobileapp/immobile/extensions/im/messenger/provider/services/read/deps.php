<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'im:messenger/const',
		'im:messenger/lib/feature',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest',
		'im:messenger/lib/utils',
		'im:messenger/lib/uuid-manager',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/counters/counter-manager/storage/writer',
	],
	'bundle' => [
		'./src/read-message-queue',
		'./src/service',
		'./src/store-writer',
	],
];