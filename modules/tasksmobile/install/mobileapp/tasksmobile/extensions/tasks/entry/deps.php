<?php

return [
	'components'=> [
		'tasks:tasks.dashboard',
		'tasks:tasks.list.legacy',
		'tasks:tasks.task.tabs',
		'tasks:tasks.task.view',
	],
	'extensions' => [
		'apptheme',
		'feature',
		'settings/disabled-tools',
		'layout/ui/info-helper',
		'notify-manager',
	],
];