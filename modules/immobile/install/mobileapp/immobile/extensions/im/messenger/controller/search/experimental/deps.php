<?php

return [
	'extensions' => [
		'loc',
		'utils/string',
		'selector/entity',
		'selector/providers/base',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/lib/params',
		'im:messenger/lib/rest',
		'im:messenger/lib/converter/search',
		'im:messenger/lib/element/chat-avatar',
		'im:messenger/lib/element/chat-avatar',
		'im:messenger/lib/emitter',
		'im:messenger/lib/date-formatter',
	],
	'bundle' => [
		'./src/config',
		'./src/provider',
		'./src/search-item',
		'./src/selector',
		'./src/store-updater',
		'./src/service/local-search-service',
		'./src/service/server-search-service',
		'./src/helper/get-words-from-text',
		'./src/helper/search-date-formatter',
	],
];