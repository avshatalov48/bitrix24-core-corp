<?php

return [
	'extensions' => [
		'im:lib/theme',
		'im:messenger/core/embedded',
		'im:messenger/controller/search/experimental',
		'im:messenger/lib/converter/ui/layout',
		'im:messenger/lib/logger',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/ui/notification'
	],
	'bundle' => [
		'./src/controller',
		'./src/view',
	],
];