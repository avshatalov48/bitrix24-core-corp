<?php

return [
	'components' => [
		'project.tabs',
		'tasks:tasks.dashboard',
		'user.disk',
		'disk:disk.tabs.group',
	],
	'extensions' => [
		'qrauth/utils',
		'rest',
		'tariff-plan-restriction',
	],
];
