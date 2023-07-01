<?php

return [
	'extensions' => [
		'alert',
		'layout/ui/product-grid',
		'crm:loc',

		'crm:product-grid/components/product-details',
		'crm:product-grid/components/product-pricing',
		'crm:product-grid/components/sku-selector',
		'crm:product-grid/components/stateful-product-card',

		'crm:product-grid/menu/product-add',
		'crm:product-grid/menu/product-context-menu',

		'crm:product-grid/services/currency-converter',
		'crm:product-grid/services/product-model-loader',
		'crm:product-grid/services/product-wizard',

		'crm:product-grid/model',
		'crm:product-calculator',
		'layout/ui/context-menu',
		'layout/ui/floating-button',
		'utils/object',
		'loc',
		'utils/error-notifier',
		'rest',
		'catalog/barcode-scanner',
		'catalog/store/events',
		'analytics-label',
	],
];
