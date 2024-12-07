<?php

return [
	'extensions' => [
		'loc',
		'im:messenger/lib/rest-manager',
		'im:messenger/lib/logger',
		'im:messenger/const',
		'im:messenger/lib/params',
		'im:messenger/provider/rest',
		'im:messenger/lib/di/service-locator',
	],
	'bundle' => [
		'./src/release-view',
	],
];