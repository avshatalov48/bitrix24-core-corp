<?php

return [
	'extensions' => [
		'apptheme',
		'assets/icons',
		'type',
		'toast',
		'loc',
		'utils/date',
		'utils/date/formats',
		'utils/object',
		'utils/skeleton',
		'qrauth/utils',

		'layout/pure-component',
		'layout/ui/counter-view',
		'layout/ui/simple-list/items/extended',
		'layout/ui/user/avatar',

		'tasks:layout/task/create/opener',

		'tokens',
		'ui-system/blocks/chips/chip-status',
		'ui-system/form/buttons',
		'ui-system/layout/card',
		'ui-system/typography/heading',
		'ui-system/typography/text',
		'ui-system/blocks/link',

		'statemanager/redux/connect',
		'statemanager/redux/slices/users',

		'tariff-plan-restriction',
		'tasks:entry',
		'tasks:enum',
		'tasks:layout/deadline-pill',
		'tasks:statemanager/redux/slices/flows',
		'tasks:flow-list/simple-list/items/type',

		'require-lazy',
	],
	'bundle' => [
		'./src/flow',
		'./src/flow-content',
		'./src/flow-similar-content',
		'./src/flow-promo-content',
		'./src/flow-disabled-content',
		'./src/flow-content-chooser',
		'./src/flows-information-card',
	],
];
