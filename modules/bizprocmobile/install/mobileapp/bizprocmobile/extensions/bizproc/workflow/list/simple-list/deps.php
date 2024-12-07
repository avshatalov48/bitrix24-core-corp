<?php

return [
	'bundle' => [
		'./item',
		'./bottom-panel',
	],
	'extensions' => [
		'apptheme',
		'alert',
		'event-emitter',
		'loc',
		'type',
		'toast',

		'utils/object',
		'utils/function',
		'utils/date/formats',

		'layout/pure-component',
		'layout/ui/simple-list',
		'layout/ui/bottom-toolbar',
		'layout/ui/friendly-date',

		'ui-system/form/checkbox',

		'layout/ui/counter-view',
		'bizproc:workflow/faces',
		'bizproc:task/buttons',
		'bizproc:task/task-constants',
		'bizproc:workflow/list/view-mode',
	],
];
