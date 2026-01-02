<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/note-popup.bundle.css',
	'js' => 'dist/note-popup.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.vue3',
		'booking.lib.resolvable',
		'booking.component.popup',
		'booking.component.button',
	],
	'skip_core' => false,
];
