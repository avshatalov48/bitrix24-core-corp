<?php

if(class_exists("xdimport"))
{
	return;
}

IncludeModuleLangFile(__FILE__);

Class xdimport extends CModule
{
	var $MODULE_ID = "xdimport";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";
	var $errors;

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}

		$this->MODULE_NAME = GetMessage("XDI_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("XDI_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		$this->InstallFiles();
		$this->InstallDB();
		$GLOBALS['APPLICATION']->IncludeAdminFile(GetMessage("XDI_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/step1.php"); 
	}
	
	function InstallDB()
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();
	
		$this->errors = false;
		if(!$DB->Query("SELECT 'x' FROM b_xdi_lf_scheme", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/xdimport/install/db/".$connection->getType()."/install.sql");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		} 
		
		RegisterModule("xdimport");
		CModule::IncludeModule("xdimport");
		RegisterModuleDependences("main", "OnBuildGlobalMenu", "xdimport", "CXDImport", "OnBuildGlobalMenu");
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'xdimport', 'CXDILFEventHandlers', 'OnFillSocNetAllowedSubscribeEntityTypes');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'xdimport', 'CXDILFEventHandlers', 'OnFillSocNetLogEvents');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('socialnetwork', 'onLogIndexGetContent', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\Log', 'onIndexGetContent');
		$eventManager->registerEventHandler('socialnetwork', 'onLogCommentIndexGetContent', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\LogComment', 'onIndexGetContent');
		$eventManager->registerEventHandler('socialnetwork', 'onContentViewed', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\ContentViewHandler', 'onContentViewed');

		return true;
	}
	
	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		return true;
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;

		$step = intval($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("XDI_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			$APPLICATION->IncludeAdminFile(GetMessage("XDI_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/unstep2.php");
		}
	}
	
	function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB;

		$connection = \Bitrix\Main\Application::getConnection();
		
		$this->errors = false;
		
		if (!$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/xdimport/install/db/".$connection->getType()."/uninstall.sql");

		if(!empty($this->errors))
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		} 

		UnRegisterModuleDependences("main", "OnBuildGlobalMenu", "xdimport", "CXDImport", "OnBuildGlobalMenu");
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'xdimport', 'CXDILFEventHandlers', 'OnFillSocNetAllowedSubscribeEntityTypes');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'xdimport', 'CXDILFEventHandlers', 'OnFillSocNetLogEvents');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unregisterEventHandler('socialnetwork', 'onLogIndexGetContent', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\Log', 'onIndexGetContent');
		$eventManager->unregisterEventHandler('socialnetwork', 'onLogCommentIndexGetContent', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\LogComment', 'onIndexGetContent');
		$eventManager->unregisterEventHandler('socialnetwork', 'onContentViewed', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\ContentViewHandler', 'onContentViewed');

		UnRegisterModule("xdimport");

		return true;
	}
	
	function UnInstallFiles($arParams = array())
	{
		// Delete files
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components");

		return true;
	}
}
