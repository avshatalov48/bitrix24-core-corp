<?php

return [
	'extensions' => [
		'analytics',
		'tokens',
		'asset-manager',
		'layout/ui/menu',
		'feature',
		'loc',
		'tasks:statemanager/redux/slices/tasks/field-change-registry',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/groups',
		'tasks:statemanager/redux/slices/tasks-stages',
		'tasks:statemanager/redux/slices/tasks/selector',
		'tasks:dashboard/settings-action-executor',
		'type',
		'statemanager/redux/store',
		'statemanager/redux/slices/users',

		'utils/object',
		'layout/ui/list/base-filter',
		'layout/ui/list/base-more-menu',
		'layout/ui/list/base-sorting',
	],
	'bundle' => [
		'./src/filter',
		'./src/more-menu',
		'./src/navigation-title',
		'./src/pull',
		'./src/sorting',
	],
];
