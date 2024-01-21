<?php

return [
	'bundle' => [
		'./component',
		'./view',
		'./skeleton',
	],
	'extensions' => [
		'apptheme',
		'loc',
		'event-emitter',
		'notify-manager',

		'utils/random',
		'utils/skeleton',

		'layout/pure-component',
		'layout/ui/wizard/step',
		'layout/ui/fields/focus-manager',
		'layout/ui/entity-editor/manager',

		'bizproc:wizard/progress-bar-number',
	],
];
