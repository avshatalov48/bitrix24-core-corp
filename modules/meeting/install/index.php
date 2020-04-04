<?
IncludeModuleLangFile(__FILE__);

if(class_exists("meeting")) return;
Class meeting extends CModule
{
	var $MODULE_ID = "meeting";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "N";

	function meeting()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("MEETING_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("MEETING_MODULE_DESCRIPTION");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_meeting WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/db/".strtolower($DB->type)."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			RegisterModule("meeting");

			RegisterModuleDependences("tasks", "OnTaskDelete", "meeting", "CMeetingEventHandlers", "OnTaskDelete");
			RegisterModuleDependences("calendar", "OnAfterCalendarConvert", "meeting", "CMeetingEventHandlers", "OnAfterCalendarConvert");
			RegisterModuleDependences("main", "OnBeforeUserDelete", "meeting", "CMeetingEventHandlers", "OnBeforeUserDelete");

			return true;
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/db/".strtolower($DB->type)."/uninstall.sql");

			if ($this->errors === false && CModule::IncludeModule('forum'))
			{
				$dbRes = CSite::GetList($by='sort', $order='asc', array());
				while ($arSite = $dbRes->Fetch())
				{
					$forumId = COption::GetOptionInt('meeting', 'comments_forum_id', 0, SITE_ID);
					$forumId = ($forumId > 0 ? $forumId : COption::GetOptionInt('meeting', 'comments_forum_id', 0));
					if ($forumId > 0)
					{
						CForumNew::Delete($forumId);
					}
				}
			}
		}

		UnRegisterModuleDependences("calendar", "OnAfterCalendarConvert", "meeting", "CMeetingEventHandlers", "OnAfterCalendarConvert");
		UnRegisterModuleDependences("tasks", "OnTaskDelete", "meeting", "CMeetingEventHandlers", "OnTaskDelete");

		UnRegisterModule("meeting");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

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

	function InstallFiles($arParams = array())
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", True, True);
		}
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION, $USER, $step;
		$step = IntVal($step);

		if(!$USER->IsAdmin())
			return;

		if (!check_bitrix_sessid())
			$step = 1;

		if(!CBXFeatures::IsFeatureEditable("Meeting"))
		{
			$this->errors = array(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
			$APPLICATION->ThrowException(implode("<br>", $this->errors));

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("MEETING_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/step2.php");
		}
		elseif($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("MEETING_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/step1.php");
		}
		elseif($step==2)
		{
			if ($_REQUEST['install_public'])
			{
				global $meeting_folder;
				require_once("index_public.php");
			}

			$this->InstallDB(array());
			$this->InstallFiles(array());
			CBXFeatures::SetFeatureEnabled("Meeting", true);

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("MEETING_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/step2.php");
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $USER, $step;
		if($USER->IsAdmin())
		{
			$step = IntVal($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("MEETING_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));
				$this->UnInstallFiles();
				CBXFeatures::SetFeatureEnabled("Meeting", false);
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("MEETING_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/meeting/install/unstep2.php");
			}
		}
	}
}
?>