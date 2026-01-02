<?php

return [
	'extensions' => [
		'type',
		'in-app-url',
		'communication/phone-menu',
		'im:messenger/loc',
		'im:messenger/const',
		'im:messenger/lib/params',
		'im:messenger/lib/logger',
		'im:messenger/controller/dialog/lib/helper/text',
	],
	'bundle' => [
		'./src/manager',
		'./src/handler/action',
		'./src/handler/bot-command',
	],
];
