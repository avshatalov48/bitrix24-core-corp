<?php

return [
	'extensions' => [
		'feature',
		'layout/ui/context-menu',
		'layout/ui/kanban',
		'layout/ui/kanban/toolbar',
		'layout/ui/loading-screen',
		'layout/ui/stateful-list',
		'layout/ui/simple-list/skeleton',
		'rest',
		'statemanager/redux/connect',
		'statemanager/redux/store',
		'tasks:layout/dashboard/toolbar',
		'tasks:enum',
		'tasks:layout/simple-list/items',
		'tasks:layout/simple-list/skeleton',
		'tasks:statemanager/redux/slices/stage-counters',
		'tasks:statemanager/redux/slices/stage-settings',
		'tasks:statemanager/redux/slices/kanban-settings',
		'tasks:statemanager/redux/slices/tasks',
		'tasks:statemanager/redux/slices/tasks-stages',
	],
	'bundle' => [
		'./src/base-view',
		'./src/kanban-adapter',
		'./src/list-adapter',
	],
];
