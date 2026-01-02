<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'im:messenger/loc',
		'im:messenger/const',
		'im:messenger/lib/element/chat-title',
		'im:messenger/lib/element/chat-avatar',
		'im:messenger/lib/helper',
		'im:messenger/lib/uuid-manager',
		'im:messenger/lib/notifier',
		'im:messenger/lib/params',
		'im:messenger/lib/parser',
		'im:messenger/lib/emitter',
		'im:messenger/lib/converter/data/message',
		'im:messenger/provider/pull/base',
		'im:messenger/provider/pull/lib/file',
		'im:messenger/provider/data',
		'im:messenger-v2/provider/pull/lib/new-message-manager',
		'im:messenger-v2/provider/pull/lib/input-action-listener',
	],
	'bundle' => [
		'./src/handler',
	],
];
