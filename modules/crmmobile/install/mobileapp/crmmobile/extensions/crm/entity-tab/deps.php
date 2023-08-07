<?php

return [
	'extensions' => [
		'require-lazy',
		'loc',
		'type',
		'alert',
		'ui/kanban',
		'utils/random',
		'utils/string',
		'layout/ui/detail-card/tabs/factory/type',
		'layout/ui/empty-screen',
		'layout/pure-component',
		'layout/ui/simple-list/view-mode',
		'pull/client/events',
		'layout/ui/context-menu/item',

		'crm:type',
		'crm:loc',
		'crm:entity-actions',
		'crm:assets/entity',
		'crm:assets/category',
		'crm:category-list/actions',
		'crm:entity-tab/type',
		'crm:state-storage',
		'crm:storage/category',
		'crm:entity-detail/component/menu-provider',
	],
	'components' => [
		'crm:crm.entity.details',
	],
	'bundle' => [
		'./filter',
		'./pull-manager',
		'./sort',
	],
];
