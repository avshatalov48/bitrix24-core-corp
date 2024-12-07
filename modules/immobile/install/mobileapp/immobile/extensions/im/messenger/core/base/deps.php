<?php

return [
	'extensions' => [
		'statemanager/vuex',
		'statemanager/vuex-manager',
		'utils/object',
		'im:messenger/lib/params',
		'im:messenger/lib/feature',
		'im:messenger/db/repository',
		'im:messenger/db/model-writer',
		'im:messenger/model',
		'im:messenger/db/update',
		'im:messenger/table',
		'im:messenger/lib/logger',
		'im:messenger/lib/state-manager/vuex-manager/mutation-manager',
		'im:messenger/const',
	],
	'bundle' => [
		'./src/application',
	],
];