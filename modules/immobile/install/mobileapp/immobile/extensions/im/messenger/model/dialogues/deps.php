<?php

return [
	'extensions' => [
		'type',
		'utils/object',
		'utils/url',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/params',
		'im:messenger/lib/permission-manager',
		'im:messenger/lib/utils',
		'utils/array',
	],
	'bundle' => [
		'./src/model',
		'./src/validator',
		'./src/default-element',
		'./src/collab/model',
		'./src/collab/validator',
		'./src/collab/default-element',
		'./src/copilot/model',
		'./src/copilot/validator',
		'./src/copilot/default-element',
		'./src/ai-assistant/model',
		'./src/ai-assistant/validator',
		'./src/ai-assistant/default-element',
	],
];
