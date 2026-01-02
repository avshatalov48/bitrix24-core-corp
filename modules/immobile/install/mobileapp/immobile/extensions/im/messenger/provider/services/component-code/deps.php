<?php

return [
	'extensions' => [
		'notify-manager',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/feature',
		'im:messenger/lib/ui/notification',
		'im:messenger/provider/services/chat',
	],
	'bundle' => [
		'./src/service',
	],
];
