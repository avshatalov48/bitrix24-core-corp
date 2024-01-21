<?php

return [
	'extensions' => [
		'type',
		'utils/function',
		'utils/object',
		'im:messenger/const',
	],
	'bundle' => [
		'./src/shared-storage/base',
		'./src/shared-storage/recent',
		'./src/shared-storage/draft',
		'./src/native/share-dialog',
		'./src/simple-wrapper/map-cache',
	],
];