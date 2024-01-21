<?php

return [
	'extensions' => [
		'apptheme',
		'statemanager/redux/connect',
		'layout/pure-component',

		'crm:statemanager/redux/slices/tunnels',
		'crm:statemanager/redux/slices/stage-settings',
	],
	'bundle' => [
		'./tunnel-content',
	],
];
