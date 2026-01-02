<?php

return [
	'extensions' => [
		'type',
		'tokens',
		'bottom-sheet',
		'statemanager/redux/store',
		'statemanager/redux/slices/users',
		'statemanager/redux/batched-actions',
		'statemanager/redux/slices/users/thunk',
		'layout/ui/reaction-list/user-list',
		'layout/ui/reaction-list/tab-panel',
	],
	'bundle' => [
		'./src/view',
		'./src/controller',
	],
];
