<?php

return [
	'extensions' => [
		'analytics-label',
		'layout/ui/wizard/step',
		'crm:product-grid',
		'crm:product-grid/services/product-model-loader',
		'loc',
		'event-emitter',
		'crm:receive-payment/progress-bar-number',
	],
	'bundle' => [
		'./product-grid',
		'./product-model-loader',
	],
];
