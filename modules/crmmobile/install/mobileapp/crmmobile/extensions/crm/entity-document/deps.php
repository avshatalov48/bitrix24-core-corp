<?php

return [
	'extensions' => [
		'crm:error',
		'crm:product-grid',
		'crm:product-grid/services/product-model-loader',
		'crm:entity-document/product',
		'crm:type',
		'layout/ui/detail-card/tabs/shimmer/crm-product',
		'feature',
	],
	'bundle' => [
		'./base-document',
		'./payment-document',
		'./delivery-document',
	],
];
