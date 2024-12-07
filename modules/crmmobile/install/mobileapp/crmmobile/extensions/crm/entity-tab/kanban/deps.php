<?php

return [
	'extensions' => [
		'loc',
		'notify',
		'notify-manager',
		'haptics',
		'utils/object',
		'layout/ui/kanban',
		'require-lazy',
		'ui-system/blocks/icon',

		'crm:type',
		'crm:entity-actions/change-crm-mode',
		'crm:entity-detail/component/smart-activity-menu-item',
		'crm:entity-tab',
		'crm:kanban/toolbar',
		'crm:ui/loading-progress',
		'crm:simple-list/items',

		'statemanager/redux/store',
		'statemanager/redux/batched-actions',
		'crm:statemanager/redux/slices/stage-counters',
		'crm:statemanager/redux/slices/kanban-settings',
		'crm:statemanager/redux/slices/stage-settings',
	],
];
