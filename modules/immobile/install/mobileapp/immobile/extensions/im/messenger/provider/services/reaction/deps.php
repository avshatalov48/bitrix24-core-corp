<?php

return [
	'extensions' => [
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest',
	],
	'bundle' => [
		'./src/service',
	],
];
