<?php

return [
	'extensions' => [
		'disk',
		'apptheme',
		'im:messenger/api/dialog-opener',
		'tasks:task',
		'require-lazy',
	],
	'components' => [
		'user.profile',
	],
	'bundle' => [
		'./src/profile',
		'./src/profile-view',
		'./src/backdrop-profile',
	],
];
