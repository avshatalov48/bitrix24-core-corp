<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-party-refused.bundle.css',
	'js' => 'dist/user-party-refused.bundle.js',
	'rel' => [
		'main.core',
		'ui.switcher',
		'ui.design-tokens',
		'sign.v2.helper',
	],
	'skip_core' => false,
];
