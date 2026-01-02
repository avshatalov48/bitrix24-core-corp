<?php

return [
	'extensions' => [
		'im:chat/messengercommon',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/converter/data/recent',
	],
	'bundle' => [
		'./src/message-manager',
	],
];