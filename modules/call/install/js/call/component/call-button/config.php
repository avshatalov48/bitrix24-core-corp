<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/call-button.bundle.css',
	'js' => 'dist/call-button.bundle.js',
	'rel' => [
		'main.core.events',
		'im.v2.lib.local-storage',
		'im.v2.lib.promo',
		'im.public',
		'im.v2.application.core',
		'im.v2.const',
		'im.v2.lib.permission',
		'im.v2.lib.menu',
		'im.v2.lib.call',
		'im.v2.lib.rest',
		'im.v2.lib.feature',
		'call.lib.analytics',
		'call.const',
		'call.component.elements',
		'main.core',
		'call.core',
		'ui.vue3.directives.hint',
	],
	'skip_core' => false,
];
