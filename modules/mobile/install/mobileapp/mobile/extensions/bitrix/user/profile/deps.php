<?php

return [
	'extensions' => [
		'disk',
		'apptheme',
		'im:messenger/api/dialog-opener',
		'require-lazy',
		'haptics',
		'alert',
		'loc',
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
