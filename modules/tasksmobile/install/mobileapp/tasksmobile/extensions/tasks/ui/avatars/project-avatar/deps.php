<?php

return [
	'extensions' => [
		'ui-system/blocks/avatar',

		'tasks:statemanager/redux/slices/groups'
	],
	'bundle' => [
		'./src/providers/redux',
		'./src/providers/selector'
	],
];
