<?php

return [
	'extensions' => [
		'crm:timeline/item/activity',
		'crm:timeline/item/log',
		'crm:timeline/item/ui/*',
		'crm:type',
		'animation',
		'loc',
		'in-app-url',
		'in-app-url/components/link',
		'layout/ui/friendly-date',
		'layout/ui/friendly-date/time-ago',
	],
	'bundle' => [
		'./factory',
		'./base',
		'./compatible',
		'./model',
	]
];