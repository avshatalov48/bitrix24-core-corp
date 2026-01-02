<?php

return [
	'extensions' => [
		'utils/object',
		'im:messenger/lib/logger',
		'im:messenger/controller/dialog/lib/header/title/title',
		'im:messenger/controller/dialog/lib/header/buttons/buttons',
		'im:messenger/controller/dialog/lib/message-menu',
	],
	'bundle' => [
		'./src/configuration',
		'./src/configurator',
	],
];
