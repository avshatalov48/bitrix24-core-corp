<?php

return [
	'extensions' => [
		'layout/ui/stateful-list',
		'layout/ui/stateful-list/type-generator',
		'layout/ui/search-bar',
		'ui-system/blocks/status-block',
		'ui-system/layout/box',
		'user/profile',
		'loc',

		'asset-manager',
		'tokens',

		'statemanager/redux/store',
		'statemanager/redux/slices/users',
		'statemanager/redux/batched-actions',

		'intranet:simple-list/items',
		'intranet:user-list',
		'intranet:statemanager/redux/slices/employees/selector',
		'intranet:statemanager/redux/slices/employees/observers/stateful-list-observer',
		'intranet:invite-opener-new',
		'intranet:analytics',
	],
];
