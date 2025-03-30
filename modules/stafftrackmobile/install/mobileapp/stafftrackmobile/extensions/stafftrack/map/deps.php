<?php

return [
	'extensions' => [
		'loc',
		'type',
		'utils',
		'tokens',
		'asset-manager',
		'toast',
		'assets/icons',
		'utils/skeleton',
		'haptics',
		'utils/url',
		'layout/pure-component',
		'alert',
		'notify-manager',

		'ui-system/blocks/switcher',
		'ui-system/blocks/chips/chip-button',
		'ui-system/typography/text',
		'ui-system/typography/text-field',
		'ui-system/blocks/icon',
		'ui-system/popups/aha-moment',

		'stafftrack:check-in',
		'stafftrack:ajax',
		'stafftrack:model/shift',
		'stafftrack:base-menu',
		'stafftrack:analytics',
		'stafftrack:data-managers/settings-manager',
		'stafftrack:ui',
	],
	'bundle' => [
		'./src/location-menu',
		'./src/disabled-geo-aha',
		'./src/disabled-geo-user-enum',
	],
];
