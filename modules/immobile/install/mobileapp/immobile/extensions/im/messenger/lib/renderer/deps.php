<?php

return [
	'extensions' => [
		'im:messenger/lib/converter',
		'im:messenger/lib/logger',
	],
	'bundle' => [
		'./src/base/worker',
		'./src/recent',
	],
];