<?php

return [
	'extensions' => [
		'im:messenger/lib/di/service-locator',
		'im:messenger/provider/pull/lib/recent/base',
	],
	'bundle' => [
		'./src/update-manager',
		'./src/message-manager',
	],
];