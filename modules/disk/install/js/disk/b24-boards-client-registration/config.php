<?php

use Bitrix\Main\Loader;
use Bitrix\Disk\Document\BoardsHandler;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$serviceName = '';
if (Loader::includeModule('disk'))
{
	$serviceName = BoardsHandler::getName();
}

return [
	'js' => 'dist/disk.b24-boards-client-registration.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.dialogs.messagebox',
		'ui.forms',
		'ui.layout-form',
		'ui.buttons',
		'ui.alerts',
	],
	'skip_core' => false,
	'settings' => [
		'serviceName' => $serviceName,
	],
];