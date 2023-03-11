<?php

return [
	'extensions' => [
		'loc',
		'alert',
		'utils/string',
		'ui/kanban/*',
		'crm:type',
		'crm:loc',
		'crm:entity-actions',
		'crm:assets/entity',
		'crm:assets/category',
		'crm:entity-tab/type',
		'crm:entity-detail/opener',
		'crm:state-storage',
		'crm:category-list-view',
		'crm:entity-detail/component/menu-provider',
		'layout/ui/detail-card/tabs/factory/type',
		'layout/ui/empty-screen',
		'layout/pure-component',
		'layout/ui/simple-list/view-mode',
		'pull/client/events',
		'utils/random',
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
