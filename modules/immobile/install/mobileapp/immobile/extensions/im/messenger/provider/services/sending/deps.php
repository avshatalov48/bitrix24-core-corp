<?php

return [
	'extensions' => [
		'native/filesystem',
		'type',
		'utils/file',
		'utils/function',
		'utils/uuid',
		'uploader/client',
		'im:messenger/lib/helper',
		'im:messenger/const',
		'im:messenger/lib/logger',
		'im:messenger/lib/di/service-locator',
		'im:messenger/lib/ui/notification',
		'im:messenger/lib/params',
		'im:messenger/provider/services/sending/file',
		'im:messenger/provider/services/sending/files',
		'im:messenger/provider/services/sending/upload-manager',
		'im:messenger/provider/services/sending/upload-task',
		'im:messenger/provider/services/analytics',
	],
	'bundle' => [
		'./src/file',
		'./src/files',
		'./src/service',
		'./src/upload-manager',
		'./src/upload-task',
	],
];
