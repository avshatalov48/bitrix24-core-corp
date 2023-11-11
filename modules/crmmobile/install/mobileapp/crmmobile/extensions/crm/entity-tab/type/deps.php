<?php

return [
	'extensions' => [
		'alert',
		'loc',
		'notify',
		'crm:type',
		'crm:entity-chat-opener',
		'layout/ui/menu',
	],
	'bundle' => [
		'./entities/base',
		'./entities/deal',
		'./entities/lead',
		'./entities/contact',
		'./entities/company',
		'./entities/smart-invoice',
		'./entities/quote',
		'./entities/dynamic',
		'./traits/exclude-item',
		'./traits/open-chat',
	],
];
