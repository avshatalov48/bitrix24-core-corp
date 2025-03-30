<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/structure-components.bundle.css',
	'js' => 'dist/structure-components.bundle.js',
	'rel' => [
		'main.popup',
		'ui.icon-set.api.vue',
		'main.sidepanel',
		'main.core',
		'ui.hint',
	],
	'skip_core' => false,
];