<?php

return [
	'extensions' => [
		'layout/ui/menu',
		'layout/ui/context-menu',
		'utils/logger/warn-logger',
		'utils/object',
		'type',

		'tasks:layout/action-menu/engines/base',
		'tasks:layout/action-menu/engines/bottom-menu',
		'tasks:layout/action-menu/engines/top-menu',
		'tasks:layout/action-menu/actions',

		'statemanager/redux/store',
		'tasks:statemanager/redux/slices/tasks',
	],
	'bundle' => [
		'./engines/base',
		'./engines/top-menu',
		'./engines/bottom-menu',
	],
];
