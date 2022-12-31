<?php
global $MESS;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\BitrixHandler;
use Bitrix\Disk\Document\OnlyOffice\Models\DocumentSessionTable;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\UserConfiguration;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = mb_substr($PathInstall, 0, mb_strlen($PathInstall) - mb_strlen("/index.php"));

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if(class_exists("disk")) return;

Class disk extends CModule
{
	var $MODULE_ID = "disk";
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

		$this->MODULE_NAME = GetMessage("DISK_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("DISK_INSTALL_DESCRIPTION");
	}

	function GetModuleTasks()
	{
		return array(
			'disk_access_read' => array(
				"LETTER" => "R",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'disk_read',
				),
			),
			'disk_access_add' => array(
				"LETTER" => "T",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'disk_read', 'disk_add',
				),
			),
			'disk_access_edit' => array(
				"LETTER" => "W",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'disk_read', 'disk_add', 'disk_edit', 'disk_delete', 'disk_start_bp',
				),
			),
			'disk_access_sharing' => array(
				"LETTER" => "S",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'disk_sharing',
				),
			),
			'disk_access_full' => array(
				"LETTER" => "X",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'disk_read', 'disk_add', 'disk_edit', 'disk_settings', 'disk_delete', 'disk_destroy', 'disk_restore', 'disk_rights', 'disk_sharing', 'disk_start_bp', 'disk_create_wf',
				),
			),
		);
	}

	public function migrateToBox()
	{
		if (\Bitrix\Main\Loader::includeModule('disk'))
		{
			$commonStorage = \Bitrix\Disk\Driver::getInstance()->getStorageByCommonId('shared_files_s1');
			if ($commonStorage)
			{
				$commonStorage->changeBaseUrl('/docs/shared/');
			}

			$defaultViewerServiceCode = Configuration::getDefaultViewerServiceCode();
			if ($defaultViewerServiceCode === OnlyOfficeHandler::getCode())
			{
				UserConfiguration::resetDocumentServiceForAllUsers();

				Configuration::setDefaultViewerService(BitrixHandler::getCode());
				DocumentSessionTable::clearTable();

				Option::set('disk', 'documents_enabled', 'N');
				Option::delete('disk', [
					'name' => 'disk_onlyoffice_server',
				]);
			}
		}
	}

	function InstallDB($install_wizard = true)
	{
		global $DB, $APPLICATION;

		$errors = null;
		if (!$DB->Query("SELECT 'x' FROM b_disk_storage", true))
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/db/mysql/install.sql");
		$this->InstallTasks();

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		$isWebdavInstalled = isModuleInstalled('webdav');
		$this->RegisterModuleDependencies(!$isWebdavInstalled);

		RegisterModule("disk");

		static::InstallUserFields();

		CAgent::addAgent('Bitrix\\Disk\\ExternalLink::removeExpiredWithTypeAuto();', 'disk', 'N');

		CAgent::addAgent('Bitrix\\Disk\\Bitrix24Disk\\UploadFileManager::removeIrrelevant();', 'disk', 'N', 1800);

		CAgent::addAgent('Bitrix\\Disk\\Internals\\Cleaner::deleteShowSession(3, 2);', 'disk', 'N', 3600);
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Cleaner::deleteRightSetupSession();', 'disk', 'N');
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Cleaner::emptyOldDeletedLogEntries();', 'disk', 'N', 2592000);
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Rights\\Healer::restartSetupSession();', 'disk', 'N', 3600);
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Rights\\Healer::markBadSetupSession();', 'disk', 'N');
		CAgent::addAgent('Bitrix\\Disk\\Search\\Reindex\\ExtendedIndex::processWithStatusExtended();', 'disk', 'N', 1800);
		/** @see \Bitrix\Disk\Internals\Cleaner::deleteVersionsByTtlAgent */
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Cleaner::deleteVersionsByTtlAgent(3);', 'disk', 'N', 7200);
		/** @see \Bitrix\Disk\Internals\Cleaner::deleteTrashCanFilesByTtlAgent */
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Cleaner::deleteTrashCanFilesByTtlAgent(3);', 'disk', 'N', 8000);
		/** @see \Bitrix\Disk\Internals\Cleaner::deleteTrashCanEmptyFolderByTtlAgent */
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Cleaner::deleteTrashCanEmptyFolderByTtlAgent(3);', 'disk', 'N', 8000);
		/** @see \Bitrix\Disk\Internals\Cleaner::releaseObjectLocksAgent() */
		CAgent::addAgent('Bitrix\\Disk\\Internals\\Cleaner::releaseObjectLocksAgent();', 'disk', 'N', 7200);
		/** @see \Bitrix\Disk\Document\OnlyOffice\RestrictionManager::deleteOldOrPendingAgent() */
		CAgent::addAgent('Bitrix\\Disk\\Document\\OnlyOffice\\RestrictionManager::deleteOldOrPendingAgent();', 'disk', 'N', 3600);

		if(!$isWebdavInstalled)
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/lib/configuration.php");
			\Bitrix\Main\Config\Option::set(
				'disk',
				'successfully_converted',
				'Y'
			);
			\Bitrix\Main\Config\Option::set(
				'disk',
				'disk_revision_api',
				Configuration::REVISION_API
			);
			/** @see \Bitrix\Disk\Search\Reindex\BaseObjectIndex::STATUS_STOP */
			/** @see \Bitrix\Disk\Search\Reindex\BaseObjectIndex::stopExecution(); */
			\Bitrix\Main\Config\Option::set(
				'disk',
				'needBaseObjectIndex',
				'N'
			);

			/** @see \Bitrix\Disk\Search\Reindex\HeadIndex::STATUS_FINISH */
			/** @see \Bitrix\Disk\Search\Reindex\HeadIndex::finishExecution(); */
			/** @see \Bitrix\Disk\Search\Reindex\HeadIndex::isReady(); */
			\Bitrix\Main\Config\Option::set(
				'disk',
				'needHeadIndexStepper',
				'F'
			);
		}
		else
		{
			\CAdminNotify::add(array(
				"MESSAGE" => Loc::getMessage("DISK_NOTIFY_MIGRATE_WEBDAV", array(
					"#LINK#" => "/bitrix/admin/disk_from_webdav_convertor.php?lang=".\Bitrix\Main\Application::getInstance()->getContext()->getLanguage(),
				)),
				"TAG" => "disk_migrate_from_webdav",
				"MODULE_ID" => "disk",
				"ENABLE_CLOSE" => "N",
			));
		}

		self::tryToEnableZipNginx();

		return true;
	}

	protected static function tryToEnableZipNginx()
	{
		if (
			\Bitrix\Main\Loader::includeModule('disk') &&
			!\Bitrix\Disk\ZipNginx\Configuration::isEnabled() &&
			\Bitrix\Disk\ZipNginx\Configuration::isModInstalled()
		)
		{
			\Bitrix\Disk\ZipNginx\Configuration::enable();
		}
	}

	public static function RegisterModuleDependencies($isAlreadyConverted = true)
	{
		if($isAlreadyConverted)
		{
			RegisterModuleDependences("main", "OnAfterUserAdd", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onAfterUserAdd");
			RegisterModuleDependences("main", "onUserDelete", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onUserDelete");
			RegisterModuleDependences("main", "OnAfterUserUpdate", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onAfterUserUpdate");
		}

		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'disk', 'Bitrix\\Disk\\Uf\\FileUserType', 'GetUserTypeDescription');
		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'disk', 'Bitrix\\Disk\\Uf\\VersionUserType', 'GetUserTypeDescription');

		if($isAlreadyConverted)
		{
			RegisterModuleDependences("search", "OnReindex", "disk", "\\Bitrix\\Disk\\Search\\IndexManager", "onSearchReindex");
			RegisterModuleDependences("search", "OnSearchGetURL", "disk", "\\Bitrix\\Disk\\Search\\IndexManager", "onSearchGetUrl");

			RegisterModuleDependences("socialnetwork", "OnSocNetFeaturesAdd", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetFeaturesAdd");
			RegisterModuleDependences("socialnetwork", "OnSocNetFeaturesUpdate", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetFeaturesUpdate");
			RegisterModuleDependences("socialnetwork", "OnSocNetUserToGroupAdd", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetUserToGroupAdd");
			RegisterModuleDependences("socialnetwork", "OnSocNetUserToGroupUpdate", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetUserToGroupUpdate");
			RegisterModuleDependences("socialnetwork", "OnSocNetUserToGroupDelete", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetUserToGroupDelete");
			RegisterModuleDependences("socialnetwork", "OnBeforeSocNetGroupDelete", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onBeforeSocNetGroupDelete");
			RegisterModuleDependences("socialnetwork", "OnSocNetGroupDelete", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetGroupDelete");
			RegisterModuleDependences("socialnetwork", "OnSocNetGroupAdd", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetGroupAdd");
			RegisterModuleDependences("socialnetwork", "OnSocNetGroupUpdate", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetGroupUpdate");
			RegisterModuleDependences('socialnetwork', "OnAfterFetchDiskUfEntity", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onAfterFetchDiskUfEntity");
			RegisterModuleDependences("im", "OnBeforeConfirmNotify", "disk", "\\Bitrix\\Disk\\Sharing", "OnBeforeConfirmNotify");
			RegisterModuleDependences("im", "OnGetNotifySchema", "disk", "\\Bitrix\\Disk\\Integration\\NotifySchema", "onGetNotifySchema");

			RegisterModuleDependences("rest", "OnRestServiceBuildDescription", "disk", "\\Bitrix\\Disk\\Rest\\RestManager", "onRestServiceBuildDescription");
			RegisterModuleDependences("rest", "onRestGetModule", "disk", "\\Bitrix\\Disk\\Rest\\RestManager", "onRestGetModule");
			RegisterModuleDependences("rest", "OnRestAppDelete", "disk", "\\Bitrix\\Disk\\Rest\\RestManager", "onRestAppDelete");
		}

		RegisterModuleDependences("iblock", "OnBeforeIBlockDelete", "disk", "disk", "OnBeforeIBlockDelete");
		RegisterModuleDependences("perfmon", "OnGetTableSchema", "disk", "disk", "OnGetTableSchema");
		RegisterModuleDependences("main", "OnAfterRegisterModule", "main", "disk", "installUserFields", 100, "/modules/disk/install/index.php"); // check UF

		RegisterModuleDependences("iblock", "OnIBlockPropertyBuildList", "disk", "\\Bitrix\\Disk\\Integration\\FileDiskProperty", "GetUserTypeDescription");

		RegisterModuleDependences('disk', 'onAfterDeleteStorage', 'disk', "\\Bitrix\\Disk\\Integration\\Volume", 'onStorageDelete');
		RegisterModuleDependences('main', 'onUserDelete', 'disk', "\\Bitrix\\Disk\\Integration\\Volume", 'onUserDelete');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler("main", "onFileTransformationComplete", "disk", "\\Bitrix\\Disk\\Integration\\TransformerManager", "resetCacheInUfAfterTransformation");
	}

	function UnInstallDB($arParams = Array())
	{
		global $DB, $APPLICATION;

		if(CModule::IncludeModule("search"))
		{

			CSearch::deleteIndex("disk");
		}


		$errors = null;
		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			static::UnInstallUserFields();
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/db/mysql/uninstall.sql");

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
		}

		CAgent::removeModuleAgents("disk");
		COption::removeOption('disk');


		//UnRegisterModuleDependences
		UnRegisterModuleDependences("main", "OnAfterRegisterModule", "main", "disk", "installUserFields", "/modules/disk/install/index.php"); // check UF

		UnRegisterModuleDependences("main", "OnAfterUserAdd", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onAfterUserAdd");
		UnRegisterModuleDependences("main", "OnAfterUserAdd", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onUserDelete");
		UnRegisterModuleDependences("main", "OnAfterUserUpdate", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onAfterUserUpdate");

		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'disk', 'Bitrix\\Disk\\Uf\\FileUserType', 'GetUserTypeDescription');
		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'disk', 'Bitrix\\Disk\\Uf\\VersionUserType', 'GetUserTypeDescription');

		UnRegisterModuleDependences("search", "OnReindex", "disk", "\\Bitrix\\Disk\\Search\\IndexManager", "onSearchReindex");
		UnRegisterModuleDependences("search", "OnSearchGetURL", "disk", "\\Bitrix\\Disk\\Search\\IndexManager", "onSearchGetUrl");

		UnRegisterModuleDependences('socialnetwork', 'OnSocNetFeaturesAdd', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onSocNetFeaturesAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetFeaturesUpdate', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onSocNetFeaturesUpdate');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupAdd', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onSocNetUserToGroupAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onSocNetUserToGroupUpdate');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onSocNetUserToGroupDelete');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onSocNetGroupDelete');
		UnRegisterModuleDependences("socialnetwork", "OnBeforeSocNetGroupDelete", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onBeforeSocNetGroupDelete");
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetGroupAdd', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onSocNetGroupAdd');
		UnRegisterModuleDependences("socialnetwork", "OnSocNetGroupUpdate", "disk", "\\Bitrix\\Disk\\SocialnetworkHandlers", "onSocNetGroupUpdate");
		UnRegisterModuleDependences('socialnetwork', 'OnAfterFetchDiskUfEntity', 'disk', "\\Bitrix\\Disk\\SocialnetworkHandlers", 'onAfterFetchDiskUfEntity');

		UnRegisterModuleDependences("iblock", "OnBeforeIBlockDelete", "disk", "disk", "OnBeforeIBlockDelete");
		UnRegisterModuleDependences("perfmon", "OnGetTableSchema", "disk", "disk", "OnGetTableSchema");

		UnRegisterModuleDependences("im", "OnBeforeConfirmNotify", "disk", "\\Bitrix\\Disk\\Sharing", "OnBeforeConfirmNotify");
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "disk", "\\Bitrix\\Disk\\Integration\\NotifySchema", "onGetNotifySchema");

		UnRegisterModuleDependences("rest", "OnRestServiceBuildDescription", "disk", "\\Bitrix\\Disk\\Rest\\RestManager", "onRestServiceBuildDescription");
		UnRegisterModuleDependences("rest", "onRestGetModule", "disk", "\\Bitrix\\Disk\\Rest\\RestManager", "onRestGetModule");
		UnRegisterModuleDependences("rest", "OnRestAppDelete", "disk", "\\Bitrix\\Disk\\Rest\\RestManager", "onRestAppDelete");

		UnRegisterModuleDependences("iblock", "OnIBlockPropertyBuildList", "disk", "\\Bitrix\\Disk\\Integration\\FileDiskProperty", "GetUserTypeDescription");

		UnRegisterModuleDependences('disk', 'onAfterDeleteStorage', 'disk', "\\Bitrix\\Disk\\Integration\\Volume", 'onStorageDelete');
		UnRegisterModuleDependences('main', 'onUserDelete', 'disk', "\\Bitrix\\Disk\\Integration\\Volume", 'onUserDelete');

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler("main", "onFileTransformationComplete", "disk", "\\Bitrix\\Disk\\Integration\\TransformerManager", "resetCacheInUfAfterTransformation");

		UnRegisterModule("disk");

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
		global $APPLICATION;
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/disk/install/tools/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools', true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/disk/install/services/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/services', true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/public/docs", $_SERVER["DOCUMENT_ROOT"]."/docs", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/public/templates", $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/webdav", $_SERVER["DOCUMENT_ROOT"]."/bitrix/webdav", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", true, true);

			CUrlRewriter::add(
					array(
						"CONDITION" => "#^/docs/pub/(?<hash>[0-9a-f]{32})/(?<action>[0-9a-zA-Z]+)/\?#",
						"RULE" => "hash=$1&action=$2&",
						"ID" => "bitrix:disk.external.link",
						"PATH" => "/docs/pub/index.php"
					)
			);

			CUrlRewriter::add(
				array(
					"CONDITION" => "#^/disk/(?<action>[0-9a-zA-Z]+)/(?<fileId>[0-9]+)/\?#",
					"RULE" => "action=$1&fileId=$2&",
					"ID" => "bitrix:disk.services",
					"PATH" => "/bitrix/services/disk/index.php",
				)
			);

			$APPLICATION->SetFileAccessPermission('/bitrix/tools/disk/', array('*' => 'R'));
			$APPLICATION->SetFileAccessPermission('/bitrix/services/disk/', array('*' => 'R'));
			$APPLICATION->SetFileAccessPermission('/docs/pub/', array('*' => 'R'));
			$APPLICATION->SetFileAccessPermission('/bitrix/admin/disk_bizproc_activity_settings.php', array('2' => 'R'));
			$APPLICATION->SetFileAccessPermission('/bitrix/admin/disk_bizproc_selector.php', array('2' => 'R'));
			$APPLICATION->SetFileAccessPermission('/bitrix/admin/disk_bizproc_wf_settings.php', array('2' => 'R'));

			\Bitrix\Main\UrlPreview\Router::setRouteHandler(
				'/disk/#action#/#fileId#/',
				'disk',
				'\Bitrix\Disk\Ui\Preview\File',
				array(
					'action' => '$action',
					'fileId' => '$fileId',
				)
			);

			\Bitrix\Main\UrlPreview\Router::setRouteHandler(
					'/docs/pub/#hash#/#action#/',
					'disk',
					'\Bitrix\Disk\Ui\Preview\ExternalLink',
					array(
							'action' => '$action',
							'hash' => '$hash',
					)
			);

			self::tryToEnableZipNginx();
		}

		return true;
	}
	function UnInstallFiles()
	{
		global $APPLICATION;
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			DeleteDirFilesEx("/bitrix/js/disk/");
			DeleteDirFilesEx("/bitrix/tools/disk/");
			DeleteDirFilesEx("/bitrix/services/disk/");
		}
		$APPLICATION->SetFileAccessPermission('/bitrix/tools/disk/', array('*' => 'D'));
		$APPLICATION->SetFileAccessPermission('/bitrix/services/disk/', array('*' => 'D'));

		return true;
	}

	public static function InstallUserFields($moduleId = "all")
	{}

	public static function UnInstallUserFields()
	{
		$ent = new CUserTypeEntity;
		foreach(array("disk_file", "disk_version") as $type)
		{
			$rsData = CUserTypeEntity::GetList(array("ID" => "ASC"), array("USER_TYPE_ID" => $type));
			if ($rsData && ($arRes = $rsData->Fetch()))
			{
				do {
					$ent->Delete($arRes['ID']);
				} while ($arRes = $rsData->Fetch());
			}
		}
	}

	function DoInstall()
	{
		global $APPLICATION, $step;

		$this->InstallFiles();
		$this->InstallDB();
		$this->InstallEvents();

		$APPLICATION->IncludeAdminFile(GetMessage("DISK_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/step1.php");
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;

		$this->errors = array();

		$step = intval($step);
		if($step<2)
		{
			if (isModuleInstalled('webdav') && Option::get('disk', 'process_converted', false) === 'Y')
			{
				$this->errors[] = GetMessage("DISK_UNINSTALL_ERROR_MIGRATE_PROCESS");
			}

			$GLOBALS["disk_installer_errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("DISK_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();

			$this->UnInstallEvents();

			$GLOBALS["disk_installer_errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("DISK_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/disk/install/unstep2.php");
		}
	}

	public static function OnGetTableSchema()
	{
		return array(
			"disk" => array(
				'b_disk_object' => array(
					'ID' => array(
						'b_disk_attached_object' => 'OBJECT_ID',
						'b_disk_deleted_log' => 'OBJECT_ID',
						'b_disk_deleted_log_v2' => 'OBJECT_ID',
						'b_disk_edit_session' => 'OBJECT_ID',
						'b_disk_object' => 'REAL_OBJECT_ID',
						'b_disk_object^' => 'PARENT_ID',
						'b_disk_object_path' => 'OBJECT_ID',
						'b_disk_object_path^' => 'PARENT_ID',
						'b_disk_right' => 'OBJECT_ID',
						'b_disk_sharing' => 'LINK_OBJECT_ID',
						'b_disk_sharing^' => 'REAL_OBJECT_ID',
						'b_disk_simple_right' => 'OBJECT_ID',
						'b_disk_storage' => 'ROOT_OBJECT_ID',
						'b_disk_version' => 'OBJECT_ID',
						'b_disk_external_link' => 'OBJECT_ID',
						'b_disk_cloud_import' => 'OBJECT_ID',
						'b_disk_object_lock' => 'OBJECT_ID',
						'b_disk_onlyoffice_document_session' => 'OBJECT_ID',
						'b_disk_onlyoffice_document_info' => 'OBJECT_ID',
						'b_disk_tracked_object' => 'OBJECT_ID',
						'b_disk_tracked_object^' => 'REAL_OBJECT_ID',
						'b_disk_recently_used' => 'OBJECT_ID',
					),
				),
				'b_disk_sharing' => array(
					'ID' => array(
						'b_disk_sharing' => 'PARENT_ID',
					)
				),
				'b_disk_attached_object' => array(
					'ID' => array(
						'b_disk_tracked_object' => 'ATTACHED_OBJECT_ID',
					)
				),
				'b_disk_storage' => array(
					'ID' => array(
						'b_disk_object' => 'STORAGE_ID',
						'b_disk_sharing' => 'REAL_STORAGE_ID',
						'b_disk_sharing^' => 'LINK_STORAGE_ID',
						'b_disk_deleted_log' => 'STORAGE_ID',
						'b_disk_deleted_log_v2' => 'STORAGE_ID',
					)
				),
				'b_disk_version' => array(
					'ID' => array(
						'b_disk_attached_object' => 'VERSION_ID',
						'b_disk_external_link' => 'VERSION_ID',
						'b_disk_edit_session' => 'VERSION_ID',
						'b_disk_cloud_import' => 'VERSION_ID',
						'b_disk_onlyoffice_document_session' => 'VERSION_ID',
						'b_disk_onlyoffice_document_info' => 'VERSION_ID',
					)
				),
				'b_disk_tmp_file' => array(
					'ID' => array(
						'b_disk_cloud_import' => 'TMP_FILE_ID',
					)
				),
				'b_disk_onlyoffice_document_info' => array(
					'EXTERNAL_HASH' => array(
						'b_disk_onlyoffice_document_session' => 'EXTERNAL_HASH',
					)
				),
			),
			"main" => array(
				"b_file" => array(
					"ID" => array(
						"b_disk_object" => "FILE_ID",
						"b_disk_version" => "FILE_ID",
					)
				),
				"b_user" => array(
					"ID" => array(
						'b_disk_object' => 'CREATED_BY',
						'b_disk_object^' => 'UPDATED_BY',
						'b_disk_object^^' => 'DELETED_BY',
						'b_disk_version' => 'CREATED_BY',
						'b_disk_version^' => 'OBJECT_CREATED_BY',
						'b_disk_version^^' => 'OBJECT_UPDATED_BY',
						'b_disk_attached_object' => 'CREATED_BY',
						'b_disk_external_link' => 'CREATED_BY',
						'b_disk_sharing' => 'CREATED_BY',
						'b_disk_edit_session' => 'USER_ID',
						'b_disk_edit_session^' => 'OWNER_ID',
						'b_disk_deleted_log' => 'USER_ID',
						'b_disk_deleted_log_v2' => 'USER_ID',
						'b_disk_cloud_import' => 'USER_ID',
						'b_disk_tracked_object' => 'USER_ID',
						'b_disk_object_lock' => 'CREATED_BY',
						'b_disk_onlyoffice_document_session' => 'USER_ID',
						'b_disk_onlyoffice_document_session^' => 'OWNER_ID',
						'b_disk_onlyoffice_document_info' => 'OWNER_ID',
						'b_disk_recently_used' => 'USER_ID',
					)
				),
				"b_task" => array(
					"ID" => array(
						"b_disk_right" => "TASK_ID",
					)
				),
				"b_iblock_element" => array(
					"ID" => array(
						"b_disk_object" => "WEBDAV_ELEMENT_ID",
					)
				),
				"b_iblock_section" => array(
					"ID" => array(
						"b_disk_object" => "WEBDAV_SECTION_ID",
					)
				),
				"b_iblock" => array(
					"ID" => array(
						"b_disk_object" => "WEBDAV_IBLOCK_ID",
					)
				),
			),
		);
	}

	public static function OnBeforeIBlockDelete($id)
	{
		$id = (int)$id;
		$query = CIBlock::GetList(array('ID' => 'ASC'), array('TYPE' => 'library', 'ID' => $id));
		if(!$query)
		{
			return;
		}

		$iblock = $query->fetch();
		if(!$iblock)
		{
			return;
		}
		if(Configuration::isSuccessfullyConverted())
		{
			return false;
		}

		return;
	}

}
?>
