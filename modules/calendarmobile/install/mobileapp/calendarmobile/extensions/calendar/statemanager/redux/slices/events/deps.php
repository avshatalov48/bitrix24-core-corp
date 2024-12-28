<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'statemanager/redux/toolkit',
		'statemanager/redux/reducer-registry',
		'statemanager/redux/state-cache',

		'calendar:date-helper',
	],
	'bundle' => [
		'./src/meta',
		'./src/selector',
		'./src/thunk',
		'./src/extra-reducer',
		'./src/model',
		'./src/recursion-parser',
	],
];
