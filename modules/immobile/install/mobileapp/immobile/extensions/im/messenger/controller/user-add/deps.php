<?php

return [
	'extensions' => [
		'type',
		'loc',
		'im:lib/theme',
		'im:messenger/lib/di/service-locator',
		'im:messenger/const',
		'im:messenger/controller/search',
		'im:messenger/lib/element',
		'im:messenger/lib/emitter',
		'im:messenger/lib/helper/dialog',
		'im:messenger/lib/params',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/logger',
		'im:messenger/lib/rest',
		'layout/ui/widget-header-button',
	],
	'bundle' => [
		'./src/view',
	],
];