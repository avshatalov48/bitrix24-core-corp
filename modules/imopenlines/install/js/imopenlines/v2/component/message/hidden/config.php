<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/hidden.bundle.css',
	'js' => 'dist/hidden.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.directives.hint',
		'im.v2.component.message.base',
		'im.v2.lib.date-formatter',
		'im.v2.component.message.elements',
		'im.v2.lib.parser',
	],
	'skip_core' => true,
];
