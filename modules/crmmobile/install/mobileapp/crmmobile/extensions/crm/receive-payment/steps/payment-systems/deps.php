<?php

return [
	'extensions' => [
		'analytics-label',
		'layout/ui/wizard/step',
		'layout/ui/fields/boolean',
		'layout/ui/banners',
		'layout/pure-component',
		'notify-manager',
		'crm:receive-payment/progress-bar-number',
		'crm:terminal/services/payment-system',
		'crm:payment-system/creation/actions/oauth',
		'crm:payment-system/creation/actions/before',
		'crm:error',
		'bottom-sheet',
	],
	'bundle' => [
		'./skip-switcher',
		'./paysystem-settings',
		'./payment-methods',
		'./payment-method-entry',
		'./main-block-layout',
		'./expandable-list',
	],
];
