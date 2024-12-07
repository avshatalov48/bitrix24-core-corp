<?php
return [
	'extensions' => [
		'tokens',
		'ui-system/layout/card',
		'ui-system/typography/text',
		'ui-system/blocks/icon',
		'layout/pure-component',
		'layout/ui/fields/theme',

		'tasks:layout/fields/date-plan',
		'tasks:layout/fields/date-plan/theme/air/redux-content',

		'statemanager/redux/connect',
		'tasks:statemanager/redux/slices/tasks/selector',
	],
	'bundle' => [
		'./src/redux-content',
	],
];