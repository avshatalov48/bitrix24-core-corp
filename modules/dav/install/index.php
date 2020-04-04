<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class dav extends CModule
{
	var $MODULE_ID = "dav";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function dav()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("DAV_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("DAV_INSTALL_DESCRIPTION");
	}

	function InstallDB($install_wizard = true)
	{
		global $DB, $DBType, $APPLICATION;

		$arCurPhpVer = Explode(".", PhpVersion());
		if (IntVal($arCurPhpVer[0]) < 5)
			return true;

		$errors = null;
		if (!$DB->Query("SELECT 'x' FROM b_dav_locks", true))
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/install/db/".$DBType."/install.sql");

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors)); 
			return false;
		}

		RegisterModule("dav");
		RegisterModuleDependences("main", "OnBeforeProlog", "main", "", "", 50, "/modules/dav/prolog_before.php");

		RegisterModuleDependences("main", "OnAfterUserAdd", "dav", "CDavExchangeMail", "handleUserChange");
		RegisterModuleDependences("main", "OnAfterUserUpdate", "dav", "CDavExchangeMail", "handleUserChange");
		RegisterModuleDependences("main", "OnBeforeUserTypeDelete", "dav", "CDavExchangeMail", "handleUserTypeDelete");
		RegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Dav\Application', "onApplicationsBuildList", 100, "modules/dav/lib/application.php"); // main here is not a mistake


		$arUrlRewrite = array();
		if (file_exists($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php"))
			include($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php");

		$rule = array(
			"CONDITION" => "#^/\\.well-known#",
			"RULE" => "",
			"ID" => "",
			"PATH" => "/bitrix/groupdav.php",
		);

		$canAdd = true;
		foreach ($arUrlRewrite as $r)
		{
			if ($r["CONDITION"] == $rule["CONDITION"])
			{
				$canAdd = false;
				break;
			}
		}

		if ($canAdd)
		{
			CUrlRewriter::Add($rule);
		}


		return true;
	}

	function UnInstallDB($arParams = Array())
	{
		global $DB, $DBType, $APPLICATION;

		$errors = null;
		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/install/db/".$DBType."/uninstall.sql");

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors)); 
				return false;
			}
		}

		UnRegisterModuleDependences("main", "OnBeforeProlog", "main", "", "", "/modules/dav/prolog_before.php");

		UnRegisterModuleDependences("main", "OnAfterUserAdd", "dav", "CDavExchangeMail", "handleUserChange");
		UnRegisterModuleDependences("main", "OnAfterUserUpdate", "dav", "CDavExchangeMail", "handleUserChange");
		UnRegisterModuleDependences("main", "OnBeforeUserTypeDelete", "dav", "CDavExchangeMail", "handleUserTypeDelete");
		UnRegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Dav\Application', "onApplicationsBuildList", "modules/dav/lib/application.php"); // main here is not a mistake

		UnRegisterModule("dav");

		return true;
	}

	function InstallEvents()
	{
		$arCurPhpVer = Explode(".", PhpVersion());
		if (IntVal($arCurPhpVer[0]) < 5)
			return true;

		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles()
	{
		$arCurPhpVer = Explode(".", PhpVersion());
		if (IntVal($arCurPhpVer[0]) < 5)
			return true;

		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/install/bitrix",  $_SERVER["DOCUMENT_ROOT"]."/bitrix", true, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		}

		return true;
	}

	function InstallPublic()
	{
		$arCurPhpVer = Explode(".", PhpVersion());
		if (IntVal($arCurPhpVer[0]) < 5)
			return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $step;

		$this->errors = null;

		$curPhpVer = PhpVersion();
		$arCurPhpVer = Explode(".", $curPhpVer);
		if (IntVal($arCurPhpVer[0]) < 5)
		{
			$this->errors = array(GetMessage("DAV_PHP_L439", array("#VERS#" => $curPhpVer)));
		}
		elseif (!CBXFeatures::IsFeatureEditable("DAV"))
		{
			$this->errors = array(GetMessage("DAV_ERROR_EDITABLE"));
		}
		else
		{
			$this->InstallFiles();
			$this->InstallDB(false);
			$this->InstallEvents();
			$this->InstallPublic();
			CBXFeatures::SetFeatureEnabled("DAV", true);
		}

		$GLOBALS["errors"] = $this->errors;
		$APPLICATION->IncludeAdminFile(GetMessage("DAV_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/install/step2.php");
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;

		$this->errors = null;

		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("DAV_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			
			$this->UnInstallEvents();

			CBXFeatures::SetFeatureEnabled("DAV", false);
			$GLOBALS["errors"] = $this->errors;

			$APPLICATION->IncludeAdminFile(GetMessage("DAV_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/install/unstep2.php");
		}
	}

	function GetModuleRightList()
	{
		$arr = array(
			"reference_id" => array("D", "R", "W"),
			"reference" => array(
					"[D] ".GetMessage("DAV_PERM_D"),
					"[R] ".GetMessage("DAV_PERM_R"),
					"[W] ".GetMessage("DAV_PERM_W")
				)
			);
		return $arr;
	}
}
