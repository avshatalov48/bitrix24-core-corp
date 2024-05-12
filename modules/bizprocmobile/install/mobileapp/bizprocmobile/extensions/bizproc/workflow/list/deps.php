<?php

return [
	'bundle' => [
		'./item',
		'./skeleton',
	],
	'extensions' => [
		'apptheme',
		'assets/common',
		'alert',
		'loc',
		'rest',
		'require-lazy',
		'toast',
		'event-emitter',
		'statemanager/redux/slices/users',
		'statemanager/redux/store',
		'layout/pure-component',
		'utils/function',
		'utils/object',
		'utils/skeleton',
		'utils/date/formats',
		'layout/ui/friendly-date',
		'layout/ui/empty-screen',
		'layout/ui/search-bar',
		'layout/ui/stateful-list',
		'layout/ui/simple-list/items/base',
		'layout/ui/simple-list/skeleton',
		'bizproc:workflow/faces',
		'bizproc:task/buttons',
	],
];
