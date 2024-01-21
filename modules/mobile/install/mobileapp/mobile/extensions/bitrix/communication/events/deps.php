<?php

return [
	'extensions' => [
		'require-lazy',
		'communication/connection',
		'communication/phone-menu',
		'in-app-url',
		'notify-manager',
		'type',
		'utils/string',
		'utils/url',
		'utils/object',
		'im:messenger/api/dialog-opener',
	],
	'bundle' => [
		'./base',
		'./email',
		'./im',
		'./phone',
		'./web',
	],
];
