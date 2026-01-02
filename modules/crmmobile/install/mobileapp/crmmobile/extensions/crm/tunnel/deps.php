<?php

return [
	'extensions' => [
		'apptheme',
		'statemanager/redux/connect',
		'layout/pure-component',
		'tokens',
		'ui-system/typography/text',
		'ui-system/blocks/icon',

		'crm:statemanager/redux/slices/tunnels',
		'crm:statemanager/redux/slices/stage-settings',
	],
	'bundle' => [
		'./tunnel-content',
	],
];
