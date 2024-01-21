<?php

return [
	'extensions' => [
		'type',
		'apptheme',
		'utils/props',
		'require-lazy',
		'layout/ui/buttons',

		'haptics',
		'utils/object',
		'notify-manager',
		'layout/ui/kanban/settings',

		'crm:category-permissions',
		'crm:stage-list',
		'statemanager/redux/connect',
		'crm:statemanager/redux/slices/kanban-settings',
		'crm:statemanager/redux/slices/stage-settings',
	],
];
