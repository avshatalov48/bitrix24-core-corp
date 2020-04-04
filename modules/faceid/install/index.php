<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("faceid")) return;

Class faceid extends CModule
{
	var $MODULE_ID = "faceid";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	function faceid()
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
			$this->MODULE_VERSION = FACEID_VERSION;
			$this->MODULE_VERSION_DATE = FACEID_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("FACEID_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("FACEID_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("FACEID_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/install/step1.php");
		}
		elseif($step == 2)
		{
			$this->InstallDB(Array(
				'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"]
			));
			$this->InstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("FACEID_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/install/step2.php");
		}
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;
		if (strlen($params['PUBLIC_URL']) > 0 && strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = Array();
			}
			$this->errors[] = GetMessage('FACEID_CHECK_PUBLIC_PATH');
		}

		if (!$DB->Query("SELECT 'x' FROM b_faceid_tracking_visitors WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/install/db/".strtolower($DB->type)."/install.sql");
		}

		RegisterModuleDependences("crm", "OnAfterCrmControlPanelBuild", "faceid", "\\Bitrix\\FaceId\\FaceId", "insertIntoCrmMenu");

		// users' workday
		RegisterModuleDependences("main", "OnAfterUserUpdate", "faceid", "\\Bitrix\\Faceid\\UsersTable", "onUserPhotoChange");
		RegisterModuleDependences("main", "OnAfterUserAdd", "faceid", "\\Bitrix\\Faceid\\UsersTable", "onUserPhotoChange");
		RegisterModuleDependences("main", "OnBeforeUserUpdate", "faceid", "\\Bitrix\\Faceid\\UsersTable", "onUserPhotoDelete");

		// app password
		RegisterModuleDependences("main", "OnApplicationsBuildList", "faceid", "\\Bitrix\\FaceId\\TrackingWorkdayApplication", "OnApplicationsBuildList");

		// deleting faces
		RegisterModuleDependences("main", "OnAfterUserDelete", "faceid", "\\Bitrix\\FaceId\\UsersTable", "onUserDelete");

		// rest api
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'faceid', 'Bitrix\FaceId\Api\Face', 'onRestServiceBuildDescription');

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		RegisterModule("faceid");

		COption::SetOptionString("faceid", "portal_url", $params['PUBLIC_URL']);

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/install/services", $_SERVER["DOCUMENT_ROOT"]."/bitrix/services", true, true);

		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("B24_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/faceid/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("FACEID_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/faceid/install/unstep2.php");
		}
	}

	function UnInstallDB($arParams = Array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;

		if (isset($arParams['savedata']) && !$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/faceid/install/db/mysql/uninstall.sql");

		UnRegisterModuleDependences("crm", "OnAfterCrmControlPanelBuild", "faceid", "\\Bitrix\\FaceId\\FaceId", "insertIntoCrmMenu");

		// users' workday
		UnRegisterModuleDependences("main", "OnAfterUserUpdate", "faceid", "\\Bitrix\\Faceid\\UsersTable", "onUserPhotoChange");
		UnRegisterModuleDependences("main", "OnAfterUserAdd", "faceid", "\\Bitrix\\Faceid\\UsersTable", "onUserPhotoChange");
		UnRegisterModuleDependences("main", "OnBeforeUserUpdate", "faceid", "\\Bitrix\\Faceid\\UsersTable", "onUserPhotoDelete");

		// app password
		UnRegisterModuleDependences("main", "OnApplicationsBuildList", "faceid", "\\Bitrix\\FaceId\\TrackingWorkdayApplication", "OnApplicationsBuildList");

		// deleting faces
		UnRegisterModuleDependences("main", "OnAfterUserDelete", "faceid", "\\Bitrix\\FaceId\\UsersTable", "onUserDelete");

		if(is_array($this->errors))
			$arSQLErrors = $this->errors;

		if(!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		}

		UnRegisterModule("faceid");

		return true;
	}

	function UnInstallFiles($arParams = array())
	{
		return true;
	}
}
?>