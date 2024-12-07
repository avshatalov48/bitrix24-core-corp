<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/access-rights-wrapper.bundle.css',
	'js' => 'dist/access-rights-wrapper.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.accessrights',
	],
	'skip_core' => true,
];
