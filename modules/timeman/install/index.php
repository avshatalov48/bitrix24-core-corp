<?php

if (class_exists("timeman"))
{
	return;
}

IncludeModuleLangFile(__FILE__);

class timeman extends CModule
{
	var $MODULE_ID = "timeman";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "N";
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
		elseif (defined('TIMEMAN_VERSION') && defined('TIMEMAN_VERSION_DATE'))
		{
			$this->MODULE_VERSION = TIMEMAN_VERSION;
			$this->MODULE_VERSION_DATE = TIMEMAN_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("TIMEMAN_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("TIMEMAN_MODULE_DESCRIPTION");
	}

	function InstallDB()
	{
		global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();

		if (!$DB->TableExists('b_timeman_entries'))
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/timeman/install/db/' . $connection->getType() . '/install.sql');

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
		}

		$this->InstallTasks();

		RegisterModule($this->MODULE_ID);

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/fields.php");

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CReportNotifications', 'AddEvent');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CReportNotifications', 'OnFillSocNetAllowedSubscribeEntityTypes');

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CTimeManNotify', 'OnFillSocNetLogEvents');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CTimeManNotify', 'OnFillSocNetAllowedSubscribeEntityTypes');
		RegisterModuleDependences("im", "OnGetNotifySchema", "timeman", "CTimemanNotifySchema", "OnGetNotifySchema");

		RegisterModuleDependences(
			'main',
			'OnAfterUserUpdate',
			'timeman',
			'CTimeManNotify',
			'OnAfterUserUpdate',
			200
		);
		RegisterModuleDependences('main', 'OnAfterUserUpdate', 'timeman', 'CReportNotifications', 'OnAfterUserUpdate');
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'timeman', '\Bitrix\Timeman\Rest', 'onRestServiceBuildDescription');

		RegisterModuleDependences("perfmon", "OnGetTableSchema", "timeman", "CTimeManTableSchema", "OnGetTableSchema");
		RegisterModuleDependences('main', 'onAfterUserUpdate', 'timeman', '\CReportSettings', 'onUserUpdate');
		RegisterModuleDependences("forum", "OnAfterCommentAdd", 'timeman', 'CTimeManNotify', "onAfterForumCommentAdd");

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('im', 'onStatusSet', 'timeman', '\Bitrix\Timeman\Absence', 'onImUserStatusSet');
		$eventManager->registerEventHandler('im', 'onDesktopStart', 'timeman', '\Bitrix\Timeman\Absence', 'onImDesktopStart');
		$eventManager->registerEventHandler('main', 'OnUserSetLastActivityDate', 'timeman', '\Bitrix\Timeman\Absence', 'onUserSetLastActivityDate');

		$eventManager->registerEventHandlerCompatible("timeman", "OnAfterTMDayStart", 'timeman', '\Bitrix\Timeman\Absence', 'onUserStartWorkDay');
		$eventManager->registerEventHandlerCompatible("timeman", "OnAfterTMDayPause", 'timeman', '\Bitrix\Timeman\Absence', 'onUserPauseWorkDay');
		$eventManager->registerEventHandlerCompatible("timeman", "OnAfterTMDayContinue", 'timeman', '\Bitrix\Timeman\Absence', 'onUserContinueWorkDay');
		$eventManager->registerEventHandlerCompatible("timeman", "OnAfterTMDayEnd", 'timeman', '\Bitrix\Timeman\Absence', 'onUserEndWorkDay');

		CAgent::AddAgent('\Bitrix\Timeman\Absence::searchOfflineUsersWithActiveDayAgent();', "timeman", "N", 60);
		CAgent::AddAgent('\Bitrix\Timeman\Absence::searchOfflineDesktopUsersWithActiveDayAgent();', "timeman", "N", 60);
		CAgent::AddAgent('\Bitrix\Timeman\Service\Agent\InitialSettingsAgent::installDefaultData();', 'timeman', "N", 130);
		CAgent::AddAgent('\Bitrix\Timeman\Service\Agent\InitialSettingsAgent::installDefaultPermissions();', 'timeman', "N", 130);

		if (!IsModuleInstalled('bitrix24'))
		{
			\Bitrix\Main\Config\Option::set('timeman', 'intranet_network_range', serialize([
				["ip_range" => "10.0.0.0-10.255.255.255", "name" => "10.x.x.x"],
				["ip_range" => "172.16.0.0-172.31.255.255", "name" => "172.x.x.x"],
				["ip_range" => "192.168.0.0-192.168.255.255", "name" => "192.168.x.x"],
			]));
		}

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;

		$connection = \Bitrix\Main\Application::getConnection();

		if (array_key_exists("savedata", $arParams) && ($arParams["savedata"] != 'Y'))
		{
			if(CModule::IncludeModule("socialnetwork"))
			{
				$dbLog = CSocNetLog::GetList(
					array(),
					array(
						"ENTITY_TYPE" => array("R", "T"),
						"EVENT_ID" => array("timeman_entry", "report")
					),
					false,
					false,
					array("ID")
				);
				while ($arLog = $dbLog->Fetch())
				{
					CSocNetLog::Delete($arLog["ID"]);
				}
			}

			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/timeman/install/db/' . $connection->getType() . '/uninstall.sql');

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}

			$this->UnInstallTasks();
		}

		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CReportNotifications', 'AddEvent');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CReportNotifications', 'OnFillSocNetAllowedSubscribeEntityTypes');

		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CTimeManNotify', 'OnFillSocNetLogEvents');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CTimeManNotify', 'OnFillSocNetAllowedSubscribeEntityTypes');
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "timeman", "CTimemanNotifySchema", "OnGetNotifySchema");

		UnRegisterModuleDependences('main', 'OnAfterUserUpdate', 'timeman', 'CTimeManNotify', 'OnAfterUserUpdate');
		UnRegisterModuleDependences('main', 'OnAfterUserUpdate', 'timeman', 'CReportNotifications', 'OnAfterUserUpdate');
		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'timeman', '\Bitrix\Timeman\Rest', 'onRestServiceBuildDescription');

		UnRegisterModuleDependences("perfmon", "OnGetTableSchema", "timeman", "CTimeManTableSchema", "OnGetTableSchema");
		UnRegisterModuleDependences('main', 'onAfterUserUpdate', 'timeman', '\CReportSettings', 'onUserUpdate');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unregisterEventHandler('im', 'onStatusSet', 'timeman', '\Bitrix\Timeman\Absence', 'onImUserStatusSet');
		$eventManager->unregisterEventHandler('im', 'onDesktopStart', 'timeman', '\Bitrix\Timeman\Absence', 'onImDesktopStart');
		$eventManager->unregisterEventHandler('main', 'OnUserSetLastActivityDate', 'timeman', '\Bitrix\Timeman\Absence', 'onUserSetLastActivityDate');

		$eventManager->unRegisterEventHandler("timeman", "OnAfterTMDayStart", 'timeman', '\Bitrix\Timeman\Absence', 'onUserStartWorkDay');
		$eventManager->unRegisterEventHandler("timeman", "OnAfterTMDayPause", 'timeman', '\Bitrix\Timeman\Absence', 'onUserPauseWorkDay');
		$eventManager->unRegisterEventHandler("timeman", "OnAfterTMDayContinue", 'timeman', '\Bitrix\Timeman\Absence', 'onUserContinueWorkDay');
		$eventManager->unRegisterEventHandler("timeman", "OnAfterTMDayEnd", 'timeman', '\Bitrix\Timeman\Absence', 'onUserEndWorkDay');

		UnRegisterModule($this->MODULE_ID);

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/themes",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/tools",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",
			true, true
		);

		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/images",
			true, true
		);

		return true;
	}

	function DoInstall()
	{
		global $APPLICATION;

		if (!CBXFeatures::IsFeatureEditable('timeman'))
		{
			$this->errors = array(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("TIMEMAN_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		else
		{
			if (!IsModuleInstalled($this->MODULE_ID))
			{
				if ($this->InstallDB())
				{
					CBXFeatures::SetFeatureEnabled('timeman');
					$this->InstallFiles();
				}
			}
		}
	}

	function DoUninstall()
	{
		global $APPLICATION, $USER, $step;

		if($USER->IsAdmin())
		{
			$step = intval($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("TIMEMAN_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));

				CBXFeatures::SetFeatureEnabled('timeman', false);

				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("TIMEMAN_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep2.php");
			}
		}
	}

	function GetModuleTasks()
	{
		return [
			'timeman_denied' => [
				'LETTER' => 'D',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'tm_manage',
				],
			],
			'timeman_subordinate' => [
				'LETTER' => 'N',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'tm_manage',
					'tm_read_subordinate',
					'tm_write_subordinate',
					'tm_read_schedules_all',
					'tm_read_shift_plans_all',
				],
			],
			'timeman_read' => [
				'LETTER' => 'R',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'tm_manage',
					'tm_read',
					'tm_write_subordinate',
					'tm_read_schedules_all',
					'tm_read_shift_plans_all',
					'tm_update_schedules_all',
					'tm_update_shift_plans_all',
				],
			],
			'timeman_write' => [
				'LETTER' => 'T',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'tm_manage',
					'tm_read',
					'tm_write',
				],
			],
			'timeman_full_access' => [
				'LETTER' => 'W',
				'BINDING' => 'module',
				'OPERATIONS' => [
					'tm_manage',
					'tm_manage_all',
					'tm_read',
					'tm_write',
					'tm_settings',
					'tm_read_schedules_all',
					'tm_read_shift_plans_all',
					'tm_update_schedules_all',
					'tm_update_shift_plans_all',
				],
			],
		];
	}
}
