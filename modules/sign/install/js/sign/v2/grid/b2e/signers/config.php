<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/signers.bundle.css',
	'js' => 'dist/signers.bundle.js',
	'rel' => [
		'sign.v2.api',
		'ui.dialogs.messagebox',
		'main.core',
		'ui.buttons',
		'main.popup',
	],
	'skip_core' => false,
];
