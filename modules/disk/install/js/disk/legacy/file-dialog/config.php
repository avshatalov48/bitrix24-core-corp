<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/disk/file_dialog.js',
	'css' => '/bitrix/js/disk/css/file_dialog.css',
	'lang' => '/bitrix/modules/disk/lang/' . LANGUAGE_ID . '/install/js/file_dialog.php',
	'rel' => ['core', 'popup', 'disk.legacy.disk', 'ui.design-tokens'],
];
