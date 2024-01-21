<?php

return [
	'extensions' => [
		'loc',
		'apptheme',
		'utils/color',
		'utils/date',
		'utils/date/formats',
		'utils/object',
		'utils/skeleton',
		'layout/pure-component',
		'layout/ui/counter-view',
		'layout/ui/simple-list/items/extended',
		'layout/ui/user/avatar',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
		'tasks:layout/deadline-pill',
	],
	'bundle' => [
		'./src/task-content',
	],
];
