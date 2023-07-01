<?php

return [
	'extensions' => [
		'log',
		'type',
		'utils/object',
		'im:messenger/core',
		'im:messenger/lib/logger',
		'im:messenger/lib/params',
		'im:messenger/const',
	],
	'bundle' => [
		'./src/parser',
		'./src/functions/url',
		'./src/functions/quote',
		'./src/functions/mention',
		'./src/functions/lines',
		'./src/functions/emoji',
		'./src/functions/common',
		'./src/functions/call',
		'./src/functions/slash-command',
		'./src/functions/action',
		'./src/functions/disk',
		'./src/functions/font',
		'./src/functions/image',
		'./src/utils/parsed-elements',
		'./src/utils/utils',
		'./src/elements/dialog/message/quote-active',
		'./src/elements/dialog/message/quote-inactive',
		'./src/elements/dialog/message/text',
	],
];