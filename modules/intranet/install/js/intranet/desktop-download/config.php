<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/desktop-download.bundle.css',
	'js' => 'dist/desktop-download.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'downloadLinks' => \Bitrix\Intranet\Portal::getInstance()->getSettings()->getDesktopDownloadLinks(),
	],
];
