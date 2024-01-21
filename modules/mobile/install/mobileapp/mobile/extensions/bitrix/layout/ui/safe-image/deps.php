<?php

return [
	'extensions' => [
		'apptheme',
		'utils/validation',
		'layout/polyfill',
	],
	'bundle' => [
		'./src/safe-image',
		'./src/shimmed-safe-image',
	],
];
