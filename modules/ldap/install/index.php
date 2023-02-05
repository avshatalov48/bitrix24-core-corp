<?php

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

Class ldap extends CModule
{
	var $MODULE_ID = "ldap";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	
	var $errors = array();

	function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = LDAP_VERSION;
			$this->MODULE_VERSION_DATE = LDAP_VERSION_DATE;
		}

		$this->MODULE_NAME = Loc::getMessage("LDAP_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("LDAP_MODULE_DESC");
	}
	
	function CheckLDAP()
	{
		if(!function_exists("ldap_connect"))
		{
			$this->errors[] = Loc::getMessage("LDAP_MOD_INST_ERROR_PHP");
			return false;
		}
		return true;
	}
	
	function InstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		$this->errors = array();
		if ($this->CheckLDAP())
		{
			$errors = false;
			
			if(!$DB->Query("SELECT 'x' FROM b_ldap_server WHERE 1=0", true))
			{
				$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/ldap/install/db/mysql/install.sql");
			}
			
			if (is_array($errors))
			{
				$this->errors = array_merge($this->errors, $errors);
			}
			else 
			{
				RegisterModule("ldap");
				RegisterModuleDependences("main", "OnUserLoginExternal", "ldap", "CLdap", "OnUserLogin", 1);
				RegisterModuleDependences("main", "OnExternalAuthList", "ldap", "CLdap", "OnExternalAuthList");
				RegisterModuleDependences('main', 'OnFindExternalUser', 'ldap', 'CLDAP', 'OnFindExternalUser');
				RegisterModuleDependences('main', 'OnEventLogGetAuditTypes', 'ldap', 'CLDAP', 'onEventLogGetAuditTypes');
			}
		}
		
		if(count($this->errors) > 0)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		return true;		
	}
	
	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		$errors = false;
		if($arParams['savedata']!="Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/ldap/install/db/mysql/uninstall.sql");
			if (!is_array($errors))
				COption::RemoveOption('ldap');
		}
		
		if (!is_array($errors))
		{
			UnRegisterModuleDependences("main", "OnUserLoginExternal", "ldap", "CLdap", "OnUserLogin");
			UnRegisterModuleDependences("main", "OnExternalAuthList", "ldap", "CLdap", "OnExternalAuthList");
			UnRegisterModuleDependences('main', 'OnBeforeProlog', 'ldap', 'CLDAP', 'NTLMAuth');
			UnRegisterModuleDependences('main', 'OnFindExternalUser', 'ldap', 'CLDAP', 'OnFindExternalUser');
			UnRegisterModuleDependences('main', 'OnEventLogGetAuditTypes', 'ldap', 'CLDAP', 'onEventLogGetAuditTypes');
			UnRegisterModule("ldap");
		}
		else
		{
			$APPLICATION->ThrowException(implode("<br>", $errors));
			return false;
		}

		return true;		
	}
	
	function InstallEvents()
	{
		if (!$this->CheckLDAP())
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		$by = "name";
		$order = "asc";
		$dbLang = CLanguage::GetList();
		while($arLang = $dbLang->Fetch())
		{
			$lid = $arLang["LID"];
			IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/bitrix/modules/ldap/install/events.php", $lid);

			$et = new CEventType;
			$et->Add(array(
				"LID" => $lid,
				"EVENT_NAME" => "LDAP_USER_CONFIRM",
				"NAME" => Loc::getMessage("LDAP_USER_CONFIRM_TYPE_NAME"),
				"DESCRIPTION" => Loc::getMessage("LDAP_USER_CONFIRM_TYPE_DESC"),
			));

			$arSites = array();
			$sites = CSite::GetList("name", "asc", Array("LANGUAGE_ID"=> $lid));
			while ($site = $sites->Fetch())
				$arSites[] = $site["LID"];

			if(count($arSites) > 0)
			{
				$mess = new CEventMessage;
				$mess->Add(array(
					"ACTIVE" => "Y",
					"EVENT_NAME" => "LDAP_USER_CONFIRM",
					"LID" => $arSites,
					"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
					"EMAIL_TO" => "#EMAIL#",
					"BCC" => "#BCC#",
					"SUBJECT" => Loc::getMessage("LDAP_USER_CONFIRM_EVENT_NAME"),
					"MESSAGE" => Loc::getMessage("LDAP_USER_CONFIRM_EVENT_DESC", array("#LANGUAGE_ID#" => $lid)),
					"BODY_TYPE" => "text",
				));
			}
		}

		return true;
	}

	function UnInstallEvents()
	{	
		$dbEvent = CEventMessage::GetList('', '', Array("EVENT_NAME" => "LDAP_USER_CONFIRM"));
		while ($arEvent = $dbEvent->Fetch())
			CEventMessage::Delete($arEvent["ID"]);

		$eventType = new CEventType;
		$eventType->Delete("LDAP_USER_CONFIRM");

		return true;
	}
	
	function InstallFiles($arParams = array())
	{
		global $APPLICATION;
		if ($this->CheckLDAP())
		{
			if($_ENV["COMPUTERNAME"]!='BX')
			{
				CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/ldap/install/images", $_SERVER['DOCUMENT_ROOT']."/bitrix/images/ldap");
				CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ldap/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
				CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ldap/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
			}
		}

		if(count($this->errors) > 0)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		return true;		
		
	}
	
	function UnInstallFiles($arParams = array())
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ldap/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ldap/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
		DeleteDirFilesEx("/bitrix/themes/.default/icons/ldap/");//icons
		DeleteDirFilesEx("/bitrix/images/ldap/");//images
		
		return true;
	}

	function DoInstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION;
		$APPLICATION->ResetException();
		if ($this->InstallDB())
		{
			$this->InstallFiles();
			$this->InstallEvents();
		}
		$APPLICATION->IncludeAdminFile(Loc::getMessage("LDAP_INSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/ldap/install/step1.php");
	}

	function DoUninstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step<2)
			$APPLICATION->IncludeAdminFile(Loc::getMessage("LDAP_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/ldap/install/unstep1.php");
		elseif($step==2)
		{
			$APPLICATION->ResetException();
			if ($this->UnInstallDB(array('savedata' => $_REQUEST['savedata'])))
			{
				$this->UnInstallFiles();
				$this->UnInstallEvents();
			}
			$APPLICATION->IncludeAdminFile(Loc::getMessage("LDAP_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/ldap/install/unstep2.php");
		}
	}
}
?>