<?php

return [
	'extensions' => [
		'rest',
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/stage-settings/meta',
		'tasks:statemanager/redux/slices/stage-settings/thunk',
		'tasks:statemanager/redux/slices/kanban-settings/thunk',
	],
	'bundle' => [
		'./src/extra-reducer',
		'./src/selector',
		'./src/slice',
	]
];
