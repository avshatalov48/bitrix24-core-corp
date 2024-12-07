<?php

return [
	'extensions' => [
		'alert',
		'layout/ui/collapsible-text',
		'layout/ui/fields/file',
		'layout/ui/fields/theme',
		'layout/ui/fields/theme/air/elements/add-button',
		'layout/ui/user/avatar',
		'loc',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
		'statemanager/redux/store',
		'tasks:layout/fields/result',
		'tasks:statemanager/redux/slices/tasks',
		'tasks:statemanager/redux/slices/tasks-results',
		'tokens',
		'ui-system/blocks/icon',
		'ui-system/layout/card',
		'ui-system/typography/text',
		'utils/date',
		'utils/date/formats',
		'utils/skeleton',
	],
	'bundle' => [
		'./src/redux-content',
	],
];