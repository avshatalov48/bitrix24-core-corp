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
	],
	'bundle' => [
		'./src/tools',
		'./src/extra-reducer',
		'./src/selector',
		'./src/slice',
	]
];
