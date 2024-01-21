<?php

return [
	'extensions' => [
		'apptheme',
		'utils/object',
		'layout/pure-component',
		'layout/ui/product-grid',
		'layout/ui/detail-card/tabs/shimmer/crm-product',
		'layout/ui/product-grid/components/inline-sku-tree',
		'layout/ui/product-grid/components/product-card',

		'crm:error',
		'crm:product-grid',
		'crm:product-grid/services/product-model-loader',
		'crm:product-grid/model',
	],
	'bundle' => [
		'./product-grid',
		'./product-card',
	],
];
