<?php

return [
	'extensions' => [
		'alert',
		'type',
		'loc',
		'require-lazy',
		'user/profile',
		'utils/date',

		'statemanager/redux/connect',
		'tasks:statemanager/redux/slices/flows',
		'tasks:statemanager/redux/slices/groups',

		'tasks:layout/flow/list',

		'ui-system/typography/heading',
		'ui-system/blocks/link',
		'ui-system/layout/card',
		'ui-system/blocks/avatar',
		'ui-system/popups/aha-moment',
		'ui-system/blocks/chips/chip-status',

		'layout/ui/scroll-view',
		'layout/ui/collapsible-text',
		'layout/pure-component',
	],
	'bundle' => [
		'./src/common',
	],
];
