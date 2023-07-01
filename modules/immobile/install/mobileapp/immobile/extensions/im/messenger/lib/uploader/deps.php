<?php

return [
	'extensions' => [
		'type',
		'uploader/client',
		'im/messenger/const',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/uploader',
		'./src/task',
	],
];