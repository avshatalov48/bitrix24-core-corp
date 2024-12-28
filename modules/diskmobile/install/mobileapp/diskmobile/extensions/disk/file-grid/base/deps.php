<?php

return [
	'extensions' => [
		'alert',
		'haptics',
		'loc',
		'tokens',
		'type',
		'toast',
		'assets/icons',
		'rest/run-action-executor',
		'utils/object',
		'utils/file',
		'qrauth/utils',

		'ui-system/layout/box',
		'ui-system/blocks/status-block',
		'ui-system/popups/aha-moment',

		'layout/ui/search-bar',
		'layout/ui/stateful-list',
		'layout/ui/stateful-list/type-generator',
		'layout/ui/loading-screen',

		'statemanager/redux/slices/users',
		'statemanager/redux/batched-actions',
		'statemanager/redux/store',

		'disk:cache',
		'disk:enum',
		'disk:pull',
		'disk:uploader',
		'disk:file-grid/navigation',
		'disk:simple-list/items',
		'disk:statemanager/redux/slices/files',
		'disk:statemanager/redux/slices/storages',
		'disk:statemanager/redux/slices/files/selector',
		'disk:statemanager/redux/slices/files/observers/stateful-list',
		'disk:statemanager/redux/slices/settings',
		'disk:dialogs/create-folder',
	],
];