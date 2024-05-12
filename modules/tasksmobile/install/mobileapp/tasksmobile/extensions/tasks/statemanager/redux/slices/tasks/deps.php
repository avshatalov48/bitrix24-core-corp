<?php

return [
	'extensions' => [
		'device/connection',
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'statemanager/redux/slices/users',
		'statemanager/redux/store',
		'tasks:statemanager/redux/slices/groups',
		'tasks:statemanager/redux/slices/tasks/expiration-registry',
		'tasks:statemanager/redux/slices/tasks/extra-reducer',
		'tasks:statemanager/redux/slices/tasks/meta',
		'tasks:statemanager/redux/slices/tasks/model/task',
		'tasks:statemanager/redux/slices/tasks/selector',
		'tasks:statemanager/redux/slices/tasks/thunk',
	],
	'bundle' => [
		'./src/mapper',
	],
];
