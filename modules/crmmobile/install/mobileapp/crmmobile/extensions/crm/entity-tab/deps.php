<?php

return [
	'extensions' => [
		'loc',
		'alert',
		'utils/string',
		'ui/kanban',
		'layout/ui/detail-card/tabs/factory/type',
		'layout/ui/empty-screen',
		'layout/ui/plan-restriction',
		'layout/pure-component',
		'layout/ui/simple-list/view-mode',
		'pull/client/events',
		'type',
		'utils/random',

		'crm:type',
		'crm:loc',
		'crm:conversion',
		'crm:entity-actions',
		'crm:entity-actions/conversion',
		'crm:assets/entity',
		'crm:assets/category',
		'crm:entity-tab/type',
		'crm:entity-detail/opener',
		'crm:state-storage',
		'crm:category-list-view',
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
