<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/helper',
		'im:messenger/lib/feature',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
	],
	'bundle' => [
		'./src/table',
		'./src/option',
		'./src/recent',
		'./src/counter',
		'./src/dialog',
		'./src/user',
		'./src/file',
		'./src/message',
		'./src/temp-message',
		'./src/reaction',
		'./src/queue',
		'./src/smile',
		'./src/link-pin',
		'./src/link-pin-message',
		'./src/internal/dialog',
		'./src/copilot',
		'./src/sidebar/file',
	],
];
