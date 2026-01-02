<?php

return [
	'components' => [
		'tasks:tasks.dashboard',
		'tasks:tasks.task.view-new',
	],
	'extensions' => [
		'layout/ui/info-helper',
		'notify-manager',
		'require-lazy',
		'settings/disabled-tools',
		'tariff-plan-restriction',
		'tasks:enum',
		'tokens',
		'loc',
	],
];
