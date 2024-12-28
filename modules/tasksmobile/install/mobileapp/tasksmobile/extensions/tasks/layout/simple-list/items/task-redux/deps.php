<?php

return [
	'extensions' => [
		'assets/icons',
		'animation',
		'tokens',
		'loc',
		'utils/color',
		'utils/date',
		'utils/date/formats',
		'utils/object',
		'utils/skeleton',
		'layout/pure-component',
		'layout/ui/counter-view',
		'layout/ui/simple-list/items/extended',
		'ui-system/blocks/icon',
		'ui-system/blocks/avatar',
		'ui-system/typography/text',
		'statemanager/redux/store',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
		'tasks:enum',
		'tasks:layout/deadline-pill',
		'tasks:statemanager/redux/slices/tasks',
		'tasks:layout/fields/time-tracking/timer',
	],
	'bundle' => [
		'./src/task-content',
	],
];
