<?php

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/recognize-links.bundle.css',
	'js' => 'dist/recognize-links.bundle.js',
	'rel' => [
		'main.core',
	],
	'settings' => [
		'netUrl' => Loader::includeModule('socialservices') ? \CBitrix24NetOAuthInterface::NET_URL : '',
		'isImInstalled' =>  ModuleManager::isModuleInstalled('im'),
	],
	'skip_core' => false,
];
