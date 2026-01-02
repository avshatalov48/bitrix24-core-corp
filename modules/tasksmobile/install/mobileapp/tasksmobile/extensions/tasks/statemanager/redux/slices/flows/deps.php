<?php

return [
	'extensions' => [
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'utils/type',
		'tasks:statemanager/redux/slices/flows/meta',
		'device/connection',
	],
	'bundle' => [
		'./src/thunk',
		'./src/extra-reducer',
		'./src/selector',
		'./src/action',
		'./src/reducer',
		'./src/slice',
		'./src/tool',
	],
];
