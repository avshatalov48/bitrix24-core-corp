<?php

return [
	'extensions' => [
		'alert',
		'notify',
		'crm:type',
		'crm:timeline/scheduler',
	],
	'bundle' => [
		'./entities/base',
		'./entities/deal',
		'./entities/lead',
		'./entities/contact',
		'./entities/company',
		'./entities/smart-invoice',
		'./entities/quote',
		'./traits/exclude-item',
	],
];
