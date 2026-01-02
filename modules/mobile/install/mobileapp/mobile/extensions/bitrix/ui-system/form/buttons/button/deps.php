<?php

return [
	'extensions' => [
		'type',
		'tokens',
		'utils/type',
		'utils/object',
		'utils/string',
		'utils/enums/base',
		'utils/validation',
		'utils/enums/style',
		'ui-system/blocks/icon',
		'ui-system/typography/text',
		'layout/ui/loaders/spinner',
	],
	'bundle' => [
		'./src/size-enum',
		'./src/design-enum',
		'./src/state-decorator',
	],
];
