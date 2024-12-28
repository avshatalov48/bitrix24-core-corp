<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/call-message.bundle.css',
	'js' => 'dist/call-message.bundle.js',
	'rel' => [
		'ui.vue3.directives.hint',
		'main.core',
		'im.v2.component.message.elements',
		'im.v2.component.message.base',
		'im.v2.lib.date-formatter',
		'im.v2.lib.call',
		'im.public',
		'im.v2.const',
		'call.lib.analytics',
	],
	'skip_core' => false,
];
