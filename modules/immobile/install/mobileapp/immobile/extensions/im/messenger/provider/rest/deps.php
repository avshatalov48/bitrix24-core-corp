<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/logger',
		'im:messenger/lib/page-navigation',
		'im:messenger/lib/helper',
		'im:messenger/lib/rest',
		'im:messenger/lib/feature',
		'im:messenger/lib/ui/notification',
		'im:messenger/const',
		'im:messenger/provider/rest',
		'im:messenger/provider/services/analytics',
	],
	'bundle' => [
		'./src/dialog',
		'./src/chat',
		'./src/message',
		'./src/recent',
		'./src/promotion',
		'./src/user',
		'./src/openlines',
		'./src/copilot',
	],
];
