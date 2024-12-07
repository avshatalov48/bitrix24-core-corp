<?php

return [
	'extensions' => [
		'rest',
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/stage-settings/meta',
		'tasks:statemanager/redux/slices/stage-settings/thunk',
		'tasks:statemanager/redux/slices/stage-settings/selector',
		'tasks:statemanager/redux/slices/kanban-settings/thunk',
		'tasks:statemanager/redux/slices/kanban-settings/action',
		'tasks:statemanager/redux/slices/tasks/thunk',
	],
	'bundle' => [
		'./src/extra-reducer',
		'./src/slice',
	]
];
