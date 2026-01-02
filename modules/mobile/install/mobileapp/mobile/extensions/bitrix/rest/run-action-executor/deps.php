<?php

return [
	'extensions' => [
		'utils/function',
		'utils/object',
		'utils/storage',
		'utils/logger',
		'storage-cache',
	],
	'bundle' => [
		'./cache',
		'./src/request-manager',
	],
];
