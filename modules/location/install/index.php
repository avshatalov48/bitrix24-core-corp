<?php

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if(class_exists("location")) return;

Class location extends CModule
{
	var $MODULE_ID = "location";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $errors = [];

	public function __construct()
	{
		$arModuleVersion = [];

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = Loc::getMessage('LOCATION_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('LOCATION_MODULE_DESCRIPTION');
	}

	public function DoInstall()
	{
		global $APPLICATION;

		$this->InstallFiles();
		$this->InstallDB();
		$this->InstallEvents();

		RegisterModule("location");
		$GLOBALS["errors"] = $this->errors;
		$this->setDefaultFormatCode();
		$APPLICATION->IncludeAdminFile(Loc::getMessage("LOCATION_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/location/install/step1.php");
	}

	public function setDefaultFormatCode()
	{
		if(!\Bitrix\Main\Loader::includeModule('location'))
		{
			return;
		}

		$event = new Event("location", "onInitialFormatCodeSet");
		$event->send();
		$results = $event->getResults();
		$formatCode = Bitrix\Location\Infrastructure\FormatCode::getDefault();

		if (is_array($results) && !empty($results))
		{
			foreach ($results as $result)
			{
				if ($result->getType() !== EventResult::SUCCESS)
					continue;

				$params = $result->getParameters();

				if(isset($params["formatCode"]))
				{
					$formatCode = $params["formatCode"];
					break;
				}
			}
		}

		Bitrix\Location\Infrastructure\FormatCode::setCurrent($formatCode);
	}

	public function DoUninstall()
	{
		global $APPLICATION, $step;
		$step = intval($step);

		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(Loc::getMessage("LOCATION_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/location/install/unstep1.php");
		}
		elseif($step == 2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			UnRegisterModule("location");
			$this->UnInstallFiles();
			$this->UnInstallEvents();
			\CAgent::RemoveModuleAgents('location');

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(Loc::getMessage("LOCATION_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/location/install/unstep2.php");
		}

		return true;
	}

	public function InstallDB()
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(!$DB->Query("SELECT 'x' FROM b_location", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/location/install/db/".$DBType."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
		}
	}

	public function UnInstallDB($arParams = Array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if($DB->Query("SELECT 'x' FROM b_location", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/location/install/db/".$DBType."/uninstall.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
		}
	}

	public function InstallFiles($arParams = array())
	{
		if($_ENV["COMPUTERNAME"] !== 'BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/location/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		}

		return true;
	}

	public function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"] !== 'BX')
		{
			DeleteDirFilesEx("/bitrix/js/location/");
		}

		return true;
	}

	public function InstallEvents()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler("ui", "onUIFormInitialize", "location", "\\Bitrix\\Location\\Infrastructure\\EventHandler", "onUIFormInitialize");
	}

	public function UnInstallEvents()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler("ui", "onUIFormInitialize", "location", "\\Bitrix\\Location\\Infrastructure\\EventHandler", "onUIFormInitialize");
	}
}
?>