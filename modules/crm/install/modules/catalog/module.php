<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Main\ModuleManager::isModuleInstalled('catalog'))
{
	if (!function_exists('OnModuleInstalledEvent'))
	{
		function OnModuleInstalledEvent($id)
		{
			foreach (GetModuleEvents("main", "OnModuleInstalled", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id));
		}
	}

	$CModule = new CModule();
	if($Module = $CModule->CreateModuleObject("catalog"))
	{
		OnModuleInstalledEvent('catalog');
		$result = true;

		if(!Main\ModuleManager::isModuleInstalled('bitrix24') || !defined('BX24_HOST_NAME'))
			$result = $Module->InstallFiles();

		if ($result)
			$result = $Module->InstallDB();
		if ($result)
			$result = $Module->InstallEvents();
		if (!$result)
		{
			$errMsg[] = Loc::getMessage('CRM_CANT_INSTALL_CATALOG');
			$bError = true;
			return;
		}
		unset($Module);
	}
	unset($CModule);
}

if (!Main\ModuleManager::isModuleInstalled('catalog'))
{
	$errMsg[] = Loc::getMessage('CRM_CATALOG_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!Main\Loader::includeModule('catalog'))
{
	$errMsg[] = Loc::getMessage('CRM_CATALOG_NOT_INCLUDED');
	$bError = true;
	return;
}
