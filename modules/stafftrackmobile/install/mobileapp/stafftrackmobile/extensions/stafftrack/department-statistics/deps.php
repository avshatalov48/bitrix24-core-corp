<?php

return [
	'extensions' => [
		'loc',
		'type',
		'bottom-sheet',
		'asset-manager',
		'utils/string',
		'utils/skeleton',
		'haptics',

		'apptheme',
		'tokens',
		'ui-system/layout/box',
		'ui-system/layout/area',
		'ui-system/blocks/stage-selector',
		'ui-system/blocks/icon',
		'ui-system/typography/text',
		'ui-system/typography/heading',
		'ui-system/blocks/avatar',

		'layout/pure-component',
		'layout/ui/scroll-view',

		'stafftrack:data-managers/shift-manager',
		'stafftrack:data-managers/option-manager',
		'stafftrack:date-helper',
		'stafftrack:month-picker',
		'stafftrack:shift-view',
		'stafftrack:analytics',
		'stafftrack:ui',
	],
	'bundle' => [
		'./src/skeleton',
		'./src/segment-button',
		'./src/progress-bar',
		'./src/month-selector',
		'./src/table-statistics-view',
		'./src/table-user',
		'./src/month-statistics',
		'./src/today-statistics',
	],
];
