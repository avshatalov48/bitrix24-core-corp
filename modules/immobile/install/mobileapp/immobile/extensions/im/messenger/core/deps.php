<?php

return [
	'extensions' => [
		'im:messenger/lib/params',
		'im:messenger/lib/feature',
		'im:messenger/db/repository',
		'im:messenger/db/model-writer',
		'im:messenger/db/update',
		'im:messenger/table',
		'im:messenger/lib/logger',
		'im:messenger/const',
	],
	'bundle' => [
		'./src/application',
	],
];