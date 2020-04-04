<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if(class_exists('translate'))
{
	return;
}

class translate extends \CModule
{
	public $MODULE_ID = 'translate';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_CSS;
	public $MODULE_GROUP_RIGHTS = 'Y';

	public function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", '/', __FILE__);
		$path = substr($path, 0, strlen($path) - strlen('/index.php'));
		include($path.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = Loc::getMessage('TRANS_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('TRANS_MODULE_DESCRIPTION');
	}

	public function InstallDB()
	{
		Main\ModuleManager::registerModule($this->MODULE_ID);

		$eventManager = Main\EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible('main', 'OnPanelCreate', $this->MODULE_ID, 'CTranslateEventHandlers', 'TranslatOnPanelCreate');

		return true;
	}

	public function UnInstallDB()
	{
		\COption::RemoveOption($this->MODULE_ID);

		$eventManager = Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('main', 'OnPanelCreate', $this->MODULE_ID, 'CTranslateEventHandlers', 'TranslatOnPanelCreate');

		Main\ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}

	public function InstallEvents()
	{
		return true;
	}

	public function UnInstallEvents()
	{
		return true;
	}

	public function InstallFiles()
	{
		if ($_ENV['COMPUTERNAME'] != 'BX')
		{
			\CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/translate/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true, true);
			\CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/translate/install/images', $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/translate', true, true);
			\CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/translate/install/themes', $_SERVER['DOCUMENT_ROOT'].'/bitrix/themes', true, true);
		}

		return true;
	}

	public function UnInstallFiles()
	{
		\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/translate/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
		\DeleteDirFilesEx('/bitrix/images/translate/');
		\DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/translate/install/themes/.default/', $_SERVER["DOCUMENT_ROOT"].'/bitrix/themes/.default');//css
		\DeleteDirFilesEx('/bitrix/themes/.default/start_menu/translate/');//start_menu
		\DeleteDirFilesEx('/bitrix/themes/.default/icons/translate/');//icons

		return true;
	}

	public function DoInstall()
	{
		global $APPLICATION;
		$this->InstallDB();
		$this->InstallFiles();
		$APPLICATION->IncludeAdminFile(Loc::getMessage('TRANSLATE_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/translate/install/step.php');
	}

	public function DoUninstall()
	{
		global $APPLICATION;
		$this->UnInstallFiles();
		$this->UnInstallDB();
		$APPLICATION->IncludeAdminFile(Loc::getMessage('TRANSLATE_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/translate/install/unstep.php');
	}
}