<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/service',
	],
];
