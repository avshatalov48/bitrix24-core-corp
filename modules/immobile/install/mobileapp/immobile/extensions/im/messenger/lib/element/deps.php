<?php

return [
	'extensions' => [
		'type',
		'loc',
		'utils/color',
		'im:messenger/core',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/lib/params',
		'im:messenger/lib/date-formatter',
	],
	'bundle' => [
		'./src/chat-avatar/chat-avatar',
		'./src/chat-title/chat-title',
		'./src/dialog/message/base',
		'./src/dialog/message/text',
		'./src/dialog/message/deleted',
		'./src/dialog/message/image',
		'./src/dialog/message/audio',
		'./src/dialog/message/video',
		'./src/dialog/message/file',
		'./src/dialog/message/unsupported',
		'./src/dialog/message/system-text',
		'./src/dialog/message/date-separator',
		'./src/dialog/message/unread-separator',
		'./src/dialog/message-menu/action',
		'./src/dialog/message-menu/menu',
		'./src/dialog/message-menu/reaction',
	],
];