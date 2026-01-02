<?php

return [
	'extensions' => [
		'type',
		'utils/uuid',
		'im:messenger/const',
		'im:messenger/lib/utils',
		'im:messenger/lib/helper',
		'im:messenger/lib/feature',
		'im:messenger/lib/date-formatter',
		'im:messenger/lib/state-manager/vuex-manager/mutation-handlers-waiter',
		'im:messenger/model/recent/default-element',
		'im:messenger/model/recent/search/model',
	],
	'bundle' => [
		'./src/model',
		'./src/normalizer',
	],
];
