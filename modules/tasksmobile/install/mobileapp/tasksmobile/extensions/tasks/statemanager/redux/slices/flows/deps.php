<?php

return [
	'extensions' => [
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'utils/type',
		'tasks:statemanager/redux/slices/flows/meta',
	],
	'bundle' => [
		'./src/selector',
		'./src/action',
		'./src/reducer',
		'./src/slice',
		'./src/tool',
	],
];
