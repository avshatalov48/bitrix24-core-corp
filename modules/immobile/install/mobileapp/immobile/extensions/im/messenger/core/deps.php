<?php

return [
	'extensions' => [
		'im:messenger/lib/settings',
		'im:messenger/db/repository',
		'im:messenger/db/model-writer',
		'im:messenger/db/update',
		'im:messenger/table',
		'im:messenger/cache',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/application',
	],
];