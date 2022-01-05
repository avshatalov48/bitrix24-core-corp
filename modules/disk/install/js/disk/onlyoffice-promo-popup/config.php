<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/disk.onlyoffice-promo-popup.bundle.js',
	'rel' => [
		'main.core',
		'ui.buttons',
		'disk',
		'disk_information_popups',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];