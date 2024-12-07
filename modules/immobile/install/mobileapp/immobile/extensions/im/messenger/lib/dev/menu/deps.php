<?php

return [
	'extensions' => [
		'alert',
		'apptheme',
		'type',
		'utils/logger',
		'require-lazy',
		'layout/ui/banners/banner-button',
		'statemanager/vuex',
		'statemanager/vuex-manager',
		'im:messenger/const',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/element',
		'im:messenger/lib/emitter',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/ui/base/checkbox',
		'im:messenger/provider/rest',
		
	],
	'bundle' => [
		'./src/menu',
		'./src/developer-settings',
		'./src/logging-settings',
		'./src/chat-dialog',
		'./src/chat-dialog-benchmark',
		'./src/vuex-manager',
		'./src/playground',
		'./src/dialog-snippets',
	],
];