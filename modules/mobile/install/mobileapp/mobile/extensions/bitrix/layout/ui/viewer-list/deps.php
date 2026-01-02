<?php

return [
	'extensions' => [
		'loc',
		'tokens',
		'layout/ui/simple-list/items',
		'layout/ui/simple-list/items/base',
		'layout/ui/stateful-list',
		'layout/ui/user-list',
		'rest/run-action-executor',
		'statemanager/redux/store',
		'statemanager/redux/slices/users/thunk',
		'statemanager/redux/slices/users',
		'bottom-sheet',
		'user-profile',
	],
	'bundle' => [
		'./src/list',
		'./src/list-item',
		'./src/list-items-factory',
	]
];
