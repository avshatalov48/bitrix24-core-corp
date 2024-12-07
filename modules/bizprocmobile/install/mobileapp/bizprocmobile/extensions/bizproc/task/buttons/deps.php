<?php

return [
	'bundle' => [
		'./accept-button',
		'./button',
		'./buttons-wrapper',
		'./decline-button',
		'./detail-button',
		'./start-button',
		'./delegate-button',
	],
	'extensions' => [
		'alert',
		'apptheme',
		'event-emitter',
		'loc',
		'rest',
		'require-lazy',
		'type',
		'haptics',

		'utils/object',
		'utils/function',

		'layout/ui/buttons/primary',
		'layout/ui/buttons/cancel',
		'selector/widget/factory',

		'bizproc:task/task-constants',
	],
];
