<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/parties.bundle.css',
	'js' => 'dist/parties.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.b2e.company-selector',
		'sign.v2.b2e.document-validation',
		'sign.v2.b2e.representative-selector',
		'sign.v2.helper',
	],
	'skip_core' => false,
];
