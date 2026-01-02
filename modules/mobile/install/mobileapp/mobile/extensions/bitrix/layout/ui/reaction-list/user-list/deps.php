<?php

return [
	'extensions' => [
		'loc',
		'tokens',
		'layout/ui/scroll-view',
		'layout/ui/simple-list/items/base',
		'layout/ui/simple-list/items',
		'layout/ui/stateful-list',
		'layout/ui/user/user-name',
		'ui-system/blocks/chips/chip-inner-tab',
		'ui-system/typography/text',
		'ui-system/blocks/avatar',
		'ui-system/blocks/reaction/icon',

		'statemanager/redux/slices/users',
		'statemanager/redux/connect',
		'user-profile',
	],
	'bundle' => [
		'./src/reaction-items-factory',
		'./src/list-item',
		'./src/redux-list-item',
		'./src/reaction',
	],
];
