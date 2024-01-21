<?php

return [
	'extensions' => [
		'disk',
		'apptheme',
		'im:messenger/api/dialog-opener',
	],
	'components' => [
		'user.profile',
	],
	'bundle' => [
		'./src/profile',
		'./src/profile-view',
	],
];
