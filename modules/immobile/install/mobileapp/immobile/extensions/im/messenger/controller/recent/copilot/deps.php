<?php

return [
	'extensions' => [
		'type',
		'im:messenger/controller/recent/lib',
		'utils/object',
		'im:messenger/const',
		'im:messenger/cache',
		'im:messenger/provider/rest',
		'im:messenger/lib/logger',
		'im:messenger/lib/converter',
		'im:messenger/provider/rest',
		'im:messenger/lib/emitter',
		'im:messenger/lib/params',
		'im:messenger/lib/counters',
	],
	'bundle' => [
		'./src/recent',
	],
];
