<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("xdimport")) return;

Class xdimport extends CModule
{
	var $MODULE_ID = "xdimport";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	function xdimport()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = XDI_VERSION;
			$this->MODULE_VERSION_DATE = XDI_VERSION_DATE;
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
	
		$this->errors = false;
		if(!$DB->Query("SELECT 'x' FROM b_xdi_lf_scheme", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/xdimport/install/db/".strtolower($DB->type)."/install.sql");

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

		return true;
	}
	
	function InstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		}
		return true;
	}

	function InstallEvents()
	{
		return true;
	}
	
	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("XDI_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/xdimport/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			$APPLICATION->IncludeAdminFile(GetMessage("XDI_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/xdimport/install/unstep2.php");
		}
	}
	
	function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB, $errors;
		
		$this->errors = false;
		
		if (!$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/xdimport/install/db/".strtolower($DB->type)."/uninstall.sql");

		if(is_array($this->errors))
			$arSQLErrors = array_merge($arSQLErrors, $this->errors);

		if(!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		} 

		UnRegisterModuleDependences("main", "OnBuildGlobalMenu", "xdimport", "CXDImport", "OnBuildGlobalMenu");
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'xdimport', 'CXDILFEventHandlers', 'OnFillSocNetAllowedSubscribeEntityTypes');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'xdimport', 'CXDILFEventHandlers', 'OnFillSocNetLogEvents');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unregisterEventHandler('socialnetwork', 'onLogIndexGetContent', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\Log', 'onIndexGetContent');
		$eventManager->unregisterEventHandler('socialnetwork', 'onLogCommentIndexGetContent', 'xdimport', '\Bitrix\XDImport\Integration\Socialnetwork\LogComment', 'onIndexGetContent');

		UnRegisterModule("xdimport");

		return true;
	}
	
	function UnInstallFiles($arParams = array())
	{
		global $DB;
		
		// Delete files
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components");

		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}
	
	function GetModuleRightList()
	{
		global $MESS;
		$arr = array();
		return $arr;
	}
}
?>