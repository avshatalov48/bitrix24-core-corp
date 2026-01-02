<?php

return [
	'extensions' => [
		'pull/client/events',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/lib/counters/counter-manager/const',
		'im:messenger/lib/counters/counter-manager/storage/writer',
		'im:messenger/provider/push/message-handler/counter',
		'im:messenger/provider/pull/counter',
		'im:messenger/provider/services/sync/fillers/counter',
		'im:messenger/provider/services/read',
	],
];
