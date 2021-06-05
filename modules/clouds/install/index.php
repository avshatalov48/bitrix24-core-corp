<?
IncludeModuleLangFile(__FILE__);

if(class_exists("clouds")) return;
Class clouds extends CModule
{
	var $MODULE_ID = "clouds";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("CLO_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("CLO_MODULE_DESCRIPTION");
	}

	function GetModuleTasks()
	{
		return array(
			'clouds_denied' => array(
				"LETTER" => "D",
				"BINDING" => "module",
				"OPERATIONS" => array(
				),
			),
			'clouds_browse' => array(
				"LETTER" => "F",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'clouds_browse',
				),
			),
			'clouds_upload' => array(
				"LETTER" => "U",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'clouds_browse',
					'clouds_upload',
				),
			),
			'clouds_full_access' => array(
				"LETTER" => "W",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'clouds_browse',
					'clouds_upload',
					'clouds_config',
				),
			),
		);
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_clouds_file_bucket WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/db/".mb_strtolower($DB->type)."/install.sql");
		}


		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			$this->InstallTasks();

			RegisterModule("clouds");
			CModule::IncludeModule("clouds");
			RegisterModuleDependences("main", "OnEventLogGetAuditTypes", "clouds", "CCloudStorage", "GetAuditTypes");
			RegisterModuleDependences("main", "OnBeforeProlog", "clouds", "CCloudStorage", "OnBeforeProlog", 90);
			RegisterModuleDependences("main", "OnAdminListDisplay", "clouds", "CCloudStorage", "OnAdminListDisplay");
			RegisterModuleDependences("main", "OnBuildGlobalMenu", "clouds", "CCloudStorage", "OnBuildGlobalMenu");
			RegisterModuleDependences("main", "OnFileSave", "clouds", "CCloudStorage", "OnFileSave");
			RegisterModuleDependences("main", "OnAfterFileSave", "clouds", "CCloudStorage", "OnAfterFileSave");
			RegisterModuleDependences("main", "OnGetFileSRC", "clouds", "CCloudStorage", "OnGetFileSRC");
			RegisterModuleDependences("main", "OnFileCopy", "clouds", "CCloudStorage", "OnFileCopy");
			RegisterModuleDependences("main", "OnPhysicalFileDelete", "clouds", "CCloudStorage", "OnFileDelete");
			RegisterModuleDependences("main", "OnMakeFileArray", "clouds", "CCloudStorage", "OnMakeFileArray");
			RegisterModuleDependences("main", "OnBeforeResizeImage", "clouds", "CCloudStorage", "OnBeforeResizeImage");
			RegisterModuleDependences("main", "OnAfterResizeImage", "clouds", "CCloudStorage", "OnAfterResizeImage");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_AmazonS3", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_GoogleStorage", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_OpenStackStorage", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_RackSpaceCloudFiles", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_ClodoRU", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_Selectel", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_HotBox", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_Yandex", "GetObjectInstance");
			RegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_S3", "GetObjectInstance");
			RegisterModuleDependences("perfmon", "OnGetTableSchema", "clouds", "clouds", "OnGetTableSchema");

			//agents
			CAgent::RemoveAgent("CCloudStorage::CleanUp();", "clouds");
			CAgent::Add(array(
				"NAME"=>"CCloudStorage::CleanUp();",
				"MODULE_ID"=>"clouds",
				"ACTIVE"=>"Y",
				"AGENT_INTERVAL"=>86400,
				"IS_PERIOD"=>"N",
			));

			return true;
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(!array_key_exists("save_tables", $arParams) || $arParams["save_tables"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/db/".mb_strtolower($DB->type)."/uninstall.sql");
			$this->UnInstallTasks();
		}

		UnRegisterModuleDependences("main", "OnEventLogGetAuditTypes", "clouds", "CCloudStorage", "GetAuditTypes");
		UnRegisterModuleDependences("main", "OnBeforeProlog", "clouds", "CCloudStorage", "OnBeforeProlog");
		UnRegisterModuleDependences("main", "OnAdminListDisplay", "clouds", "CCloudStorage", "OnAdminListDisplay");
		UnRegisterModuleDependences("main", "OnBuildGlobalMenu", "clouds", "CCloudStorage", "OnBuildGlobalMenu");
		UnRegisterModuleDependences("main", "OnFileSave", "clouds", "CCloudStorage", "OnFileSave");
		UnRegisterModuleDependences("main", "OnAfterFileSave", "clouds", "CCloudStorage", "OnAfterFileSave");
		UnRegisterModuleDependences("main", "OnGetFileSRC", "clouds", "CCloudStorage", "OnGetFileSRC");
		UnRegisterModuleDependences("main", "OnFileCopy", "clouds", "CCloudStorage", "OnFileCopy");
		UnRegisterModuleDependences("main", "OnPhysicalFileDelete", "clouds", "CCloudStorage", "OnFileDelete");
		UnRegisterModuleDependences("main", "OnMakeFileArray", "clouds", "CCloudStorage", "OnMakeFileArray");
		UnRegisterModuleDependences("main", "OnBeforeResizeImage", "clouds", "CCloudStorage", "OnBeforeResizeImage");
		UnRegisterModuleDependences("main", "OnAfterResizeImage", "clouds", "CCloudStorage", "OnAfterResizeImage");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_AmazonS3", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_GoogleStorage", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_OpenStackStorage", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_RackSpaceCloudFiles", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_ClodoRU", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_Selectel", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_HotBox", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_Yandex", "GetObjectInstance");
		UnRegisterModuleDependences("clouds", "OnGetStorageService", "clouds", "CCloudStorageService_S3", "GetObjectInstance");
		UnRegisterModuleDependences("perfmon", "OnGetTableSchema", "clouds", "clouds", "OnGetTableSchema");

		//agents
		CAgent::RemoveAgent("CCloudStorage::CleanUp();", "clouds");

		UnRegisterModule("clouds");

		if(!defined("BX_CLOUDS_UNINSTALLED"))
			define("BX_CLOUDS_UNINSTALLED", true);

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		return true;
	}

	function migrateToBox()
	{
		global $DB, $CACHE_MANAGER;

		// Delete delayed resize
		COption::RemoveOption("clouds", "delayed_resize");
		$DB->Query("DELETE FROM b_clouds_file_resize");
		// Cancel any backup sync jobs
		$DB->Query("DELETE FROM b_clouds_copy_queue");
		$DB->Query("DELETE FROM b_clouds_delete_queue");
		// Cancel all multipart uploads in progress
		$DB->Query("DELETE FROM b_clouds_file_upload");
		// Remove file upload in progress info
		$DB->Query("DELETE FROM b_clouds_file_save");
		// Cleanup obsolete hash info
		$DB->Query("DELETE FROM b_clouds_file_hash");

		// Remove any cloud storage defined
		$DB->Query("DELETE FROM b_clouds_file_bucket");
		$CACHE_MANAGER->CleanDir("b_clouds_file_bucket");
		$DB->Query("UPDATE b_file SET HANDLER_ID=NULL WHERE HANDLER_ID is not null");

		// B24 cloud specific info
		COption::RemoveOption("clouds", "ISSUE_TIME");
		COption::RemoveOption("clouds", "master_bucket");
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
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
		}
		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");
		}
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION, $step, $USER;
		if($USER->IsAdmin())
		{
			$step = intval($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("CLO_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/step1.php");
			}
			elseif($step==2)
			{
				if($this->InstallDB())
				{
					$this->InstallFiles();
				}
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("CLO_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/step2.php");
			}
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $step, $USER;
		if($USER->IsAdmin())
		{
			$step = intval($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("CLO_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"save_tables" => $_REQUEST["save_tables"],
				));
				$this->UnInstallFiles();
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("CLO_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/clouds/install/unstep2.php");
			}
		}
	}

	public static function OnGetTableSchema()
	{
		return array(
			"clouds" => array(
				"b_clouds_file_bucket" => array(
					"ID" => array(
						"b_clouds_file_bucket" => "FAILOVER_BUCKET_ID",
						"b_clouds_file_upload" => "BUCKET_ID",
						"b_clouds_copy_queue" => "SOURCE_BUCKET_ID",
						"b_clouds_copy_queue^" => "TARGET_BUCKET_ID",
						"b_clouds_delete_queue" => "BUCKET_ID",
						"b_clouds_rename_queue" => "BUCKET_ID",
						"b_clouds_file_save" => "BUCKET_ID",
					)
				),
			),
			"main" => array(
				"b_file" => array(
					"ID" => array(
						"b_clouds_file_resize" => "FILE_ID",
					)
				),
			),
		);
	}
}
?>
