<?php

return [
	'extensions' => [
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'utils/type',
		'utils/hash',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/utils',
		'tasks:statemanager/redux/slices/tasks-stages',
		'tasks:statemanager/redux/slices/stage-counters/meta',
		'tasks:statemanager/redux/slices/kanban-settings/thunk',
		'tasks:statemanager/redux/slices/stage-settings/thunk',
	],
	'bundle' => [
		'./src/tools',
		'./src/action',
		'./src/extra-reducer',
		'./src/reducer',
		'./src/selector',
		'./src/slice',
	],
];
