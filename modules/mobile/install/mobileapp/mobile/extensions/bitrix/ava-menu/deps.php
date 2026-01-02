<?php

return [
	'extensions' => [
		'analytics',
		'calendar:entry',
		'qrauth/utils',
		'require-lazy',
		'rest/run-action-executor',
		'sign:entry',
		'stafftrack:entry',
		'tokens',
		'utils/function',
		'whats-new/ui-manager',
		'user-profile',
	],
	'bundle' => [
		'./src/calendar',
		'./src/check-in',
		'./src/sign',
	],
];
