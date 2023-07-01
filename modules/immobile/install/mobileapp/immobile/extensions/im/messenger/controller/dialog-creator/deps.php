<?php

return [
	'extensions' => [
		'type',
		'loc',
		'files/entry',
		'files/converter',
		'im:chat/selector/chat',
		'im:messenger/core',
		'im:messenger/assets/common',
		'im:messenger/const',
		'im:messenger/controller/dialog-selector',
		'im:messenger/controller/search',
		'im:messenger/lib/emitter',
		'im:messenger/lib/element',
		'im:messenger/lib/params',
		'im:messenger/lib/search/adapters/base',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/ui/base/buttons',
		'im:messenger/lib/ui/base/list',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/dialog-creator',
		'./src/dialog-dto',
		'./src/navigation-selector',
		'./src/recipient-selector',
		'./src/dialog-info',
		'./src/view/navigation-selector',
		'./src/view/recipient-selector',
		'./src/view/dialog-info',

	],
];