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
		'sign.v2.b2e.sign-settings-onboarding',
		'sign.v2.api',
		'ui.banner-dispatcher',
		'ui.icon-set.api.core',
		'ui.buttons',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
