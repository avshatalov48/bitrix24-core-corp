<?php

return [
	'extensions' => [
		'apptheme',
		'loc',
		'layout/ui/stage-list-view',
		'tasks:layout/stage-list',
		// todo remove after refactoring of stage-details
		'helpers/component',
		'statemanager/redux/connect',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/kanban-settings',
		'tasks:statemanager/redux/slices/stage-settings',
		'utils/object',
	],
];
