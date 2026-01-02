<?php

return [
	'extensions' => [
		'loc',
		'type',
		'tokens',
		'toast',
		'ui-system/typography/text',
		'ui-system/layout/area',
		'ui-system/layout/card',
		'ui-system/layout/box',
		'ui-system/layout/dialog-footer',
		'ui-system/blocks/icon',
		'ui-system/blocks/link',
		'ui-system/form/inputs/string',
		'ui-system/form/buttons/button',
		'bottom-sheet',
		'asset-manager',
		'layout/pure-component',
		'ui-system/form/inputs/input',
		'selector/widget/entity/intranet/department',
		'selector/widget/entity/socialnetwork/user',
		'utils/skeleton',
		'statemanager/redux/slices/users',
		'intranet:statemanager/redux/slices/employees',
		'statemanager/redux/batched-actions',
		'statemanager/redux/store',
		'rest/run-action-executor',
		'layout/ui/status-box',
	],
	'bundle' => [
		'./src/permissions-box',
		'./src/api',
		'./src/selector-card/base',
		'./src/selector-card/department',
		'./src/selector-card/user',
	],
];



