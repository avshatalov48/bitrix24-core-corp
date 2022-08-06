<?php

return [
	'extensions' => [
		'type',
		'im:chat/utils',
		'im:chat/messengercommon',
		'im:chat/dataconverter',
		'im:messenger/service',
		'im:messenger/lib/params',
		'im:messenger/lib/helper',
	],
	'bundle' => [
		'./src/recent',
		'./src/search',
		'./src/dialog/dialog',
		'./src/dialog/message/base',
		'./src/dialog/message/text',
		'./src/dialog/message/image',
		'./src/dialog/message/audio',
		'./src/dialog/message/unsupported',
	],
];