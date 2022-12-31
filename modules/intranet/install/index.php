<?php

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\IO;
Loc::loadMessages(__FILE__);

//if (class_exists("intranet")) return;

Class intranet extends CModule
{
	var $MODULE_ID = "intranet";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		elseif (defined('INTRANET_VERSION') && defined('INTRANET_VERSION_DATE'))
		{
			$this->MODULE_VERSION = INTRANET_VERSION;
			$this->MODULE_VERSION_DATE = INTRANET_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("INTR_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("INTR_MODULE_DESCRIPTION");
	}

	function InstallDB()
	{
		global $DB, $APPLICATION;

		if (!$DB->Query("SELECT 'x' FROM b_intranet_sharepoint ", true))
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$this->MODULE_ID.'/install/db/mysql/install.sql');

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("intranet");

		RegisterModuleDependences("search", "OnReindex", "intranet", "CIntranetSearch", "OnSearchReindex");
		RegisterModuleDependences("search", "OnSearchGetURL", "intranet", "CIntranetSearch", "OnSearchGetURL");
		RegisterModuleDependences("main", "OnAfterUserUpdate", "intranet", "CIntranetSearch", "OnUserUpdate");
		RegisterModuleDependences("main", "OnAfterUserAdd", "intranet", "CIntranetSearch", "OnUserAdd");
		RegisterModuleDependences("main", "OnUserDelete", "intranet", "CIntranetSearch", "OnUserDelete");
		RegisterModuleDependences("search", "OnSearchGetFileContent", "intranet", "CIntranetSearchConverters", "OnSearchGetFileContent");
		RegisterModuleDependences("search", "BeforeIndex", "intranet", "CIntranetSearch", "ExcludeBlogUser");
		RegisterModuleDependences("main", "OnAfterUserUpdate", "intranet", "CIntranetEventHandlers", "UpdateActivity");
		RegisterModuleDependences("main", "OnUserDelete", "intranet", "CIntranetEventHandlers", "OnUserDelete");
		RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "intranet", "CIntranetEventHandlers", "UpdateActivityIBlock");
		RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "intranet", "CIntranetEventHandlers", "UpdateActivityIBlock");
		RegisterModuleDependences("iblock", "OnAfterIBlockElementDelete", "intranet", "CIntranetEventHandlers", "OnAfterIBlockElementDelete");
		RegisterModuleDependences("main", "OnAfterUserAdd", "intranet", "CIntranetEventHandlers", "OnAfterUserAdd");
		RegisterModuleDependences("main", "OnUserInitialize", "intranet", "CIntranetEventHandlers", "OnAfterUserInitialize");
		RegisterModuleDependences("main", "OnAfterUserAuthorize", "intranet", "CIntranetInviteDialog", "OnAfterUserAuthorize");
		RegisterModuleDependences("main", "OnAfterUserAuthorize", "intranet", "CIntranetEventHandlers", "OnAfterUserAuthorize");
		RegisterModuleDependences("forum", "onAfterMessageAdd", "intranet", "CIntranetEventHandlers", "onAfterForumMessageAdd");
		RegisterModuleDependences("forum", "onAfterMessageDelete", "intranet", "CIntranetEventHandlers", "onAfterForumMessageDelete");
		RegisterModuleDependences("main", "OnAfterUserTypeAdd", "intranet", "CIntranetEventHandlers", "OnAfterUserTypeAdd");

		RegisterModuleDependences("iblock", "OnBeforeIBlockSectionUpdate", "intranet", "CIntranetEventHandlers", "OnBeforeIBlockSectionUpdate");
		RegisterModuleDependences("iblock", "OnBeforeIBlockSectionAdd", "intranet", "CIntranetEventHandlers", "OnBeforeIBlockSectionAdd");

		RegisterModuleDependences("main", "OnUserTypeBuildList", "intranet", "CUserTypeEmployee", "GetUserTypeDescription");
		RegisterModuleDependences("iblock", "OnIBlockPropertyBuildList", "intranet", "CIBlockPropertyEmployee", "GetUserTypeDescription");

		RegisterModuleDependences("main", "OnBeforeProlog", "intranet", "CIntranetEventHandlers", "OnCreatePanel");

		// OnAfterUserAdd was already bound above, so skip it
		RegisterModuleDependences("main", "OnBeforeUserUpdate", "intranet", "CIntranetEventHandlers", "OnBeforeUserUpdate");
		RegisterModuleDependences("main", "OnAfterUserUpdate", "intranet", "CIntranetEventHandlers", "OnAfterUserUpdate");
		RegisterModuleDependences("socialservices", "OnAfterSocServUserAdd", "intranet", "CIntranetEventHandlers", "OnAfterSocServUserAdd");

		// cache
		RegisterModuleDependences("main", "onUserDelete", "intranet", "CIntranetEventHandlers", "ClearAllUsersCache");
		RegisterModuleDependences("main", "onAfterUserAdd", "intranet", "CIntranetEventHandlers", "ClearAllUsersCache");
		RegisterModuleDependences("main", "OnAfterUserUpdate", "intranet", "CIntranetEventHandlers", "ClearSingleUserCache");
		RegisterModuleDependences("iblock", "OnAfterIBlockSectionUpdate", "intranet", "CIntranetEventHandlers", "ClearDepartmentCache");

		RegisterModuleDependences("socialnetwork", "OnFillSocNetAllowedSubscribeEntityTypes", "intranet", "CIntranetEventHandlers", "OnFillSocNetAllowedSubscribeEntityTypes");
		RegisterModuleDependences("socialnetwork", "OnFillSocNetLogEvents", "intranet", "CIntranetEventHandlers", "OnFillSocNetLogEvents");

		RegisterModuleDependences("socialnetwork", "OnFillSocNetAllowedSubscribeEntityTypes", "intranet", "CIntranetNotify", "OnFillSocNetAllowedSubscribeEntityTypes");
		RegisterModuleDependences("socialnetwork", "OnFillSocNetLogEvents", "intranet", "CIntranetNotify", "OnFillSocNetLogEvents");
		RegisterModuleDependences("socialnetwork", "OnSendMentionGetEntityFields", "intranet", "CIntranetNotify", "OnSendMentionGetEntityFields");

		RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "intranet", "CIntranetEventHandlers", "SPRegisterUpdatedItem");
		RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "intranet", "CIntranetEventHandlers", "SPRegisterUpdatedItem");

		// rating
		RegisterModuleDependences("main", "OnAfterAddRatingRule", "intranet", "CRatingRulesIntranet", "OnAfterAddRatingRule");
		RegisterModuleDependences("main", "OnAfterUpdateRatingRule", "intranet", "CRatingRulesIntranet", "OnAfterUpdateRatingRule");
		RegisterModuleDependences("main", "OnGetRatingRuleObjects",  "intranet", "CRatingRulesIntranet", "OnGetRatingRuleObjects");
		RegisterModuleDependences("main", "OnGetRatingRuleConfigs",  "intranet", "CRatingRulesIntranet", "OnGetRatingRuleConfigs");
		RegisterModuleDependences("main", "OnAfterAddRating", 	"intranet", "CRatingsComponentsIntranet", "OnAfterAddRating", 200);
		RegisterModuleDependences("main", "OnAfterUpdateRating", "intranet", "CRatingsComponentsIntranet", "OnAfterUpdateRating", 200);
		RegisterModuleDependences("main", "OnSetRatingsConfigs", "intranet", "CRatingsComponentsIntranet", "OnSetRatingConfigs", 200);
		RegisterModuleDependences("main", "OnGetRatingsConfigs", "intranet", "CRatingsComponentsIntranet", "OnGetRatingConfigs", 200);
		RegisterModuleDependences("main", "OnGetRatingsObjects", "intranet", "CRatingsComponentsIntranet", "OnGetRatingObject", 200);

		//auth provider
		RegisterModuleDependences("main", "OnAuthProvidersBuildList", "intranet", "CIntranetAuthProvider", "GetProviders");
		RegisterModuleDependences('main', 'OnBeforeUserUpdate', 'intranet', 'CIntranetAuthProvider', 'OnBeforeUserUpdate');
		RegisterModuleDependences('main', 'OnAfterUserAdd', 'intranet', 'CIntranetAuthProvider', 'OnAfterUserAdd');
		RegisterModuleDependences('iblock', 'OnBeforeIBlockSectionUpdate', 'intranet', 'CIntranetAuthProvider', 'OnBeforeIBlockSectionUpdate');
		RegisterModuleDependences('iblock', 'OnAfterIBlockSectionDelete', 'intranet', 'CIntranetAuthProvider', 'OnAfterIBlockSectionDelete');
		RegisterModuleDependences("search", "OnSearchCheckPermissions", "intranet", "CIntranetAuthProvider", "OnSearchCheckPermissions");

		// activity pulse
		RegisterModuleDependences("crm", "OnAfterCrmContactAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onAfterCrmContactAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmCompanyAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onAfterCrmCompanyAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmLeadAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onAfterCrmLeadAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmDealAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onAfterCrmDealAddEvent");
		RegisterModuleDependences("crm", "OnAfterCrmAddEvent", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onAfterCrmAddEventEvent");
		RegisterModuleDependences("sale", "OnOrderAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onOrderAddEvent");
		RegisterModuleDependences("sale", "OnOrderUpdate", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onOrderUpdateEvent");
		RegisterModuleDependences("catalog", "OnProductAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onProductAddEvent");
		RegisterModuleDependences("catalog", "OnProductUpdate", "intranet", "\\Bitrix\\Intranet\\UStat\\CrmEventHandler", "onProductUpdateEvent");
		RegisterModuleDependences("webdav", "OnAfterDiskFileAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFileAddEvent");
		RegisterModuleDependences("webdav", "OnAfterDiskFileUpdate", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFileUpdateEvent");
		RegisterModuleDependences("webdav", "OnAfterDiskFolderAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFolderAddEvent");
		RegisterModuleDependences("webdav", "OnAfterDiskFolderUpdate", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFolderUpdateEvent");
		RegisterModuleDependences("webdav", "OnAfterDiskFirstUsageByDay", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFirstUsageByDayEvent");
		RegisterModuleDependences("disk", "OnAfterDiskFileAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFileAddEvent");
		RegisterModuleDependences("disk", "OnAfterDiskFileUpdate", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFileUpdateEvent");
		RegisterModuleDependences("disk", "OnAfterDiskFolderAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFolderAddEvent");
		RegisterModuleDependences("disk", "OnAfterDiskFolderUpdate", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFolderUpdateEvent");
		RegisterModuleDependences("disk", "OnAfterDiskFirstUsageByDay", "intranet", "\\Bitrix\\Intranet\\UStat\\DiskEventHandler", "onAfterDiskFirstUsageByDayEvent");
		RegisterModuleDependences("im", "OnAfterMessagesAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\ImEventHandler", "onAfterMessagesAddEvent");
		RegisterModuleDependences("im", "OnCallStart", "intranet", "\\Bitrix\\Intranet\\UStat\\ImEventHandler", "onCallStartEvent");
		RegisterModuleDependences('im', 'OnGetNotifySchema', 'intranet', '\Bitrix\Intranet\Integration\Im', 'onGetNotifySchema');
		RegisterModuleDependences("main", "OnAddRatingVote", "intranet", "\\Bitrix\\Intranet\\UStat\\LikesEventHandler", "onAddRatingVoteEvent");
		RegisterModuleDependences("mobileapp", "OnMobileInit", "intranet", "\\Bitrix\\Intranet\\UStat\\MobileEventHandler", "onMobileInitEvent");
		RegisterModuleDependences("blog", "OnPostAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\SocnetEventHandler", "onPostAddEvent");
		RegisterModuleDependences("blog", "OnCommentAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\SocnetEventHandler", "onCommentAddEvent");
		RegisterModuleDependences("tasks", "OnTaskAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\TasksEventHandler", "onTaskAddEvent");
		RegisterModuleDependences("tasks", "OnTaskUpdate", "intranet", "\\Bitrix\\Intranet\\UStat\\TasksEventHandler", "onTaskUpdateEvent");
		RegisterModuleDependences("tasks", "OnTaskElapsedTimeAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\TasksEventHandler", "onTaskElapsedTimeAddEvent");
		RegisterModuleDependences("tasks", "OnAfterCommentAdd", "intranet", "\\Bitrix\\Intranet\\UStat\\TasksEventHandler", "onAfterCommentAddEvent");

		RegisterModuleDependences('iblock', 'OnModuleUnInstall', 'intranet', 'CIntranetEventHandlers', 'OnIBlockModuleUnInstall');

		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'intranet', 'CIntranetRestService', 'OnRestServiceBuildDescription');

		RegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Intranet\OutlookApplication',	"OnApplicationsBuildList", 100, "modules/intranet/lib/outlookapplication.php");
		RegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Intranet\PublicApplication',	"OnApplicationsBuildList", 100, "modules/intranet/lib/publicapplication.php");

		RegisterModuleDependences("rest", "OnRestAppInstall", "intranet", 'CIntranetEventHandlers', "onRestAppInstall");
		RegisterModuleDependences("rest", "OnRestAppDelete", "intranet", 'CIntranetEventHandlers', "onRestAppDelete");

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('main', 'onApplicationScopeError', 'intranet', '\Bitrix\Intranet\PublicApplication', 'onApplicationScopeError');
		$eventManager->registerEventHandler('socialservices', '\Bitrix\Socialservices\User::'.\Bitrix\Main\Entity\DataManager::EVENT_ON_AFTER_ADD, 'intranet', 'CIntranetEventHandlers', 'OnAfterSocServUserAdd');
		$eventManager->registerEventHandler('security', 'onOtpRequired', 'intranet', '\Bitrix\Intranet\Integration\Security', 'onOtpRequired');

		// for main user online status
		$eventManager->registerEventHandlerCompatible('main', 'onUserOnlineStatusGetCustomOfflineStatus', 'intranet', '\Bitrix\Intranet\UserAbsence', 'onUserOnlineStatusGetCustomOfflineStatus');

		$eventManager->registerEventHandlerCompatible('iblock', 'OnAfterIBlockElementAdd', 'intranet', '\Bitrix\Intranet\Absence\Event', 'onAfterIblockElementAdd');
		$eventManager->registerEventHandlerCompatible('iblock', 'OnAfterIBlockElementUpdate', 'intranet', '\Bitrix\Intranet\Absence\Event', 'onAfterIblockElementUpdate');
		$eventManager->registerEventHandlerCompatible('iblock', 'OnAfterIBlockElementDelete', 'intranet', '\Bitrix\Intranet\Absence\Event', 'onAfterIblockElementDelete');

		// for main user index rebuild
		$eventManager->registerEventHandlerCompatible('iblock', 'OnAfterIBlockSectionUpdate', 'intranet', '\Bitrix\Intranet\Integration\Main', 'onAfterIblockSectionUpdate');

		// for livefeed indexation
		$eventManager->registerEventHandler('socialnetwork', 'onLogCommentIndexGetContent', 'intranet', '\Bitrix\Intranet\Integration\Socialnetwork\LogComment', 'onIndexGetContent');

		$eventManager->registerEventHandler('main', 'OnUISelectorGetProviderByEntityType', 'intranet', '\Bitrix\Intranet\Integration\Main\UISelector\Handler', 'OnUISelectorGetProviderByEntityType');
		$eventManager->registerEventHandler('main', 'OnUISelectorActionProcessAjax', 'intranet', '\Bitrix\Intranet\Integration\Main\UISelector\Handler', 'OnUISelectorActionProcessAjax');

		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationEntity', 'intranet', '\Bitrix\Intranet\Integration\Rest\Configuration\Controller', 'getEntityList');
		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationExport', 'intranet', '\Bitrix\Intranet\Integration\Rest\Configuration\Controller', 'onExport');
		$eventManager->registerEventHandler('rest', 'OnRestApplicationConfigurationImport', 'intranet', '\Bitrix\Intranet\Integration\Rest\Configuration\Controller', 'onImport');
		$eventManager->registerEventHandler('rest', 'onAfterPlacementAdd::LEFT_MENU', 'intranet', '\Bitrix\Intranet\Integration\Rest\EventHandler', 'onRegisterPlacementLeftMenu');
		$eventManager->registerEventHandler('rest', 'onAfterPlacementDelete::LEFT_MENU', 'intranet', '\Bitrix\Intranet\Integration\Rest\EventHandler', 'onUnRegisterPlacementLeftMenu');

		// for control button and secretary
		$eventManager->registerEventHandler('tasks', 'onTaskUpdate', 'intranet', '\Bitrix\Intranet\Integration\Tasks', 'onTaskUpdate');
		$eventManager->registerEventHandler('calendar', 'OnAfterCalendarEntryUpdate', 'intranet', '\Bitrix\Intranet\Integration\Calendar', 'onCalendarEventUpdate');
		$eventManager->registerEventHandler('calendar', 'OnAfterCalendarEventDelete', 'intranet', '\Bitrix\Intranet\Integration\Calendar', 'OnCalendarEventDelete');

		CAgent::AddAgent('\\Bitrix\\Intranet\\UStat\\UStat::recountHourlyCompanyActivity();', "intranet", "N", 60);
		CAgent::AddAgent('\\Bitrix\\Intranet\\UStat\\UStat::recount();', "intranet", "N", 3600);

		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bitrix24"))
		{
			CAgent::AddAgent("CIntranetSharepoint::AgentLists();", "intranet", "N", 500);
			CAgent::AddAgent("CIntranetSharepoint::AgentQueue();", "intranet", "N", 300);
			CAgent::AddAgent("CIntranetSharepoint::AgentUpdate();", "intranet", "N", 3600);
		}

		\Bitrix\Main\Loader::includeModule('intranet');
		\Bitrix\Intranet\Integration\Timeman\Worktime::registerEventHandler();

		$arFields = Array(
			"ACTIVE" => "N",
			"NAME" => GetMessage("INTR_INSTALL_RATING_RULE"),
			"ENTITY_TYPE_ID" => "USER",
			"CONDITION_NAME" => "SUBORDINATE",
			"CONDITION_MODULE" => "intranet",
			"CONDITION_CLASS" => "CRatingRulesIntranet",
			"CONDITION_METHOD" => "subordinateCheck",
			"CONDITION_CONFIG" => Array(
				"SUBORDINATE" => Array(
				),
			),
			"ACTION_NAME" => "empty",
			"ACTION_CONFIG" => Array(),
			"ACTIVATE" => "N",
			"ACTIVATE_CLASS" => "empty",
			"ACTIVATE_METHOD" => "empty",
			"DEACTIVATE" => "N",
			"DEACTIVATE_CLASS" => "empty ",
			"DEACTIVATE_METHOD" => "empty",
			"~CREATED" => $DB->GetNowFunction(),
			"~LAST_MODIFIED" => $DB->GetNowFunction(),
		);
		$arFields["CONDITION_CONFIG"] = serialize($arFields["CONDITION_CONFIG"]);
		$arFields["ACTION_CONFIG"] = serialize($arFields["ACTION_CONFIG"]);
		$DB->Add("b_rating_rule", $arFields, array("ACTION_CONFIG", "CONDITION_CONFIG"));

		$this->InstallUserFields();

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		return true;
	}

	function InstallEvents()
	{
		global $DB;

		$sIn = "'INTRANET_USER_INVITATION'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] <= 0)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/events.php");
		}
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
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/components",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/gadgets",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/gadgets",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/admin",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/themes",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/themes",
				true, true
			);

			// here: set access rights for all of the services
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/tools",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/images",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/images",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/services",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/services",
				true, true
			);

			foreach (["portal", "portal_clear"] as $wizard)
			{
				if (IO\Directory::isDirectoryExists(
					$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/wizards/bitrix/".$wizard)
				)
				{
					CopyDirFiles(
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/wizards/bitrix/".$wizard,
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/".$wizard,
						true,
						true,
						true
					);
				}
			}
		}

		\Bitrix\Main\UrlRewriter::add(
			\CSite::getDefSite() ?: 's1',
			array(
				'CONDITION' => '#^/stssync/contacts/#',
				'RULE' => '',
				'ID' => 'bitrix:stssync.server',
				'PATH' => '/bitrix/services/stssync/contacts/index.php',
			)
		);

		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function InstallUserFields()
	{
		$arMess = self::__GetMessagesForAllLang(__DIR__.'/property_names.php', array(
			'UF_PHONE_INNER',
			'UF_1C',
			'UF_INN',
			'UF_DISTRICT',
			'UF_SKYPE',
			'UF_SKYPE_LINK',
			'UF_ZOOM',
			'UF_TWITTER',
			'UF_FACEBOOK',
			'UF_LINKEDIN',
			'UF_XING',
			'UF_WEB_SITES',
			'UF_SKILLS',
			'UF_INTERESTS',
			'UF_DEPARTMENT',
			'UF_EMPLOYMENT_DATE'
		));

		$arProperties = Array(

			'UF_PHONE_INNER' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_PHONE_INNER',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 2,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'S',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),

			'UF_1C' => Array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_1C',
				'USER_TYPE_ID' => 'boolean',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'I',
				'SHOW_IN_LIST' => 'N',
				'EDIT_IN_LIST' => 'N',
				'IS_SEARCHABLE' => 'Y',
				'SETTINGS' => array(
					'DISPLAY' => 'CHECKBOX',
				),
			),

			'UF_INN' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_INN',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'I',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),

			'UF_DISTRICT' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_DISTRICT',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_SKYPE' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_SKYPE',
				'USER_TYPE_ID' => 'string_formatted',
				'XML_ID' => 'UF_SKYPE',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
				'SETTINGS' => array('PATTERN' => '<a href="skype://#VALUE#">#VALUE#</a>'),
			),
			'UF_SKYPE_LINK' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_SKYPE_LINK',
				'USER_TYPE_ID' => 'url',
				'XML_ID' => 'UF_SKYPE_LINK',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_ZOOM' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_ZOOM',
				'USER_TYPE_ID' => 'url',
				'XML_ID' => 'UF_ZOOM',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'N',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_TWITTER' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_TWITTER',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_FACEBOOK' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_FACEBOOK',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_LINKEDIN' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_LINKEDIN',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_XING' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_XING',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_WEB_SITES' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_WEB_SITES',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_SKILLS' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_SKILLS',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_INTERESTS' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_INTERESTS',
				'USER_TYPE_ID' => 'string',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'Y',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
			'UF_DEPARTMENT' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_DEPARTMENT',
				'USER_TYPE_ID' => 'iblock_section',
				'XML_ID' => '',
				'SORT' => 1,
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'I',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y'
			),
			'UF_EMPLOYMENT_DATE' => array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => 'UF_EMPLOYMENT_DATE',
				'USER_TYPE_ID' => 'date',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'E',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
			),
		);

		$arLanguages = array();
		$rsLanguage = CLanguage::GetList();
		while($arLanguage = $rsLanguage->Fetch())
			$arLanguages[] = $arLanguage["LID"];

		foreach ($arProperties as $arProperty)
		{
			$dbRes = CUserTypeEntity::GetList(Array(), Array("ENTITY_ID" => $arProperty["ENTITY_ID"], "FIELD_NAME" => $arProperty["FIELD_NAME"]));
			if ($dbRes->Fetch())
				continue;

			$arLabelNames = Array();
			foreach($arLanguages as $languageID)
			{
				$arLabelNames[$languageID] = $arMess[$arProperty["FIELD_NAME"]][$languageID];
			}

			$arProperty["EDIT_FORM_LABEL"] = $arLabelNames;
			$arProperty["LIST_COLUMN_LABEL"] = $arLabelNames;
			$arProperty["LIST_FILTER_LABEL"] = $arLabelNames;

			$userType = new CUserTypeEntity();
			$userType->Add($arProperty);
		}

		\Bitrix\Main\Entity\Base::destroy(\Bitrix\Main\UserTable::getEntity());
	}


	function DoInstall()
	{
		if (!IsModuleInstalled("intranet"))
		{
			$this->InstallDB();
			$this->InstallEvents();
			$this->InstallFiles();
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $USER, $step;
		$APPLICATION->IncludeAdminFile(GetMessage("INTR_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/del_denied.php");
	}

	private static function __GetMessagesForAllLang($file, $MessID, $strDefMess = false, $arLangList = array())
	{
		$arResult = false;

		if (empty($MessID))
			return $arResult;
		if (!is_array($MessID))
			$MessID = array($MessID);

		if (empty($arLangList))
		{
			$rsLangs = CLanguage::GetList('lid', 'asc', array("ACTIVE" => "Y"));
			while ($arLang = $rsLangs->Fetch())
			{
				$arLangList[] = $arLang['LID'];
			}
		}
		foreach ($arLangList as $strLID)
		{
			$MESS = \Bitrix\Main\Localization\Loc::loadLanguageFile($file, $strLID);
			foreach ($MessID as $strMessID)
			{
				if ($strMessID == '')
					continue;
				$arResult[$strMessID][$strLID] = (isset($MESS[$strMessID]) ? $MESS[$strMessID] : $strDefMess);
			}
		}


		return $arResult;
	}

	public function migrateToBox()
	{
		\Bitrix\Main\Config\Option::set('intranet', '~bitrix24_migrated_from_cloud', 'Y');
		\Bitrix\Main\Config\Option::set('main', 'wizard_firstportal_s1', 'Y', 's1');
		\Bitrix\Main\Config\Option::set('main', '~wizard_id', 'portal', 's1');
		\Bitrix\Main\Config\Option::set('main', 'wizard_solution', 'bitrix:portal', 's1');

		if (
			\Bitrix\Main\ModuleManager::isModuleInstalled('extranet')
			&& \Bitrix\Main\IO\Directory::isDirectoryExists(\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/extranet')
		)
		{
			\Bitrix\Main\ModuleManager::delete('extranet');
		}
	}
}
?>
