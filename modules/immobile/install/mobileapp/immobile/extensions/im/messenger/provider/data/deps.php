<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/di/service-locator',
		'im:messenger/model/dialogues',
	],
	'bundle' => [
		'./src/base',
		'./src/result',
		'./src/chat',
		'./src/chat/getter',
		'./src/chat/deleter',
		'./src/chat/entity-updater',
		'./src/recent',
		'./src/recent/deleter',
		'./src/message',
		'./src/message/deleter',
	],
];
