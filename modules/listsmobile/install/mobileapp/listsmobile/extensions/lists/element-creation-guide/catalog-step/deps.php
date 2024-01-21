<?php

return [
	'bundle' => [
		'./skeleton',
		'./view',
		'./component',
	],
	'extensions' => [
		'loc',
		'apptheme',
		'event-emitter',
		'notify-manager',
		'storage-cache',

		'utils/date/duration',
		'utils/skeleton',
		'utils/random',

		'layout/pure-component',
		'layout/ui/wizard/step',

		'lists:wizard/progress-bar-number',
		'lists:element-creation-guide/stub',
	],
];
