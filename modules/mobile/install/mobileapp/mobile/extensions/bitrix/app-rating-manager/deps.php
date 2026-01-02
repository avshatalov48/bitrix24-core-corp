<?php

return [
	'components' => [
		'apprating.box',
	],
	'extensions' => [
		'tourist',
		'layout/ui/app-rating',
		'bizproc:task/task-constants',
		'feature',
		'type',
		'rest/run-action-executor',
	],
	'bundle' => [
		'./src/manager',
		'./src/storage-provider',
		'./src/tourist-storage-provider',
		'./src/test-storage-provider',
		'./src/api',
	],
];