<?php

return [
	'extensions' => [
		'apptheme',
		'type',
		'utils/object',
		'utils/uuid',
		'im:chat/utils',
		'im:chat/messengercommon',
		'im:chat/dataconverter',
		'im:messenger/lib/di/service-locator',
		'im:messenger/provider/rest',
		'im:messenger/const',
		'im:messenger/lib/params',
		'im:messenger/lib/helper',
		'im:messenger/lib/date-formatter',
		'im:messenger/lib/element',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/lib/smile-manager',
		'im:messenger/lib/utils',
	],
	'bundle' => [
		'./src/recent',
		'./src/search',
		'./src/dialog',
		'./src/chat-layout',
	],
];
