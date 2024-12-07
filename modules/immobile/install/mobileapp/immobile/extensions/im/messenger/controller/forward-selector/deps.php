<?php

return [
	'extensions' => [
		'loc',
		'im:lib/theme',
		'im:messenger/controller/search/experimental',
		'im:messenger/lib/converter',
		'im:messenger/lib/logger',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/ui/notification',
		'im:messenger/lib/feature',
	],
	'bundle' => [
		'./src/selector',
		'./src/view',
	],
];
