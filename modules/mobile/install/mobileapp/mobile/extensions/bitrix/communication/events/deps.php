<?php

return [
	'extensions' => [
		'in-app-url',
		'notify-manager',
		'utils/string',
		'utils/url',
		'utils/object',
		'im:messenger/api/dialog-opener',
		'communication/phone-menu',
		'communication/connection',
	],
	'bundle' => [
		'./base',
		'./email',
		'./web',
		'./phone',
		'./im',
	],
];