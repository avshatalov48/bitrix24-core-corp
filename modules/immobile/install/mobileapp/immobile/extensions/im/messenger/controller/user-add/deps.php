<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'im:lib/theme',
		'im:messenger/loc',
		'im:messenger/lib/di/service-locator',
		'im:messenger/provider/services/chat',
		'im:messenger/const',
		'im:messenger/controller/search',
		'im:messenger/lib/element/chat-title',
		'im:messenger/lib/element/chat-avatar',
		'im:messenger/lib/emitter',
		'im:messenger/lib/helper/dialog',
		'im:messenger/lib/params',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/logger',
		'layout/ui/widget-header-button',
	],
	'bundle' => [
		'./src/view',
	],
];
