<?php

return [
	'extensions' => [
		'layout/pure-component',
		'utils/enums/base',
		'ui-system/typography/text',
		'utils/test',
		'ui-system/blocks/icon',
		'ui-system/layout/card',
		'tokens',
		'ui-system/blocks/avatar',
		'ui-system/blocks/badges/counter',
		'statemanager/redux/connect',
		'loc',
		'intranet:statemanager/redux/slices/department',
		'statemanager/redux/slices/users/selector',
		'type',
	],
	'bundle' => [
		'./src/mode-enum',
		'./src/card',
		'./src/redux-provider',
	],
];
