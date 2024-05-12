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
		'ui/kanban',
		'utils/random',
		'utils/string',
		'layout/ui/detail-card/tabs/factory/type',
		'layout/ui/empty-screen',
		'layout/pure-component',
		'layout/ui/simple-list/view-mode',
		'pull/client/events',
		'layout/ui/context-menu/item',
		'layout/ui/kanban/filter',

		'crm:type',
		'crm:loc',
		'crm:entity-actions',
		'crm:assets/entity',
		'crm:assets/category',
		'crm:category-list/actions',
		'crm:entity-tab/type',
		'crm:entity-tab/filter',
		'crm:state-storage',
		'crm:storage/category',
		'crm:entity-detail/component/menu-provider',
	],
	'components' => [
		'crm:crm.entity.details',
	],
	'bundle' => [
		'./pull-manager',
		'./sort',
	],
];
