<?php

return [
	'extensions' => [
		'apptheme',
		'utils/object',
		'layout/ui/product-grid',
		'layout/ui/product-grid/components/inline-sku-tree',
		'layout/ui/product-grid/components/product-card',
		'layout/ui/product-grid/components/summary',

		'crm:product-grid/model',
		'crm:product-grid',
	],
	'bundle' => [
		'./product-card',
		'./product-grid',
	],
];
