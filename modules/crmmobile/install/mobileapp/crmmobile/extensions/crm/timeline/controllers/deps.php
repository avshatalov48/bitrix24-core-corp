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
		'analytics',
		'in-app-url',
		'user-profile',
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
