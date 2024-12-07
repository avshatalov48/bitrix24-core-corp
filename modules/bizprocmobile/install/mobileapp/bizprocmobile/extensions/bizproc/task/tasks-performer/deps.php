<?php

return [
	'bundle' => [
		'./notifier',
	],
	'extensions' => [
		'apptheme',
		'event-emitter',
		'loc',
		'type',
		'toast',

		'layout/pure-component',

		'bizproc:task/tasks-performer/rules',
		'bizproc:task/tasks-performer/informers',
	],
];
