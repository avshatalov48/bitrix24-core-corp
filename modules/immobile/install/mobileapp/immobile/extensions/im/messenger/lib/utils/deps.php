<?php

return [
	'extensions' => [
		'loc',
		'utils/date',
		'utils/date/formats',
		'layout/ui/friendly-date/time-ago-format',
		'im:messenger/lib/date-formatter',
	],
	'bundle' => [
		'./src/user',
		'./src/date',
		'./src/object',
	],
];