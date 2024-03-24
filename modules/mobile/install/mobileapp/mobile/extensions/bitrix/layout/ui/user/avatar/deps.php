<?php

return [
	'extensions' => [
		'type',
		'utils/url',
		'layout/ui/safe-image',
		'layout/ui/user/empty-avatar',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
	],
	'bundle' => [
		'./src/redux-avatar',
		'./src/base-avatar',
	],
];
