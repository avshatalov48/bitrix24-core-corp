<?php

return [
	'extensions' => [
		'crm:timeline/item/activity',
		'crm:timeline/item/log',

		'crm:timeline/item/ui/background',
		'crm:timeline/item/ui/body',
		'crm:timeline/item/ui/context-menu',
		'crm:timeline/item/ui/footer',
		'crm:timeline/item/ui/header',
		'crm:timeline/item/ui/icon',
		'crm:timeline/item/ui/loading-overlay',
		'crm:timeline/item/ui/market-banner',
		'crm:timeline/item/ui/styles',
		'crm:timeline/item/ui/user-avatar',

		'crm:type',
		'animation',
		'loc',
		'in-app-url',
		'in-app-url/components/link',
		'layout/ui/friendly-date',
		'layout/ui/friendly-date/time-ago',
		'layout/ui/file/icon',
		'layout/ui/file/selector',
	],
	'bundle' => [
		'./factory',
		'./base',
		'./compatible',
		'./model',
	],
];
