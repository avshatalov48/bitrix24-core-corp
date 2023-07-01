<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'im:chat/utils',
		'im:chat/messengercommon',
		'im:chat/dataconverter',
		'im:messenger/core',
		'im:messenger/provider/rest',
		'im:messenger/const',
		'im:messenger/lib/params',
		'im:messenger/lib/helper',
		'im:messenger/lib/date-formatter',
		'im:messenger/lib/element',
	],
	'bundle' => [
		'./src/recent',
		'./src/search',
		'./src/dialog',

	],
];