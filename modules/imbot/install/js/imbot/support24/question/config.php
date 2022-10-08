<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/question.bundle.css',
	'js' => 'dist/question.bundle.js',
	'rel' => [
		'ui.vue.vuex',
		'ui.notification',
		'main.loader',
		'ui.info-helper',
		'main.core.events',
		'ui.fonts.opensans',
		'ui.buttons',
		'ui.design-tokens',
		'ui.vue',
		'main.core',
		'ui.forms',
	],
	'skip_core' => false,
];