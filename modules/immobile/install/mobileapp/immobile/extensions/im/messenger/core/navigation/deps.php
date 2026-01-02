<?php

return [
	'extensions' => [
		'entity-ready',
		'statemanager/vuex-manager',
		'im:messenger/const',
		'im:messenger/core/base',
		'im:messenger/db/update',
		'im:messenger/db/repository',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
	],
	'bundle' => [
		'./src/application',
	],
];
