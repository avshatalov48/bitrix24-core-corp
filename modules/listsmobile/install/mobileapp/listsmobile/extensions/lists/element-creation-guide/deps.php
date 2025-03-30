<?php

return [
	'bundle' => [
		'./stub',
		'./alert',
	],
	'extensions' => [
		'loc',
		'apptheme',
		'event-emitter',
		'alert',
		'haptics',

		'utils/object',
		'utils/random',

		'layout/ui/wizard',
		'layout/ui/empty-screen',
		'layout/pure-component',

		'lists:element-creation-guide/catalog-step',
		'lists:element-creation-guide/description-step',
		'lists:element-creation-guide/detail-step'
	],
];
