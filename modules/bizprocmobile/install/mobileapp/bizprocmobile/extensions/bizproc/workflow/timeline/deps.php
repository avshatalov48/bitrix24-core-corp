<?php

return [
	'bundle' => [
		'./components/step/subtitle',
		'./components/step/title',
		'./components/step/user-list',
		'./components/stubs/content-stub',
		'./components/stubs/stubs',
		'./components/stubs/user-stub',
		'./components/steps-list-collapsed',
		'./components/components',
		'./components/counter',
		'./components/step-wrapper',
		'./components/step-content',
		'./icons',
		'./skeleton',
	],
	'extensions' => [
		'animation',
		'apptheme',
		'loc',
		'in-app-url',
		'notify-manager',
		//'rest',
		'layout/ui/friendly-date',
		'layout/ui/user/avatar',
		'statemanager/redux/slices/users',
		'statemanager/redux/store',
		'user/profile',
		'utils/date/duration',
		'utils/date',
		'utils/date/formats',
		'utils/object',
		'utils/skeleton',
		'layout/ui/safe-image',
		'type',

		'layout/ui/file',
		'utils/file',

		'bizproc:helper/duration',
	],
];
