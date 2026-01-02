<?php

return [
	'extensions' => [
		'utils/uuid',
		'analytics',
		'im:messenger/const',
		'im:messenger/lib/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/visibility-manager',
		'im:messenger/provider/services/message',
		'im:messenger/provider/services/analytics',
		'im:messenger/controller/dialog/chat',
		'im:messenger/controller/dialog/lib/message-menu',
		'im:messenger/controller/dialog/lib/helper/text',
		'im:messenger/controller/dialog/lib/mention/provider',
		'im:messenger/controller/dialog/lib/configurator',
		'im:messenger/lib/converter/ui/message',
	],
	'bundle' => [
		'./src/dialog',
		'./src/component/message-menu',
		'./src/component/mention/manager',
		'./src/component/mention/provider',
	],
];
