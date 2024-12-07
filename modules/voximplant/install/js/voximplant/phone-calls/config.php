<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('rest');
\Bitrix\Main\Loader::includeModule('crm');

return [
	'css' => 'dist/phone-calls.bundle.css',
	'js' => 'dist/phone-calls.bundle.js',
	'rel' => [
		'intranet.desktop-download',
		'main.core.events',
		'main.core',
		'im.v2.lib.desktop-api',
		'main.popup',
		'ui.dialogs.messagebox',
		'applayout',
		'crm_form_loader',
		'phone_number',
	],
	'skip_core' => false,
];