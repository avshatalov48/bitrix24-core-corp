<?php

return [
	'extensions' => [
		'haptics',
		'loc',
		'im:lib/theme',
		'im:messenger/assets/common',
		'im:messenger/lib/ui/base/item',
		'im:messenger/lib/emitter',
		'im:messenger/const',
		'im:messenger/lib/element',
		'im:messenger/lib/date-formatter',
	],
	'bundle' => [
		'./src/controller',
		'./src/reaction-item',
		'./src/user-list',
		'./src/view',
	],
];