<?php

return [
	'extensions' => [
		'reload/listeners', // reload vars after reload script
		'im:chat/uploader', // chat uploader
		'im:chat/background', // chat background processes (message, reaction, read, .etc)
		'project/background', // project background processes (view, .etc)
		'disk/background', // task background processes (view, .etc)
		'rest',
		'livefeed',
		'livefeed/publicationqueue',
		'comments/uploadqueue',
		'catalog:background/barcodescanner',
		'push/listener',

		'background/notifications',

		'tasks:background/cache-warmup', // warmup dashboard components and cache for faster render
		'tasks:task', // task background processes (view, .etc)
		'tasks:task/checklist/uploader', // task checklist uploader
		'tasks:task/uploader', // task uploader
		'tasks:task/background', // task background processes (view, .etc)
		'tasks:background/tasks-notifications',

		'crm:in-app-url/background',
		"sign:background",
		'crm:background/crm-notifications',

		'files/background-manager', // files background processes (upload, .etc)
		'ava-menu',

		'intranet:intranet-background',

		'bizproc:background/opener',

		'background/notifications/open-desktop',
		'background/notifications/open-helpdesk',
		'background/notifications/promotion',

		'calendar:background',
	],
];
