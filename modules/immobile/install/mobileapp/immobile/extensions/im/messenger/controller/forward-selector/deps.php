<?php

return [
	'extensions' => [
		'apptheme',
		'loc',
		'im:messenger/controller/search/experimental',
		'im:messenger/lib/converter',
		'im:messenger/lib/logger',
		'im:messenger/lib/ui/selector',
		'im:messenger/lib/ui/notification'
	],
	'bundle' => [
		'./src/selector',
		'./src/view',
	],
];