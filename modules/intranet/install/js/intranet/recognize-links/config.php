<?php
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
		'netUrl' => \Bitrix\Main\Loader::includeModule('socialservices') ? \CBitrix24NetOAuthInterface::NET_URL : '',
		'isImInstalled' =>  \Bitrix\Main\ModuleManager::isModuleInstalled('im')
	],
	'skip_core' => false,
];
