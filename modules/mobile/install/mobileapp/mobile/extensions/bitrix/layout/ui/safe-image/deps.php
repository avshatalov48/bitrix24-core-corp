<?php

return [
	'extensions' => [
		'apptheme',
		'tokens',
		'utils/validation',
		'layout/polyfill',
	],
	'bundle' => [
		'./src/safe-image',
		'./src/shimmed-safe-image',
	],
];
