<?php

return [
	'extensions' => [
		'layout/ui/kanban/toolbar',
		'statemanager/redux/connect',
		'tasks:statemanager/redux/slices/stage-settings',
		'tasks:statemanager/redux/slices/stage-counters',
		'tasks:statemanager/redux/slices/kanban-settings',
		'tasks:layout/stage-list-view',
	],
	'bundle' => [
		'./src/stage-dropdown',
	],
];
