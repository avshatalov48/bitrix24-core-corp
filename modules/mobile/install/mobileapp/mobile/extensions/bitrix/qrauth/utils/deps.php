<?php

return [
	'components' => [
		'qrcodeauth',
	],
	'extensions' => [
		'loc',
		'require-lazy',
		'utils/page-manager',
	],
	'bundle' => [
		'./src/manager'
	]
];
