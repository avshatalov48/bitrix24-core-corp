<?php

return [
	'extensions' => [
		'type',
		'alert',
		'utils/object',
		'im:messenger/loc',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/emitter',
		'im:messenger/lib/page-navigation',
		'im:messenger/provider/rest',
	],
	'bundle' => [
		'./src/service',
		'./src/pin',
		'./src/hide',
	],
];
