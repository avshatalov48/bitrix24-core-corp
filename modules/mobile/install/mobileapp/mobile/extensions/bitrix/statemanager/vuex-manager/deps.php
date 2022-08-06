<?php

return [
	'extensions' => [
		'utils/uuid',
		'utils/logger',
		'statemanager/vuex',
	],
	'bundle' => [
		'./src/storage/base',
		'./src/storage/shared-storage',
		'./src/mutation-manager',
		'./src/vuex-manager',
	]
];