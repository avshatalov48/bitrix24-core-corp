<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/participant.bundle.css',
	'js' => 'dist/participant.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.components.popup',
		'ui.vue3.components.rich-loc',
		'tasks.v2.const',
		'tasks.v2.component.elements.hint',
		'tasks.v2.component.elements.hover-pill',
		'tasks.v2.component.elements.user-custom-tag-selector',
	],
	'skip_core' => true,
];
