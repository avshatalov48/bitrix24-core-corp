<?php

return [
	'extensions' => [
		'type',
		'utils/array',
		'im:messenger/lib/logger',
		'im:messenger/lib/helper',
		'im:messenger/controller/sidebar-v2/loc',
		'im:messenger/const',
		'im:messenger/lib/rest',
		'im:messenger/loc',
		'im:messenger/provider/services/analytics',
		'im:messenger/provider/services/chat',
		'selector/widget/entity/socialnetwork/user',
		'im:messenger/lib/params',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/emitter',
		'im:messenger/controller/sidebar-v2/user-actions/base',
		'im:messenger/controller/selector/member',
	],
	'bundle' => [
		'./src/participants-selector',
		'./src/rest-service',
	],
];
