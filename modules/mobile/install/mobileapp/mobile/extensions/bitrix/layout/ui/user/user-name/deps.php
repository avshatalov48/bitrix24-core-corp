<?php

return [
	'extensions' => [
		'tokens',
		'layout/ui/user/enums',
		'layout/pure-component',
		'ui-system/typography/text',
		'statemanager/redux/store',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
	],
	'bundle' => [
		'./src/providers/redux',
		'./src/providers/selector',
		'./src/enums/type-enum'
	]
];
