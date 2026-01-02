<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/field-add.bundle.css',
	'js' => 'dist/field-add.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.icon-set.api.vue',
		'tasks.v2.component.elements.hover-pill',
	],
	'skip_core' => true,
];
