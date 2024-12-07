<?php

return [
	'extensions' => [
		'type',
		'layout/ui/list/base-filter',
		'tasks:statemanager/redux/slices/tasks/field-change-registry',
		'statemanager/redux/store',
		'statemanager/redux/slices/users',
		'tasks:statemanager/redux/slices/groups',
		'tasks:statemanager/redux/slices/tasks-stages',
		'tasks:statemanager/redux/slices/tasks/selector',
	],
];
