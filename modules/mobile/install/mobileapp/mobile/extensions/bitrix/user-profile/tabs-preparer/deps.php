<?php

return [
	'components' => [
		'calendar:calendar.event.list',
		'livefeed.postform', // for gratitude creation
		'intranet:user.list',
	],
	'extensions' => [
		'disk:file-grid/profile-files',
		'loc',
		'statemanager/redux/slices/users/selector',
		'statemanager/redux/store',
		'user-profile/common-tab',
		'user-profile/const',
	],
];
