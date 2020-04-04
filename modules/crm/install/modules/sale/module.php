<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Main\ModuleManager::isModuleInstalled('sale'))
{
	if (!function_exists('OnModuleInstalledEvent'))
	{
		function OnModuleInstalledEvent($id)
		{
			foreach (GetModuleEvents("main", "OnModuleInstalled", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id));
		}
	}

	global $DB;

	// clean before install
	if (!$DB->TableExists('b_sale_order'))
	{
		$arTablesToDrop = array();
		if ($DB->TableExists('b_sale_delivery2paysystem'))
			$arTablesToDrop[] = 'sale_delivery2paysystem';
		if ($DB->TableExists('b_sale_person_type_site'))
			$arTablesToDrop[] = 'sale_person_type_site';
		if ($DB->TableExists('b_sale_store_barcode'))
			$arTablesToDrop[] = 'sale_store_barcode';
		foreach ($arTablesToDrop as $tableName)
		{			
			$DB->Query('DROP TABLE if exists b_$tableName', true);
		}

		unset($arTablesToDrop, $strSql, $strSql1);
	}

	$CModule = new CModule();
	/** @var sale $Module */
	if($Module = $CModule->CreateModuleObject("sale"))
	{
		OnModuleInstalledEvent('sale');
		$result = true;

		if(!Main\ModuleManager::isModuleInstalled('bitrix24') || !defined('BX24_HOST_NAME'))
		{
			$result = $Module->InstallFiles();
		}

		if ($result)
		{
			$result = $Module->InstallDB();
		}

		if ($result)
		{
			$Module->InstallEvents();
		}

		if (!$result)
		{
			$errMsg[] = Loc::getMessage('CRM_INSTALL_SALE_CANT_INSTALL');
			$bError = true;
			return;
		}
		unset($Module);
	}
}

if (!Main\ModuleManager::isModuleInstalled('sale'))
{
	$errMsg[] = Loc::getMessage('CRM_INSTALL_SALE_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!Main\Loader::includeModule('sale'))
{
	$errMsg[] = Loc::getMessage('CRM_INSTALL_SALE_NOT_INCLUDED');
	$bError = true;
	return;
}
