<?php

return [
	'components' => [
		'project.tabs',
		'tasks:tasks.dashboard',
		'user.disk',
	],
	'extensions' => [
		'qrauth/utils',
		'rest',
		'tariff-plan-restriction',
	],
];
