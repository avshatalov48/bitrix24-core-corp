<?php

return [
	'bundle' => [
		'./bottom-panel',
		'./item',
		'./skeleton',
	],
	'extensions' => [
		'apptheme',
		'assets/common',
		'loc',
		'type',
		'require-lazy',
		//'rest',
		'toast',
		'utils/object',
		'utils/skeleton',

		'statemanager/redux/slices/users',
		'statemanager/redux/store',

		'layout/pure-component',
		'layout/ui/bottom-toolbar',
		'layout/ui/empty-screen',
		'layout/ui/search-bar',
		'layout/ui/stateful-list',
		'layout/ui/simple-list/items',
		'layout/ui/simple-list/skeleton',

		'bizproc:workflow/list/simple-list',
		'bizproc:workflow/list/view-mode',

		'bizproc:task/tasks-performer',
	],
];
