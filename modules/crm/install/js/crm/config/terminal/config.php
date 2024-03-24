<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}


return [
	'css' => [
		'dist/terminal.bundle.css',
		'/bitrix/components/bitrix/ui.button.panel/templates/.default/style.css',
	],
	'js' => 'dist/terminal.bundle.js',
	'rel' => [
		'ui.dialogs.messagebox',
		'ui.vue3',
		'ui.switcher',
		'main.popup',
		'ui.label',
		'main.core',
		'ui.vue3.vuex',
		'ui.notification',
	],
	'skip_core' => false,
];
