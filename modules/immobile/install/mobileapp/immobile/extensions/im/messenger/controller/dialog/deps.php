<?php

return [
	'extensions' => [
		'type',
		'loc',
		'im:messenger/controller/base',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/page-navigation',
		'im:messenger/lib/converter',
		'im:messenger/cache',
		'im:messenger/service',
		'im:messenger/lib/params',
		'im:messenger/lib/event',
		'im:messenger/push-handler',
		'im:messenger/lib/counters',
		'im:messenger/lib/integration/immobile/calls',
		'im:messenger/lib/element',
		'im:chat/const/background',
		'im:chat/performance',
		'im:chat/utils',
		'im:chat/messengercommon',
		'utils/uuid',
	],
	'bundle' => [
		'./src/dialog',
		'./src/web',
	],
];