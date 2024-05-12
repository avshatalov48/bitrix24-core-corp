<?php

return [
	'extensions' => [
		'apptheme',
		'loc',
		'type',
		'haptics',
		'notify-manager',
		'utils/url',
		'im:messenger/assets/common',
		'im:messenger/const',
		'im:messenger/controller/user-profile',
		'im:messenger/controller/forward-selector',
		'im:messenger/lib/params',
		'im:messenger/lib/logger',
		'im:messenger/lib/feature',
		'im:messenger/lib/ui/notification',
		'im:messenger/controller/dialog/lib/helper/text',
	],
	'bundle' => [
		'./src/action',
		'./src/action-type',
		'./src/icons',
		'./src/menu',
		'./src/message',
		'./src/reaction',
		'./src/view',
	],
];