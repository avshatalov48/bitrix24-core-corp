<?php

return [
	'extensions' => [
		'rest/run-action-executor',
		'statemanager/redux/toolkit',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/kanban-settings/meta',
		'tasks:statemanager/redux/slices/kanban-settings/tools',
	],
	'bundle' => [
		'./src/data-provider',
	],
];
