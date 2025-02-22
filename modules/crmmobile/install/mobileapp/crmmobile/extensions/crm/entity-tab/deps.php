<?php

return [
	'extensions' => [
		'analytics',
		'apptheme',
		'require-lazy',
		'loc',
		'type',
		'alert',
		'notify',
		'tokens',
		'qrauth/utils',
		'utils/random',
		'utils/string',
		'notify-manager',
		'layout/ui/menu',
		'layout/ui/context-menu',
		'layout/ui/detail-card/tabs/factory/type',
		'layout/ui/empty-screen',
		'layout/pure-component',
		'layout/ui/simple-list/view-mode',
		'pull/client/events',
		'layout/ui/context-menu/item',
		'layout/ui/kanban/filter',
		'ui-system/blocks/icon',

		'crm:type',
		'crm:loc',
		'crm:entity-actions',
		'crm:assets/entity',
		'crm:category-list/actions',
		'crm:entity-tab/type',
		'crm:entity-tab/filter',
		'crm:state-storage',
		'crm:statemanager/redux/slices/stage-counters',
	],
	'components' => [
		'crm:crm.entity.details',
	],
	'bundle' => [
		'./pull-manager',
		'./sort',
	],
];
