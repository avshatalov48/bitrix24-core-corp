<?
IncludeModuleLangFile(__FILE__);

Class extranet extends CModule
{
	var $MODULE_ID = "extranet";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	var $errors;

	function extranet()
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
			$this->MODULE_VERSION = EXTRANET_VERSION;
			$this->MODULE_VERSION_DATE = EXTRANET_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("EXTRANET_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("EXTRANET_MODULE_DESC");
	}

	function DoInstall()
	{
		global $DB, $DBType, $DOCUMENT_ROOT, $APPLICATION;
		$APPLICATION->ResetException();

		if(!CBXFeatures::IsFeatureEditable("Extranet"))
		{
			$this->errors = array(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
			$APPLICATION->ThrowException(implode("<br>", $this->errors));

			$GLOBALS["errors"] = $this->errors;
		}
		else
		{
			if ($this->InstallDB())
			{
				$this->InstallEvents();
				$this->InstallFiles();
			}
			$APPLICATION->IncludeAdminFile(GetMessage("EXTRANET_INSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/extranet/install/step1.php");
		}

	}

	function InstallDB($arParams = array())
	{

		global $DB, $DBType, $APPLICATION;

		$this->errors = array();

		RegisterModule("extranet");

		RegisterModuleDependences('main', 'OnBeforeProlog', 'extranet', 'CExtranet', 'ExtranetRedirect', 200);
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetFeaturesList', 'extranet', 'CExtranet', 'ModifyGroupDefaultFeatures', 100);
		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetGroupUpdate', 'extranet', 'CExtranet', 'OnBeforeSocNetGroupUpdateHandler', 100);
		RegisterModuleDependences('socialnetwork', 'OnSocNetGroupUpdate', 'extranet', 'CExtranet', 'OnSocNetGroupUpdateHandler', 100);
		RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupAdd', 'extranet', 'CExtranet', 'OnSocNetUserToGroupAdd');
		RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'extranet', 'CExtranet', 'OnSocNetUserToGroupUpdate');
		RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'extranet', 'CExtranet', 'OnSocNetUserToGroupDelete');
		RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'OnUserDelete', 10);
		RegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'extranet', 'CExtranet', 'OnSocNetGroupDelete');
		RegisterModuleDependences('main', 'onBeforeUserAdd', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
		RegisterModuleDependences('main', 'onBeforeUserUpdate', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
		RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnDelete');
		RegisterModuleDependences('main', 'OnUserLogout', 'extranet', 'CExtranet', 'OnUserLogout');
		RegisterModuleDependences('socialnetwork', 'OnGetProfileView', 'extranet', 'CExtranet', 'OnGetProfileView');

/*
		// there is UF_PUBLIC user field creation in the bitrix:extranet wizard

		$rsUserTypeEntity = CUserTypeEntity::GetList(array(), array("ENTITY_ID"=>"USER", "FIELD_NAME"=>"UF_PUBLIC"));
		$arUserTypeEntity = $rsUserTypeEntity->Fetch();
		if (!$arUserTypeEntity)
		{
			$ob = new CUserTypeEntity();
			$arFields = array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_PUBLIC',
				'USER_TYPE_ID' => 'boolean',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'I',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'N',
				'SETTINGS' => array(
					'DISPLAY' => 'CHECKBOX',
				),
			);

			$FIELD_ID = $ob->Add($arFields);
			if (!$FIELD_ID)
				return false;
		}

*/

		return true;
	}

	function InstallFiles($arParams = array())
	{
		global $APPLICATION;

		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/install/gadgets", $_SERVER["DOCUMENT_ROOT"]."/bitrix/gadgets", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/install/wizards", $_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/install/services", $_SERVER["DOCUMENT_ROOT"]."/bitrix/services", true, true);
		}

		CUrlRewriter::Add(array(
			"CONDITION" => "#^/stssync/contacts_extranet/#",
			"RULE" => "",
			"ID" => "bitrix:stssync.server",
			"PATH" => "/bitrix/services/stssync/contacts_extranet/index.php",
		));

		CUrlRewriter::Add(array(
			"CONDITION" => "#^/stssync/contacts_extranet_emp/#",
			"RULE" => "",
			"ID" => "bitrix:stssync.server",
			"PATH" => "/bitrix/services/stssync/contacts_extranet_emp/index.php",
		));

		CUrlRewriter::Add(array(
			"CONDITION" => "#^/stssync/tasks_extranet/#",
			"RULE" => "",
			"ID" => "bitrix:stssync.server",
			"PATH" => "/bitrix/services/stssync/tasks_extranet/index.php",
		));

		CUrlRewriter::Add(array(
			"CONDITION" => "#^/stssync/calendar_extranet/#",
			"RULE" => "",
			"ID" => "bitrix:stssync.server",
			"PATH" => "/bitrix/services/stssync/calendar_extranet/index.php",
		));

		return true;
	}

	function InstallEvents()
	{
		global $DB;

		$sIn = "'EXTRANET_WG_TO_ARCHIVE', 'EXTRANET_WG_FROM_ARCHIVE', 'EXTRANET_INVITATION'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] <= 0)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/install/events/set_events.php");
		}
		return true;
	}


	function DoUninstall()
	{
		global $DB, $APPLICATION, $step;

		$step = IntVal($step);
		if($step<2)
			$APPLICATION->IncludeAdminFile(GetMessage("EXTRANET_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/install/unstep1.php");
		elseif($step==2)
		{
			$APPLICATION->ResetException();
			if ($this->UnInstallDB(array('admin' => 'Y', 'savedata' => $_REQUEST['savedata'])))
			{
				if($_REQUEST["savedata"] != "Y")
					$this->UnInstallEvents();

				$this->UnInstallFiles();
			}
			$APPLICATION->IncludeAdminFile(GetMessage("EXTRANET_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/install/unstep2.php");
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		$errors = false;
		if($arParams['savedata'] != 'Y')
		{
			COption::RemoveOption("extranet", "extranet_site");
		}

		if (is_array($errors))
		{
			$APPLICATION->ThrowException(implode(' ', $errors));
			return false;
		}

		CAgent::RemoveModuleAgents('extranet');
		UnRegisterModuleDependences('main', 'OnBeforeProlog', 'extranet', 'CExtranet', 'ExtranetRedirect');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetFeaturesList', 'extranet', 'CExtranet', 'ModifyGroupDefaultFeatures');
		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetGroupUpdate', 'extranet', 'CExtranet', 'OnBeforeSocNetGroupUpdateHandler');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetGroupUpdate', 'extranet', 'CExtranet', 'OnSocNetGroupUpdateHandler');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupAdd', 'extranet', 'CExtranet', 'OnSocNetUserToGroupAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'extranet', 'CExtranet', 'OnSocNetUserToGroupUpdate');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'extranet', 'CExtranet', 'OnSocNetUserToGroupDelete');
		UnRegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'OnUserDelete');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'extranet', 'CExtranet', 'OnSocNetGroupDelete');
		UnRegisterModuleDependences('main', 'onBeforeUserAdd', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
		UnRegisterModuleDependences('main', 'onBeforeUserUpdate', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
		UnRegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnDelete');
		UnRegisterModuleDependences('main', 'OnUserLogout', 'extranet', 'CExtranet', 'OnUserLogout');
		UnRegisterModuleDependences('socialnetwork', 'OnGetProfileView', 'extranet', 'CExtranet', 'OnGetProfileView');

		UnRegisterModule('extranet');

		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		$sIn = "'EXTRANET_WG_TO_ARCHIVE', 'EXTRANET_WG_FROM_ARCHIVE', 'EXTRANET_INVITATION'";
		$DB->Query("DELETE FROM b_event_message WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFilesEx("/bitrix/wizards/bitrix/extranet/");
		return true;
	}

}
?>