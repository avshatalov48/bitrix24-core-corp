<?php

return [
	'extensions' => [
		'im:messenger/controller/recent/lib',
		'utils/object',
		'im:messenger/const',
		'im:messenger/cache',
		'im:messenger/provider/rest',
		'im:messenger/lib/logger',
		'im:messenger/lib/converter',
		'im:messenger/provider/rest',
		'im:messenger/lib/emitter',
		'im:messenger/lib/counters',
		'im:messenger/lib/integration/immobile/calls',
	],
	'bundle' => [
		'./src/recent',
	],
];