<?php

return [
	'extensions' => [
		'tokens',
		'loc',

		'layout/ui/list/base-more-menu',
		'layout/ui/list/base-sorting',
		'layout/ui/list/base-filter',

		'selector/widget/entity/intranet/department',
		'ui-system/typography/text',
		'ui-system/blocks/icon',
		'assets/icons/types',
	],
	'bundle' => [
		'./src/more-menu',
		'./src/filter',
		'./src/sorting',
		'./src/department-button',
	],
];
