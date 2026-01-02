<?php

return [
	'extensions' => [
		'im:lib/theme',
		'im:messenger/loc',
		'im:messenger/controller/search/experimental',
		'im:messenger/lib/converter/ui/layout',
		'im:messenger/lib/logger',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/ui/notification',
	],
	'bundle' => [
		'./src/selector',
		'./src/view',
	],
];
