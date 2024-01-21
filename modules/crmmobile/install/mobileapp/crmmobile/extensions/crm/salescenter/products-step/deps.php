<?php

return [
	'extensions' => [
		'layout/ui/wizard/step',
		'crm:product-grid',
		'crm:product-grid/services/product-model-loader',
		'loc',
		'event-emitter',
		'crm:salescenter/progress-bar-number',
		'layout/ui/warning-block',
		'layout/ui/empty-screen',
	],
	'bundle' => [
		'./product-grid',
		'./product-model-loader',
	],
];
