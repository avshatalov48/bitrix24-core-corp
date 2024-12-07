<?php

return [
	'extensions' => [
		'im:chat/selector/adapter/dialog-list',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/element',
		'im:messenger/controller/dialog-selector',
		'type',
		'im:messenger/lib/utils',
	],
	'bundle' => [
		'./src/base',
		'./src/user',
		'./src/copilot',
		'./src/adapter/user',
	],
];