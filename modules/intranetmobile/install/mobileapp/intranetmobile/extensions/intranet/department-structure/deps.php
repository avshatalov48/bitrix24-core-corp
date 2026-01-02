<?php

return [
	'extensions' => [
		'layout/pure-component',
		'ui-system/blocks/avatar',
		'tokens',
		'ui-system/typography/text',
		'utils/test',
		'ui-system/blocks/icon',
		'ui-system/blocks/department-card',
		'utils/skeleton',
		'ui-system/layout/card',
		'type',
		'intranet:statemanager/redux/slices/department',
		'statemanager/redux/store',
		'statemanager/redux/connect',
	],
	'bundle' => [
		'./src/skeleton',
		'./src/redux-structure',
		'./src/company',
	],
];
