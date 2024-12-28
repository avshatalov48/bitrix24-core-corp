<?php

return [
	'extensions' => [
		'alert',
        'analytics',
		'asset-manager',
		'feature',
		'haptics',
		'utils/string',
		'utils/function',
		'selector/widget/entity/socialnetwork/user',
		'statemanager/redux/slices/users',
		'statemanager/redux/store',
		'tariff-plan-restriction',
		'tasks:enum',
		'tasks:layout/online',
		'tasks:layout/task/create/opener',
		'tasks:layout/task/view-new/ui/extra-settings',
		'tasks:loc',
		'tasks:layout/dod',
		'tasks:statemanager/redux/slices/groups',
		'tasks:statemanager/redux/slices/tasks',
		'toast',
		'ui-system/blocks/icon',
	],
	'bundle' => [
		'./src/error',
	],
];
