<?php

return [
	'extensions' => [
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/provider/pull/lib/new-message-manager/base',
	],
	'bundle' => [
		'./src/message-manager',
	],
];
