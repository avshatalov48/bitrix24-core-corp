<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/registry.bundle.css',
	'js' => 'dist/registry.bundle.js',
	'rel' => [
		'im.v2.application.core',
		'ui.vue3.vuex',
		'imopenlines.v2.const',
		'main.core',
		'im.v2.model',
	],
	'skip_core' => false,
];
