<?php

return [
	'extensions' => [
		'alert',
		'loc',
		'notify',
		'crm:type',
		'apptheme',
		'layout/ui/item-selector',
		'layout/ui/menu',
		'layout/ui/empty-screen',
		'ui-system/blocks/icon',
		'crm:entity-chat-opener',
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
