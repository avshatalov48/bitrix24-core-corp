<?php

return [
	'extensions' => [
		'apptheme',
		'tokens',
		'type',
		'loc',
		'asset-manager',
		'assets/common',
		'assets/icons',
		'layout/ui/menu',
		'feature',

		'tasks:statemanager/redux/slices/tasks/field-change-registry',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/groups',
		'tasks:statemanager/redux/slices/tasks-stages',
		'tasks:statemanager/redux/slices/tasks/selector',
		'statemanager/redux/store',
		'statemanager/redux/slices/users',

		'utils/object',
		'layout/ui/list/base-filter',
		'layout/ui/list/base-more-menu',
		'layout/ui/list/base-sorting',

		'rest/run-action-executor',
	],
	'bundle' => [
		'./src/navigation-title',
		'./src/pull',
		'./src/more-menu',
		'./src/filter',
		'./src/sorting',
	],
];
