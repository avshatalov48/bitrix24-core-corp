<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/onboarding-popup.bundle.css',
	'js' => 'dist/onboarding-popup.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.vue3',
		'crm.integration.ui.banner-dispatcher',
		'ui.lottie',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.design-tokens',
		'ui.buttons',
	],
	'skip_core' => false,
];
