<?php

return [
	'extensions' => [
		"reload/listeners", // reload vars after reload script
		"im:chat/uploader", // chat uploader
		"im:chat/background", // chat background processes (message, reaction, read, .etc)
		"task/checklist/uploader", // task checklist uploader
		"task/uploader", // task uploader
		"task/background", // task background processes (view, .etc)
		'project/background', // project background processes (view, .etc)
		"task", // task background processes (view, .etc)
		"disk/background", // task background processes (view, .etc)
		"rest",
		'livefeed',
		'livefeed/publicationqueue',
		'comments/uploadqueue',
		'catalog/background/barcodescanner',
		'push/listener',
	]
];
