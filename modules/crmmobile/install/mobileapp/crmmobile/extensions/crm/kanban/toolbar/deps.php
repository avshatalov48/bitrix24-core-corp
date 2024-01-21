<?php

return [
	'extensions' => [
		'loc',
		'layout/ui/kanban/toolbar',
		'layout/pure-component',

		'statemanager/redux/connect',
		'crm:statemanager/redux/slices/stage-settings',
		'crm:statemanager/redux/slices/stage-counters',
		'crm:statemanager/redux/slices/kanban-settings',
	],
	'bundle' => [
		'./entity-toolbar',
		'./stage-dropdown',
		'./stage-summary',
	],
];
