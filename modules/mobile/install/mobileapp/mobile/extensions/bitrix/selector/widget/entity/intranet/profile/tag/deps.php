<?php

return [
	'extensions' => [
		'selector/widget/entity',
		'selector/providers/common',
		'rest/run-action-executor',
		'ui-system/blocks/icon',
		'tokens',
		'loc',
		'statemanager/redux/slices/users/thunk',
		'statemanager/redux/store',
	],
	'bundle' => [
		'./src/provider',
	],
];
