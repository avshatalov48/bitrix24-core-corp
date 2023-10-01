<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$isCloud = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24') && defined('BX24_HOST_NAME');

return [
	'isCloud' => $isCloud,
];