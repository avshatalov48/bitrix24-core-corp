<?php

return [
	'extensions' => [
		'rest',
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/utils',
		'tasks:statemanager/redux/slices/kanban-settings/meta',
		'tasks:statemanager/redux/slices/kanban-settings/thunk',
		'tasks:statemanager/redux/slices/stage-settings/thunk',
		'tasks:statemanager/redux/slices/kanban-settings/src/tools',
		'tasks:statemanager/redux/slices/kanban-settings/reducer',
		'tasks:statemanager/redux/slices/kanban-settings/action',
		'tasks:statemanager/redux/slices/tasks/thunk',
		'tasks:statemanager/redux/slices/kanban-settings/selector',
	],
	'bundle' => [
		'./src/extra-reducer',
		'./src/slice',
	],
];
