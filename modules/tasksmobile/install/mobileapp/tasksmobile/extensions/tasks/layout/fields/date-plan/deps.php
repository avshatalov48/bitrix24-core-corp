<?php
return [
	'extensions' => [
		'loc',
		'type',
		'tokens',
		'assets/icons/types',

		'toast/base',
		'bottom-sheet',
		'layout/ui/menu',
		'layout/pure-component',
		'layout/ui/fields/number',
		'ui-system/layout/box',
		'ui-system/layout/area',
		'ui-system/blocks/icon',
		'ui-system/typography/text',
		'ui-system/form/buttons/button',
		'ui-system/form/inputs/datetime',

		'tasks:layout/fields/date-plan/view',
		'tasks:task/datesResolver',

		'utils/date/formats',
		'utils/date',

		'tasks:statemanager/redux/slices/tasks/selector',
		'tasks:statemanager/redux/slices/groups',
		'statemanager/redux/connect',
	],
	'bundle' => [
		'./src/view',
		'./src/view-redux-content',
		'./src/formatter',
		'./src/dates-resolver',
	],
];
