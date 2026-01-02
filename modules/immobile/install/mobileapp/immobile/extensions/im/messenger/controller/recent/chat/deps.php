<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'im:messenger/cache',
		'im:messenger/const',
		'im:messenger/lib/emitter',
		'im:messenger/lib/logger',
		'im:messenger/provider/data',
		'im:messenger/provider/rest',
		'im:messenger/controller/recent/lib',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/converter/ui/recent',
		'im:messenger/lib/integration/callmobile/call-manager',
		'im:messenger/lib/counters/counter-manager/messenger/actions',
		'im:messenger/lib/counters/counter-manager/messenger/sender',
	],
	'bundle' => [
		'./src/recent',
		'../../../cache/src/native/share-dialog',
	],
];