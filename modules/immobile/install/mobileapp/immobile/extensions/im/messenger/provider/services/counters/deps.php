<?php

return [
	'extensions' => [
		'type',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/di/service-locator',
	],
	'bundle' => [
		'./src/service',
	],
];
