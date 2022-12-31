<?php

return [
	'extensions' => [
		"reload/listeners", // reload vars after reload script
		"im:chat/uploader", // chat uploader
		"im:chat/background", // chat background processes (message, reaction, read, .etc)
		"tasks:task/checklist/uploader", // task checklist uploader
		"tasks:task/uploader", // task uploader
		"tasks:task/background", // task background processes (view, .etc)
		'project/background', // project background processes (view, .etc)
		"tasks:task", // task background processes (view, .etc)
		"disk/background", // task background processes (view, .etc)
		"rest",
		'livefeed',
		'livefeed/publicationqueue',
		'comments/uploadqueue',
		'catalog/background/barcodescanner',
		'push/listener',
		'crm:in-app-url/background',
		'crm:background/timeline-notifications',
	],
];
