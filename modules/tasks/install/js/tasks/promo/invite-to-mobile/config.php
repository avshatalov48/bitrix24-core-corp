<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/invite-to-mobile.bundle.css',
	'js' => 'dist/invite-to-mobile.bundle.js',
	'rel' => [
		'main.core',
		'ui.banner-dispatcher',
		'ui.mobile-promoter',
	],
	'skip_core' => false,
];
