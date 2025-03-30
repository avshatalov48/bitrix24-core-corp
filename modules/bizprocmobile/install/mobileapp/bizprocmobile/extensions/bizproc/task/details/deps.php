<?php

return [
	'bundle' => [
		'./skeleton',
		'./buttons',
	],
	'extensions' => [
		'alert',
		'apptheme',
		'in-app-url',
		'loc',
		'rest',
		'haptics',
		'event-emitter',
		'notify-manager',
		'toast',

		'utils/file',
		'utils/function',
		'utils/skeleton',
		'utils/random',

		'layout/ui/context-menu',
		'layout/pure-component',
		'layout/ui/fields/focus-manager',
		'selector/widget/factory',

		'bizproc:workflow/comments',
		'bizproc:task/task-constants',
		'bizproc:task/buttons',
		'bizproc:task/fields',
	],
];
