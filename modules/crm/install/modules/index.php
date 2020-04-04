<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

//install modules sale & catalog without data.
if (!\Bitrix\Main\ModuleManager::isModuleInstalled('sale'))
{
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/crm/install/modules/sale/module.php");
}

if (!\Bitrix\Main\ModuleManager::isModuleInstalled('catalog'))
{
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/crm/install/modules/catalog/module.php");
}