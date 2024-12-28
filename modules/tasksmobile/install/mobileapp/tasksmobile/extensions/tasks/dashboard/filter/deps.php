<?php

return [
	'extensions' => [
		'entity-ready',
		'type',
		'layout/ui/list/base-filter',
		'tasks:statemanager/redux/slices/tasks/field-change-registry',
		'statemanager/redux/store',
		'statemanager/redux/slices/users',
		'storage-cache',
		'tasks:statemanager/redux/slices/groups',
		'tasks:statemanager/redux/slices/tasks-stages',
		'tasks:statemanager/redux/slices/tasks/selector',
	],
];
