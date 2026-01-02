<?php

return [
	'extensions' => [
		'type',
		'entity-ready',
		'utils/array',
		'utils/uuid',
		'utils/object',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/feature',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/lib/emitter',
		'im:messenger/lib/rest',
		'im:messenger/provider/services/sync/service/date',
		'im:messenger/provider/services/sync/service/load',
		'im:messenger/provider/services/sync/service/pull-event-queue',
	],
	'bundle' => [
		'./src/date',
		'./src/load',
		'./src/pull-event-queue',
	],
];
