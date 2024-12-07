<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/di/service-locator',
	],
	'bundle' => [
		'./src/base',
		'./src/result',
		'./src/chat',
		'./src/chat/getter',
		'./src/chat/deleter',
		'./src/recent',
		'./src/recent/deleter',
	],
];