<?php

return [
	'extensions' => [
		'rest',
		'statemanager/redux/toolkit',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/stage-settings/meta',
	],
	'bundle' => [
		'./src/data-provider',
	]
];
