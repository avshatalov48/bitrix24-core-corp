<?php

return [
	'extensions' => [
		'loc',
		'alert',
		'toast',
		'bbcode/parser',
		'tokens',
		'text-editor',
		'assets/icons',
		'bottom-sheet',
		'layout/pure-component',
		'layout/ui/friendly-date',
		'layout/ui/menu',
		'layout/ui/scroll-view',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
		'statemanager/redux/store',
		'ui-system/blocks/avatar',
		'ui-system/layout/card',
		'ui-system/typography/text',
		'utils/date',

		'tasks:layout/action-menu/actions',
		'tasks:statemanager/redux/slices/tasks',
		'tasks:statemanager/redux/slices/tasks-results',
	],
	'bundle' => [
		'./src/date',
		'./src/list',
		'./src/list-item',
		'./src/menu',
		'./src/view',
		'./src/view-redux-content',
	],
];
