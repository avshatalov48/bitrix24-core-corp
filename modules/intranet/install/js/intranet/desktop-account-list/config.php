<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/desktop-account-list.bundle.css',
	'js' => 'dist/desktop-account-list.bundle.js',
	'rel' => [
		'ui.popupcomponentsmaker',
		'ui.avatar',
		'main.popup',
		'ui.dialogs.messagebox',
		'im.v2.lib.desktop-api',
		'main.core.events',
		'main.core',
	],
	'skip_core' => false,
];
