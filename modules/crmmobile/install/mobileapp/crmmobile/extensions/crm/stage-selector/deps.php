<?php

return [
	'extensions' => [
		'utils/prop',
		'layout/ui/fields/stage-selector',

		'crm/loc',
		'crm:type/id',
		'crm:entity-actions/check-change-stage',

		'statemanager/redux/connect',

		'crm:statemanager/redux/slices/kanban-settings',
		'crm:statemanager/redux/slices/stage-settings',
		'crm:stage-selector/item',
	],
];
