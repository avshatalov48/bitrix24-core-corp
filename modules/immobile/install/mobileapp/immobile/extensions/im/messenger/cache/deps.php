<?php

return [
	'extensions' => [
		'type',
		'utils/function',
		'utils/object',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/lib/params',
	],
	'bundle' => [
		'./src/shared-storage/base',
		'./src/shared-storage/base-recent',
		'./src/shared-storage/chat-recent',
		'./src/shared-storage/copilot-recent',
		'./src/shared-storage/draft',
		'./src/native/share-dialog',
		'./src/simple-wrapper/map-cache',
	],
];