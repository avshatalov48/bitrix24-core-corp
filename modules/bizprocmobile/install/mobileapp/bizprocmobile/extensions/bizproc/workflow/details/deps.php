<?php

return [
	'bundle' => [
		'./skeleton',
		'./content',
	],
	'extensions' => [
		'apptheme',
		'alert',
		'event-emitter',
		'in-app-url',
		'haptics',
		'loc',
		'notify-manager',

		'utils/random',
		'utils/skeleton',

		'layout/pure-component',
		'layout/ui/fields/focus-manager',
		'layout/ui/entity-editor/manager',
		'layout/ui/collapsible-text',

		'bizproc:workflow/comments',
	],
];
