<?php

return [
	'extensions' => [
		'loc',
		'bottom-sheet',
		'asset-manager',
		'toast',
		'assets/icons',
		'utils/url',
		'utils/function',
		'haptics',

		'tokens',
		'ui-system/layout/box',
		'ui-system/layout/area',
		'ui-system/layout/card',
		'ui-system/blocks/icon',
		'ui-system/form/buttons/button',
		'ui-system/typography/text',
		'ui-system/typography/heading',
		'ui-system/blocks/chips/chip-button',
		'ui-system/blocks/avatar',
		'layout/polyfill',
		'layout/pure-component',

		'stafftrack:data-managers/shift-manager',
		'stafftrack:date-helper',
		'stafftrack:month-picker',
		'stafftrack:shift-view',
		'stafftrack:analytics',
	],
	'bundle' => [
		'./src/calendar',
		'./src/calendar-header',
		'./src/month-selector',
	],
];
