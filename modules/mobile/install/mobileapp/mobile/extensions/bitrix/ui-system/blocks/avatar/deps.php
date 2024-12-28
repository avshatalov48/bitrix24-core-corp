<?php

return [
	'extensions' => [
		'type',
		'tokens',
		'feature',
		'assets/icons',
		'asset-manager',
		'utils/url',
		'utils/type',
		'utils/object',
		'utils/enums/base',
		'layout/ui/safe-image',
		'layout/ui/user/enums',
		'layout/pure-component',
		'layout/ui/user/empty-avatar',
		'statemanager/redux/store',
		'statemanager/redux/connect',
		'statemanager/redux/slices/users',
	],
	'bundle' => [
		'./src/elements/base',
		'./src/elements/native',
		'./src/elements/layout',
		'./src/enums/shape-enum',
		'./src/enums/entity-type-enum',
		'./src/enums/element-type-enum',
		'./src/enums/accent-gradient-enum',
		'./src/enums/native-placeholder-type-enum',
		'./src/providers/redux',
		'./src/providers/selector',
	]
];
