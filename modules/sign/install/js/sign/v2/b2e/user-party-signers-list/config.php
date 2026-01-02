<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-party-signers-list.bundle.css',
	'js' => 'dist/user-party-signers-list.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.b2e.user-party',
	],
	'skip_core' => false,
];
