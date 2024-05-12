<?php

return [
	'extensions' => [
		'analytics',
		'loc',
		'type',
		'alert',
		'apptheme',
		'require-lazy',
		'notify-manager',
		'statemanager/redux/store',

		'crm:type',
		'crm:category-list-view/open',
		'crm:entity-actions/change-crm-mode',
		'crm:entity-actions/conversion',
		'crm:statemanager/redux/slices/kanban-settings',
	],
	'bundle' => [
		'./change-pipeline',
		'./change-stage',
		'./copy-entity',
		'./public-errors',
		'./share',
	],

];
