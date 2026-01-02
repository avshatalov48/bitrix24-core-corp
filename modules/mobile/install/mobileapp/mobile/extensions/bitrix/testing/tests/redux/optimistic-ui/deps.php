<?php

return [
	'extensions' => [
		'statemanager/redux/store',
		'statemanager/redux/state-cache',
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'statemanager/redux/slices/users-test/meta',
		'statemanager/redux/change-registry',
		'statemanager/redux/optimistic-ui',
		'utils/logger',
		'utils/type',
		'device/connection',
		'testing',
		'testing/tests/redux/optimistic-ui/playground-slice-users',
	],
	'bundle' => [
		'./playground-slice-users/extension',
		'./playground-slice-users/meta/extension',
		'./playground-slice-users/src/selector',
		'./playground-slice-users/src/thunk',
		'./playground-slice-users/src/data',
	]
];
