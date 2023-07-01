<?
global $DOCUMENT_ROOT, $MESS;

IncludeModuleLangFile(__FILE__);

if (class_exists("calendar"))
	return;

class calendar extends CModule
{
	var $MODULE_ID = "calendar";
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

		$this->MODULE_NAME = GetMessage("CAL_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("CAL_MODULE_DESCRIPTION");
	}

	function GetModuleTasks()
	{
		return array(
			// Tasks for sections
			'calendar_denied' => array(
				"LETTER" => "D",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array()
			),
			'calendar_view_time' => array(
				"LETTER" => "O",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time'
				)
			),
			'calendar_view_title' => array(
				"LETTER" => "P",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title'
				)
			),
			'calendar_view' => array(
				"LETTER" => "R",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title',
					'calendar_view_full'
				)
			),
			'calendar_edit' => array(
				"LETTER" => "W",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title',
					'calendar_view_full',
					'calendar_add',
					'calendar_edit',
					'calendar_edit_section'
				)
			),
			'calendar_access' => array(
				"LETTER" => "X",
				"BINDING" => "calendar_section",
				"OPERATIONS" => array(
					'calendar_view_time',
					'calendar_view_title',
					'calendar_view_full',
					'calendar_add',
					'calendar_edit',
					'calendar_edit_section',
					'calendar_edit_access'
				),
			),
			// Tasks for types
			'calendar_type_denied' => array(
				"LETTER" => "D",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array()
			),
			'calendar_type_view' => array(
				"LETTER" => "R",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array(
					'calendar_type_view'
				)
			),
			'calendar_type_edit' => array(
				"LETTER" => "W",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array(
					'calendar_type_view',
					'calendar_type_add',
					'calendar_type_edit',
					'calendar_type_edit_section'
				)
			),
			'calendar_type_access' => array(
				"LETTER" => "X",
				"BINDING" => "calendar_type",
				"OPERATIONS" => array(
					'calendar_type_view',
					'calendar_type_add',
					'calendar_type_edit',
					'calendar_type_edit_section',
					'calendar_type_edit_access'
				)
			)
		);
	}

	function InstallDB()
	{
		global $DB, $APPLICATION;

		$errors = static::InstallUserFields();
		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		COption::SetOptionString("intranet", "calendar_2", "Y");

		if (!$DB->Query("SELECT 'x' FROM b_calendar_access ", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$this->MODULE_ID.'/install/db/mysql/install.sql');
		}

		$this->InstallTasks();

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("calendar");
		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->registerEventHandlerCompatible("pull", "OnGetDependentModule", "calendar", "CCalendarPullSchema", "OnGetDependentModule");
		$eventManager->registerEventHandlerCompatible("im", "OnGetNotifySchema", "calendar", "CCalendarNotifySchema", "OnGetNotifySchema");
		$eventManager->registerEventHandlerCompatible("im", "OnBeforeConfirmNotify", "calendar", "CCalendar", "HandleImCallback");
		$eventManager->registerEventHandlerCompatible('intranet', 'OnPlannerInit', 'calendar', 'CCalendarEventHandlers', 'OnPlannerInit');
		$eventManager->registerEventHandlerCompatible('intranet', 'OnPlannerAction', 'calendar', 'CCalendarEventHandlers', 'OnPlannerAction');
		$eventManager->registerEventHandlerCompatible('rest', 'OnRestServiceBuildDescription', 'calendar', 'CCalendarRestService', 'OnRestServiceBuildDescription');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'OnFillSocNetFeaturesList', 'calendar', 'CCalendarLiveFeed', 'AddEvent');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'OnSonetLogEntryMenuCreate', 'calendar', 'CCalendarLiveFeed', 'OnSonetLogEntryMenuCreate');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'OnAfterSonetLogEntryAddComment', 'calendar', 'CCalendarLiveFeed', 'OnAfterSonetLogEntryAddComment');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'OnForumCommentIMNotify', 'calendar', 'CCalendarLiveFeed', 'OnForumCommentIMNotify');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'onAfterCommentAddAfter', 'calendar', 'CCalendarLiveFeed', 'OnAfterCommentAddAfter');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'onAfterCommentUpdateAfter', 'calendar', 'CCalendarLiveFeed', 'OnAfterCommentUpdateAfter');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'onAfterCommentAddBefore', 'calendar', 'CCalendarLiveFeed', 'OnAfterCommentAddBefore');
		$eventManager->registerEventHandlerCompatible('socialnetwork', 'OnSocNetGroupDelete', 'calendar', 'CCalendar', 'OnSocNetGroupDelete');
		$eventManager->registerEventHandlerCompatible('search', 'BeforeIndex', 'calendar', 'CCalendarLiveFeed', 'FixForumCommentURL');
		$eventManager->registerEventHandlerCompatible("main", "OnAfterRegisterModule", "main", "calendar", "InstallUserFields", 100, "/modules/calendar/install/index.php"); // check webdav UF

		$eventManager->registerEventHandler("dav", "OnDavCalendarProperties", "calendar", "CCalendar", "OnDavCalendarSync");
		$eventManager->registerEventHandler("dav", "OnExchandeCalendarDataSync", "calendar", "CCalendar", "OnExchangeCalendarSync");
		$eventManager->registerEventHandler('socialnetwork', 'onLogIndexGetContent', 'calendar', '\Bitrix\Calendar\Integration\Socialnetwork\Log', 'onIndexGetContent');

		$eventManager->registerEventHandler('main', 'OnBeforeUserTypeAdd', 'calendar', '\Bitrix\Calendar\UserField\ResourceBooking', 'onBeforeUserTypeAdd');

		$eventManager->registerEventHandlerCompatible("main", "OnUserTypeBuildList", "calendar", "\\Bitrix\\Calendar\\UserField\\ResourceBooking", "getUserTypeDescription", 154);

		$eventManager->registerEventHandler('mail', 'onReplyReceivedICAL_INVENT', 'calendar', '\Bitrix\Calendar\ICal\MailInvitation\IncomingInvitationReplyHandler', 'handleFromRequest');

		$eventManager->registerEventHandler('socialnetwork', 'onSocNetUserToGroupAdd', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetUserToGroupAdd');
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetUserToGroupUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetUserToGroupUpdate');
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetUserToGroupDelete', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetUserToGroupDelete');
		$eventManager->registerEventHandler('socialnetwork', 'onSocNetGroupUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetGroupUpdate');

		$eventManager->registerEventHandler('main', 'OnBeforeUserUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnBeforeUserUpdate');
		$eventManager->registerEventHandler('main', 'OnAfterUserUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterUserUpdate');
		$eventManager->registerEventHandler('main', 'OnAfterUserAdd', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterUserAdd');
		$eventManager->registerEventHandler('main', 'OnBeforeUserDelete', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnBeforeUserDelete');
		$eventManager->registerEventHandler('main', 'OnAfterUserDelete', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterUserDelete');

		$eventManager->registerEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'onBeforeIBlockSectionUpdate');
		$eventManager->registerEventHandler('iblock', 'onAfterIBlockSectionUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'onAfterIBlockSectionUpdate');
		$eventManager->registerEventHandler('iblock', 'OnAfterIBlockSectionAdd', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterIBlockSectionAdd');

		if($DB->Query("CREATE fulltext index IXF_B_CALENDAR_EVENT_SEARCHABLE_CONTENT on b_calendar_event (SEARCHABLE_CONTENT)", true))
		{
			COption::SetOptionString("calendar", "~ft_b_calendar_event", true);
		}

		CAgent::AddAgent("\\Bitrix\\Calendar\\Sync\\Managers\\DataSyncManager::dataSyncAgent();", "calendar", "N", 60);
		CAgent::AddAgent("\\Bitrix\\Calendar\\Sync\\Util\\CleanConnectionAgent::cleanAgent();", "calendar");
		CAgent::AddAgent("\\Bitrix\\Calendar\\Sync\\Office365\\SectionManager::updateSectionsAgent();", "calendar", "N", 3600);
		if (COption::GetOptionString('calendar', 'sync_by_push', false))
		{
			CAgent::AddAgent("\\Bitrix\\Calendar\\Sync\\Managers\\PushWatchingManager::renewWatchChannels();", "calendar", "N", 3600);
		}
		else
		{
			CAgent::AddAgent("\\Bitrix\\Calendar\\Sync\\Managers\\DataExchangeManager::importAgent();", 'calendar', 'N', 180);
		}
		CAgent::AddAgent("Bitrix\\Calendar\\Core\\Queue\\Agent\\EventDelayedSyncAgent::runAgent();", "calendar", "N", 3600);
		CAgent::AddAgent("\\Bitrix\\Calendar\\Sync\\Managers\\EventQueueManager::checkEvents();", "calendar", "N", 300);
		CAgent::AddAgent("\\Bitrix\\Calendar\\Rooms\\Util\\CleanLocationEventsAgent::cleanAgent();", 'calendar', 'N', 86400);
		CAgent::AddAgent("Bitrix\\Calendar\\Core\\Queue\\Agent\\EventsWithEntityAttendeesFindAgent::runAgent();", "calendar", "N", 3600);
		CAgent::AddAgent("Bitrix\\Calendar\\Core\\Queue\\Agent\\EventAttendeesUpdateAgent::runAgent();", "calendar", "N", 3600);
		CAgent::AddAgent("\\Bitrix\\Calendar\\Sharing\\Util\\ExpiredLinkCleanAgent::runAgent();", "calendar");

		$this->InstallTemplateRules();

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;
		CAgent::RemoveModuleAgents('calendar');
		$errors = null;

		CAgent::RemoveAgent("CCalendarSync::doSync();", "calendar");

		if ((true == array_key_exists("savedata", $arParams)) && ($arParams["savedata"] != 'Y'))
		{
			$GLOBALS["USER_FIELD_MANAGER"]->OnEntityDelete("CALENDAR_EVENT");
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$this->MODULE_ID.'/install/db/mysql/uninstall.sql');

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
			$this->UnInstallTasks();
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler("pull", "OnGetDependentModule", "calendar", "CCalendarPullSchema", "OnGetDependentModule");
		$eventManager->unRegisterEventHandler("im", "OnGetNotifySchema", "calendar", "CCalendarNotifySchema", "OnGetNotifySchema");
		$eventManager->unRegisterEventHandler("im", "OnBeforeConfirmNotify", "calendar", "CCalendar", "HandleImCallback");
		$eventManager->unRegisterEventHandler('intranet', 'OnPlannerInit', 'calendar', 'CCalendarEventHandlers', 'OnPlannerInit');
		$eventManager->unRegisterEventHandler('intranet', 'OnPlannerAction', 'calendar', 'CCalendarEventHandlers', 'OnPlannerAction');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'calendar', 'CCalendarRestService', 'OnRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnFillSocNetFeaturesList', 'calendar', 'CCalendarLiveFeed', 'AddEvent');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnSonetLogEntryMenuCreate', 'calendar', 'CCalendarLiveFeed', 'OnSonetLogEntryMenuCreate');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnAfterSonetLogEntryAddComment', 'calendar', 'CCalendarLiveFeed', 'OnAfterSonetLogEntryAddComment');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnForumCommentIMNotify', 'calendar', 'CCalendarLiveFeed', 'OnForumCommentIMNotify');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onAfterCommentAddAfter', 'calendar', 'CCalendarLiveFeed', 'OnAfterCommentAddAfter');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onAfterCommentUpdateAfter', 'calendar', 'CCalendarLiveFeed', 'OnAfterCommentUpdateAfter');
		$eventManager->unRegisterEventHandler('socialnetwork', 'onAfterCommentAddBefore', 'calendar', 'CCalendarLiveFeed', 'OnAfterCommentAddBefore');
		$eventManager->unRegisterEventHandler('socialnetwork', 'OnSocNetGroupDelete', 'calendar', 'CCalendar', 'OnSocNetGroupDelete');
		$eventManager->unRegisterEventHandler('search', 'BeforeIndex', 'calendar', 'CCalendarLiveFeed', 'FixForumCommentURL');
		$eventManager->unRegisterEventHandler("main", "OnAfterRegisterModule", "main", "calendar", "InstallUserFields", "/modules/calendar/install/index.php"); // check webdav UF
		$eventManager->unRegisterEventHandler("dav", "OnDavCalendarProperties", "calendar", "CCalendar", "OnDavCalendarSync");
		$eventManager->unRegisterEventHandler("dav", "OnExchandeCalendarDataSync", "calendar", "CCalendar", "OnExchangeCalendarSync");
		$eventManager->unRegisterEventHandler('socialnetwork', 'onLogIndexGetContent', 'calendar', '\Bitrix\Calendar\Integration\Socialnetwork\Log', 'onIndexGetContent');
		$eventManager->unRegisterEventHandler('main', 'OnBeforeUserTypeAdd', 'calendar', '\Bitrix\Calendar\UserField\ResourceBooking', 'onBeforeUserTypeAdd');
		$eventManager->unRegisterEventHandler('mail', 'onReplyReceivedICAL_INVENT', 'calendar', '\Bitrix\Calendar\ICal\MailInvitation\IncomingInvitationReplyHandler', 'handleFromRequest');
		$eventManager->unregisterEventHandler('socialnetwork', 'onSocNetUserToGroupAdd', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetUserToGroupAdd');
		$eventManager->unregisterEventHandler('socialnetwork', 'onSocNetUserToGroupUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetUserToGroupUpdate');
		$eventManager->unregisterEventHandler('socialnetwork', 'onSocNetUserToGroupDelete', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetUserToGroupDelete');
		$eventManager->unregisterEventHandler('socialnetwork', 'onSocNetGroupUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\SocNetGroup', 'onSocNetGroupUpdate');
		$eventManager->unregisterEventHandler('main', 'OnBeforeUserUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnBeforeUserUpdate');
		$eventManager->unregisterEventHandler('main', 'OnAfterUserUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterUserUpdate');
		$eventManager->unregisterEventHandler('main', 'OnAfterUserAdd', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterUserAdd');
		$eventManager->unregisterEventHandler('main', 'OnBeforeUserDelete', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnBeforeUserDelete');
		$eventManager->unregisterEventHandler('main', 'OnAfterUserDelete', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterUserDelete');
		$eventManager->unregisterEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'onBeforeIBlockSectionUpdate');
		$eventManager->unregisterEventHandler('iblock', 'onAfterIBlockSectionUpdate', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'onAfterIBlockSectionUpdate');
		$eventManager->unregisterEventHandler('iblock', 'OnAfterIBlockSectionAdd', 'calendar', '\Bitrix\Calendar\Watcher\Membership\Handler\Department', 'OnAfterIBlockSectionAdd');

		UnRegisterModule("calendar");

		// Clear cache
		$arPath = array(
			'access_tasks',
			'type_list',
			'section_list',
			'attendees_list',
			'event_list'
		);
		$cache = new CPHPCache;
		foreach($arPath as $path)
		{
			if ($path != '')
			{
				$cache->CleanDir("calendar/" . $path);
			}
		}

		// Remove tasks from LiveFeed
		if (
			IsModuleInstalled('socialnetwork')
			&& CModule::IncludeModule('socialnetwork')
		)
		{
			$dbRes = CSocNetLog::GetList(
				array(),
				array("EVENT_ID" => "calendar"),
				false,
				false,
				array("ID")
			);

			if ($dbRes)
			{
				while ($arRes = $dbRes->Fetch())
				{
					CSocNetLog::Delete($arRes["ID"]);
				}
			}
		}

		// Remove tasks from IM
		if (IsModuleInstalled('im') && CModule::IncludeModule('im') && method_exists('CIMNotify', 'DeleteByModule'))
		{
			CIMNotify::DeleteByModule('calendar');
		}

		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Managers\\DataSyncManager::dataSyncAgent();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Util\\CleanConnectionAgent::cleanAgent();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Office365\\SectionManager::updateSectionsAgent();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Managers\\PushWatchingManager::renewWatchChannels();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Managers\\DataExchangeManager::importAgent();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\GoogleApiPush::createWatchChannels(0);", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\GoogleApiPush::processPush();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\GoogleApiPush::renewWatchChannels();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\GoogleApiPush::checkPushChannel();", "calendar");
		CAgent::RemoveAgent("CCalendarSync::doSync();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Google\\QueueManager::checkNotSendEvents();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Google\\QueueManager::checkIncompleteSync();", 'calendar');
		CAgent::RemoveAgent("Bitrix\\Calendar\\Core\\Queue\\Agent\\EventsWithEntityAttendeesFindAgent::runAgent();", "calendar");
		CAgent::RemoveAgent("Bitrix\\Calendar\\Core\\Queue\\Agent\\EventAttendeesUpdateAgent::runAgent();", "calendar");
		CAgent::RemoveAgent("Bitrix\\Calendar\\Core\\Queue\\Agent\\EventDelayedSyncAgent::runAgent();", "calendar");
		CAgent::RemoveAgent("Bitrix\\Calendar\\Core\\Queue\\Agent\\EventDelayedSyncAgent::runAgent();", "calendar");
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Sync\\Managers\\EventQueueManager::checkEvents();", 'calendar');
		CAgent::RemoveAgent("\\Bitrix\\Calendar\\Rooms\\Util\\CleanLocationEventsAgent::cleanAgent();", 'calendar');

		$templateCheck = \Bitrix\Main\SiteTemplateTable::getList([
			'filter' => [
				'TEMPLATE' => 'calendar_sharing',
			]
		])->fetch();
		if ($templateCheck)
		{
			\Bitrix\Main\SiteTemplateTable::delete($templateCheck['ID']);
		}

		return true;
	}

	function InstallTemplateRules()
	{
		if (
			file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/templates/pub/")
			&& !file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/pub/")
		)
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/templates/pub/",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/pub/",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = false
			);
		}

		$default_site_id = CSite::GetDefSite();
		if ($default_site_id)
		{
			$sharingTemplateFound = false;
			$sharingTemplate = [
				'SORT' => 0,
				'CONDITION' => "CSite::InDir('/pub/calendar-sharing/')",
				'TEMPLATE' => 'calendar_sharing'
			];

			$arFields = ["TEMPLATE"=>[]];
			$dbTemplates = CSite::GetTemplateList($default_site_id);
			while($template = $dbTemplates->Fetch())
			{
				if ($template["CONDITION"] === "CSite::InDir('/pub/calendar-sharing/')")
				{
					$sharingTemplateFound = true;
					$template = $sharingTemplate;
				}

				$arFields["TEMPLATE"][] = [
					"SORT" => $template['SORT'],
					"CONDITION" => $template['CONDITION'],
					"TEMPLATE" => $template['TEMPLATE'],
				];
			}
			if (!$sharingTemplateFound)
			{
				$arFields["TEMPLATE"][] = $sharingTemplate;
			}

			$obSite = new CSite;
			$arFields["LID"] = $default_site_id;
			$obSite->Update($default_site_id, $arFields);
		}

		return true;
	}

	function InstallEvents()
	{
		global $DB;

		$sIn = "'CALENDAR_INVITATION'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();

		if($ar["C"] <= 0)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/events.php");
		}

		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		$sIn = "'CALENDAR_INVITATION', 'SEND_ICAL_INVENT', 'CALENDAR_SHARING'";
		$DB->Query("DELETE FROM b_event_message WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}

	public static function InstallUserFields($id = "all")
	{
		global $APPLICATION;
		$errors = null;

		if(($id == 'all' || $id == 'disk') && IsModuleInstalled('disk'))
		{
			$uf = new CUserTypeEntity;
			$rsData = CUserTypeEntity::getList(array("ID" => "ASC"), array("ENTITY_ID" => 'CALENDAR_EVENT', "FIELD_NAME" => 'UF_WEBDAV_CAL_EVENT'));
			if (!($rsData && ($arRes = $rsData->Fetch())))
			{
				$intID = $uf->add(array(
					"ENTITY_ID" => 'CALENDAR_EVENT',
					"FIELD_NAME" => 'UF_WEBDAV_CAL_EVENT',
					"XML_ID" => 'UF_WEBDAV_CAL_EVENT',
					"USER_TYPE_ID" => 'disk_file',
					"SORT" => 100,
					"MULTIPLE" => "Y",
					"MANDATORY" => "N",
					"SHOW_FILTER" => "N",
					"SHOW_IN_LIST" => "N",
					"EDIT_IN_LIST" => "Y",
					"IS_SEARCHABLE" => "Y"
				), false);

				if (false == $intID && ($strEx = $APPLICATION->getException()))
				{
					$errors[] = $strEx->getString();
				}
			}
		}

		if(($id == 'all' || $id == 'webdav') && IsModuleInstalled('webdav'))
		{
			$ENTITY_ID = 'CALENDAR_EVENT';
			$FIELD_NAME = 'UF_WEBDAV_CAL_EVENT';
			$arElement = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($ENTITY_ID, 0);
			if (empty($arElement) || $arElement == array() ||$arElement == false || !isset($arElement[$FIELD_NAME]))
			{
				$arFields = array(
					"ENTITY_ID" => $ENTITY_ID,
					"FIELD_NAME" => $FIELD_NAME,
					"XML_ID" => $FIELD_NAME,
					"USER_TYPE_ID" => "webdav_element",
					"SORT" => 100,
					"MULTIPLE" => "Y",
					"MANDATORY" => "N",
					"SHOW_FILTER" => "N",
					"SHOW_IN_LIST" => "N",
					"EDIT_IN_LIST" => "Y",
					"IS_SEARCHABLE" => "N"
				);

				$obUserField  = new CUserTypeEntity;
				$intID = $obUserField->Add($arFields, false);
				if (false == $intID)
				{
					if ($strEx = $APPLICATION->GetException())
					{
						$errors[] = $strEx->GetString();
					}
				}
			}
		}

		return $errors;
	}

	function InstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/tools",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/components",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/admin",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/images",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/images",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/activities",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/activities",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/services",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/services",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/templates",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates",
				true, true
			);

			$siteId = \CSite::GetDefSite();
			if ($siteId)
			{
				\Bitrix\Main\UrlRewriter::add($siteId, array(
					"CONDITION" => "#^/stssync/calendar/#",
					"RULE" => "",
					"ID" => "bitrix:stssync.server",
					"PATH" => "/bitrix/services/stssync/calendar/index.php",
				));
			}
		}

		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFilesEx('/bitrix/templates/calendar_sharing/');
		}

		return true;
	}

	function DoInstall()
	{
		global $APPLICATION;

		if (!IsModuleInstalled("calendar"))
		{
			$this->InstallFiles();
			$this->InstallDB();
			$this->InstallEvents();

			$GLOBALS["errors"] = $this->errors;

			$APPLICATION->IncludeAdminFile(GetMessage("CAL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/step1.php");
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $USER, $step;
		if($USER->IsAdmin())
		{
			$step = intval($step);

			if (\Bitrix\Main\ModuleManager::isModuleInstalled('calendarmobile'))
			{
				$APPLICATION->throwException(GetMessage('CAL_MODULE_UNINSTALL_ERROR_CALENDARMOBILE'));
			}

			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("CAL_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));
				$this->UnInstallEvents();
				$this->UnInstallFiles();

				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("CAL_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/install/unstep2.php");
			}
		}
	}

	function InstallDemoCalendarType()
	{

	}
}
?>
