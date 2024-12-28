<?php

return [
	'extensions' => [
		'loc',
		'selector/widget/entity',
		'selector/providers/nested-department-provider',
		'utils/object',
		'tokens',
		'selector/widget/entity/tree-selectors/shared/navigator',
		'selector/widget/entity/tree-selectors/nested-department-selector',
	],
	'bundle' => [
		'./src/entity',
		'./src/navigator',
	],
];
