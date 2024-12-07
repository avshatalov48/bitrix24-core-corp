<?php

return [
	'extensions' => [
		'type',
		'logger',
		'utils/object',
		'im:messenger/lib/feature',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/vuex',
		'./src/vuex/writer',
		'./src/vuex/recent',
		'./src/vuex/dialog',
		'./src/vuex/user',
		'./src/vuex/file',
		'./src/vuex/reaction',
		'./src/vuex/message',
		'./src/vuex/temp-message',
		'./src/vuex/queue',
		'./src/vuex/pin-message',
		'./src/vuex/application',
		'./src/vuex/copilot',
		'./src/vuex/sidebar/file',
	],
];