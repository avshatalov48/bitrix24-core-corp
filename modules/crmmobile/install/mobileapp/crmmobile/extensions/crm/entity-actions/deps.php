<?php

return [
	'extensions' => [
		'loc',
		'type',
		'alert',
		'notify-manager',

		'crm:type',
		'crm:crm-mode',
		'crm:conversion',
		'crm:storage/category',
		'crm:category-list-view',
		'crm:entity-detail/opener',
		'crm:category-list-view/open',
	],
	'bundle' => [
		'./change-pipeline',
		'./change-crm-mode',
		'./change-stage',
		'./copy-entity',
		'./public-errors',
		'./share',
	],

];
