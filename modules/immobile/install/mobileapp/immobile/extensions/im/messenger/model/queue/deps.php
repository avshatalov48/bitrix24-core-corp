<?php

return [
	'extensions' => [
		'type',
		'utils/uuid',
		'utils/object',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/model',
		'./src/default-element',
	],
];