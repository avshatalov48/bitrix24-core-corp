<?php

return [
	'extensions' => [
		'assets/icons',

		'utils/url',
		'utils/color',
		'utils/object',
		'utils/validation',

		'layout/ui/safe-image',
		'selector/widget/factory',
		'user/profile/view-profile-backdrop',
	],
	'bundle' => [
		'./src/user-selected-list',
		'./src/selection-manager',
		'./src/backdrop',
	],
];
