<?php

return [
	'bundle' => [
		'./entity-editor',
		'./entity-manager',
	],
	'extensions' => [
		'loc',
		'apptheme',
		'notify-manager',

		'layout/ui/empty-screen',
		'layout/ui/entity-editor',
		'layout/ui/entity-editor/manager',

		'animation/components/fade-view',

		'lists:entity-detail/tab',
	],
];
