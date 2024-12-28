<?php

return [
	'extensions' => [
		'tokens',
		'loc',

		'layout/ui/list/base-more-menu',
		'layout/ui/list/base-sorting',
		'layout/ui/list/base-filter',

		'ui-system/blocks/icon',
		'assets/icons/types',

		'disk:statemanager/redux/slices/settings',
	],
	'bundle' => [
		'./src/more-menu',
		'./src/filter',
		'./src/sorting',
	],
];
