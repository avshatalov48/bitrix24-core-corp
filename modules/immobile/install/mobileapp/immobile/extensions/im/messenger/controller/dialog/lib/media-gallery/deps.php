<?php

return [
	'extensions' => [
		'type',
		'tokens',
		'utils/object',
		'media-gallery',
		'utils/validation',
		'im:messenger/lib/helper',
		'im:messenger/const',
		'im:messenger/lib/date-formatter',
		'im:messenger/lib/logger',
		'im:messenger/lib/ui/alert',
		'im:messenger/controller/dialog/lib/message-menu',
		'im:messenger/view/dialog',
		'im:messenger/lib/visibility-manager',
		'im:messenger/lib/emitter',
		'im:messenger/lib/parser',
	],
	'bundle' => [
		'./src/media-menu',
	],
];
