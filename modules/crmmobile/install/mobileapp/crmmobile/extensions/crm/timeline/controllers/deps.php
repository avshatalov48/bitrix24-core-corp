<?php

return [
	'extensions' => [
		'require-lazy',
		'alert',
		'crm:type',
		'type',
		'loc',
		'notify-manager',
		'communication/events',
		'tasks:task',
		'utils/copy',
		'utils/string',
		'haptics',
		'user/profile',
		'analytics',
		'in-app-url',
	],
	'bundle' => [
		'./base',
		'./openline',
		'./activity',
		'./call',
		'./comment',
		'./email',
		'./note',
		'./helpdesk',
		'./todo',
		'./document',
		'./payment',
		'./order-check',
		'./calendar-sharing',
		'./task',
		'./clipboard',
		'./visit',
		'./bizproc',
	]
];
