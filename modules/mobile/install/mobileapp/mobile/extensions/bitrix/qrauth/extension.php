<?php
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

return [
	'cloud'=> ModuleManager::isModuleInstalled("bitrix24") && COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'
];