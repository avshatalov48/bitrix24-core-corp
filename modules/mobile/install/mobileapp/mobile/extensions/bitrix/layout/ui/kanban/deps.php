<?php

return [
	'extensions' => [
		'require-lazy',
		'layout/ui/kanban/counter',
		'layout/ui/kanban/toolbar',
		'layout/ui/pure-component',
		'layout/ui/loading-screen',
		'layout/ui/stateful-list',
		'loc',
		'type',
		'utils',
		'utils/error-notifier',
		'utils/function',
		'utils/object',

		'crm:storage/category',
	],
	'bundle' => [
		'./refs-container',
	],
];
