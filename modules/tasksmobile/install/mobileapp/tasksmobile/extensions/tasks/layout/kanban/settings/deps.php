<?php

return [
	'extensions' => [
		'apptheme',
		'utils/props',
		'require-lazy',

		'utils/object',
		'notify-manager',
		'layout/ui/kanban/settings',

		'statemanager/redux/connect',
		'tasks:statemanager/redux/types',
		'tasks:statemanager/redux/slices/kanban-settings',
		'tasks:statemanager/redux/slices/stage-settings',
	],
];
