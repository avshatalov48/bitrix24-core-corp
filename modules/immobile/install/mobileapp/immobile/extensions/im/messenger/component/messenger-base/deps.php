<?php

return [
	'extensions' => [
		'entity-ready',
		'im:messenger/const',
		'im:messenger/loc',
		'im:messenger/lib/anchors',
		'im:messenger/lib/feature',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/visibility-manager',
		'im:messenger/lib/counters/tab-counters',
		'im:messenger/lib/di/service-locator',
		'im:messenger/provider/pull/anchor',
		'im:messenger/provider/services/connection',
		'im:messenger/provider/services/queue',
		'im:messenger/provider/services/sending',
		'im:messenger/lib/integration/callmobile/call-manager',
		'im:messenger/provider/services/sync/service',
		'im:messenger/lib/counters/counter-manager/messenger/handler',
	],
];
