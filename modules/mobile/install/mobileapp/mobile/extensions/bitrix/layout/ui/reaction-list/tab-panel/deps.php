<?php

return [
	'extensions' => [
		'loc',
		'tokens',
		'ui-system/blocks/chips/chip-inner-tab',
		'layout/ui/scroll-view',
		'ui-system/blocks/reaction/icon',

		'statemanager/redux/slices/reactions/selector',
		'statemanager/redux/connect',
	],
	'bundle' => [
		'./src/tab-panel',
		'./src/redux-panel',
	],
];