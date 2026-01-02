<?php

return [
	'extensions' => [
		'type',
		'tokens',
		'elements-stack',

		'ui-system/typography/text',
		'ui-system/blocks/avatar',

		'utils/date/duration',
		'utils/object',

		'layout/pure-component',
	],
	'bundle' => [
		'./src/constants',
		'./src/elements-stack-steps',
		'./src/step',
		'./src/stack',
		'./src/stick',

		'./src/block/text',
		'./src/block/text-stub',
		'./src/block/duration',
		'./src/block/avatar',
		'./src/block/avatar-stub',
		'./src/block/counter',
	],
];
