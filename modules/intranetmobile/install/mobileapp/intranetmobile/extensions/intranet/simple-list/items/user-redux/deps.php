<?php

return [
	'extensions' => [
		'tokens',
		'loc',
		'toast',
		'utils/date',
		'layout/ui/context-menu',
		'layout/ui/user/user-name',
		'ui-system/typography/heading',
		'ui-system/form/buttons',
		'ui-system/blocks/avatar',
		'ui-system/blocks/chips/chip-status',
		'ui-system/blocks/chips/chip-button',
		'ui-system/blocks/badges/counter',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
		'intranet:statemanager/redux/slices/employees',
		'communication/phone-menu',
		'utils/function',
	],
	'bundle' => [
		'./src/user-content',
		'./src/user-view',
		'./src/action-menu',
		'./src/actions',
	],
];
