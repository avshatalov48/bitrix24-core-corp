<?php

return [
	'extensions' => [
		'tokens',
		'animation',
		'utils/file',
		'utils/date/dynamic-date-formatter',
		'utils/date/formats',
		'utils/date/moment',
		'utils/color',

		'assets/icons',

		'layout/pure-component',
		'layout/ui/safe-image',
		'layout/ui/simple-list/items/base',

		'ui-system/typography/text',
		'ui-system/typography/bbcodetext',
		'ui-system/blocks/icon',
		'ui-system/blocks/badges/counter',
		'ui-system/blocks/folder/icon',
		'ui-system/blocks/file/preview',
		'ui-system/blocks/avatar',

		'statemanager/redux/store',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users/selector',
		'disk:statemanager/redux/slices/files/selector',
		'disk:statemanager/redux/slices/settings',
		'disk:user-actions/open-chat',
		'disk:statemanager/redux/slices/storages',
	],
	'bundle' => [
		'./src/file-content',
		'./src/file-view',
		'./src/action-menu',
	],
];