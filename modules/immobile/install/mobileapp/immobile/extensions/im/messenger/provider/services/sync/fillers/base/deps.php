<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/user-manager',
		'im:messenger/provider/services/lib/message-context-creator',
		'im:messenger/lib/logger',
		'im:messenger/lib/emitter',
		'im:messenger/lib/helper',
		'im:messenger/provider/data',
		'im:messenger/lib/counters/counter-manager/messenger/sender',
		'im:messenger/lib/feature',
	],
	'bundle' => [],
];
