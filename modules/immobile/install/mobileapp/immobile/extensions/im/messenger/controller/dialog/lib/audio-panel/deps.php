<?php

return [
	'extensions' => [
		'loc',
		'type',
		'utils/function',
		'utils/object',
		'im:messenger/const',
		'im:messenger/lib/helper',
		'im:messenger/lib/logger',
		'im:messenger/lib/utils',
		'im:messenger/lib/visibility-manager',
		'im:messenger/view/dialog',
		'im:messenger/provider/services/analytics',
	],
	'bundle' => [
		'./src/audio-panel',
		'./src/return-to-audio',
	],
];
