<?php

return [
	'extensions' => [
		'ui-system/blocks/avatar',
		'statemanager/redux/store',

		'tasks:statemanager/redux/slices/groups',
	],
	'bundle' => [
		'./src/providers/redux',
		'./src/providers/selector',
	],
];
