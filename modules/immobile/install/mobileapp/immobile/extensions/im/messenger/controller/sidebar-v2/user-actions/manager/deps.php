<?php

return [
	'extensions' => [
		'im:messenger/lib/logger',
		'im:messenger/const',
		'im:messenger/lib/emitter',
		'im:messenger/lib/params',
		'im:messenger/lib/rest',
		'im:messenger/controller/sidebar-v2/user-actions/base',
	],
	'bundle' => [
		'./src/rest-service',
	],
];
