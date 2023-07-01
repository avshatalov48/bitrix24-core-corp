<?php

return [
	'extensions' => [
		'communication/connection',
		'communication/email-menu',
		'communication/phone-menu',
		'in-app-url',
		'notify-manager',
		'type',
		'utils/string',
		'utils/url',
		'utils/object',
		'crm:mail/opener',
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
