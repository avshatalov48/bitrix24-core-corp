<?php

return [
	'bundle' => [
		'./icons',
		'./components/stubs/content-stub',
		'./components/stubs/stubs',
		'./components/stubs/user-stub',
		'./components/steps-list-collapsed',
		'./components/components',
		'./components/counter',
		'./components/step-wrapper',
		'./components/step-content',
	],
	'extensions' => [
		'animation',
		'apptheme',
		'bizproc:workflow/timeline/stubs',
		'loc',
		//'rest',
		'layout/ui/user/avatar',
		'statemanager/redux/slices/users',
		'statemanager/redux/store',
		'user/profile',
		'utils/date/duration',
		'utils/object',
		'layout/ui/safe-image',
		'type',
	],
];
