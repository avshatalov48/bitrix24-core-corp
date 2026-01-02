<?php

return [
	'extensions' => [
		'loc',
		'type',
		'tokens',
		'bottom-sheet',
		'in-app-url',
		'utils/skeleton',
		'toast',

		'layout/ui/form',
		'layout/ui/user/avatar',
		'layout/ui/fields/user',

		'statemanager/redux/connect',
		'statemanager/redux/slices/users',

		'calendar:ajax/booking',
		'calendar:event-view-form',
		'calendar:booking/manager',
		'calendar:layout/user-field-air-theme',
	],
	'bundle' => [
		'./src/form',

		'./src/fields/client',
		'./src/fields/manager',
		'./src/fields/resources',
		'./src/fields/services',
		'./src/fields/note',
	],
];
