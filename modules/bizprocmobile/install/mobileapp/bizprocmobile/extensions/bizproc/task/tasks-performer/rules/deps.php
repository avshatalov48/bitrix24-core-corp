<?php

return [
	'bundle' => [
		'./task-opener',
		'./inline-delegate-rule',
		'./inline-task-rule',
		'./rule',
		'./rules-chain',
		'./one-by-one-rule',
		'./task-hash-rule',
		'./sequential-task-rule',
	],
	'extensions' => [
		'apptheme',
		'alert',
		'loc',
		'type',
		'toast',
		'utils/object',
		'utils/function',

		'layout/pure-component',

		'bizproc:task/task-constants',
		'bizproc:task/buttons',
		'bizproc:task/details',
		'bizproc:task/tasks-performer/informers',
	],
];
