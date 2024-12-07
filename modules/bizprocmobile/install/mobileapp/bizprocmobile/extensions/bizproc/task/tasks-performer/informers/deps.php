<?php

return [
	'bundle' => [
		'./similar-tasks-informer',
		'./task-list-informer',
	],
	'extensions' => [
		'apptheme',
		'apptheme/extended',
		'event-emitter',
		'loc',
		'type',
		'toast',

		'utils/object',

		'layout/pure-component',
		'layout/ui/safe-image',

		'bizproc:task/buttons',
		'bizproc:task/task-constants',
		'bizproc:workflow/list/simple-list',
	],
];
