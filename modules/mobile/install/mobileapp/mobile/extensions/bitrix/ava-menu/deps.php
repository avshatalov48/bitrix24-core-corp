<?php

return [
	'extensions' => [
		'loc',
		'tokens',
		'analytics',
		'require-lazy',
		'qrauth/utils',

		'stafftrack:entry',
		'sign:entry',
		'calendar:entry',
	],
	'bundle' => [
		'./src/check-in',
		'./src/sign',
		'./src/calendar',
	],
];
