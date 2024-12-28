<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/onboarding.bundle.css',
	'js' => 'dist/onboarding.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'sign.tour',
		'ui.banner-dispatcher',
		'ui.buttons',
		'ui.design-tokens',
		'ui.icon-set.api.core',
	],
	'skip_core' => false,
];
