<?php

return [
	'extensions' => [
		'type',
		'loc',
		'utils/object',
		'im:messenger/core',
		'im:messenger/const',
		'im:messenger/provider/rest',
		'im:messenger/lib/logger',
		'im:messenger/lib/converter',
		'im:messenger/lib/page-navigation',
		'im:messenger/lib/helper',
		'im:messenger/provider/rest',
		'im:messenger/lib/params',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/emitter',
		'im:messenger/lib/counters',
		'im:messenger/cache/share-dialog',
		'im:messenger/lib/integration/immobile/calls',
		'im:chat/timer',
		'user/profile',
	],
	'bundle' => [
		'./src/item-action',
		'./src/renderer',
		'./src/recent',
	],
];