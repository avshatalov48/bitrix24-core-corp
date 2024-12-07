<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/view/dialog',
		'im:messenger/lib/helper',
		'im:messenger/lib/emitter',
		'im:messenger/lib/feature',
		'im:messenger/lib/ui/notification',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/plan-limit',
	],
	'bundle' => [
		'./src/context-manager',
	],
];
