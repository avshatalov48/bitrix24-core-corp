<?php

return [
	'extensions' => [
		'layout/polyfill',
		'layout/ui/kanban/toolbar',
		'layout/ui/money',
		'haptics',
		'navigation-loader',
		'crm:type',
		'crm:storage/category',
		'crm:stage-toolbar',
		'crm:state-storage',
	],
	'bundle' => [
		'./entities/base',
		'./entities/deal',
		'./entities/lead',
		'./entities/smart-invoice',
		'./entities/quote',
	],
];
