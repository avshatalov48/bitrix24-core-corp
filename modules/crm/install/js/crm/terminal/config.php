<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;

return [
	'css' => 'dist/terminal.bundle.css',
	'js' => 'dist/terminal.bundle.js',
	'rel' => [
		'main.core',
		'ui.qrauthorization',
	],
	'skip_core' => false,
	'settings' => [
		'qr' => Crm\Terminal\AuthLink::get(),
	]
];