<?
IncludeModuleLangFile(__FILE__);

Class xmpp extends CModule
{
	var $MODULE_ID = "xmpp";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	function xmpp()
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
			$this->MODULE_VERSION = XMPP_VERSION;
			$this->MODULE_VERSION_DATE = XMPP_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("XMPP_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("XMPP_MODULE_DESC");
	}

	function InstallDB($arParams = array())
	{
		RegisterModule("xmpp");
		RegisterModuleDependences("socialnetwork", "OnSocNetMessagesAdd", "xmpp", "CXMPPFactory", "OnSocNetMessagesAdd");
		RegisterModuleDependences("im", "OnAfterMessagesAdd", "xmpp", "CXMPPFactory", "OnSocNetMessagesAdd");
		RegisterModuleDependences("im", "OnAfterMessagesUpdate", "xmpp", "CXMPPFactory", "OnImMessagesUpdate");
		RegisterModuleDependences("im", "OnAfterMessagesDelete", "xmpp", "CXMPPFactory", "OnImMessagesUpdate");
		RegisterModuleDependences("im", "OnAfterFileUpload", "xmpp", "CXMPPFactory", "OnImFileUpload");
		RegisterModuleDependences("im", "OnAfterNotifyAdd", "xmpp", "CXMPPFactory", "OnSocNetMessagesAdd");
		RegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Xmpp\XmppApplication', "onApplicationsBuildList", 100, "modules/xmpp/lib/xmppapplication.php"); // main here is not a mistake

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences("socialnetwork", "OnSocNetMessagesAdd", "xmpp", "CXMPPFactory", "OnSocNetMessagesAdd");
		UnRegisterModuleDependences("im", "OnAfterMessagesAdd", "xmpp", "CXMPPFactory", "OnSocNetMessagesAdd");
		UnRegisterModuleDependences("im", "OnAfterMessagesUpdate", "xmpp", "CXMPPFactory", "OnImMessagesUpdate");
		UnRegisterModuleDependences("im", "OnAfterMessagesDelete", "xmpp", "CXMPPFactory", "OnImMessagesUpdate");
		UnRegisterModuleDependences("im", "OnAfterFileUpload", "xmpp", "CXMPPFactory", "OnImFileUpload");
		UnRegisterModuleDependences("im", "OnAfterNotifyAdd", "xmpp", "CXMPPFactory", "OnSocNetMessagesAdd");
		UnRegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Xmpp\XmppApplication', "onApplicationsBuildList", "modules/xmpp/lib/xmppapplication.php"); // main here is not a mistake
		UnRegisterModule("xmpp");
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
		}
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes");

		return true;
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;

		if (IsModuleInstalled("xmpp"))
			return false;
		if (!check_bitrix_sessid())
			return false;

		$this->InstallDB();
		$this->InstallEvents();
		$this->InstallFiles();

		$APPLICATION->IncludeAdminFile(GetMessage("XMPP_INSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/xmpp/install/step.php");
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;

		if (!check_bitrix_sessid())
			return false;

		$this->UnInstallDB();
		$this->UnInstallEvents();
		$this->UnInstallFiles();

		$APPLICATION->IncludeAdminFile(GetMessage("XMPP_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/xmpp/install/unstep.php");
	}
}
?>
