<?php

return [
	'extensions' => [
		'apptheme',
		'asset-manager',
		'assets/common',
		'layout/ui/menu',
		'loc',
		'tasks:filter/task',
		'tasks:statemanager/redux/slices/tasks/field-change-registry',
		'tasks:statemanager/redux/types',
		'type',
		'utils/object',
	],
	'bundle' => [
		'./src/filter',
		'./src/more-menu',
		'./src/navigation-title',
		'./src/pull',
		'./src/sorting',
	],
];
