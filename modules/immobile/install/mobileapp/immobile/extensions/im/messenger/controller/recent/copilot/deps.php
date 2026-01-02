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
		'im:messenger/provider/rest',
		'im:messenger/lib/emitter',
		'im:messenger/lib/params',
		'im:messenger/lib/helper',
		'im:messenger/lib/counters/counter-manager/messenger/sender',
		'im:messenger/lib/feature',
	],
	'bundle' => [
		'./src/recent',
	],
];
