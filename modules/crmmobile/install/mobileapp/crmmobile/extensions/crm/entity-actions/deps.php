<?php

return [
	'extensions' => [
		'require-lazy',
		'loc',
		'type',
		'alert',
		'notify-manager',

		'crm:type',
		'crm:storage/category',
		'crm:category-list-view/open',
		'crm:entity-actions/change-crm-mode',
		'crm:entity-actions/conversion',
	],
	'bundle' => [
		'./change-pipeline',
		'./change-stage',
		'./copy-entity',
		'./public-errors',
		'./share',
	],

];
