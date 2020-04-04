<?
IncludeModuleLangFile(__FILE__);

class CEventCalendar
{
	function Init($arParams)
	{
		global $USER;
		// Owner params
		$this->ownerType = $arParams['ownerType'];

		if (CModule::IncludeModule('calendar') && COption::GetOptionString("intranet", "calendar_2", "N") == "Y")
		{
			$this->reserveMeetingReadonlyMode = $arParams['reserveMeetingReadonlyMode'];
			$this->pathToReserveNew = CHTTP::urlAddParams(preg_replace("/#user_id#/i", $USER->GetID(), $arParams['pathToUserCalendar']), array('EVENT_ID' => 'NEW', 'CHOOSE_MR' => 'Y'));
		}

		$this->ownerId = intVal($arParams['ownerId']);
		$this->bOwner = $this->ownerType == 'GROUP' || $this->ownerType == 'USER';
		$this->curUserId = $USER->GetID();
		$this->bSocNet = $this->IsSocNet();

		if (!$this->bSocNet)
		{
			$arParams['allowSuperpose'] = false;
		}

		// Data source
		$this->iblockId = intVal($arParams['iblockId']);

		// Cache params
		$this->cachePath = "event_calendar/";
		$this->cacheTime = intVal($arParams['cacheTime']);
		$this->bCache = $this->cacheTime > 0;

		// Urls
		$page = preg_replace(
			array(
				"/action=.*?\&/i",
				"/bx_event_calendar_request=.*?\&/i",
				"/clear_cache=.*?\&/i",
				"/bitrix_include_areas=.*?\&/i",
				"/bitrix_show_mode=.*?\&/i",
				"/back_url_admin=.*?\&/i"
			),
			"", $arParams['pageUrl'].'&'
		);
		$page = preg_replace(array("/^(.*?)\&$/i","/^(.*?)\?$/i"), "\$1", $page);
		$this->pageUrl = $page;
		$this->fullUrl = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST'].$page;
		$this->outerUrl = $GLOBALS['APPLICATION']->GetCurPageParam('', array("action", "bx_event_calendar_request", "clear_cache", "bitrix_include_areas", "bitrix_show_mode", "back_url_admin", "SEF_APPLICATION_CUR_PAGE_URL"), false);

		$this->userIblockId = $arParams['userIblockId'];
		//$iblockId = COption::GetOptionInt("intranet", 'iblock_calendar'); // Get iblock id for users calendar from module-settings

		// Superposing
		$this->allowSuperpose = $arParams['allowSuperpose'];
		if (!$USER->IsAuthorized())
			$this->allowSuperpose = false;

		if ($this->allowSuperpose)
		{
			$this->allowAdd2SP = $this->ownerType == 'USER';
			$this->arSPIblIds = is_array($arParams['arSPIblIds']) ? $arParams['arSPIblIds'] : array();
			$this->spGroupsIblId = intVal($arParams['spGroupsIblId']); // iblock id for groups calendars
			$this->superposeGroupsCals = $this->spGroupsIblId > 0 ? $arParams['superposeGroupsCals'] : false;
			$this->superposeUsersCals = $this->userIblockId > 0 ? $arParams['superposeUsersCals'] : false;
			$this->superposeCurUserCals = $arParams['superposeCurUserCals'];
			$this->arSPCalsDisplayedDef = is_array($arParams['arSPCalDispDef']) ? $arParams['arSPCalDispDef'] : array();
			$this->addCurUserCalDispByDef = $this->superposeCurUserCals && $arParams['addCurUserCalDispByDef'];
			$this->superposeExportLink = $this->GetSPExportLink();
		}

		// Reserve meeting and reserve video meeting
		$this->allowResMeeting = $arParams["allowResMeeting"] && $arParams["RMiblockId"] > 0;
		$this->RMiblockId = $arParams["RMiblockId"];
		$this->RMPath = $arParams["RMPath"];
		$this->RMUserGroups = $arParams["RMUserGroups"];
		// Check groups for reserve meeting access
		if(!$USER->IsAdmin() && (CIBlock::GetPermission($this->RMiblockId) < "R" || count(array_intersect($USER->GetUserGroupArray(), $this->RMUserGroups)) <= 0))
			$this->allowResMeeting = false;

		// Check groups for reserve video meeting access
		$this->allowVideoMeeting = $arParams["allowVideoMeeting"] && $arParams["VMiblockId"] > 0;
		$this->VMiblockId = $arParams["VMiblockId"];
		$this->VMPath = $arParams["VMPath"];
		$this->VMPathDetail = $arParams["VMPathDetail"];
		$this->VMUserGroups = $arParams["VMUserGroups"];

		if(!$USER->IsAdmin() && (CIBlock::GetPermission($this->VMiblockId) < "R" || count(array_intersect($USER->GetUserGroupArray(), $this->VMUserGroups)) <= 0))
			$this->allowVideoMeeting = false;

		// Init variables
		$this->arCalenderIndex = array();
		$this->sSPCalKey = "SP_CAL_DISPLAYED";
		$this->sSPTrackingUsersKey = "SP_CAL_TRACKING_USERS";

		$this->bSocNetLog = true;
		$this->pathToUser = $arParams['pathToUser'];
		$this->pathToUserCalendar = $arParams['pathToUserCalendar'];
		$this->pathToGroupCalendar = $arParams['pathToGroupCalendar'];
		$this->newSectionId = 'none';
		$this->reinviteParamsList = $arParams['reinviteParamsList'];

		$this->bExtranet = CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite();

		if (!class_exists('CUserOptions'))
			include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");
	}

	function GetPermissions($arParams = array())
	{
		global $USER;
		$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
		$bOwner = isset($arParams['bOwner']) ? $arParams['bOwner'] : $this->bOwner;
		$ownerId = isset($arParams['ownerId']) ? $arParams['ownerId'] : $this->ownerId;
		$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
		$bCurUser = !isset($arParams['userId']) || $arParams['userId'] == $GLOBALS['USER']->GetID();
		$userId = $bCurUser ? $GLOBALS['USER']->GetID() : intVal($arParams['userId']);
		$bCheckSocNet = !isset($arParams['bCheckSocNet']) || $arParams['bCheckSocNet'] !== false;

		if ($bCurUser)
		{
			$maxPerm = CIBlock::GetPermission($iblockId);
		}
		else
		{
			$arGroups = CUser::GetUserGroup($userId);
			$arGroupPerm = CIBlock::GetGroupPermissions($iblockId);
			$maxPerm = 'D';
			foreach($arGroupPerm as $k => $perm)
				if (in_array($k, $arGroups) && $perm > $maxPerm)
					$maxPerm = $perm;
		}
		$bAccess = $maxPerm >= 'R';
		$bCurUserOwner = $ownerType != 'USER' || $this->ownerId == $userId;
		$bReadOnly = $bAccess && $maxPerm < 'W';

		// $maxPerm :
		// 	D - denied
		//	R - check SocNet permissions (if it's socnet)
		//	>W - turn on GOD-mode

		/* modified by wladart */
		// to re-calc readonly even if it's not readonly by iblock settings, for all users
		//if ($bCheckSocNet && class_exists('CSocNetFeatures'))
		if ($bCheckSocNet && $bOwner && class_exists('CSocNetFeatures')) // Check permissions for SocNet
		{
			$SONET_ENT = $ownerType == 'USER' ? SONET_ENTITY_USER : SONET_ENTITY_GROUP;
			if (!CSocNetFeatures::IsActiveFeature($SONET_ENT, $ownerId, "calendar"))
			{
				$bAccess = false;
				$bReadOnly = true;
			}
			else
			{
				$bAccess = CSocNetFeaturesPerms::CanPerformOperation($userId, $SONET_ENT, $ownerId, "calendar", 'view');
				$bReadOnly = !CSocNetFeaturesPerms::CanPerformOperation($userId, $SONET_ENT, $ownerId, "calendar", 'write');
			}
		}

		if (!$bCurUserOwner)
			$bReadOnly = true;

		if ($arParams['setProperties'] !== false)
		{
			$this->bAccess = $bAccess;
			$this->bReadOnly = $bReadOnly;
			$this->bCurUser = $bCurUser;
			$this->userId = $userId;
			$this->userName = $USER->IsAuthorized() ? $USER->GetFirstName()." ".$USER->GetLastName() : 'Unknown user';
			$this->bCurUserOwner = $bCurUserOwner;
		}

		return array('bAccess' => $bAccess, 'bReadOnly' => $bReadOnly);
	}

	function Show($Params)
	{
		global $APPLICATION, $USER, $EC_UserFields;

		$this->GetPermissions(array('userId' => $curUserId));
		if (!$this->bAccess)
			return $APPLICATION->ThrowException(GetMessage("EC_ACCESS_DENIED"));

		if ($this->reserveMeetingReadonlyMode)
			$this->bReadOnly = true;

		$arCalendars = $this->GetCalendarsEx(); // Cache inside
		$sectionId = $this->GetSectionId();

		// * * * HANDLE SUPERPOSED CALENDARS  * * *
		if ($this->allowSuperpose)
			$this->HandleSuperpose($this->arSPIblIds, true);

		$arCalendarIds = $this->GetUserActiveCalendars();

		// Show popup event at start
		if (isset($_GET['EVENT_ID']) && intVal($_GET['EVENT_ID']) > 0)
		{
			$eventId = intVal($_GET['EVENT_ID']);
			$bDelEvent = false;
			$rsEvent = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $this->iblockId, "ACTIVE" => "Y", "ID" => $eventId, "CHECK_PERMISSIONS" => "N"), false, false, array("ACTIVE_FROM", "IBLOCK_SECTION_ID", "CREATED_BY", "PROPERTY_CONFIRMED"));

			if ($ev = $rsEvent->Fetch())
			{
				if ($ev["CREATED_BY"] == $USER->GetId() && isset($_GET['CONFIRM']) && $ev["PROPERTY_CONFIRMED_ENUM_ID"] == $this->GetConfirmedID($this->iblockId, "Q"))
				{
					$this->GenEventDynClose($eventId);
					if ($_GET['CONFIRM'] == 'Y')
					{
						$this->ClearCache($this->cachePath.'events/'.$this->iblockId.'/');
						$this->ConfirmEvent(array('id' => $eventId, 'bCheckOwner' => false));
					}
					elseif ($_GET['CONFIRM'] == 'N')
					{
						$this->ClearCache($this->cachePath.'events/'.$this->iblockId.'/');

						CECEvent::Delete(array(
							'id' => $eventId,
							'iblockId' => $this->iblockId,
							'ownerType' => $this->ownerType,
							'ownerId' => $this->ownerId,
							'userId' => $USER->GetId(),
							'pathToUserCalendar' => $this->pathToUserCalendar,
							'RMiblockId' => $this->allowResMeeting ? $this->RMiblockId : 0,
							'allowResMeeting' => $this->allowResMeeting,

							'VMiblockId' => $this->allowVideoMeeting ? $this->VMiblockId : 0,
							'allowVideoMeeting' => $this->allowVideoMeeting,
						));
						$bDelEvent = true; // Event was deleted
						$eventId = false;
					}
				}
				elseif($ev["CREATED_BY"] == $USER->GetId() && isset($_GET['CLOSE_MESS']) && $_GET['CLOSE_MESS']=='Y')
				{
					$this->GenEventDynClose($eventId);
				}

				if (!$bDelEvent)
				{
					// If user turn off this calendar
					if (!in_array($ev["IBLOCK_SECTION_ID"], $arCalendarIds))
						$arCalendarIds[] = $ev["IBLOCK_SECTION_ID"];
					if (!isset($_GET['EVENT_DATE']))
						$date = $ev['ACTIVE_FROM'];
					else
						$date = $_GET['EVENT_DATE'];

					$ts = MakeTimeStamp($date, getTSFormat());
					$startup_event_date = date(getDateFormat(false), $ts);
					$init_month = date('m', $ts);
					$init_year = date('Y', $ts);
				}
			}
			$arStartupEvent = $eventId ? array('id' => $eventId, 'date' => $startup_event_date) : false;
		}
		else
		{
			$arStartupEvent = false;
		}

		if (!$init_month && !$init_year && strlen($Params["initDate"]) > 0 && strpos($Params["initDate"], '.') !== false)
		{
			$ts = MakeTimeStamp($Params["initDate"], getTSFormat());
			$init_month = date('m', $ts);
			$init_year = date('Y', $ts);
		}

		if (!isset($init_month))
			$init_month = date("m");
		if (!isset($init_year))
			$init_year = date("Y");

		$id = 'EC'.rand().'_';

		if (!isset($Params['weekHolidays']))
			$Params['weekHolidays'] = array(5, 6);

		if ($Params["workTime"][0] <= 0)
			$Params["workTime"][0] = 9;
		if ($Params["workTime"][1] <= 0)
			$Params["workTime"][1] = 19;

		if (isset($Params['yearHolidays']))
		{
			$Params['yearHolidays'] = explode(',', $Params['yearHolidays']);
			array_walk($Params['yearHolidays'], 'trim_');
		}
		else
		{
			$Params['yearHolidays'] = array();
		}

		$arCalColors = Array('#CEE669','#E6A469','#98AEF6','#7DDEC2','#B592EC','#D98E85','#F6EA68','#DDBFEB');

		$arStrWeek = array(
			array(GetMessage('EC_MON_F'), GetMessage('EC_MON')),
			array(GetMessage('EC_TUE_F'), GetMessage('EC_TUE')),
			array(GetMessage('EC_WEN_F'), GetMessage('EC_WEN')),
			array(GetMessage('EC_THU_F'), GetMessage('EC_THU')),
			array(GetMessage('EC_FRI_F'), GetMessage('EC_FRI')),
			array(GetMessage('EC_SAT_F'), GetMessage('EC_SAT')),
			array(GetMessage('EC_SAN_F'), GetMessage('EC_SAN'))
		);

		$arStrMonth = array(GetMessage('EC_JAN'), GetMessage('EC_FEB'), GetMessage('EC_MAR'), GetMessage('EC_APR'), GetMessage('EC_MAY'), GetMessage('EC_JUN'), GetMessage('EC_JUL'), GetMessage('EC_AUG'), GetMessage('EC_SEP'), GetMessage('EC_OCT'), GetMessage('EC_NOV'), GetMessage('EC_DEC'));
		$arStrMonth_R = array(GetMessage('EC_JAN_R'), GetMessage('EC_FEB_R'), GetMessage('EC_MAR_R'), GetMessage('EC_APR_R'), GetMessage('EC_MAY_R'), GetMessage('EC_JUN_R'), GetMessage('EC_JUL_R'), GetMessage('EC_AUG_R'), GetMessage('EC_SEP_R'), GetMessage('EC_OCT_R'), GetMessage('EC_NOV_R'), GetMessage('EC_DEC_R'));

		$EC_UserFields = false;
		$this->CheckSectionProperties($this->iblockId, $this->ownerType);
		$UserSettings = $this->GetUserSettings();

		if (!$this->bReadOnly && count($arCalendars) > 0)
			$this->bShowBanner = true;

		if (isset($UserSettings['ShowBanner']) && !$UserSettings['ShowBanner'])
			$this->bShowBanner = false;

		$JSConfig = Array(
			'sessid' => bitrix_sessid(),
			'month' => $arStrMonth,
			'month_r' => $arStrMonth_R,
			'days' => $arStrWeek,
			'id' => $id,
			'week_holidays' => $Params['weekHolidays'],
			'year_holidays' => $Params['yearHolidays'],
			'iblockId' => $this->iblockId,
			'init_month' => $init_month,
			'init_year' => $init_year,
			'arCalColors' => $arCalColors,
			'bReadOnly' => $this->bReadOnly,
			'ownerType' => $this->ownerType,
			'ownerId' => $this->ownerId,
			'userId' => $this->userId,
			'userName' => $this->userName,
			'section_id' => $sectionId,
			'arCalendars' => $arCalendars,
			'arCalendarIds' => $arCalendarIds,
			'page' => $this->pageUrl,
			'fullUrl' => $this->fullUrl,
			'startupEvent' => $arStartupEvent,
			'bSuperpose' => $this->bSuperpose,
			'arSPCalendars' => $this->arSPCal,
			'arSPCalendarsShow' => $this->arSPCalShow,
			'superposeExportLink' => $this->superposeExportLink,
			'bSPUserCals' => $this->superposeUsersCals || $this->superposeCurUserCals,
			'SP' => $this->GetCurCalsSPParams(),
			'allowAdd2SP' => $this->allowAdd2SP,
			'workTime' => $Params["workTime"],
			'Settings' => $UserSettings,
			'bSocNet' => $this->bSocNet,
			'pathToUser' => $this->pathToUser,
			'dateFormat' => CSite::GetDateFormat("SHORT", SITE_ID),
			'meetingRooms' => $this->GetMeetingRoomList(),
			'allowResMeeting' => $this->allowResMeeting,
			'allowVideoMeeting' => $this->allowVideoMeeting,
			'bExtranet' => $this->bExtranet,
			'bShowBanner' => $this->bShowBanner,
			'planner_js_src' => '/bitrix/js/intranet/event_calendar/planner.js?v='.filemtime($_SERVER['DOCUMENT_ROOT']."/bitrix/js/intranet/event_calendar/planner.js"),
			'reserveMeetingReadonlyMode' => $this->reserveMeetingReadonlyMode
		);

		if ($this->reserveMeetingReadonlyMode)
			$JSConfig['pathToReserveNew'] = $this->pathToReserveNew;

		if (CEventCalendar::IsCalDAVEnabled() && $this->ownerType == "USER")
		{
			$JSConfig['bCalDAV'] = true;
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
				$serverName = SITE_SERVER_NAME;
			else
				$serverName = COption::GetOptionString("main", "server_name", $_SERVER["SERVER_NAME"]);

			$JSConfig['caldav_link_all'] = (CMain::IsHTTPS() ? "https://" : "http://").$serverName;
			if ($this->ownerType == 'USER')
			{
				if ($this->ownerId == $this->userId)
				{
					$login = $USER->GetLogin();
				}
				else
				{
					$rsUser = CUser::GetByID($this->ownerId);
					if($arUser = $rsUser->Fetch())
						$login = $arUser['LOGIN'];
				}

				$JSConfig['caldav_link_one'] = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER["SERVER_NAME"]."/bitrix/groupdav.php/".SITE_ID."/".$login."/calendar/#CALENDAR_ID#/";
			}
			else if ($this->ownerType == 'GROUP')
			{
				$JSConfig['caldav_link_one'] = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER["SERVER_NAME"]."/bitrix/groupdav.php/".SITE_ID."/group-".$this->ownerId."/calendar/#CALENDAR_ID#/";
			}

			if ($this->ownerType == 'USER')
			{
				$res = CDavConnection::GetList(
					array("ID" => "DESC"),
					array("ENTITY_TYPE" => "user", "ENTITY_ID" => $this->ownerId, "ACCOUNT_TYPE" => 'caldav'),
					false,
					false
				);

				$arConnections = array();
				while($arCon = $res->Fetch())
				{
					$arConnections[] = array(
						'id' => $arCon['ID'],
						'name' => $arCon['NAME'],
						'link' => $arCon['SERVER'],
						'user_name' => $arCon['SERVER_USERNAME'],
						'last_result' => $arCon['LAST_RESULT'],
						'sync_date' => $arCon['SYNCHRONIZED']
					);
				}
				$JSConfig['connections'] = $arConnections;
			}
		}

		if (CEventCalendar::IsExchangeEnabled() && $this->ownerType == 'USER')
		{
			$JSConfig['bExchange'] = true;
		}

		$from_limit = date(getDateFormat(false), mktime(0, 0, 0, $init_month - 1, 20, $init_year));
		$to_limit = date(getDateFormat(false), mktime(0, 0, 0, $init_month + 1, 10, $init_year));
		$this->SetLoadLimits($init_month, $init_year);

		if ($sectionId !== false)
		{
			// Get events  (*Cache Inside)
			$ids = array();
			for ($i = 0, $l = count($arCalendars); $i < $l; $i++)
			{
				if (in_array($arCalendars[$i]['ID'], $arCalendarIds))
					$ids[] = $arCalendars[$i]['ID'];
			}
			$JS_arEvents = $this->GetEventsEx(array(
				"bJS" => true,
				"arCalendarIds" => $ids,
				'DontSaveOptions' => true,
				'checkPermissions' => false
			));

			// Get events from superposed calendars *Favorite calendars*
			$JS_arSPEvents = $this->GetSuperposedEvents(array('bJS' => true));
		}
		else
		{
			$JS_arEvents = '[]';
			$JS_arSPEvents = '[]';
		}

		$APPLICATION->AddHeadString('<link rel="stylesheet" type="text/css" href="'.CUtil::GetAdditionalFileURL("/bitrix/js/intranet/event_calendar/event_calendar.css").'">');

		// Build calendar base html and dialogs
		CEventCalendar::BuildCalendarSceleton(array(
			'bExtranet' => $this->bExtranet,
			'bReadOnly' => $this->bReadOnly,
			'id' => $id,
			'arCalendarsCount' => count($arCalendars),
			'allowSuperpose' => $this->allowSuperpose ,
			'bSocNet' => $this->bSocNet,
			'week_days' => $arStrWeek,
			'ownerId' => $this->ownerId,
			'ownerType' => $this->ownerType,
			'component' => $component,
			'bShowBanner' => $this->bShowBanner
		));

		// Append Javascript files and CSS files
		$this->AppendJS(array(
			'JSConfig' => CUtil::PhpToJSObject($JSConfig),
			'JS_arEvents' => $JS_arEvents,
			'JS_arSPEvents' => $JS_arSPEvents
		));
	}

	function AppendJS($Params)
	{
		global $USER;
		CUtil::InitJSCore(array('ajax', 'window'));

		// Add scripts
		$arJS = array(
			'/bitrix/js/intranet/event_calendar/core.js',
			'/bitrix/js/intranet/event_calendar/dialogs.js',
			'/bitrix/js/intranet/event_calendar/week.js',
			'/bitrix/js/intranet/event_calendar/events.js',
			'/bitrix/js/intranet/event_calendar/controlls.js'
		);
		$arJS[] = '/bitrix/js/main/utils.js';

		$arCSS = array();
		if (!$USER->IsAuthorized()) // For anonymus  users
		{
			$arCSS[] = '/bitrix/themes/.default/pubstyles.css';
		}

		for($i = 0, $l = count($arJS); $i < $l; $i++)
			$arJS[$i] .= '?v='.filemtime($_SERVER['DOCUMENT_ROOT'].$arJS[$i]);

		for($i = 0, $l = count($arCSS); $i < $l; $i++)
			$arCSS[$i] .= '?v='.filemtime($_SERVER['DOCUMENT_ROOT'].$arCSS[$i]);

		?>
		<script>
		<?CEventCalendar::AppendLangMessages();?>
		BX.ready(function()
			{
				window.bxRunEC = function()
				{
					if (!window.JCEC || !window.ECCalMenu ||
					!window.ECMonthSelector || !window.ECUserControll)
						return setTimeout(window.bxRunEC, 100);

					new JCEC(<?=$Params['JSConfig']?>, <?=$Params['JS_arEvents']?>, <?=$Params['JS_arSPEvents']?>);
				};

				<?if (count($arCSS) > 0):?>
				BX.loadCSS(<?= '["'.implode($arCSS, '","').'"]'?>);
				<?endif;?>
				BX.loadScript(<?= '["'.implode($arJS, '","').'"]'?>, bxRunEC);
			}
		);
		</script>
		<?
	}

	function Request($action)
	{
		global $APPLICATION;
		$sectionId = ($_REQUEST['section_id'] == 'none') ? 'none' : intVal($_REQUEST['section_id']);
		CUtil::JSPostUnEscape();

		// Export calendar
		if ($action == 'export')
		{
			// We don't need to check access  couse we will check security SIGN from the URL
			$bCheck = $_GET['check'] == 'Y';
			$calendarId = $_GET['calendar_id'];
			if ($bCheck) // Just for access check from calendar interface
			{
				$GLOBALS['APPLICATION']->RestartBuffer();
				if ($this->CheckSign($_GET['sign'], intVal($_GET['user_id']), $calendarId > 0 ? $calendarId : 'superposed_calendars'))
					echo 'BEGIN:VCALENDAR';
				die();
			}

			if (!isset($calendarId) || intVal($calendarId) <= 0) // Export entire view
			{
				$this->ReturnICal_SP(array(
					'userId' => intVal($_GET['user_id']),
					'sign' => $_GET['sign']
				));
			}
			else // Export calendar
			{
				$this->ReturnICal(array(
					'calendarId' => intVal($calendarId),
					'userId' => intVal($_GET['user_id']),
					'sign' => $_GET['sign'],
					'ownerType' => $_GET['owner_type'],
					'ownerId' => $_GET['owner_id'],
					'iblockId' => $_GET['ibl']
				));
			}
		}
		else
		{
			// First of all - CHECK ACCESS
			$this->GetPermissions(array('userId' => $curUserId));
			if (!$this->bAccess)
				return $APPLICATION->ThrowException(GetMessage("EC_ACCESS_DENIED"));

			$APPLICATION->RestartBuffer();
			if (!check_bitrix_sessid())
			{
				echo '<!--BX_EC_DUBLICATE_ACTION_REQUEST'.bitrix_sessid().'-->';
				return;
			}

			switch ($action)
			{
				// * * * * * Add and Edit event * * * * *
				case 'add':
				case 'edit':
					if ($this->bReadOnly)
						return $this->ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$id = intVal($_POST['id']);
					// If other calendar was selected for event
					if ($_POST['b_recreate'] == 'Y' && intVal($_POST['old_calendar']))
					{
						$old_id = $id;
						$id = 0;
						$action = 'add';
					}

					$from_ts = MakeTimeStamp($_POST['from'], getTSFormat());
					$to_ts = MakeTimeStamp($_POST['to'], getTSFormat());

					$arGuests = isset($_POST['guest']) ? $_POST['guest'] : false;

					$bPeriodic = isset($_POST['per_type']) && $_POST['per_type'] != 'none';
					if($bPeriodic)
					{
						$per_type = trim($_POST['per_type']);
						$per_count = intVal($_POST['per_count']);

						$per_from_ts = MakeTimeStamp($_POST['per_from'], getTSFormat());
						if ($per_from_ts < $from_ts)
							$per_from_ts = mktime(date("H", $from_ts), date("i", $from_ts), date("s", $from_ts), date("m", $per_from_ts), date("d", $per_from_ts), date("Y", $per_from_ts)); // Set time of current event for all events in period
						else
							$per_from_ts = $from_ts;

						$per_from = date(getDateFormat(), $per_from_ts);
						$per_ts = ($_POST['per_to'] == 'no_limit') ? 2145938400 : MakeTimeStamp($_POST['per_to'], getTSFormat());
						$per_to = date(getDateFormat(), $per_ts);

						$per_week_days = ($per_type == 'weekly') ? trim($_POST['per_week_days']) : '';
						$per_len = intVal($to_ts - $from_ts);
						$from = $per_from;
						$to = $per_to;

						$PROP = Array(
							'PERIOD_TYPE' => strtoupper($per_type),
							'PERIOD_COUNT' => $per_count,
							'EVENT_LENGTH' => $per_len,
							'PERIOD_ADDITIONAL' => $per_week_days,
						);
					}
					else
					{
						$from = date(getDateFormat(), $from_ts);
						$to = date(getDateFormat(), $to_ts);
						$PROP = Array('PERIOD_TYPE' => 'NONE');
					}

					if ($_POST['rem'] == 'Y' && floatval($_POST['rem_count']) > 0 && in_array($_POST['rem_type'], array('min','hour','day')))
						$arRem = array('count' => floatval($_POST['rem_count']), 'type' => $_POST['rem_type']);
					else
						$arRem = false;

					$PROP['ACCESSIBILITY'] = ($_POST['accessibility'] && in_array($_POST['accessibility'], array('quest', 'free','absent'))) ? $_POST['accessibility'] : 'busy';
					$PROP['IMPORTANCE'] = ($_POST['importance'] && in_array($_POST['importance'], array('high', 'low'))) ? $_POST['importance'] : 'normal';
					$PROP['PRIVATE'] = ($_POST['private_event'] == true) ? $_POST['private_event'] : false;

					if (isset($_POST['host']) && intVal($_POST['host']) > 0)
						$PROP['PARENT'] = intVal($_POST['host']);

					$isMeeting = !!$_POST['is_meeting'];

					$arParams = array(
						'iblockId' => $this->iblockId,
						'ownerType' => $this->ownerType,
						'ownerId' => $this->ownerId,
						'sectionId' => $sectionId,
						'calendarId' => $_POST['calendar'],
						'bNew' => $action == 'add',
						'id' => $id,
						'name' => trim($_POST['name']),
						'desc' => trim($_POST['desc']),
						'dateFrom' => cutZeroTime($from),
						'dateTo' => cutZeroTime($to),
						'isMeeting' => $isMeeting,
						'prop' => $PROP,
						'remind' => $arRem,
						'fullUrl' => $this->fullUrl,
						'userId' => $this->userId,
						'pathToUserCalendar' => $this->pathToUserCalendar,
						'pathToGroupCalendar' => $this->pathToGroupCalendar,
						'userIblockId' => $this->userIblockId,
						'location' => array(
							'old' => trim($_POST['location_old']),
							'new' => trim($_POST['location_new']),
							'change' => $_POST['location_change'] == 'Y'
						),
						'RMiblockId' => $this->allowResMeeting ? $this->RMiblockId : 0,
						'allowResMeeting' => $this->allowResMeeting,
						'RMPath' => $this->RMPath,

						'VMiblockId' => $this->allowVideoMeeting ? $this->VMiblockId : 0,
						'allowVideoMeeting' => $this->allowVideoMeeting,
						'VMPath' => $this->VMPath,
						'VMPathDetail' => $this->VMPathDetail
					);

					if ($isMeeting)
					{
						$arParams['guests'] = $arGuests;
						$arParams['meetingText'] = trim($_POST['meeting_text']);
						$arParams['setSpecialNotes'] = !!$_POST['setSpecialNotes'];

						if(isset($_POST['status']))
							$arParams['status'] = $_POST['status'];

						$arParams['reinviteParamsList'] = $this->reinviteParamsList;
					}

					$eventId = $this->SaveEvent($arParams);
					// We successfully create new event and have to delete old
					if (is_int($eventId) && $eventId > 0 && $_POST['b_recreate'] == 'Y' && intVal($_POST['old_calendar']))
					{
						// delete original event
						$res = CECEvent::Delete(array(
							'id' => $old_id,
							'iblockId' => $this->iblockId,
							'ownerType' => $this->ownerType,
							'ownerId' => $this->ownerId,
							'userId' => $this->userId,
							'pathToUserCalendar' => $this->pathToUserCalendar,
							'pathToGroupCalendar' => $this->pathToGroupCalendar,
							'userIblockId' => $this->userIblockId,
							'RMiblockId' => $this->allowResMeeting ? $this->RMiblockId : 0,
							'allowResMeeting' => $this->allowResMeeting,
							'VMiblockId' => $this->allowVideoMeeting ? $this->VMiblockId : 0,
							'allowVideoMeeting' => $this->allowVideoMeeting,
						));

						if ($res !== true)
							return $this->ThrowError(strlen($res) > 0 ? $res : GetMessage('EC_EVENT_DEL_ERROR'));
					}
					break;

				// * * * * * Delete event * * * * *
				case 'delete':
					if ($this->bReadOnly)
						return $this->ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$res = CECEvent::Delete(array(
						'id' => intVal($_POST['id']),
						'iblockId' => $this->iblockId,
						'ownerType' => $this->ownerType,
						'ownerId' => $this->ownerId,
						'userId' => $this->userId,
						'pathToUserCalendar' => $this->pathToUserCalendar,
						'pathToGroupCalendar' => $this->pathToGroupCalendar,
						'userIblockId' => $this->userIblockId,
						'RMiblockId' => $this->allowResMeeting ? $this->RMiblockId : 0,
						'allowResMeeting' => $this->allowResMeeting,
						'VMiblockId' => $this->allowVideoMeeting ? $this->VMiblockId : 0,
						'allowVideoMeeting' => $this->allowVideoMeeting,
					));

					if ($res !== true)
						return $this->ThrowError(strlen($res) > 0 ? $res : GetMessage('EC_EVENT_DEL_ERROR'));

					?><script>window._bx_result = true;</script><?

					$this->ClearCache($this->cachePath.'events/'.$this->iblockId.'/');
					break;

				// * * * * * Load events for some time limits * * * * *
				case 'load_events':
					$this->SetLoadLimits(intVal($_POST['month']), intVal($_POST['year']));
					$cl = ($_POST['usecl'] == 'Y' && !isset($_POST['cl'])) ? Array() : $_POST['cl'];

					$this->arCalendarIds = $cl;
					$this->arHiddenCals_ = is_array($_POST['hcl']) ? $_POST['hcl'] : Array();

					$ev = $this->GetEventsEx(array("bJS" => true, 'bCheckSPEvents' => true));
					if ($this->allowSuperpose && !$this->bListMode)
					{
						$this->HandleSuperpose($this->arSPIblIds);
						$spev = $this->GetSuperposedEvents(array('bJS' => true));
						if ($spev != '[]')
						{
							if ($ev == '[]')
								$ev = $spev;
							else
								$ev = substr($ev, 0, -1).','.substr($spev, 1);
						}
					}

					?><script>window._bx_ar_events = <?= $ev?>;</script><?
					break;

				// * * * * * Edit calendar * * * * *
				case 'calendar_edit':
					if ($this->bReadOnly)
						return $this->ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$id = intVal($_POST['id']);
					$bNew = (!isset($id) || $id == 0);
					$arFields = Array(
						'ID' => $id,
						'NAME' => trim($_POST['name']),
						'DESCRIPTION' => trim($_POST['desc']),
						'COLOR' => colorReplace($_POST['color']),
						'EXPORT' => isset($_POST['export']) && $_POST['export'] == 'Y',
						'EXPORT_SET' => (isset($_POST['exp_set']) && in_array($_POST['exp_set'], array('all', '3_9', '6_12'))) ? $_POST['exp_set'] : 'all',
						'PRIVATE_STATUS' => (isset($_POST['private_status']) && in_array($_POST['private_status'], array('private', 'time','title'))) ? $_POST['private_status'] : 'full'
					);

					if ($bNew)
						$arFields['IS_EXCHANGE'] = $_POST['is_exchange'] == 'Y';

					$id = $this->SaveCalendar(array('sectionId' => $sectionId, 'arFields' => $arFields));
					if (intVal($id) <= 0)
						return $this->ThrowError(strlen($res) > 0 ? $res : GetMessage('EC_CALENDAR_SAVE_ERROR'));

					$export_link = $arFields['EXPORT'] ? $this->GetExportLink($id, $this->ownerType, $this->ownerId, $this->iblockId) : '';
					$outlookJs = CECCalendar::GetOutlookLink(array(
						'ID' => $id,
						'PREFIX' => $this->GetOwnerName(array('iblockId' => $this->iblockId, 'ownerType' => $this->ownerType, 'ownerId' => $this->ownerId))
					));

					if ($this->ownerType == 'USER' && $_POST['is_def_meet_calendar'] == 'Y')
					{
						$SET = $this->GetUserSettings();
						$SET['MeetCalId'] = $id;
						$this->SetUserSettings($SET);
					}

					?><script>window._bx_calendar = {ID: <?=intVal($id)?>, EXPORT_LINK: '<?= $export_link?>',  EXPORT: '<?= $arFields['EXPORT']?>',  EXPORT_SET: '<?= $arFields['EXPORT_SET']?>', OUTLOOK_JS: '<?= CUtil::JSEscape($outlookJs)?>'};</script><?

					// Clear cache
					$this->ClearCache($this->cachePath.$this->iblockId."/calendars/".($this->bOwner ? $this->ownerId : 0)."/");
					$this->ClearCache($this->cachePath.'events/'.$this->iblockId.'/');

					if ($this->ownerType == 'GROUP')
						$this->ClearCache($this->cachePath.'sp_groups/');
					elseif($this->ownerType == 'USER')
						$this->ClearCache($this->cachePath.'sp_user/');
					else
						$this->ClearCache($this->cachePath.'sp_common/');
					break;

				// * * * * * Delete calendar * * * * *
				case 'calendar_delete':
					if ($this->bReadOnly)
						return $this->ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$id = intVal($_POST['id']);
					if (!$this->CheckCalendar(array('calendarId' => $id, 'sectionId' => $sectionId)))
						return $this->ThrowError(GetMessage('EC_CALENDAR_DEL_ERROR').' '.GetMessage('EC_CAL_INCORRECT_ERROR'));

					$res = $this->DeleteCalendar($id);
					if ($res !== true)
						return $this->ThrowError(strlen($res) > 0 ? $res : GetMessage('EC_CALENDAR_DEL_ERROR'));

					// Clear cache
					$this->ClearCache($this->cachePath.$this->iblockId."/calendars/".($this->bOwner ? $this->ownerId : 0)."/");

					if ($this->ownerType == 'GROUP')
						$this->ClearCache($this->cachePath.'sp_groups/');
					elseif($this->ownerType == 'USER')
						$this->ClearCache($this->cachePath.'sp_user/');
					else
						$this->ClearCache($this->cachePath.'sp_common/');

					?><script>window._bx_result = true;</script><?
					break;

				// * * * * * Append superposed calendar * * * * *
				case 'spcal_disp_save':
					$spcl = is_array($_POST['spcl']) ? $_POST['spcl'] : Array();
					if (!$this->SaveDisplayedSPCalendars($spcl))
						return $this->ThrowError('Error! Cant save displayed superposed calendars');
					?><script>window._bx_result = true;</script><?
					break;

				// * * * * * Hide superposed calendar * * * * *
				case 'spcal_hide':
					$this->HideSPCalendar(intVal($_POST['id']));
					?><script>window._bx_result = true;</script><?
					break;

				// * * * * * Return info about user, and user calendars * * * * *
				case 'spcal_user_cals':
					$name = trim($_POST['name']);
					if ($res = $this->HandleSPUserCals($name))
					{
						?><script>window._bx_result = <?=CUtil::PhpToJSObject($res);?>;</script><?
					}
					else
					{
						?><script>window._bx_result = [];</script><?
					}
					break;

				// * * * * * Return info about user, and user calendars * * * * *
				case 'spcal_del_user':
					if (!$this->DeleteTrackingUser(intVal($_POST['id'])))
						return $this->ThrowError('Error! Cant delete tracking user!');
					?><script>window._bx_result = true;</script><?
					break;

				// * * * * * Delete all tracking users * * * * *
				case 'spcal_del_all_user':
					$this->DeleteTrackingUser();
					?><script>window._bx_result = true;</script><?
					break;

				// * * * * * Add calendar to Superposed * * * * *
				case 'add_cal2sp':
					if (!$this->AddCalendar2SP())
						return $this->ThrowError('Error! Cant add calendar');

					$this->ClearCache($this->cachePath.'sp_handle/'.($this->curUserId % 1000)."/");

					?><script>window._bx_result = true;</script><?
					break;

				// * * * * * Save user settings * * * * *
				case 'set_settings':
					if (isset($_POST['clear_all']) && $_POST['clear_all'] == true)
					{
						// Del user options
						$res = $this->SetUserSettings(false);
						?><script>window._bx_result = <?=CUtil::PhpToJSObject($res);?>;</script><?
					}
					else
					{
						$Set = array(
							'tab_id' => $_POST['tab_id'],
							'cal_sec' => $_POST['cal_sec'],
							'sp_cal_sec' => $_POST['sp_cal_sec'],
							'planner_scale' => isset($_POST['planner_scale']) ? intVal($_POST['planner_scale']) : false,
							'planner_width' => isset($_POST['planner_width']) ? intVal($_POST['planner_width']) : false,
							'planner_height' => isset($_POST['planner_height']) ? intVal($_POST['planner_height']) : false
						);
						if (isset($_POST['meet_cal_id']))
							$Set['MeetCalId'] = intVal($_POST['meet_cal_id']);
						$Set['blink'] = $_POST['blink'] !== 'false';

						if (isset($_POST['show_ban']))
							$Set['ShowBanner'] = (bool) $_POST['show_ban'];

						$this->SetUserSettings($Set);
					}
					break;

				// * * * * * Find guests for event by name * * * * *
				case 'get_guests':
					if (isset($_POST['from']))
					{
						$from = date(getDateFormat(), MakeTimeStamp($_POST['from'], getTSFormat()));
						$to = isset($_POST['to']) ? date(getDateFormat(), MakeTimeStamp($_POST['to'], getTSFormat())) : $from;
					}
					else
					{
						$from = false;
						$to = false;
					}
					$bAddCurUser = false;
					$res = $this->HandleUserSearch(trim($_POST['name']), $from, $to, false, $_POST['event_id'], $bAddCurUser);

					?><script>window._bx_result = <?=CUtil::PhpToJSObject($res);?>;<?if ($bAddCurUser):?>window._bx_add_cur_user = true;<?endif;?></script><?
					break;

				// * * * * * Confirm user part in event * * * * *
				case 'confirm_event':
					$this->ClearCache($this->cachePath.'events/'.$this->iblockId.'/');
					$this->ConfirmEvent(array('id' => intVal($_POST['id'])));
					?><script>window._bx_result = true;</script><?
					break;

				// * * * * * Check users accessibility * * * * *
				case 'check_guests':
					$res = $this->CheckGuestsAccessibility(array('arGuests' => $_POST['guests'], 'from' => $_POST['from'], 'to' => $_POST['to'], 'eventId' => $_POST['event_id']));
					?><script>window._bx_result = <?=CUtil::PhpToJSObject($res);?>;</script><?
					break;

				// * * * * * Get list of group members * * * * *
				case 'get_group_members':
					if ($this->ownerType == 'GROUP')
					{
						if (isset($_POST['from']))
						{
							$from = date(getDateFormat(), MakeTimeStamp($_POST['from'], getTSFormat()));
							$to = isset($_POST['to']) ? date(getDateFormat(), MakeTimeStamp($_POST['to'], getTSFormat())) : $from;
						}
						else
						{
							$from = false;
							$to = false;
						}

						$bAddCurUser = false;
						$res = $this->GetGroupMembers(array('groupId' => $this->ownerId, 'from' => $from, 'to' => $to), $bAddCurUser);

						?><script>window._bx_result = <?=CUtil::PhpToJSObject($res);?>;<?if ($bAddCurUser):?>window._bx_add_cur_user = true;<?endif;?></script><?
					}
					break;

				// * * * * * Get intranet company structure * * * * *
				case 'get_company_structure':
					CEventCalendar::GetIntranetStructure();
					break;

				// * * * * * Get Guests Accessability * * * * *
				case 'get_guests_accessability':
					$this->GetGuestsAccessability(array(
						'users' => $_POST['users'],
						'from' => date(getDateFormat(), MakeTimeStamp($_POST['from'], getTSFormat())),
						'to' => date(getDateFormat(), MakeTimeStamp($_POST['to'], getTSFormat())),
						'curEventId' => intVal($_POST['cur_event_id'])
					));
					break;

				// * * * * * Get meeting room accessibility * * * * *
				case 'get_mr_accessability':
					$this->GetMRAccessability(array(
						'id' => intVal($_POST['id']),
						'from' => date(getDateFormat(), MakeTimeStamp($_POST['from'], getTSFormat())),
						'to' => date(getDateFormat(), MakeTimeStamp($_POST['to'], getTSFormat())),
						'curEventId' => intVal($_POST['cur_event_id'])
					));
					break;

				// * * * * * Get meeting room accessibility * * * * *
				case 'check_mr_vr_accessability':
					$check = false;
					$from = date(getDateFormat(), MakeTimeStamp($_POST['from'], getTSFormat()));
					$to = date(getDateFormat(), MakeTimeStamp($_POST['to'], getTSFormat()));
					$loc_old = $_POST['location_old'] ? CEventCalendar::ParseLocation(trim($_POST['location_old'])) : false;
					$loc_new = CEventCalendar::ParseLocation(trim($_POST['location_new']));

					$Params = array(
						'dateFrom' => cutZeroTime($from),
						'dateTo' => cutZeroTime($to),
						'regularity' => isset($_POST['per_type']) && strlen($_POST['per_type']) > 0 ? strtoupper($_POST['per_type']) : 'NONE',
						'members' => isset($_POST['guest']) ? $_POST['guest'] : false,
					);

					$tst = MakeTimeStamp($Params['dateTo']);
					if (date("H:i", $tst) == '00:00')
						$Params['dateTo'] = CIBlockFormatProperties::DateFormat(getDateFormat(true), $tst + (23 * 60 + 59) * 60);

					if (intVal($_POST['id']) > 0)
						$Params['ID'] = intVal($_POST['id']);

					if($loc_new['mrid'] == $this->VMiblockId) // video meeting
					{
						$Params['allowVideoMeeting'] = $this->allowVideoMeeting;
						$Params['VMiblockId'] = $this->VMiblockId;

						$check = CEventCalendar::CheckVR($Params);
					}
					else
					{
						$Params['allowResMeeting'] = $this->allowResMeeting;
						$Params['RMiblockId'] = $this->RMiblockId;
						$Params['mrid'] = $loc_new['mrid'];
						$Params['mrevid_old'] = $loc_old ? $loc_old['mrevid'] : 0;
						$check = CEventCalendar::CheckMR($Params);
					}

					?><script>window._bx_result = <?= $check === true ? 'true' : '"'.$check.'"'?></script><?
					break;

				case 'connections_edit':
					if ($this->bReadOnly || $this->ownerType != 'USER')
						return $this->ThrowError(GetMessage('EC_ACCESS_DENIED'));

					if (CEventCalendar::IsCalDAVEnabled())
					{
						$i = 0;
						$l = count($_POST['connections']);
						for ($i = 0; $i < $l; $i++)
						{
							$con = $_POST['connections'][$i];
							if ($con['id'] <= 0) // It's new connection
							{
								if ($con['del'] == 'Y')
									continue;
								if (!CEventCalendar::CheckCalDavUrl($con['link'], $con['user_name'], $con['pass']))
									return CEventCalendar::ThrowError(GetMessage("EC_CALDAV_URL_ERROR"));

								$id = CDavConnection::Add(array(
									"ENTITY_TYPE" => 'user',
									"ENTITY_ID" => $this->ownerId,
									"ACCOUNT_TYPE" => 'caldav',
									"NAME" => $con['name'],
									"SERVER" => $con['link'],
									"SERVER_USERNAME" => $con['user_name'],
									"SERVER_PASSWORD" => $con['pass']
								));
							}
							elseif ($con['del'] != 'Y') // Edit connection
							{
								$arFields = array(
									"NAME" => $con['name'],
									"SERVER" => $con['link'],
									"SERVER_USERNAME" => $con['user_name']
								);

								// TODO
								//if (!CEventCalendar::CheckCalDavUrl($con['link'], $con['user_name'], $con['pass']))
								//	return CEventCalendar::ThrowError(GetMessage("EC_CALDAV_URL_ERROR", Array('#CALDAV_URL#' => $con['link'])));

								if ($con['pass'] !== 'bxec_not_modify_pass')
									$arFields["SERVER_PASSWORD"] = $con['pass'];

								CDavConnection::Update(intVal($con['id']), $arFields);
							}
							else
							{
								CDavConnection::Delete(intVal($con['id']));
								$db_res = CUserTypeEntity::GetList(array('ID'=>'ASC'), array(
									"ENTITY_ID" => "IBLOCK_".$this->iblockId."_SECTION",
									"FIELD_NAME" => "UF_BXDAVEX_CDAV_COL")
								);
								if ($db_res && ($r = $db_res->GetNext()))
								{
									$arSelectFields = array("IBLOCK_ID", "ID", "IBLOCK_SECTION_ID", "UF_BXDAVEX_CDAV_COL");
									$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'),
										Array(
											"IBLOCK_ID" => $this->iblockId,
											"CHECK_PERMISSIONS" => 'N',
											"UF_BXDAVEX_CDAV_COL" => intVal($con['id']),
											"CREATED_BY" => $this->ownerId,
											"SECTION_ID" => $this->GetSectionIDByOwnerId($this->ownerId, 'USER', $this->iblockId)
										), false, $arSelectFields);

									while($arRes = $rsData->Fetch())
									{
										if ($con['del_calendars'] == 'Y')
											CIBlockSection::Delete($arRes['ID']);
										else
											$GLOBALS["USER_FIELD_MANAGER"]->Update("IBLOCK_".$this->iblockId."_SECTION", $arRes['ID'], array("UF_BXDAVEX_CDAV_COL" => ""));
									}
								}
							}
						}

						if($err = $APPLICATION->GetException())
						{
							CEventCalendar::ThrowError($err->GetString());
						}
						else
						{
							// Manually synchronize calendars
							CDavGroupdavClientCalendar::DataSync("user", $this->ownerId);

							// Clear cache
							$this->ClearCache($this->cachePath.$this->iblockId."/calendars/".($this->bOwner ? $this->ownerId : 0)."/");
							$this->ClearCache($this->cachePath.'events/'.$this->iblockId.'/');

							if ($this->ownerType == 'GROUP')
								$this->ClearCache($this->cachePath.'sp_groups/');
							elseif($this->ownerType == 'USER')
								$this->ClearCache($this->cachePath.'sp_user/');
							else
								$this->ClearCache($this->cachePath.'sp_common/');
						}
					}
					break;
				case 'exchange_sync':
					if ($this->ownerType == 'USER' && CEventCalendar::IsExchangeEnabled())
					{
						$error = "";
						$res = CDavExchangeCalendar::DoDataSync($this->ownerId, $error);
						if ($res === true):?>
							<script>window._bx_result_sync = true;</script>
						<?elseif($res === false):?>
							<script>window._bx_result_sync = false;</script>
						<?else:
							CEventCalendar::ThrowError($error);
						endif;
					}
					break;
			}
			if ($this->ownerType == 'GROUP' && $action != 'load_events' && class_exists('CSocNetGroup'))
				CSocNetGroup::SetLastActivity($this->ownerId);
		}
		return true;
	}

	function GetEvents($arParams = array())
	{
		$DontSaveOptions = isset($arParams['DontSaveOptions']) ? $arParams['DontSaveOptions'] : false;
		$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
		$SECTION_ID = isset($arParams['sectionId']) ? $arParams['sectionId'] : 0;
		$EVENT_ID = isset($arParams['eventId']) ? $arParams['eventId'] : false;
		$bLoadAll = isset($arParams['bLoadAll']) ? $arParams['bLoadAll'] : false;
		$arCalendarIds = isset($arParams['arCalendarIds']) ? $arParams['arCalendarIds'] : $this->arCalendarIds;
		$bJS = isset($arParams['bJS']) ? $arParams['bJS'] : false;
		$forExport = isset($arParams['forExport']) ? $arParams['forExport'] : false;
		$from_limit = isset($arParams['fromLimit']) ? $arParams['fromLimit'] : $this->fromLimit;
		$to_limit = isset($arParams['toLimit']) ? $arParams['toLimit'] : $this->toLimit;
		$checkPermissions = $forExport ? 'N' : 'Y';
		$bSuperposed = isset($arParams['bSuperposed']) ? $arParams['bSuperposed'] : false;
		$bCheckSPEvents = isset($arParams['bCheckSPEvents']) ? $arParams['bCheckSPEvents'] : false;
		$timestampFrom = isset($arParams['timestampFrom']) ? $arParams['timestampFrom'] : false;
		$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
		$emptyRes = $bJS ? '[]' : Array();

		if ($bCheckSPEvents)
		{
			$arCalendars = $this->GetCalendarsEx(); // Cache inside
			$arCalInd = array();
			$arSPCalInd = array();
			for($i = 0, $l = count($arCalendars); $i < $l; $i++)
				$arCalInd[$arCalendars[$i]['ID']] = true;
			$this->HandleSuperpose($this->arSPIblIds);
			for($i = 0, $l = count($this->arSPCalShow); $i < $l; $i++)
				if (!$arCalInd[$this->arSPCalShow[$i]['ID']])
					$arSPCalInd[$this->arSPCalShow[$i]['ID']] = true;
		}

		$this->CheckProperties($iblockId);

		$RESULT = $bJS ? '' : array();
		//SELECT
		$arSelect = array(
			"ID",
			"IBLOCK_ID",
			"IBLOCK_SECTION_ID",
			"NAME",
			"ACTIVE_FROM",
			"ACTIVE_TO",
			"DETAIL_TEXT",
			"DETAIL_TEXT_TYPE",
			"TIMESTAMP_X",
			"DATE_CREATE",
			"CREATED_BY",
			"PROPERTY_*",
		);
		//WHERE
		$arFilter = array (
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => $checkPermissions,
			"!=PROPERTY_CONFIRMED" => $this->GetConfirmedID($iblockId, "N"),
		);

		if ($EVENT_ID !== false)
			$arFilter['ID'] = $EVENT_ID;

		if($timestampFrom !== false)
			$arFilter[">TIMESTAMP_X"] = $timestampFrom;

		if(isset($arParams['CREATED_BY']))
			$arFilter["CREATED_BY"] = $arParams['CREATED_BY'];

		if(isset($arParams['bNotFree']) && $arParams['bNotFree'])
			$arFilter["!=PROPERTY_ACCESSIBILITY"] = 'free';

		if ($SECTION_ID !== false)
		{
			$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
			if ($arCalendarIds !== false && is_array($arCalendarIds))
			{
				if (!$DontSaveOptions && class_exists('CUserOptions'))
					$this->SaveHidden($arCalendarIds);

				if (count($arCalendarIds) == 0)
					return $emptyRes;
				$arFilter["SECTION_ID"] = $arCalendarIds;
			}
			else
			{
				$arFilter["SECTION_ID"] = $SECTION_ID;
			}
		}

		if (!$bLoadAll)
		{
			$arFilter[">=DATE_ACTIVE_TO"] = $from_limit;
			$arFilter["<=DATE_ACTIVE_FROM"] = $to_limit;
		}

		//Sort
		$arSort = Array('ACTIVE_FROM' => 'ASC');
		$r = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
		while($obElement = $r->GetNextElement())
		{
			$arItem = $obElement->GetFields();
			if (isset($this->arCalenderIndex[$arItem['IBLOCK_SECTION_ID']]))
				$privateStatus = $this->arCalenderIndex[$arItem['IBLOCK_SECTION_ID']]['PRIVATE_STATUS'];
			else
				$privateStatus = CECCalendar::GetPrivateStatus($iblockId, $arItem['IBLOCK_SECTION_ID'], $ownerType);

			$bCurUserOwner = $arItem["CREATED_BY"] == $this->userId;
			if ($privateStatus == 'private' && !$bCurUserOwner) // event in private calendar
				continue;

			$props = $obElement->GetProperties();
			$arItem["REMIND"] = (isset($props['REMIND_SETTINGS']['VALUE'])) ? $props['REMIND_SETTINGS']['VALUE'] : '';
			$arItem["ACCESSIBILITY"] = (isset($props['ACCESSIBILITY']['VALUE'])) ? $props['ACCESSIBILITY']['VALUE'] : 'busy';
			$arItem["IMPORTANCE"] = (isset($props['IMPORTANCE']['VALUE'])) ? $props['IMPORTANCE']['VALUE'] : '';
			$arItem["PRIVATE"] = (isset($props['PRIVATE']['VALUE'])) ? $props['PRIVATE']['VALUE'] : '';
			$arItem["VERSION"] = (isset($props['VERSION']['VALUE'])) ? $props['VERSION']['VALUE'] : 1;
			$arItem['IS_MEETING'] = $props['IS_MEETING']['VALUE'] == 'Y';
			$arItem['LOCATION'] = (isset($props['LOCATION']['VALUE'])) ? $props['LOCATION']['VALUE'] : '';

			if ($arItem["PRIVATE"] && !$bCurUserOwner) // private event
				continue;

			$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
			$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));

			$per_type = (isset($props['PERIOD_TYPE']['VALUE']) && $props['PERIOD_TYPE']['VALUE'] != 'NONE') ? strtoupper($props['PERIOD_TYPE']['VALUE']) : false;

			if ($this->bSocNet) // Social net: check meeting
			{
				// Check if it's "child" event in the guest's calendar
				$parentId = $arItem['ID'];
				if (isset($props['PARENT']) && $props['PARENT']['VALUE'] > 0)
				{
					$parentId = $props['PARENT']['VALUE'];
					$rsHost = CIBlockElement::GetList(array(), array(
							"=ID" => $parentId,
						), false, false, array(
							"ID",
							"IBLOCK_ID",
							"CREATED_BY",
						)
					);
					$arHost = $rsHost->Fetch();
					if($arHost)
					{
						$rsHostUser = CUser::GetByID($arHost["CREATED_BY"]);
						if($arHostUser = $rsHostUser->Fetch())
						{
							$name = trim($arHostUser['NAME'].' '.$arHostUser['LAST_NAME']);
							if ($name == '')
								$name = trim($User['LOGIN']);
							$arItem['HOST'] = array('id' => $arHostUser['ID'], 'name' => $name);
							$arItem['HOST'] = array('id' => $arHostUser['ID'], 'name' => $name, 'parentId' => $parentId);
						}
					}
					$status = strtoupper(isset($props['CONFIRMED']) ? $props['CONFIRMED']['VALUE_XML_ID'] : 'Q');
					if ($status != 'Y' && $status != 'N')
						$status = 'Q';
					$arItem['STATUS'] = $status;
				}
				else
				{
					$arItem['HOST'] = false;
					$arItem['STATUS'] = false;
				}

				if ($arItem['IS_MEETING'])
				{
					$arGuests = array();
					$R = CECEvent::GetGuests($this->userIblockId, $parentId, array('bCheckOwner' => true, 'ownerType' => $ownerType, 'bHostIsAbsent' => $props['HOST_IS_ABSENT']['VALUE'] == 'Y')); // Get guests

					foreach($R as $guest_id => $arGuest)
					{
						$guest_id = intval($guest_id);
						$User = $arGuest['CREATED_BY'];
						$name = trim($User['NAME'].' '.$User['LAST_NAME']);
						if ($name == '')
							$name = trim($User['LOGIN']);

						$arGuests[] = array(
							'id' => $guest_id,
							'name' => $name,
							'status' => $arGuest['PROPERTY_VALUES']['CONFIRMED'],
							'bHost' => $arGuest['IS_HOST'] === true
						);
					}
					$arItem['GUESTS'] = $arGuests;

					$arItem['MEETING_TEXT'] = $props['MEETING_TEXT']['VALUE']['TEXT'];
				}
			}

			if ($bCheckSPEvents)
				$bSuperposed = $arSPCalInd[$arItem['IBLOCK_SECTION_ID']];

			if ((!$this->bCurUserOwner || $bSuperposed) && ($privateStatus == 'time' || $privateStatus == 'title'))
			{
				if (!$arItem['ACCESSIBILITY'])
					$arItem['ACCESSIBILITY'] = 'busy';
				$accessibilityMess = GetMessage('EC_ACCESSIBILITY_'.strtoupper($arItem['ACCESSIBILITY']));
				if ($privateStatus == 'time')
				{
					$arItem['~NAME'] = $arItem['NAME'] = $accessibilityMess;
					$arItem['ACCESSIBILITY'] = '';
				}
				$arItem['DETAIL_TEXT'] = '';
				$arItem['REMIND'] = '';
				$arItem['PRIVATE'] = '';
				$arItem['GUESTS'] = Array();
			}

			if ($per_type)
			{
				$count = (isset($props['PERIOD_COUNT']['VALUE'])) ? intval($props['PERIOD_COUNT']['VALUE']) : '';
				$length = (isset($props['EVENT_LENGTH']['VALUE'])) ? intval($props['EVENT_LENGTH']['VALUE']) : '';
				$additional = (isset($props['PERIOD_ADDITIONAL']['VALUE'])) ? $props['PERIOD_ADDITIONAL']['VALUE'] : '';
				if ($forExport) // only for export
				{
					$this->HandleElement($RESULT, $arItem, array('TYPE' => $per_type, 'COUNT' => intVal($count), 'LENGTH' => intVal($length), 'DAYS' => $additional), $bJS, $bSuperposed);
				}
				else
				{
					$this->DisplayPeriodicEvent($RESULT, array(
						'arItem' => $arItem,
						'perType' => $per_type,
						'count' => $count,
						'length' => $length,
						'additional' => $additional,
						'fromLimit' => $from_limit,
						'toLimit' => $to_limit,
						'bJS' => $bJS,
						'bSuperposed' => $bSuperposed
					));
				}
			}
			else
			{
				$this->HandleElement($RESULT, $arItem, false, $bJS, $bSuperposed);
			}
		}

		if (!$bJS)
			return $RESULT;
		if ($RESULT == '')
			return $emptyRes;

		return '['.substr($RESULT, 0, -1).']';

		return $emptyRes;
	}

	// Cache Inside
	function GetEventsEx($arParams = array(), $bDontCache = false)
	{
		$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
		if (!isset($arParams['sectionId']))
			$arParams['sectionId'] = $this->GetSectionId();
		$bLoadAll = isset($arParams['bLoadAll']) ? $arParams['bLoadAll'] : false;
		$arCalendarIds = isset($arParams['arCalendarIds']) ? $arParams['arCalendarIds'] : $this->arCalendarIds;
		$bJS = isset($arParams['bJS']) ? $arParams['bJS'] : false;
		$forExport = isset($arParams['forExport']) ? $arParams['forExport'] : false;
		$fromLimit = isset($arParams['fromLimit']) ? $arParams['fromLimit'] : $this->fromLimit;
		$toLimit = isset($arParams['toLimit']) ? $arParams['toLimit'] : $this->toLimit;
		$checkPermissions = ($forExport || $arParams['checkPermissions'] === false) ? 'N' : 'Y';

		$bCache = $bDontCache ? false : $this->bCache;

		if ($bCache)
		{
			$cache = new CPHPCache;
			$cachePath = $this->cachePath.'events/'.$iblockId.'/';
			$cacheId = serialize(array($arParams['sectionId'], $fromLimit, $toLimit, $arCalendarIds));
			if(($tzOffset = CTimeZone::GetOffset()) <> 0)
				$cacheId .= "_".$tzOffset;

			if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arEvents = $res['arEvents'];
			}
		}

		if (!$bCache || empty($res['arEvents']))
		{
			$arEvents = $this->GetEvents($arParams);
			if ($bCache)
			{
				$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array("arEvents" => $arEvents));
			}
		}
		else
		{
			// Save hidden calendar id's
			if (!(isset($arParams['DontSaveOptions']) && $arParams['DontSaveOptions']) && class_exists('CUserOptions'))
				$this->SaveHidden($arCalendarIds);
		}

		return $arEvents;
	}

	function SaveHidden($arCalendarIds)
	{
		$arHiddenCals = CECCalendar::GetHidden($this->userId);

		// Set visible (remove from hidden array)
		if (is_array($arCalendarIds))
		{
			for($i = 0, $l = count($arCalendarIds); $i < $l; $i++)
				if (in_array($arCalendarIds[$i], $arHiddenCals))
					array_splice($arHiddenCals, array_search($arCalendarIds[$i], $arHiddenCals), 1);
		}

		// Set visible (remove from hidden array) for superpose
		if ($this->bSuperpose)
		{
			for($i = 0, $l = count($this->arSPCalShow); $i < $l; $i++)
			{
				$id = $this->arSPCalShow[$i]['ID'];
				if (in_array($id, $arHiddenCals))
					array_splice($arHiddenCals, array_search($id, $arHiddenCals), 1);
			}
		}

		// Add calendars to hidden
		for($i = 0, $l = count($this->arHiddenCals_); $i < $l; $i++)
		{
			if (!in_array($this->arHiddenCals_[$i], $arHiddenCals))
				$arHiddenCals[] = $this->arHiddenCals_[$i];
		}

		CECCalendar::SetHidden($this->userId, $arHiddenCals);
	}

	public static function clearEventsCache($iblockId)
	{
		$ec = new static();
		$ec->init(array());

		$ec->ClearCache($ec->cachePath.'events/'.intval($iblockId).'/');
	}

	function ClearCache($cachePath = "")
	{
		if ($cachePath == '')
			$cachePath = $this->cachePath;

		$cache = new CPHPCache;
		$cache->CleanDir($cachePath);
	}

	function DisplayPeriodicEvent(&$res, $arParams)
	{
		$length = intVal($arParams['length']);// length in seconds
		$count = $arParams['count'];
		$f_limit_ts = MakeTimeStamp($arParams['fromLimit'], getTSFormat());
		$t_limit_ts = MakeTimeStamp($arParams['toLimit'], getTSFormat());
		$f_real_ts = MakeTimeStamp($arParams['arItem']['DISPLAY_ACTIVE_FROM'], getTSFormat());
		$t_real_ts = MakeTimeStamp($arParams['arItem']['DISPLAY_ACTIVE_TO'], getTSFormat());
		if ($count <= 0)
			$count = 1;

		if ($f_limit_ts < $f_real_ts)
			$f_limit_ts = $f_real_ts;
		if ($t_limit_ts > $t_real_ts)
			$t_limit_ts = $t_real_ts;

		$from_ts = $f_real_ts;
		$to_ts = $f_real_ts;

		$arDays = array();
		if ($arParams['perType'] == 'WEEKLY')
		{
			$arDays_ = explode(',', $arParams['additional']);
			$arDaysCount = count($arDays_);
			for ($i = 0; $i < count($arDays_); $i++)
				if ($arDays_[$i] != "")
					$arDays[$arDays_[$i]] = true;
			$arParams['arDays'] = $arDays;
		}

		while($to_ts < $t_limit_ts)
		{
			$f_time_h = date("H", $from_ts);
			$f_time_m = date("i", $from_ts);
			$f_time_s = date("s", $from_ts);
			$f_d = date("d", $from_ts);
			$f_m = date("m", $from_ts);
			$f_y = date("Y", $from_ts);

			if ($arParams['perType'] == 'WEEKLY')
			{
				$f_day = convertDayInd(date("w", $from_ts));
				if ($arDays[$f_day] || count($arDays) == 0)
				{
					$to_ts = mktime($f_time_h, $f_time_m, $f_time_s + $length, $f_m, $f_d, $f_y);
					if ($from_ts <= $t_limit_ts && $to_ts >= $f_limit_ts)
						CEventCalendar::HandlePeriodicElement($res, array_merge($arParams, array('dateFrom' => date(getDateFormat(), $from_ts), 'dateTo' => date(getDateFormat(), $to_ts))));
					elseif($from_ts > $f_limit_ts)
						break;
				}

				if ($f_day == 6)
					$delta = ($count - 1) * 7 + 1;
				else
					$delta = 1;
				$from_ts = mktime($f_time_h, $f_time_m, $f_time_s, $f_m, $f_d + $delta, $f_y);
			}
			else // DAILY, MONTHLY, YEARLY
			{
				$to_ts = mktime($f_time_h, $f_time_m, $f_time_s + $length, $f_m, $f_d, $f_y);
				if ($from_ts <= $t_limit_ts && $to_ts >= $f_limit_ts)
					CEventCalendar::HandlePeriodicElement($res, array_merge($arParams, array('dateFrom' => date(getDateFormat(), $from_ts), 'dateTo' => date(getDateFormat(), $to_ts))));
				elseif($from_ts > $f_limit_ts)
					break;

				switch ($arParams['perType'])
				{
					case 'DAILY':
						$from_ts = mktime($f_time_h, $f_time_m, $f_time_s, $f_m, $f_d + $count, $f_y);
						break;
					case 'MONTHLY':
						$from_ts = mktime($f_time_h, $f_time_m, $f_time_s, $f_m + $count, $f_d, $f_y);
						break;
					case 'YEARLY':
						$from_ts = mktime($f_time_h, $f_time_m, $f_time_s, $f_m, $f_d, $f_y + $count);
						break;
				}
			}
		}
	}

	function HandlePeriodicElement(&$res, $arParams)
	{
		$oPer = Array(
			"TYPE" => $arParams['perType'],
			"FROM" => cutZeroTime($arParams['arItem']["DISPLAY_ACTIVE_FROM"]),
			"TO" => cutZeroTime($arParams['arItem']["DISPLAY_ACTIVE_TO"]),
			"COUNT" => intVal($arParams['count'])
		);
		if (isset($arParams['arDays']))
			$oPer["DAYS"] = $arParams['arDays'];

		$arParams['arItem']['DISPLAY_ACTIVE_FROM'] = $arParams['dateFrom'];
		$arParams['arItem']['DISPLAY_ACTIVE_TO'] = $arParams['dateTo'];

		CEventCalendar::HandleElement($res, $arParams['arItem'], $oPer, $arParams['bJS'], $arParams['bSuperposed']);
	}

	function HandleElement(&$res, $arItem, $arPeriodic = false, $bJS = false, $bSuperposed = false)
	{
		$arEvent = array(
			'ID' => intVal($arItem['ID']),
			'IBLOCK_ID' => intVal($arItem['IBLOCK_ID']),
			'IBLOCK_SECTION_ID' => intVal($arItem['IBLOCK_SECTION_ID']),
			'NAME' => htmlspecialcharsex($arItem['~NAME']),
			'DATE_FROM' => cutZeroTime($arItem['DISPLAY_ACTIVE_FROM']),
			'DATE_TO' => cutZeroTime($arItem['DISPLAY_ACTIVE_TO']),
			'DETAIL_TEXT' => $arItem['DETAIL_TEXT'],
			'PERIOD' =>  $arPeriodic,
			'TIMESTAMP_X' => $arItem['TIMESTAMP_X'],
			'DATE_CREATE' => $arItem['DATE_CREATE'],
			'GUESTS' => $arItem['GUESTS'],
			'CREATED_BY' => $arItem['CREATED_BY'],
			'STATUS' => $arItem['STATUS'],
			'HOST' => $arItem['HOST'],
			'REMIND' => $arItem['REMIND'],
			'IMPORTANCE' => $arItem['IMPORTANCE'] ? $arItem['IMPORTANCE'] : 'normal',
			'ACCESSIBILITY' => $arItem['ACCESSIBILITY'],
			'PRIVATE' => $arItem['PRIVATE'],
			'VERSION' => $arItem['VERSION'],
			'MEETING_TEXT' => htmlspecialcharsex(addslashes($arItem['MEETING_TEXT'])),
			'IS_MEETING' => $arItem['IS_MEETING'],
			//'LOCATION' => htmlspecialcharsex($arItem['LOCATION'])
			'LOCATION' => $arItem['LOCATION']
		);

		$rsUser = CUser::GetByID($arItem['CREATED_BY']);
		if ($arUser = $rsUser->Fetch())
		{
			$url = str_replace('#USER_ID#', $arUser["ID"], COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/'));
			$arEvent['CREATED_BY_NAME_LINK'] = '<a href="'.$url.'" target="_blank">'.CUser::FormatName(CSite::GetNameFormat(), $arUser).'</a>';
		}

		if ($bSuperposed)
			$arEvent['bSuperposed'] = true;
		if ($bJS)
			$res .= CUtil::PhpToJSObject($arEvent).",";
		else
			$res[] = $arEvent;
	}

	function SetLoadLimits($init_month, $init_year)
	{
		$this->fromLimit = date(getDateFormat(false), mktime(0, 0, 0, $init_month - 1, 20, $init_year));
		$this->toLimit = date(getDateFormat(false), mktime(0, 0, 0, $init_month + 1, 10, $init_year));
	}

	function GetCalendarsEx($sectionId = false, $iblockId = false, $bOwner = true, $bDontCache = false)
	{
		if ($iblockId === false)
			$iblockId = $this->iblockId;
		if ($bOwner === true)
			$bOwner = $this->bOwner;

		if ($sectionId === false)
			$sectionId = $this->GetSectionId();

		$bCache = $bDontCache ? false : $this->bCache;
		if ($bCache)
		{
			$cache = new CPHPCache;
			$key = $bOwner ? $this->ownerId: 0;
			$cacheId = serialize(array($iblockId, $key, $sectionId));
			$cachePath = $this->cachePath.$iblockId."/calendars/".$key."/";

			if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arCalendars = $res['arCalendars'];
				$this->arCalenderIndex = $res['arCalenderIndex'];
			}
		}

		if (!$bCache || empty($arCalendars))
		{
			$arCalendars = $this->GetCalendars(array('sectionId' => $sectionId, 'iblockId' => $iblockId, 'bOwner' => $bOwner));
			if ($bCache)
			{
				$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"arCalendars" => $arCalendars,
					"arCalenderIndex" => $this->arCalenderIndex
				));
			}
		}
		if ($iblockId == $this->iblockId)
			$this->arCalendars = $arCalendars;

		return $arCalendars;
	}

	function GetCalendars($arParams = array())
	{
		$sectionId = isset($arParams['sectionId']) && $arParams['sectionId'] !== false ? $arParams['sectionId'] : 0;
		$iblockId = isset($arParams['iblockId']) && $arParams['iblockId'] !== false ? $arParams['iblockId'] : $this->iblockId;
		$xmlId = isset($arParams['xmlId']) && $arParams['xmlId'] !== false ? $arParams['xmlId'] : 0;
		$forExport = isset($arParams['forExport']) ? $arParams['forExport'] : false;
		$checkPermissions = $forExport ? 'N' : 'Y';

		if (!isset($arParams['bOwner']) || $arParams['bOwner'] === true)
		{
			$bOwner = $this->bOwner;
			$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
			$ownerId = isset($arParams['ownerId']) ? $arParams['ownerId'] : $this->ownerId;
		}
		else
		{
			$bOwner = $arParams['bOwner'];
			$ownerType = false;
			$ownerId = false;
		}

		$arFilter = Array(
			"SECTION_ID" => $sectionId,
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => $checkPermissions
		);

		if ($xmlId !== 0)
		{
			$arFilter['XML_ID'] = $xmlId;
			if ($sectionId === 0)
				unset($arFilter['SECTION_ID']);
		}

		$bCurUserOwner = true;
		if ($bOwner)
		{
			if ($ownerType == 'USER')
			{
				$arFilter["CREATED_BY"] = $ownerId;
				$bCurUserOwner =  $this->userId == $ownerId;
			}
			elseif ($ownerType == 'GROUP')
			{
				$arFilter["SOCNET_GROUP_ID"] = $ownerId;
			}
		}

		// get superpose calendars
		if (CModule::IncludeModule('extranet') && $arParams['bSuperposed'])
		{
			if (CExtranet::IsExtranetSite())
			{
				$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(SITE_ID);
				$arPublicUsersID = CExtranet::GetPublicUsers();
				$arUsersToFilter = array_merge($arUsersInMyGroupsID, $arPublicUsersID);
				$arFilter["CREATED_BY"]  = $arUsersToFilter;
			}
			else
			{
				$arFilter["CREATED_BY"] = CExtranet::GetIntranetUsers();
			}
		}

		if (!$arParams['bSuperposed'] && !$arParams['bOnlyID'])
		{
			$outerUrl = $GLOBALS['APPLICATION']->GetCurPageParam('', array("action", "bx_event_calendar_request", "clear_cache", "bitrix_include_areas", "bitrix_show_mode", "back_url_admin", "SEF_APPLICATION_CUR_PAGE_URL"), false);
		}

		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';

		$arSelectFields = array(
			"IBLOCK_ID", "ID", "NAME", "DESCRIPTION", "IBLOCK_SECTION_ID", "DATE_CREATE", "XML_ID", "CREATED_BY",
			"UF_".$ownerType."_CAL_STATUS",
			"UF_".$ownerType."_CAL_COL",
		);

		if ($ownerType == 'USER')
		{
			$bExchange = CEventCalendar::IsExchangeEnabled();
			$bCalDAV = CEventCalendar::IsCalDAVEnabled();
		}
		else
		{
			$bCalDAV = false;
			$bExchange = false;
		}

		if ($bExchange)
		{
			$arSelectFields[] = 'UF_BXDAVEX_EXCH';
		}
		if ($bCalDAV)
		{
			$arSelectFields[] = 'UF_BXDAVEX_CDAV';
			$arSelectFields[] = 'UF_BXDAVEX_CDAV_COL';
		}

		$rsData = CIBlockSection::GetList(Array('SORT' => 'ASC'), $arFilter, false, $arSelectFields);
		$arCalendars = array();

		while($arRes = $rsData->Fetch())
		{
			// Private status
			$privateStatus = $arRes["UF_".$ownerType."_CAL_STATUS"];
			if (!$privateStatus)
				$privateStatus = 'full';
			// Color
			$color = $arRes["UF_".$ownerType."_CAL_COL"];
			if (!$color)
				$color = '#CEE669';

			//$privateStatus = CECCalendar::GetPrivateStatus($iblockId, $arRes['ID'], $ownerType);

			if ($privateStatus == 'private' && !$bCurUserOwner)
				continue;

			if ($arParams['bOnlyID']) // We need only IDs of the calendars
			{
				$arCalendars[] = intVal($arRes['ID']);
				continue;
			}

			$calendar = array(
				"ID" => intVal($arRes['ID']),
				"IBLOCK_ID" => $iblockId,
				"IBLOCK_SECTION_ID" => intVal($arRes['IBLOCK_SECTION_ID']),
				"NAME" => htmlspecialcharsex($arRes['NAME']),
				"DESCRIPTION" => htmlspecialcharsex($arRes['DESCRIPTION']),
				"COLOR" => $color, //CECCalendar::GetColor($iblockId, $arRes['ID'], $ownerType),
				"PRIVATE_STATUS" => $privateStatus,
				"DATE_CREATE" => date("d.m.Y H:i", MakeTimeStamp($arRes['DATE_CREATE'], getTSFormat()))
			);

			if (!$arParams['bSuperposed'])
			{
				$calendar["OUTLOOK_JS"] = CECCalendar::GetOutlookLink(array('ID' => intVal($arRes['ID']), 'XML_ID' => $arRes['XML_ID'], 'IBLOCK_ID' => $iblockId, 'NAME' => htmlspecialcharsex($arRes['NAME']), 'PREFIX' => CEventCalendar::GetOwnerName(array('iblockId' => $iblockId, 'ownerType' => $ownerType, 'ownerId' => $ownerId)), 'LINK_URL' => $outerUrl));

				$arExport = CECCalendar::GetExportParams($iblockId, $arRes['ID'], $ownerType, $ownerId);

				$calendar["EXPORT"] = $arExport['ALLOW'];
				$calendar["EXPORT_SET"] = $arExport['SET'];
				$calendar["EXPORT_LINK"] = $arExport['LINK'];
			}

			$calendar['IS_EXCHANGE'] = $bExchange && strlen($arRes["UF_BXDAVEX_EXCH"]) > 0;

			if ($bCalDAV && $arRes["UF_BXDAVEX_CDAV_COL"])
				$calendar['CALDAV_CON'] = intVal($arRes["UF_BXDAVEX_CDAV_COL"]);

			$arCalendars[] = $calendar;
			$this->arCalenderIndex[$calendar['ID']] = $calendar;
		}

		return $arCalendars;
	}

	function GetSectionId()
	{
		if (!isset($this->sectionId))
		{
			if ($this->bOwner && intVal($this->ownerId) > 0)
				$this->sectionId = $this->GetSectionIDByOwnerId($this->ownerId, $this->ownerType, $this->iblockId);
			else
				$this->sectionId = 0;
		}
		return $this->sectionId;
	}

	// Cache inside
	function GetSectionIDByOwnerId($ownerId, $ownerType, $iblockId)
	{
		$cache = new CPHPCache;
		$cachePath = "event_calendar/section_id_by_owner/";
		$cacheTime = 2592000; // 30 days
		$cacheId = $ownerId."-".$ownerType."-".$iblockId;

		if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
		{
			$res = $cache->GetVars();
			$section_id = $res['section_id'];
		}

		if (empty($res['section_id']) || !$res['section_id'])
		{
			$arFilter = Array(
				"SECTION_ID" => '',
				"IBLOCK_ID" => $iblockId,
				"ACTIVE"	=> "Y",
				'CHECK_PERMISSIONS' => 'N',
			);
			if ($ownerType == 'USER')
				$arFilter["CREATED_BY"] = $ownerId;
			elseif ($ownerType == 'GROUP')
				$arFilter["SOCNET_GROUP_ID"] = $ownerId;
			$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'), $arFilter);
			if ($sect = $rsData->Fetch())
			{
				$section_id = $sect['ID'];
				$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array("section_id" => $section_id));
			}
			else
			{
				$section_id = false;
			}
		}

		return $section_id;
	}

	function GetSectionsForOwners($arOwners, $ownerType)
	{
		if ($ownerType == 'GROUP')
			$iblockId = $this->spGroupsIblId;
		elseif($ownerType == 'USER')
			$iblockId = $this->userIblockId;

		$arFilter = Array(
			"SECTION_ID" => '',
			"IBLOCK_ID" => $iblockId,
			"ACTIVE"	=> "Y"
		);

		if ($ownerType == 'USER')
			$arFilter["CREATED_BY"] = $arOwners;
		elseif ($ownerType == 'GROUP')
			$arFilter["SOCNET_GROUP_ID"] = $arOwners;

		$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'), $arFilter);

		$res = array();
		while($sect = $rsData->Fetch())
		{
			if ($ownerType == 'USER')
				$res[$sect['CREATED_BY']] = $sect['ID'];
			elseif ($ownerType == 'GROUP')
				$res[$sect['SOCNET_GROUP_ID']] = $sect['ID'];
		}

		return $res;
	}

	function CreateSectionForOwner($ownerId, $ownerType, $iblockId)
	{
		global $DB;
		$DB->StartTransaction();
		$bs = new CIBlockSection;

		if ($ownerType == 'USER')
		{
			$r = CUser::GetByID($ownerId);
			if($arUser = $r->Fetch())
			{
				$name = $arUser['NAME'].' '.$arUser['LAST_NAME'];
				if (strlen($name) < 2)
					$name = GetMessage('EC_DEF_SECT_USER').$arUser['ID'];
			}
			else
				return false;
		}
		elseif ($ownerType == 'GROUP')
		{
			if (!class_exists('CSocNetGroup'))
				return false;
			$arGroup = CSocNetGroup::GetByID($ownerId);
			if($arGroup && is_array($arGroup))
				$name = GetMessage('EC_DEF_SECT_GROUP').$arGroup['NAME'];
			else
				return false;
		}
		else
			return false;

		$arFields = Array(
			"ACTIVE" => "Y",
			"IBLOCK_SECTION_ID" => 0,
			"IBLOCK_ID" => $iblockId,
			"NAME" => $name,
		);

		if ($ownerType == 'GROUP' && $ownerId > 0)
			$arFields['SOCNET_GROUP_ID'] = $ownerId;

		$ID = $bs->Add($arFields);
		$res = ($ID > 0);

		if(!$res)
		{
			$DB->Rollback();
			$strWarning = $bs->LAST_ERROR;
			return false;
		}
		else
		{
			//This sets appropriate owner if section created by owner of the meeting and this calendar belongs to guest which is not current user
			if($arUser)
				$DB->Query("UPDATE b_iblock_section SET CREATED_BY = ".intval($arUser["ID"])." WHERE ID = ".intval($ID));

			$DB->Commit();
			return $ID;
		}
	}

	function GetUserActiveCalendars()
	{
		$arHiddenCals = CECCalendar::GetHidden($this->userId);
		$this->arCalendarIds = array();

		for($i = 0, $l = count($this->arCalendars); $i < $l; $i++)
		{
			$id = $this->arCalendars[$i]['ID'];
			if (!in_array($id, $arHiddenCals) && !in_array($id, $this->arCalendarIds))
				$this->arCalendarIds[] = $id;
		}

		if ($this->bSuperpose)
		{
			for($i = 0, $l = count($this->arSPCalShow); $i < $l; $i++)
			{
				$id = $this->arSPCalShow[$i]['ID'];
				if (!in_array($id, $arHiddenCals) && !in_array($id, $this->arCalendarIds))
					$this->arCalendarIds[] = $id;
			}
		}

		return $this->arCalendarIds;
	}

	// SUPERPOSE
	function HandleSuperpose($arCommonIDs = array(), $bCleanCache = false)
	{
		if (!class_exists('CUserOptions'))
			return false;

		$arIds = $this->GetDisplayedSPCalendars();
		$this->arSPCalShow = array();

		$bCache = $this->bCache;
		$cachePath = $this->cachePath.'sp_handle/'.($this->curUserId % 1000)."/";
		if ($bCleanCache && $bCache)
		{
			$cache = new CPHPCache;
			$cache->CleanDir($cachePath);
			$bCache = false;
		}

		if ($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = serialize(array($this->curUserId, $GLOBALS['USER']->GetUserGroupArray()));

			if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$this->arSPCal = $res['arSPCal'];
				$this->bSuperpose = $res['bSuperpose'];
			}
		}

		if (!$bCache || empty($res['arSPCal']))
		{
			$this->arSPCal = array();

			// *** For social network ***
			if (class_exists('CSocNetUserToGroup') && ($GLOBALS['USER']->IsAuthorized() || !$this->bCurUser))
			{
				//Groups calendars
				if ($this->superposeGroupsCals)
					$this->GetGroupsSPCalendars();

				//Users calendars
				$arUserIds = $this->superposeUsersCals ? $this->GetTrackingUsers() : array();

				// Add current user
				if ($this->superposeCurUserCals && !in_array($this->userId, $arUserIds))
					$arUserIds[] = $this->userId;

				if (count($arUserIds) > 0)
				{
					$arFeatures = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $arUserIds, "calendar");
					$arView = CSocNetFeaturesPerms::CanPerformOperation($this->userId, SONET_ENTITY_USER, $arUserIds, "calendar", 'view');

					for($i = 0, $l = count($arUserIds); $i < $l; $i++)
					{
						$userId = intVal($arUserIds[$i]);
						if ($userId <= 0)
							continue;

						if ($arFeatures[$userId] && $arView[$userId])
							$this->GetUserSPCalendars($userId, $this->userId == $userId);
					}
				}
			}

			// Common calendars
			if (is_array($arCommonIDs) && count($arCommonIDs) > 0)
				$this->GetCommonSPCalendars($arCommonIDs);

			if ($bCache)
			{
				$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"arSPCal" => $this->arSPCal,
					"bSuperpose" => $this->bSuperpose,
				));
			}
		}

		if (count($this->arSPCal) > 0)
			$this->arSPCalShow = $this->CheckDisplayedSPCalendars($arIds, $this->arSPCal);
	}

	// CACHE INSIDE
	function GetCommonSPCalendars($arIDs)
	{
		$bCache = $this->bCache;
		if ($bCache)
		{
			$cachePath = $this->cachePath.'sp_common/';
			$cache = new CPHPCache;
			$cacheId = 'ec_sp_'.implode('_', $arIDs).'ug'.implode('_', $GLOBALS['USER']->GetUserGroupArray());

			if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arCals = $res['arCommonSPCals'];
			}
		}

		if (!$bCache || empty($arCals))
		{
			$arCals = array();
			for($i = 0, $l = count($arIDs); $i < $l; $i++)
			{
				$calIblockId= intVal($arIDs[$i]);
				if ($calIblockId <= 0)
					continue;

				$res = CIBlock::GetList(array(), array("ID"=>$calIblockId, "CHECK_PERMISSIONS" => $this->bExportSP ? 'N' : 'Y'));
				if(!$res = $res->Fetch())
					continue;

				$arCals_ = $this->GetCalendars(array(
					'bOwner' => false,
					'sectionId' => 0,
					'iblockId' => $calIblockId,
					'forExport' => $this->bExportSP,
					'bSuperposed' => true
				));

				$arCals[] = Array(
					'ID' => $calIblockId,
					'NAME' => $res['NAME'],
					'GROUP_TITLE' => GetMessage('EC_SUPERPOSE_GR_COMMON'),
					'GROUP' => 'COMMON',
					'READONLY' => true,
					'ITEMS' => $this->ExtendCalArray($arCals_, array('GROUP_TITLE' => GetMessage('EC_SUPERPOSE_GR_COMMON'), "NAME" => $res['NAME']))
				);
			}

			if ($bCache)
			{
				$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array("arCommonSPCals" => $arCals));
			}
		}

		for($i = 0, $l = count($arCals); $i < $l; $i++)
		{
			if ($arCals[$i]["ID"] == $this->iblockId)
				$this->allowAdd2SP = true;

			if ($arCals[$i]["ID"] == $this->iblockId && !$this->bOwner)
				$this->_sp_par_name = $res['NAME'];

			$this->arSPCal[] = $arCals[$i];
		}

		if (!$this->bSuperpose)
			$this->bSuperpose = count($this->arSPCal) > 0;
	}

	// Cache Inside
	function GetGroupsSPCalendars()
	{
		if (!class_exists('CSocNetUserToGroup') || !class_exists('CSocNetFeatures'))
			return;

		$uid = $this->userId;
		$bCache = $this->bCache;

		if ($bCache)
		{
			$cache = new CPHPCache;
			$cachePath = $this->cachePath.'sp_groups/';
		}

		$arGroupFilter = array(
			"USER_ID" => $uid,
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_SITE_ID" => SITE_ID,
			"GROUP_ACTIVE" => "Y"
		);

		$dbGroups = CSocNetUserToGroup::GetList(
			array("GROUP_NAME" => "ASC"),
			$arGroupFilter,
			false,
			false,
			array("GROUP_ID", "GROUP_NAME")
		);

		if ($dbGroups)
		{
			$arIds = array();
			$arGroups = array();
			while ($g = $dbGroups->GetNext())
			{
				$arGroups[] = $g;
				$arIds[] = $g['GROUP_ID'];
			}

			if (count($arIds) > 0)
			{
				$arSectId = $this->GetSectionsForOwners($arIds, 'GROUP');
				$arFeaturesActive = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arIds, "calendar");
				$arView = CSocNetFeaturesPerms::CanPerformOperation($uid, SONET_ENTITY_GROUP, $arIds, "calendar", 'view');
				$arWrite = CSocNetFeaturesPerms::CanPerformOperation($uid, SONET_ENTITY_GROUP, $arIds, "calendar", 'write');

				for($i = 0, $l = count($arGroups); $i < $l; $i++)
				{
					$groupId = $arGroups[$i]['GROUP_ID'];
					$groupName = $arGroups[$i]['GROUP_NAME'];

					// Check section
					if(!array_key_exists($groupId, $arSectId) || intVal($arSectId[$groupId]) <= 0)
						continue;
					$sectionId = $arSectId[$groupId];

					// Can't view
					if (!$arFeaturesActive[$groupId] || !$arView[$groupId])
						continue;

					// Can't write
					$bReadOnly = !$arWrite[$groupId];
					$res = null;

					if ($bCache)
					{
						$cacheId = serialize(array($sectionId, $groupId, $GLOBALS['USER']->GetUserGroupArray(), $this->spGroupsIblId));

						if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
						{
							$res = $cache->GetVars();
							$arCals = $res['arCals'];
						}
					}

					if (!$bCache || empty($res['arCals']))
					{
						$arCals = array();
						// Get calendars
						$arCals_ = $this->GetCalendars(array(
							'bOwner' => true,
							'ownerType' => 'GROUP',
							'ownerId' => $groupId,
							'iblockId' => $this->spGroupsIblId,
							'sectionId' => $sectionId,
							'bSuperposed' => true
						));

						// Save SP section calendars
						$arCals = Array(
							'ID' => $this->spGroupsIblId,
							'NAME' => $groupName,
							'GROUP_TITLE' => GetMessage('EC_SUPERPOSE_GR_GROUP'),
							'GROUP' => 'SOCNET_GROUPS',
							'READONLY' => $bReadOnly,
							'READONLY' => 'Y',
							'ITEMS' => $this->ExtendCalArray($arCals_, array('GROUP_TITLE' => GetMessage('EC_SUPERPOSE_GR_GROUP'), "NAME" => $arGroups[$i]['GROUP_NAME']))
						);

						if ($bCache)
						{
							$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
							$cache->EndDataCache(array("arCals" => $arCals));
						}
					}

					$this->arSPCal[] = $arCals;

					if ($groupId == $this->ownerId)
						$this->_sp_par_name = $groupName;
					if (!$this->bSuperpose && count($arCals['ITEMS']) > 0)
						$this->bSuperpose = true;
					if ($this->ownerType == 'GROUP' && $groupId == $this->ownerId)
						$this->allowAdd2SP = true;
				}
			}
		}
	}

	function ExtendCalArray($ar, $arParams = array())
	{
		for($i = 0, $l = count($ar); $i < $l; $i++)
			$ar[$i]['SP_PARAMS'] = $arParams;
		return $ar;
	}

	function GetTrackingUsers()
	{
		$str = CUserOptions::GetOption("intranet", $this->sSPTrackingUsersKey, false, $this->userId);
		if ($str === false || !checkSerializedData($str))
			return array();
		return unserialize($str);
	}

	function SetTrackingUsers($arUserIds = array())
	{
		CUserOptions::SetOption("intranet", $this->sSPTrackingUsersKey, serialize($arUserIds));
	}

	function GetUserSPCalendars($uid, $bCurUser = false)
	{
		$bCache = $this->bCache;
		if ($bCache)
		{
			$cache = new CPHPCache;
			$cachePath = $this->cachePath.'sp_user/';
			$cacheId = 'ec_sp_'.$uid.'ug'.implode('_', $GLOBALS['USER']->GetUserGroupArray());

			if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arCals = $res['arCals'];
			}
		}

		if (!$bCache || empty($arCals))
		{
			$arCals = array();
			$sectionId = intVal($this->GetSectionIDByOwnerId($uid, 'USER', $this->userIblockId));

			// Get calendars
			if ($sectionId > 0)
			{
				$arCals_ = $this->GetCalendars(array(
					'bOwner' => true,
					'ownerType' => 'USER',
					'ownerId' => $uid,
					'iblockId' => $this->userIblockId,
					'sectionId' => $sectionId,
					'bSuperposed' => true
				));
			}
			else
			{
				$arCals_ = array();
			}

			$grTitle = GetMessage('EC_SUPERPOSE_GR_USER');
			if ($bCurUser)
			{
				$name = GetMessage('EC_SUPERPOSE_GR_CUR_USER');
			}
			else
			{
				$r = CUser::GetByID($uid);
				if (!$arUser = $r->Fetch())
					return;

				$name = trim($arUser['NAME'].' '.$arUser['LAST_NAME']);
				if ($name == '')
					$name = trim($arUser['LOGIN']);
				$name .= ' <'.$arUser['EMAIL'].'> ['.$uid.']';
			}

			if (count($arCals_) > 0)
				$arCals_ = $this->ExtendCalArray($arCals_, array('GROUP_TITLE' => $grTitle, "NAME" => $name));

			$arCals[] = Array(
				'ID' => $this->userIblockId,
				'NAME' => $name,
				'GROUP_TITLE' => $grTitle,
				'GROUP' => 'SOCNET_USERS',
				'READONLY' => true,
				'ITEMS' => $arCals_,
				'bDeletable' => !$bCurUser,
				'USER_ID' => $uid
			);

			if ($bCache)
			{
				$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array('arCals' => $arCals));
			}
		}

		for($i = 0, $l = count($arCals); $i < $l; $i++)
		{
			if ($this->addCurUserCalDispByDef && $arCals[$i]['USER_ID'] == $this->userId)
				for($j = 0; $j < count($arCals[$i]['ITEMS']); $j++)
					$this->arSPCalsDisplayedDef[] = $arCals[$i]['ITEMS'][$j]['ID'];

			$this->arSPCal[] = $arCals[$i];
		}

		if (!$this->bSuperpose)
			$this->bSuperpose = true;
	}

	function GetDisplayedSPCalendars()
	{
		if (!class_exists('CUserOptions'))
			return;
		$str = CUserOptions::GetOption("intranet", $this->sSPCalKey, false, $this->userId);
		$arIds = $str !== false && checkSerializedData($str) ? unserialize($str) : array();

		if ($str === false && count($this->arSPCalsDisplayedDef) > 0) // Set default displayed calendars
			$arIds = array_merge($arIds, $this->arSPCalsDisplayedDef);

		return $arIds;
	}

	function CheckDisplayedSPCalendars($arIds = array(), $arSPCal = array())
	{
		$arIdsChecked = array();
		if (count($arIds) > 0)
		{
			for($i = 0, $l = count($arSPCal); $i < $l; $i++)
			{
				for($j = 0, $n = count($arSPCal[$i]['ITEMS']); $j < $n; $j++)
				{
					$el = $arSPCal[$i]['ITEMS'][$j];
					if (in_array($el['ID'], $arIds))
						$arIdsChecked[] = $el;
				}
			}
		}
		return $arIdsChecked;
	}

	function SetDisplayedSPCalendars($arIds)
	{
		if (!class_exists('CUserOptions'))
			return;
		CUserOptions::SetOption("intranet", $this->sSPCalKey, serialize($arIds));
	}

	function GetSuperposedEvents($arParams = array())
	{
		$bJS = isset($arParams['bJS']) ? $arParams['bJS'] : false;
		$forExport = isset($arParams['forExport']) ? $arParams['forExport'] : false;
		$bLoadAll = isset($arParams['bLoadAll']) ? $arParams['bLoadAll'] : false;
		$result = $bJS ? '[]' : Array();
		if (!$this->bSuperpose)
			return $result;

		$bCache = $bDontCache ? false : $this->bCache;

		$arIblockIds = array();
		$ids = array();
		// Get array of iblockIds
		for($i = 0, $l = count($this->arSPCalShow); $i < $l; $i++)
		{
			$id = $this->arSPCalShow[$i]['IBLOCK_ID'];
			if (!in_array($id, $arIblockIds))
				$arIblockIds[] = $id;

			if (!isset($ids[$id]))
				$ids[$id] = array();

			$ids[$id][] = $this->arSPCalShow[$i]['ID'];
		}

		if ($bCache)
			$cache = new CPHPCache;

		for($i = 0, $l = count($arIblockIds); $i < $l; $i++)
		{
			$res = null;
			if ($bCache)
			{
				$cachePath = $this->cachePath.'events/'.$arIblockIds[$i].'/sp_events/';

				$arCacheId = array($this->curUserId, $bJS, $forExport);
				if ($bLoadAll)
					$arCacheId[] = $bLoadAll;
				else
					$arCacheId[] = $this->fromLimit." - ".$this->toLimit;
				$arCacheId[] = $ids[$arIblockIds[$i]];

				$cacheId = serialize($arCacheId);
				if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
				{
					$res = $cache->GetVars();
					$res = $res['result'];
				}
			}

			if (!$bCache || empty($res['result']))
			{
				$res = $this->GetEvents(array(
					'iblockId' => $arIblockIds[$i],
					'bJS' => $bJS,
					'bSuperposed' => true,
					'DontSaveOptions' => false,
					'forExport' => $forExport,
					'bLoadAll' => $bLoadAll
				));

				if ($bCache)
				{
					$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
					$cache->EndDataCache(array("result" => $res));
				}
			}

			if ($bJS)
			{
				if (!$res || $res == '[]')
					continue;
				if ($result == '[]')
					$result = $res;
				else
					$result = substr($result, 0, -1).','.substr($res, 1);
			}
			else
			{
				$result = array_merge($result, $res);
			}
		}

		return $result;
	}

	function GetCurCalsSPParams()
	{
		$uid = false;
		$name = '';
		if ($this->ownerType == 'USER')
		{
			$gr_title = GetMessage('EC_SUPERPOSE_GR_USER');
			$group = 'SOCNET_USERS';
			$uid = $this->ownerId;

			if ($uid == $this->userId)
			{
				$name = GetMessage('EC_SUPERPOSE_GR_CUR_USER');
			}
			else
			{
				$r = CUser::GetByID($uid);
				if (!$arUser = $r->Fetch())
					return;
				$name = trim($arUser['NAME'].' '.$arUser['LAST_NAME']);
				if ($name == '')
					$name = trim($arUser['LOGIN']);
				$name .= ' <'.$arUser['EMAIL'].'> ['.$uid.']';
			}
		}
		elseif($this->ownerType == 'GROUP')
		{
			$gr_title = GetMessage('EC_SUPERPOSE_GR_GROUP');
			$group = 'SOCNET_GROUPS';
			if (isset($this->_sp_par_name))
				$name = $this->_sp_par_name;
		}
		else
		{
			$gr_title = GetMessage('EC_SUPERPOSE_GR_COMMON');
			$group = 'COMMON';
			if (isset($this->_sp_par_name))
				$name = $this->_sp_par_name;
		}

		return array
		(
			'GROUP' => $group,
			'GROUP_TITLE' => $gr_title,
			'NAME' => $name,
			'USER_ID' => $uid
		);
	}

	function HandleSPUserCals($name)
	{
		$arFoundUsers = CSocNetUser::SearchUser($name);
		if (!is_array($arFoundUsers) || count($arFoundUsers) <= 0)
			return false;

		$arUserIds = $this->GetTrackingUsers();
		$arSPRes = array();
		foreach ($arFoundUsers as $userId => $userName)
		{
			$userId = intVal($userId);
			if ($userId <= 0 || in_array($userId, $arUserIds) || $userId == $this->userId)
				continue;

			if (!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $userId, "calendar") ||
				!CSocNetFeaturesPerms::CanPerformOperation($this->userId, SONET_ENTITY_USER, $userId, "calendar", 'view'))
				continue;

			$arUserIds[] = $userId;
			// Get calendars
			$sectionId = intVal($this->GetSectionIDByOwnerId($userId, 'USER', $this->userIblockId));
			if ($sectionId > 0)
			{
				$arCals_ = $this->GetCalendars(array(
					'bOwner' => true,
					'ownerType' => 'USER',
					'ownerId' => $userId,
					'iblockId' => $this->userIblockId,
					'sectionId' => $this->GetSectionIDByOwnerId($userId, 'USER', $this->userIblockId)
				));
			}
			else
			{
				$arCals_ = array();
			}
			$count = count($arCals_);
			$userName = htmlspecialcharsback($userName);
			if ($count > 0)
				$arCals_ = $this->ExtendCalArray($arCals_, array('GROUP_TITLE' => GetMessage('EC_SUPERPOSE_GR_USER'), "NAME" => $userName));

			// Save selected users....
			$this->SetTrackingUsers($arUserIds);

			$arSPRes[] = array(
				'ID' => $this->userIblockId,
				'USER_ID' => $userId,
				'NAME' => $userName,
				'ITEMS' => $arCals_
			);
		}
		if (count($arSPRes))
			return $arSPRes;
		else
			return false;
	}

	function DeleteTrackingUser($userId = false)
	{
		if ($userId === false)
		{
			$this->SetTrackingUsers(array());
			return true;
		}

		$arUserIds = $this->GetTrackingUsers();
		$key = array_search($userId, $arUserIds);
		if ($key === false)
			return false;
		array_splice($arUserIds, $key, 1);
		$this->SetTrackingUsers($arUserIds);
		return true;
	}

	function HideSPCalendar($id)
	{
		$arIds = $this->GetDisplayedSPCalendars(true);
		if (!in_array($id, $arIds))
			return false;

		$new_ar = array();
		for ($i = 0, $l = count($arIds); $i < $l; $i++)
			if ($arIds[$i] != $id)
				$new_ar[] = $arIds[$i];

		$this->SetDisplayedSPCalendars($new_ar);
		return true;
	}

	function SaveDisplayedSPCalendars($arIds)
	{
		array_walk($arIds, 'intval_');
		$this->SetDisplayedSPCalendars($arIds);
		return true;
	}

	function AddCalendar2SP()
	{
		if ($this->ownerType == 'USER')
		{
			// Save selected users....
			$userId = $this->ownerId;
			$arUserIds = $this->GetTrackingUsers();
			if (!in_array($userId, $arUserIds))
			{
				$arUserIds[] = $userId;
				$this->SetTrackingUsers($arUserIds);
			}
		}
		elseif ($this->ownerType == 'GROUP')
		{
			$uid = $this->userId;
			$arGroupFilter = array(
				"USER_ID" => $uid,
				"<=ROLE" => SONET_ROLES_USER,
				"GROUP_SITE_ID" => SITE_ID,
				"GROUP_ACTIVE" => "Y"
			);
			$dbGroups = CSocNetUserToGroup::GetList(
				array("GROUP_NAME" => "ASC"),
				$arGroupFilter,
				false,
				false,
				array("GROUP_ID")
			);

			if ($dbGroups)
			{
				$bExist = false;
				while ($arGroups = $dbGroups->GetNext())
				{
					if ($bExist = ($this->ownerId == $arGroups['GROUP_ID']))
						break;
				}
			}
		}

		return true;
	}

	// * * * * EXPORT TO ICAL  * * * *
	function ReturnICal($arParams)
	{
		$calendarId = $arParams['calendarId'];
		$userId = $arParams['userId'];
		$sign = $arParams['sign'];
		$arParams['ownerType'] = strtoupper($arParams['ownerType']);

		$ownerType = (isset($arParams['ownerType']) && in_array($arParams['ownerType'], array('GROUP', 'USER'))) ? $arParams['ownerType'] : false;
		$ownerId = isset($arParams['ownerId']) ? intVal($arParams['ownerId']) : $this->ownerId;
		$iblockId = (isset($arParams['iblockId']) && intVal($arParams['iblockId']) > 0) ? intVal($arParams['iblockId']) : $this->iblockId;

		$GLOBALS['APPLICATION']->RestartBuffer();
		if (!$this->CheckSign($sign, $userId, $calendarId))
			return CEventCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

		$this->bCurUserOwner = $ownerType != 'USER' || $ownerId == $userId;
		$privateStatus = CECCalendar::GetPrivateStatus($iblockId, $calendarId, $ownerType);

		if (!$this->arCalenderIndex[$calendarId]) // For get events check
			$this->arCalenderIndex[$calendarId] = array('PRIVATE_STATUS' => $privateStatus);

		if ($this->bCache)
		{
			$cache = new CPHPCache;
			$cacheId = serialize(array($iblockId, $calendarId));
			$cachePath = $this->cachePath.'ical/';

			if ($cache->InitCache($this->cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$iCalEvents = $res['iCalEvents'];
			}
		}

		if (!$this->bCache || empty($iCalEvents))
		{
			// Get iblock permissions
			$arGroups = CUser::GetUserGroup($userId);
			$arGroupPerm = CIBlock::GetGroupPermissions($iblockId);
			$maxPerm = 'D';
			foreach($arGroupPerm as $k => $perm)
				if (in_array($k, $arGroups) && $perm > $maxPerm)
					$maxPerm = $perm;

			// Check permissions
			if (($maxPerm < 'R') // iblock
				||
				(
					$ownerType == 'USER' &&  // socnet: USER
					(
						!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $ownerId, "calendar")
						||
						!CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_USER, $ownerId, "calendar", 'view')
					)
				)
				||
				(
					$ownerType == 'GROUP' && // socnet: GROUP
					(
						!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $ownerId, "calendar")
						||
						!CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $ownerId, "calendar", 'view')
					)
				)
			)
				return CEventCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

			// === Fetch events from calendar ===
			$r = CIBlockSection::GetList(Array(), Array("ID"=>$calendarId, "CHECK_PERMISSIONS"=>"N"));
			if(!($arCal = $r->Fetch()))
				return CEventCalendar::ThrowError('INCORRECT CALENDAR ID');
			$arExport = CECCalendar::GetExportParams($iblockId, $calendarId, $ownerType, $ownerId); // LINK is incorrect, but we dont need for LINK...

			if (!$arExport['ALLOW']) // Calendar is not accessible for export
				return CEventCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));
			if ($arExport['SET'] == 'all') // Get all events
			{
				$bLoadAll =true;
				$from_limit = $to_limit = false;
			}
			else // Get events from some period
			{
				$bLoadAll = false;
				if ($arExport['SET'] == '3_9') // 3 month ago and 9 future
				{
					$ago = 3;
					$future = 9;
				}
				else// if ($arExport['SET'] == '6_12')
				{
					$ago = 6;
					$future = 12;
				}
				$from_limit = date(getDateFormat(false), mktime(0, 0, 0, date("m") - $ago, 1, date("Y")));
				$to_limit = date(getDateFormat(false), mktime(0, 0, 0, date("m") + $future + 1, 1, date("Y")));
			}

			$arItems = $this->GetEvents(array(
				'ownerType' => $ownerType,
				'ownerId' => $ownerId,
				'iblockId' => $iblockId,
				'sectionId' => $calendarId,
				'fromLimit' => $from_limit,
				'toLimit' => $to_limit,
				'bLoadAll' => $bLoadAll,
				'arCalendarIds' => false,
				'forExport' => true
			));

			$iCalEvents = $this->FormatICal($arCal, $arItems);

			if ($this->bCache) // cache data
			{
				$cache->StartDataCache($this->cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array("iCalEvents" => $iCalEvents));
			}
		}
		$this->ShowICalHeaders();
		echo $iCalEvents;
		exit();
	}

	function ReturnICal_SP($arParams = array())
	{
		$userId = $arParams['userId'];
		$sign = $arParams['sign'];
		$this->userId = $userId;
		$this->bExportSP = true;

		$GLOBALS['APPLICATION']->RestartBuffer();
		if (!$this->CheckSign($sign, $userId, 'superposed_calendars') || !$this->allowSuperpose)
			return CEventCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

		$this->HandleSuperpose($this->arSPIblIds);
		$arCalenExTitle = array();

		for($i = 0, $l = count($this->arSPCalShow); $i < $l; $i++)
		{
			$cal = $this->arSPCalShow[$i];
			$this->arCalendarIds[] = $cal['ID'];
			$arCalenEx[$cal['ID']] = $cal;
		}

		// Get events from superposed calendars
		$arSPEvents = $this->GetSuperposedEvents(array('forExport' => true, 'bLoadAll' => true));
		// Add some additional info about groups of calendar and calendar name
		$arSPEvents = $this->ExtendExportEventsArray($arSPEvents, $arCalenEx);

		// Get title for combined calendars
		$dbUser = CUser::GetByID($this->userId);
		$arUser = $dbUser->Fetch();
		$ownerName = trim($arUser["NAME"]." ".$arUser["LAST_NAME"]);
		if (strlen($ownerName) <= 0)
			$ownerName = $arUser["LOGIN"];
		$title = $ownerName.': '.GetMessage('EC_EXP_SP_TITLE');
		$iCalEvents = $this->FormatICal(array('NAME' => $title, 'DESCRIPTION' => ''), $arSPEvents);
		$this->ShowICalHeaders();
		echo $iCalEvents;
		exit();
	}

	function ExtendExportEventsArray($arEvents, $arCalEx)
	{
		for($i = 0, $l = count($arEvents); $i < $l; $i++)
		{
			$calId = $arEvents[$i]['IBLOCK_SECTION_ID'];
			if (!isset($arCalEx[$calId]))
				continue;
			$arEvents[$i]['NAME'] = $arEvents[$i]['NAME'].' ['.$arCalEx[$calId]['SP_PARAMS']['NAME'].' :: '.$arCalEx[$calId]['NAME'].']';
		}
		return $arEvents;
	}

	function ShowICalHeaders()
	{
		header("Content-Type: text/calendar; charset=UTF-8");
		header("Accept-Ranges: bytes");
		header("Connection: Keep-Alive");
		header("Keep-Alive: timeout=15, max=100");
	}

	function FormatICal($arCal, $arItems)
	{
		$res = 'BEGIN:VCALENDAR
PRODID:-//Bitrix//Bitrix Calendar//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:'.$this->_ICalPaste($arCal['NAME']).'
X-WR-CALDESC:'.$this->_ICalPaste($arCal['DESCRIPTION'])."\n";

	for ($i = 0, $l = count($arItems); $i < $l; $i++)
	{
		$event = $arItems[$i];
		$fts = MakeTimeStamp($event['DATE_FROM'], getTSFormat());
		$tts = MakeTimeStamp($event['DATE_TO'], getTSFormat());

		if (date("H-i-s", $fts) == '00-00-00')
			$dtStart = date("Ymd", $fts);
		else
			$dtStart = date("Ymd\THis", $fts);
			//$dtStart = date("Ymd\THis\Z", $fts - date("Z", $fts));

		if (date("H-i-s", $tts) == '00-00-00')
			$dtEnd = date("Ymd", mktime(0, 0, 0, date("m", $tts), date("d", $tts) + 1, date("Y", $tts)));
		else
			$dtEnd = date("Ymd\THis", $tts);
			//$dtEnd = date("Ymd\THis\Z", $tts - date("Z", $tts));

		$dtStamp = str_replace('T000000Z', '', date("Ymd\THis", MakeTimeStamp($event['TIMESTAMP_X'], getTSFormat())));
		$uid = md5(uniqid(rand(), true).$event['ID']).'@bitrix';
		$period = '';
		$per = $event['PERIOD'];
		if($per)
		{
			$period = 'RRULE:FREQ='.$per['TYPE'].';';
			$period .= 'INTERVAL='.$per['COUNT'].';';
			if ($per['TYPE'] == 'WEEKLY')
			{
				$arDays = explode(',', $per['DAYS']);
				$arDayNames = Array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
				if (is_array($arDays) && count($arDays) > 0)
				{
					$period .= 'BYDAY=';
					for ($j = 0; $j < count($arDays); $j++)
						$period .= ($j == 0 ? '' : ',').$arDayNames[$arDays[$j]];
					$period .= ';';
				}
			}
			$tts_ = mktime(date("H", $fts), date("i", $fts), date("s", $fts) + $per['LENGTH'], date("m", $fts), date("d", $fts), date("Y", $fts));
			if (date("H-i-s", $tts_) == '00-00-00')
				$dtEnd_ = date("Ymd", mktime(0, 0, 0, date("m", $tts_), date("d", $tts_) + 1, date("Y", $tts_)));
			else
				$dtEnd_ = date("Ymd\THis", $tts_);
			if (date("Ymd", $tts) != '20380101')
				$period .= 'UNTIL='.$dtEnd.';';
			$period .= 'WKST=MO';
			$dtEnd = $dtEnd_;
			$period .= "\n";
		}
		$res .= 'BEGIN:VEVENT
DTSTART;VALUE=DATE:'.$dtStart.'
DTEND;VALUE=DATE:'.$dtEnd.'
DTSTAMP:'.$dtStamp.'
UID:'.$uid.'
SUMMARY:'.$this->_ICalPaste($event['NAME']).'
DESCRIPTION:'.$this->_ICalPaste($event['DETAIL_TEXT'])."\n".$period.
'CLASS:PRIVATE
SEQUENCE:0
STATUS:CONFIRMED
TRANSP:TRANSPARENT
END:VEVENT'."\n";
		}
		$res .= 'END:VCALENDAR';
		if (!defined('BX_UTF') || BX_UTF !== true)
			$res = $GLOBALS["APPLICATION"]->ConvertCharset($res, LANG_CHARSET, 'UTF-8');
		return $res;
	}

	function _ICalPaste($str)
	{
		$str = preg_replace ("/\r/i", '', $str);
		$str = preg_replace ("/\n/i", '\\n', $str);
		$str = htmlspecialcharsback($str);
		return $str;
	}

	function GetCalendarExportParams($iblockId, $calendarId, $ownerType = false, $ownerId = false)
	{
		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';
		$key = "UF_".$ownerType."_CAL_EXP";
		$ent_id = "IBLOCK_".$iblockId."_SECTION";

		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($ent_id, $calendarId);
		if (isset($arUF[$key]) && strlen($arUF[$key]['VALUE']) > 0)
			return array('ALLOW' => true, 'SET' => $arUF[$key]['VALUE'], 'LINK' => $this->GetExportLink($calendarId, $ownerType, $ownerId, $iblockId));
		else
			return array('ALLOW' => false, 'SET' => false, 'LINK' => false);
	}

	function GetExportLink($calendarId, $ownerType = false, $ownerId = false, $iblockId = false)
	{
		global $USER;
		$userId = $USER->IsAuthorized() ? $USER->GetID() : '';
		$params_ = '';
		if ($ownerType !== false)
			$params_ .=  '&owner_type='.strtolower($ownerType);
		if ($ownerId !== false)
			$params_ .=  '&owner_id='.intVal($ownerId);
		if ($iblockId !== false)
			$params_ .=  '&ibl='.strtolower($iblockId);
		return $params_.'&user_id='.$userId.'&calendar_id='.intVal($calendarId).'&sign='.$this->GetSign($userId, $calendarId);
	}

	function GetSPExportLink()
	{
		global $USER;
		$userId = $USER->IsAuthorized() ? $USER->GetID() : '';
		return '&user_id='.$userId.'&sign='.$this->GetSign($userId, 'superposed_calendars');
	}

	function SaveCalendar($arParams)
	{
		$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
		$ownerId = isset($arParams['ownerId']) ? $arParams['ownerId'] : $this->ownerId;
		$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
		$sectionId = isset($arParams['sectionId']) ? $arParams['sectionId'] : $this->sectionId;
		$arFields = $arParams['arFields'];

		global $DB;
		if ($sectionId === 'none')
		{
			$sectionId = $this->CreateSectionForOwner($ownerId, $ownerType, $iblockId); // Creating section for owner
			if ($sectionId === false)
				return false;
			else
				$this->UpdateSectionId($sectionId);
			$this->newSectionId = $sectionId;
		}

		$ID = $arFields['ID'];
		$DB->StartTransaction();
		$bs = new CIBlockSection;

		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';
		$key_color = "UF_".$ownerType."_CAL_COL";
		$key_export = "UF_".$ownerType."_CAL_EXP";
		$key_status = "UF_".$ownerType."_CAL_STATUS";

		$EXPORT = $arFields['EXPORT'] ? $arFields['EXPORT_SET'] : '';

		$arFields = Array(
			"ACTIVE"=>"Y",
			"IBLOCK_SECTION_ID"=>$sectionId,
			"IBLOCK_ID"=>$iblockId,
			"NAME"=>$arFields['NAME'],
			"DESCRIPTION"=>$arFields['DESCRIPTION'],
			$key_color => $arFields['COLOR'],
			$key_export => $EXPORT,
			$key_status => $arFields['PRIVATE_STATUS'],
			'IS_EXCHANGE' => $arFields['IS_EXCHANGE']
		);
		$GLOBALS[$key_color] = $COLOR;
		$GLOBALS[$key_export] = $EXPORT;
		$GLOBALS[$key_status] = $arFields['PRIVATE_STATUS'];
		$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$iblockId."_SECTION", $arFields);

		// Exchange
		if (CEventCalendar::IsExchangeEnabled() && $ownerType == 'USER')
		{
			$exchRes = true;
			if(isset($ID) && $ID > 0)
			{
				$calendarXmlId = CECCalendar::GetExchangeXmlId($iblockId, $ID, $ownerType);
				if (strlen($calendarXmlId) > 0 && $calendarXmlId !== 0)
				{
					$calendarModLabel = CECCalendar::GetExchModLabel($iblockId, $ID);
					$exchRes = CDavExchangeCalendar::DoUpdateCalendar($ownerId, $calendarXmlId, $calendarModLabel, $arFields);
				}
			}
			else
			{
				if ($arFields['IS_EXCHANGE'])
					$exchRes = CDavExchangeCalendar::DoAddCalendar($ownerId, $arFields);
			}

			unset($arFields['IS_EXCHANGE']);
			if ($exchRes !== true) //
			{
				if (!is_array($exchRes) || !array_key_exists("XML_ID", $exchRes))
					return CEventCalendar::ThrowError(CEventCalendar::CollectExchangeErros($exchRes));

				// It's ok, we successfuly save event to exchange calendar - and save it to DB
				$arFields['UF_BXDAVEX_EXCH'] = $exchRes['XML_ID'];
				$arFields['UF_BXDAVEX_EXCH_LBL'] = $exchRes['MODIFICATION_LABEL'];
			}
		}

		if ($ownerType == 'GROUP' && $ownerId > 0)
			$arFields['SOCNET_GROUP_ID'] = $ownerId;

		if(isset($ID) && $ID > 0)
		{
			$res = $bs->Update($ID, $arFields);
		}
		else
		{
			$ID = $bs->Add($arFields);
			$res = ($ID > 0);
			if($res)
			{
				//This sets appropriate owner if section created by owner of the meeting
				//and this calendar belongs to guest which is not current user
				if ($ownerType == 'USER' && $ownerId > 0)
					$DB->Query("UPDATE b_iblock_section SET CREATED_BY = ".intval($ownerId)." WHERE ID = ".intval($ID));
			}
		}
		if(!$res)
		{
			$DB->Rollback();
			return false;
		}

		$DB->Commit();
		return $ID;
	}

	function DeleteCalendar($ID, $arEvIds = false)
	{
		global $DB;
		if (!$this->CheckPermissionForEvent(array(), true))
			return CEventCalendar::ThrowError('EC_ACCESS_DENIED');
		@set_time_limit(0);

		// Exchange
		if (CEventCalendar::IsExchangeEnabled() && $this->ownerType == 'USER')
		{
			$calendarXmlId = CECCalendar::GetExchangeXmlId($this->iblockId, $ID);
			if (strlen($calendarXmlId) > 0 && $calendarXmlId !== 0)
			{
				$exchRes = CDavExchangeCalendar::DoDeleteCalendar($this->ownerId, $calendarXmlId);
				if ($exchRes !== true)
					return CEventCalendar::CollectExchangeErros($exchRes);
			}
		}

		$DB->StartTransaction();
		if(!CIBlockSection::Delete($ID))
		{
			$DB->Rollback();
			return false;
		}
		$DB->Commit();
		return true;
	}

	function CheckCalendar($arParams)
	{
		$calendarId = intVal($arParams['calendarId']);
		$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
		$ownerId = isset($arParams['ownerId']) ? $arParams['ownerId'] : $this->ownerId;
		$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
		$sectionId = isset($arParams['sectionId']) ? $arParams['sectionId'] : $this->sectionId;

		$arFilter = Array(
			"ID" => $calendarId,
			"SECTION_ID" => $sectionId,
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y"
		);

		if ($ownerType == 'USER')
			$arFilter["CREATED_BY"] = $ownerId;
		elseif ($ownerType == 'GROUP')
			$arFilter["SOCNET_GROUP_ID"] = $ownerId;

		$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'), $arFilter);

		if ($rsData->Fetch())
			return true;
		return false;
	}

	function SaveEvent($arParams)
	{
		global $DB;
		$iblockId = $arParams['iblockId'];
		$ownerType = $arParams['ownerType'];
		$ownerId = $arParams['ownerId'];
		$bCheckPermissions = $arParams["bCheckPermissions"] !== false;

		$calendarId = intVal($arParams['calendarId']);
		$sectionId = $arParams['sectionId'];
		$fullUrl = $arParams['fullUrl'];
		$userId = $arParams['userId'];
		$bIsInvitingEvent = $arParams['isMeeting'] && intval($arParams['prop']['PARENT']) > 0;
		$bExchange = CEventCalendar::IsExchangeEnabled() && $ownerType == 'USER';
		$bCalDav = CEventCalendar::IsCalDAVEnabled() && $ownerType == 'USER';

		if (!$bIsInvitingEvent)
		{
			// *** ADD MEETING ROOM ***
			$loc_old = CEventCalendar::ParseLocation($arParams['location']['old']);
			$loc_new = CEventCalendar::ParseLocation($arParams['location']['new']);

			if ($loc_old['mrid'] !== false && $loc_old['mrevid'] !== false && ($loc_old['mrid'] !== $loc_new['mrid'] || $arParams['location'])) // Release MR
			{
				if($loc_old['mrid'] == $arParams['VMiblockId']) // video meeting
				{
					CEventCalendar::ReleaseVR(array(
						'mrevid' => $loc_old['mrevid'],
						'mrid' => $loc_old['mrid'],
						'VMiblockId' => $arParams['VMiblockId'],
						'allowVideoMeeting' => $arParams['allowVideoMeeting'],
					));
				}
				else
				{
					CEventCalendar::ReleaseMR(array(
						'mrevid' => $loc_old['mrevid'],
						'mrid' => $loc_old['mrid'],
						'RMiblockId' => $arParams['RMiblockId'],
						'allowResMeeting' => $arParams['allowResMeeting'],
					));
				}
			}

			if ($loc_new['mrid'] !== false) // Reserve MR
			{
				if($loc_new['mrid'] == $arParams['VMiblockId']) // video meeting
				{
					$mrevid = CEventCalendar::ReserveVR(array(
						'mrid' => $loc_new['mrid'],
						'dateFrom' => $arParams['dateFrom'],
						'dateTo' => $arParams['dateTo'],
						'name' => $arParams['name'],
						'description' => GetMessage('EC_RESERVE_FOR_EVENT').': '.$arParams['name'],
						'persons' => count($arParams['guests']),
						'members' => $arParams['guests'],
						'regularity' => $arParams['prop']['PERIOD_TYPE'],
						'regularity_count' => $arParams['prop']['PERIOD_COUNT'],
						'regularity_length' => $arParams['prop']['EVENT_LENGTH'],
						'regularity_additional' => $arParams['prop']['PERIOD_ADDITIONAL'],
						'VMiblockId' => $arParams['VMiblockId'],
						'allowVideoMeeting' => $arParams['allowVideoMeeting'],
					));
				}
				else
				{
					$mrevid = CEventCalendar::ReserveMR(array(
						'mrid' => $loc_new['mrid'],
						'dateFrom' => $arParams['dateFrom'],
						'dateTo' => $arParams['dateTo'],
						'name' => $arParams['name'],
						'description' => GetMessage('EC_RESERVE_FOR_EVENT').': '.$arParams['name'],
						'persons' => $arParams['isMeeting'] && count($arParams['guests']) > 0 ? count($arParams['guests']) : 1,
						'regularity' => $arParams['prop']['PERIOD_TYPE'],
						'regularity_count' => $arParams['prop']['PERIOD_COUNT'],
						'regularity_length' => $arParams['prop']['EVENT_LENGTH'],
						'regularity_additional' => $arParams['prop']['PERIOD_ADDITIONAL'],
						'RMiblockId' => $arParams['RMiblockId'],
						'allowResMeeting' => $arParams['allowResMeeting'],
					));
				}

				if ($mrevid && $mrevid != 'reserved' && $mrevid != 'expire' && $mrevid > 0)
				{
					$loc_new = 'ECMR_'.$loc_new['mrid'].'_'.$mrevid;
					$arParams["prop"]['LOCATION'] = $loc_new;
				}
				else
				{
					$arParams["prop"]['LOCATION'] = '';
					if($mrevid == 'reserved')
						$loc_new = 'bxec_error_reserved';
					elseif($mrevid == 'expire')
						$loc_new = 'bxec_error_expire';
					else
						$loc_new = 'bxec_error';
				}
			}
			else
			{
				$loc_new = $loc_new['str'];
				$arParams["prop"]['LOCATION'] = $loc_new;
			}
		}

		//$bSocNetLog = (!isset($arParams['bSocNetLog']) || $arParams['bSocNetLog'] != false) && !$arParams["prop"]["PRIVATE"];
		//if(cmodule::includemodule('security'))

		if(CModule::IncludeModule("security"))
		{
			$filter = new CSecurityFilter;
			$arParams['desc'] = $filter->TestXSS($arParams['desc'], 'replace');
		}
		else
		{
			$arParams['desc'] = htmlspecialcharsex($arParams['desc']);
		}

		if ($calendarId > 0) // We've got subsection id - 'calendar'
		{
			//cheking permissions and correct nesting
			//if (!CEventCalendar::CheckCalendar(array('iblockId' => $iblockId, 'ownerId' => $ownerId, 'ownerType' => $ownerType, 'calendarId' => $calendarId, 'sectionId' => $sectionId)))
			//	return CEventCalendar::ThrowError(GetMessage('EC_CALENDAR_CREATE_ERROR').' '.GetMessage('EC_CAL_INCORRECT_ERROR'));
		}
		else
		{
			// Creating default calendar section for owner
			$bDisplayCalendar = !$arParams["notDisplayCalendar"]; // Output js with calendar description
			$newSectionId = 'none'; // by reference
			$calendarId = CECCalendar::CreateDefault(array(
				'ownerType' => $ownerType,
				'ownerId' => $ownerId,
				'iblockId' => $iblockId,
				'sectionId' => $sectionId
			), $bDisplayCalendar, $newSectionId);

			if (!$calendarId)
				return CEventCalendar::ThrowError('2'.GetMessage('EC_CALENDAR_CREATE_ERROR'));

			if ($newSectionId != 'none')
				$arParams['sectionId'] = $newSectionId;
		}

		$arParams['calendarId'] = $calendarId;

		if ($bIsInvitingEvent && !isset($arParams["CONFIRMED"]) && isset($arParams["status"]))
		{
			$arParams["prop"]["CONFIRMED"] = CEventCalendar::GetConfirmedID($iblockId, $arParams["status"]);
		}
		else
		{
			if($arParams["CONFIRMED"] == "Q")
				$arParams["prop"]["CONFIRMED"] = CEventCalendar::GetConfirmedID($iblockId, "Q");
			elseif($arParams["CONFIRMED"] == "Y")
				$arParams["prop"]["CONFIRMED"] = CEventCalendar::GetConfirmedID($iblockId, "Y");
			else
				unset($arParams["prop"]["CONFIRMED"]);
		}

		if (isset($arParams["remind"]))
		{
			if($arParams["remind"] !== false)
				$arParams["prop"]["REMIND_SETTINGS"] = $arParams["remind"]['count'].'_'.$arParams["remind"]['type'];
			else if(!$arParams['bNew'])
				$arParams["prop"]["REMIND_SETTINGS"] = '';
		}

		if (!isset($arParams['prop']['VERSION']))
		{
			if (!$arParams['bNew'])
			{
				$dbProp = CIBlockElement::GetProperty($iblockId, $arParams['id'], 'sort', 'asc', array('CODE' => 'VERSION'));
				if ($arProp = $dbProp->Fetch())
					$arParams['prop']['VERSION'] = intval($arProp['VALUE']);
			}
			if ($arParams['prop']['VERSION'] <= 0) $arParams['prop']['VERSION'] = 1;
			$arParams['prop']['VERSION']++;
		}

		if ($arParams['isMeeting'])
			$arParams['prop']['IS_MEETING'] = 'Y';

		if (!$bIsInvitingEvent)
		{
			$arParams['prop']['HOST_IS_ABSENT'] = ($arParams['isMeeting'] && !in_array($userId, $arParams['guests'])) ? 'Y' : 'N';
			if ($arParams['isMeeting'] && strlen($arParams['meetingText']))
				$arParams['prop']['MEETING_TEXT'] = array('VALUE' => array("TYPE" => 'text', "TEXT" => $arParams['meetingText']));
		}

		$arFields = Array(
			"ACTIVE" => "Y",
			"IBLOCK_SECTION" => $calendarId,
			"IBLOCK_ID" => $iblockId,
			"NAME" => $arParams['name'],
			"ACTIVE_FROM" => $arParams['dateFrom'],
			"ACTIVE_TO" => $arParams['dateTo'],
			"DETAIL_TEXT" => $arParams['desc'],
			"DETAIL_TEXT_TYPE" => 'html',
			"MODIFIED_BY" => $GLOBALS['USER']->GetID(),
			"PROPERTY_VALUES" => $arParams['prop']
		);
		if ($ownerType == 'GROUP' && $ownerId > 0)
			$arFields['SOCNET_GROUP_ID'] = $ownerId;

		if ($bExchange || $bCalDav)
		{
			foreach($arFields["PROPERTY_VALUES"] as $prKey => $prVal)
				$arFields["PROPERTY_".$prKey] = $prVal;
		}

		// If it's EXCHANGE - we try to save event to exchange
		if ($bExchange)
		{
			$calendarXmlId = CECCalendar::GetExchangeXmlId($iblockId, $calendarId);
			if (strlen($calendarXmlId) > 0 && $calendarXmlId !== 0) // Synchronize with Exchange
			{
				if ($arParams['bNew'])
				{
					$exchRes = CDavExchangeCalendar::DoAddItem($ownerId, $calendarXmlId, $arFields);
				}
				else
				{
					$eventModLabel = CECEvent::GetExchModLabel($iblockId, $arParams['id']);
					$eventXmlId = CECEvent::GetExchangeXmlId($iblockId, $arParams['id']);
					$exchRes = CDavExchangeCalendar::DoUpdateItem($ownerId, $eventXmlId, $eventModLabel, $arFields);
				}

				if (!is_array($exchRes) || !array_key_exists("XML_ID", $exchRes))
					return CEventCalendar::ThrowError(CEventCalendar::CollectExchangeErros($exchRes));

				// It's ok, we successfuly save event to exchange calendar - and save it to DB
				$arFields['XML_ID'] = $exchRes['XML_ID'];
				$arFields['PROPERTY_VALUES']['BXDAVEX_LABEL'] = $exchRes['MODIFICATION_LABEL'];
			}
		}

		if ($bCalDav)
		{
			$connectionId = CECCalendar::GetCalDAVConnectionId($iblockId, $calendarId);
			if ($connectionId > 0)  // Synchronize with CalDav
			{
				$calendarCalDAVXmlId = CECCalendar::GetCalDAVXmlId($iblockId, $calendarId);
				if ($arParams['bNew'])
				{
					$DAVRes = CDavGroupdavClientCalendar::DoAddItem($connectionId, $calendarCalDAVXmlId, $arFields);
				}
				else
				{
					$eventCalDAVModLabel = CECEvent::GetCalDAVModLabel($iblockId, $arParams['id']);
					$eventXmlId = CECEvent::GetExchangeXmlId($iblockId, $arParams['id']);
					$DAVRes = CDavGroupdavClientCalendar::DoUpdateItem($connectionId, $calendarCalDAVXmlId, $eventXmlId, $eventCalDAVModLabel, $arFields);
				}

				if (!is_array($DAVRes) || !array_key_exists("XML_ID", $DAVRes))
					return CEventCalendar::ThrowError(CEventCalendar::CollectCalDAVErros($DAVRes));

				// // It's ok, we successfuly save event to caldav calendar - and save it to DB
				$arFields['XML_ID'] = $DAVRes['XML_ID'];
				$arFields['PROPERTY_VALUES']['BXDAVCD_LABEL'] = $DAVRes['MODIFICATION_LABEL'];
			}
		}

		$bs = new CIBlockElement;
		$res = false;
		if (!$arParams['bNew'])
		{
			$ID = $arParams['id'];
			if($ID > 0)
				$res = $bs->Update($ID, $arFields, false);
		}
		else
		{
			//This sets appropriate owner if event created by owner of the meeting and this calendar belongs to guest which is not current user
			if($ownerType == 'USER' && $ownerId > 0 && $userId != $ownerId)
				$arFields['CREATED_BY'] = $ownerId;

			$ID = $bs->Add($arFields, false);
			$res = ($ID > 0);
		}

		if ($arParams['isMeeting'] && !$bIsInvitingEvent)
		{
			$this->CheckParentProperty($arParams['userIblockId'], $iblockId);
			$arGuestConfirm = $this->InviteGuests($ID, $arFields, $arParams['guests'], $arParams);
		}

		if(!$res)
			return CEventCalendar::ThrowError('4'.$bs->LAST_ERROR);
		else
			CIBlockElement::RecalcSections($ID);

		if(!$bPeriodic && !$arParams["notDisplayCalendar"])
		{
			if ($arParams['bNew'])
			{
				?><script>window._bx_new_event = {ID: <?=$ID?>, IBLOCK_ID: '<?=$iblockId?>', LOC: '<?= CUtil::JSEscape($loc_new)?>', arGuestConfirm: <?= CUtil::PhpToJSObject($arGuestConfirm)?>};</script><?
			}
			else
			{
				?><script>window._bx_existent_event = {ID: <?= intVal($ID)?>, NAME : '<?= CUtil::JSEscape($arParams['name'])?>', DETAIL_TEXT: '<?= CUtil::JSEscape($arParams['desc'])?>', DATE_FROM : '<?= $arParams['dateFrom']?>', DATE_TO : '<?= $arParams['dateTo']?>', LOC: '<?= CUtil::JSEscape($loc_new)?>', arGuestConfirm: <?= CUtil::PhpToJSObject($arGuestConfirm)?>};</script>
<?
			}
		}
		$this->ClearCache($this->cachePath.'events/'.$iblockId.'/');

		if($bSocNetLog && $ownerType) // log changes for socnet
		{
			CEventCalendar::SocNetLog(
				array(
					'iblockId' => $iblockId,
					'ownerType' => $ownerType,
					'ownerId' => $ownerId,
					'target' => $arParams['bNew'] ? 'add_event' : 'edit_event',
					'id' => $ID,
					'name' => $arParams['name'],
					'desc' => $arParams['desc'],
					'from' => $arParams['dateFrom'],
					'to' => $arParams['dateTo'],
					'calendarId' => $calendarId,
					'accessibility' => $arParams["prop"]["ACCESSIBILITY"],
					'importance' => $arParams["prop"]["IMPORTANCE"],
					'pathToGroupCalendar' =>  $arParams["pathToGroupCalendar"],
					'pathToUserCalendar' =>  $arParams["pathToUserCalendar"]
				)
			);
		}

		if(array_key_exists("remind", $arParams))
		{
			CECEvent::AddReminder(
				array(
					'iblockId' => $iblockId,
					'ownerType' => $ownerType,
					'ownerId' => $ownerId,
					'userId' => $userId,
					'fullUrl' => $fullUrl,
					'id' => $ID,
					'dateFrom' => $arParams['dateFrom'],
					'remind' => $arParams["remind"],
					'bNew' => $arParams['bNew']
				)
			);
		}

		return $ID;
	}

	function ConfirmEvent($arParams)
	{
		global $DB, $USER;
		if ($this->CheckPermissionForEvent($arParams, true))
		{
			$bCheck = $arParams['bCheckOwner'] === false;
			if (!$bCheck)
			{
				$arFilter = array(
					"ID" => $arParams['id'],
					"IBLOCK_ID" => $this->iblockId,
					"ACTIVE" => "Y",
					"CREATED_BY" => $USER->GetId()
				);
				$dbel = CIBlockElement::GetList(array("DATE_ACTIVE_FROM" => "ASC"),$arFilter,false,false,array());
				if ($arElements = $dbel->GetNext())
					$bCheck = true;
			}

			if ($bCheck)
			{
				$bs = new CIBlockElement;
				$bs->SetPropertyValuesEx($arParams['id'], $this->iblockId, array("CONFIRMED" => $this->GetConfirmedID($this->iblockId, "Y")));
			}
		}
	}

	function CheckPermissionForEvent($arParams, $bOnlyUser = false)
	{
		if (isset($GLOBALS['USER']) && $GLOBALS['USER']->CanDoOperation('edit_php'))
			return true;
		$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
		if ($ownerType == 'USER' || $ownerType == 'GROUP')
		{
			$ownerId = isset($arParams['ownerId']) ? $arParams['ownerId'] : $this->ownerId;
			$SONET_ENT = $ownerType == 'USER' ? SONET_ENTITY_USER : SONET_ENTITY_GROUP;
			if (!CSocNetFeatures::IsActiveFeature($SONET_ENT, $ownerId, "calendar") ||
				!CSocNetFeaturesPerms::CanPerformOperation($this->userId, $SONET_ENT, $ownerId, "calendar", 'write'))
				return false;
			if ($bOnlyUser)
				return true;
			$calendarId = isset($arParams['calendarId']) ? intVal($arParams['calendarId']) : 0;
			$sectionId = isset($arParams['sectionId']) ? $arParams['sectionId'] : $this->sectionId;
			$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
			$arFilter = Array(
				"ID" => $calendarId,
				"SECTION_ID" => $sectionId,
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y"
			);
			if ($ownerType == 'USER')
				$arFilter["CREATED_BY"] = $ownerId;
			else
				$arFilter["SOCNET_GROUP_ID"] = $ownerId;

			$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'), $arFilter);

			$arRes = $rsData->Fetch();
			if (!$arRes)
				return false;
		}
		return true;
	}

	//Check if iblock has PARENT property, Cache inside
	function CheckParentProperty($iblockId, $parentIblock = false)
	{
		$cachePath = $this->cachePath.'checked/';
		$cacheId = 'parent_property_'.$iblockId;
		$cacheTime = 31536000; // 1 year
		$cache = new CPHPCache;

		if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
		{
			$res = $cache->GetVars();
			$bRes = $res['id'] == $iblockId;
		}

		if (!$bRes)
		{
			$rsProperty = CIBlockProperty::GetList(array(), array(
				"IBLOCK_ID" => $iblockId,
				"TYPE" => "E",
				"CODE" => "PARENT",
			));
			$arProperty = $rsProperty->Fetch();
			if(!$arProperty)
			{
				$obProperty = new CIBlockProperty;
				$obProperty->Add(array(
					"IBLOCK_ID" => $iblockId,
					"ACTIVE" => "Y",
					"PROPERTY_TYPE" => "E",
					"LINK_IBLOCK_ID" => $parentIblock ? $parentIblock: $iblockId,
					"MULTIPLE" => "N",
					"NAME" => GetMessage("EC_PARENT_EVENT"),
					"CODE" => "PARENT",
				));
			}
			$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
			$cache->EndDataCache(array("id" => $iblockId));
		}
	}

	// Cache inside
	function CheckProperties($iblockId)
	{
		$bRes = false;

		$cachePath = $this->cachePath.'checked/';
		$cacheId = $iblockId;
		$cacheTime = 31536000; // 1 year

		$cache = new CPHPCache;
		if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
		{
			$res = $cache->GetVars();
			$bRes = $res['id'] == $iblockId;
		}

		if (!$bRes)
		{
			// Check properties for iblock element
			$arProps = array(
				array('CODE' => 'PERIOD_TYPE', 'TYPE' => 'S', 'NAME' => GetMessage('EC_PERIOD_TYPE')),
				array('CODE' => 'PERIOD_COUNT', 'TYPE' => 'S', 'NAME' => GetMessage('EC_PERIOD_COUNT')),
				array('CODE' => 'EVENT_LENGTH', 'TYPE' => 'S', 'NAME' => GetMessage('EC_EVENT_LENGTH')),
				array('CODE' => 'PERIOD_ADDITIONAL', 'TYPE' => 'S', 'NAME' => GetMessage('EC_PERIOD_ADDITIONAL')),
				array('CODE' => 'REMIND_SETTINGS', 'TYPE' => 'S', 'NAME' => GetMessage('EC_REMIND_SETTINGS')),
				array('CODE' => 'IMPORTANCE', 'TYPE' => 'S', 'NAME' => GetMessage('EC_IMPORTANCE')),
				array('CODE' => 'VERSION', 'TYPE' => 'S', 'NAME' => GetMessage('EC_VERSION')),
				array('CODE' => 'IS_MEETING', 'TYPE' => 'S', 'NAME' => GetMessage('EC_IS_MEETING')),
				array('CODE' => 'HOST_IS_ABSENT', 'TYPE' => 'S', 'NAME' => GetMessage('EC_HOST_IS_ABSENT')),
				array('CODE' => 'MEETING_TEXT', 'TYPE' => 'S', 'USER_TYPE'=> 'HTML', 'NAME' => GetMessage('EC_MEETING_TEXT')),
				array('CODE' => 'LOCATION', 'TYPE' => 'S', 'NAME' => GetMessage('EC_LOCATION')),
				array('CODE' => 'ACCESSIBILITY', 'TYPE' => 'S', 'NAME' => GetMessage('EC_ACCESSIBILITY')),
				array('CODE' => 'PRIVATE', 'TYPE' => 'S', 'NAME' => GetMessage('EC_PRIVATE'))
			);

			for ($i = 0, $l = count($arProps); $i < $l; $i++)
			{
				$code = $arProps[$i]['CODE'];
				$rsProperty = CIBlockProperty::GetList(array(), array(
					"IBLOCK_ID" => $iblockId,
					"CODE" => $code
				));
				$arProperty = $rsProperty->Fetch();

				if(!$arProperty)
				{
					$obProperty = new CIBlockProperty;
					$obProperty->Add(array(
						"IBLOCK_ID" => $iblockId,
						"ACTIVE" => "Y",
						"USER_TYPE" => $arProps[$i]['USER_TYPE'] ? $arProps[$i]['USER_TYPE'] : false,
						"PROPERTY_TYPE" => $arProps[$i]['TYPE'],
						"MULTIPLE" => $arProps[$i]['MULTIPLE'] == "Y" ? 'Y' : 'N',
						"NAME" => $arProps[$i]['NAME'],
						"CODE" => $arProps[$i]['CODE']
					));
				}
			}

			$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
			$cache->EndDataCache(array("id" => $iblockId));
		}
	}

	// Cache inside
	function CheckSectionProperties($iblockId, $ownerType = "")
	{
		$bRes = false;
		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';

		$cachePath = $this->cachePath.'checked/';
		$cacheTime = 31536000; // 1 year
		$cacheId = 'sect_'.$iblockId.$ownerType;
		$cache = new CPHPCache;

		if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
		{
			$res = $cache->GetVars();
			$bRes = $res['id'] == $iblockId.$ownerType;
		}

		if (!$bRes)
		{
			// Check UF for iblock sections
			global $EC_UserFields, $USER_FIELD_MANAGER;

			$arProps = array(
				array('COL', 'Color'),
				array('EXP', 'Export'),
				array('STATUS', 'Private status')
			);

			for($i = 0, $l = count($arProps); $i < $l; $i++)
			{
				$key = "UF_".$ownerType."_CAL_".$arProps[$i][0];
				$ent_id = "IBLOCK_".$iblockId."_SECTION";
				if (empty($EC_UserFields) || empty($EC_UserFields[$key]))
				{
					$db_res = CUserTypeEntity::GetList(array('ID'=>'ASC'), array("ENTITY_ID" => $ent_id, "FIELD_NAME" => $key));
					if (!$db_res || !($r = $db_res->GetNext()))
					{
						$arFields = Array(
							"ENTITY_ID" => $ent_id,
							"FIELD_NAME" => $key,
							"USER_TYPE_ID" => "string",
							"MULTIPLE" => "N",
							"MANDATORY" => "N",
						);
						$arFieldName = array();
						$rsLanguage = CLanguage::GetList($by, $order, array());
						while($arLanguage = $rsLanguage->Fetch())
							$arFieldName[$arLanguage["LID"]] = $arProps[$i][1];
						$arFields["EDIT_FORM_LABEL"] = $arFieldName;
						$obUserField  = new CUserTypeEntity;
						$r = $obUserField->Add($arFields);
						$USER_FIELD_MANAGER->arFieldsCache = array();
					}
					$EC_UserFields = $USER_FIELD_MANAGER->GetUserFields($ent_id);
				}
			}

			$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
			$cache->EndDataCache(array("id" => $iblockId.$ownerType));
		}
	}

	// function GetLinkIBlock1($iblockId)
	// {
		// if(!is_array($this->arParentPropertyCache))
			// $this->arParentPropertyCache = array();
		// if(!array_key_exists($iblockId, $this->arParentPropertyCache))
		// {
			// $rsProperty = CIBlockProperty::GetList(array(), array(
				// "IBLOCK_ID" => $iblockId,
				// "TYPE" => "E",
				// "CODE" => "PARENT",
			// ));
			// $this->arParentPropertyCache[$iblockId] = $rsProperty->Fetch();
		// }
		// if($this->arParentPropertyCache[$iblockId])
			// return $this->arParentPropertyCache[$iblockId]["LINK_IBLOCK_ID"];
		// else
			// return false;
	// }

	function GetLinkIBlock($iblockId)
	{
		$rsProperty = CIBlockProperty::GetList(array(), array(
			"IBLOCK_ID" => $iblockId,
			"TYPE" => "E",
			"CODE" => "PARENT",
			"CHECK_PERMISSIONS" => "N"
		));
		$ar = $rsProperty->Fetch();
		return $ar;
	}

	function TrimTime($strTime)
	{
		$strTime = trim($strTime);
		$strTime = preg_replace("/:00$/", "", $strTime);
		$strTime = preg_replace("/:00$/", "", $strTime);
		$strTime = preg_replace("/\\s00$/", "", $strTime);
		return rtrim($strTime);
	}

	function InviteGuests($ID, $arCalendarEvent, $arGuests, $arParams)
	{
		$arParams["prop"]["PARENT"] = $ID;
		$iblockId = $this->userIblockId;

		$userId = $arParams['userId'];
		$fullUrl = $arParams['fullUrl'];
		$pathToUserCalendar = $arParams['pathToUserCalendar'];
		$ownerName = $GLOBALS['USER']->GetFullName();
		$arGuestConfirm = array();
		$bExchange = CEventCalendar::IsExchangeEnabled() && $arParams['ownerType'] == 'USER';
		$loc = '';
		if (isset($arParams["prop"]["LOCATION"]) && strlen($arParams["prop"]["LOCATION"]) > 0)
		{
			$arLoc = CEventCalendar::ParseLocation($arParams["prop"]["LOCATION"]);
			if (!$arLoc['mrid'] || !$arLoc['mrevid'])
			{
				$loc = $arLoc['str'];
			}
			else // Meeting room
			{
				$MR = CEventCalendar::GetMeetingRoomById(array(
					'RMiblockId' => $arParams['RMiblockId'],
					'RMPath' => $arParams['RMPath'],
					'id' => $arLoc['mrid'],
					'VMiblockId' => $arParams['VMiblockId'],
					'VMPath' => $arParams['VMPath'],
					'VMPathDetail' => $arParams['VMPathDetail'],
				));

				if ($MR)
				{
					if($arLoc['mrid'] == $arParams['VMiblockId'] && strlen($arParams['VMPath']) > 0)
					{
						$url = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST'].$arParams['VMPathDetail'];
						$loc = "[url=".str_replace(Array("#id#", "#conf_id#"), Array($arLoc['mrid'], $arLoc['mrevid']), $url)."]".$MR['NAME']."[/url]";
					}
					elseif (strlen($arParams['RMPath']) > 0)
					{
						$url = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST'].$arParams['RMPath'];
						$loc = "[url=".str_replace("#id#", $arLoc['mrid'], $url)."]".$MR['NAME']."[/url]";
					}
					else
					{
						$loc = $MR['NAME'];
					}
				}
			}
		}

		//Guests
		$arAllGuests = array();
		foreach($arGuests as $guest_id)
		{
			$guest_id = intval($guest_id);
			if($guest_id > 0)
				$arAllGuests[$guest_id] = $guest_id;
		}

		//Find old guests. For new event (or only owner) - it's empty array
		if ($arParams['bNew'])
			$arOldGuests = array();
		else
			$arOldGuests = CECEvent::GetGuests($arParams['userIblockId'], $ID, array('bCheckOwner' => true, 'ownerType' => $arParams['ownerType'], 'bHostIsAbsent' => CECEvent::HostIsAbsent($arParams['iblockId'], $ID), 'DontReturnOnlyOwner' => true)); // Get guests

		$arParams["prop"]["PRIVATE"] = '';

		// Collect all new guests
		$arNewGuests = array();
		//And existing ones in order to update if event changed
		$arUpdGuests = array();
		foreach($arAllGuests as $guest_id)
		{
			if(!array_key_exists($guest_id, $arOldGuests)) // New guests
			{
				$rsUser = CUser::GetList($o, $b, array("ID_EQUAL_EXACT" => $guest_id));
				$arUser = $rsUser->Fetch();

				if($arUser)
				{
					$arUser["FULL_NAME"] = CEventCalendar::GetFullUserName($arUser);
					$arNewGuests[$guest_id] = $arUser;
				}
			}
			else
			{
				$arUpdGuests[$guest_id] = $arOldGuests[$guest_id];
			}
		}

		//Create child events for new guests
		foreach($arNewGuests as $guest_id => $arGuest)
		{
			$guestSection = CEventCalendar::GetSectionIDByOwnerId($guest_id, 'USER', $iblockId);
			$guestCalendarId = false;
			$arGuestCalendars = array();
			$res = null;

			$bForOwner = false;
			if($guest_id == $userId)
			{
				// it's owner
				if ($this->ownerType == 'USER')
					continue;
				$bForOwner = true;
			}

			if(!$guestSection) // Guest does not have any calendars
			{
				$guestSection = CEventCalendar::CreateSectionForOwner($guest_id, "USER", $iblockId);
			}
			else
			{
				//Section is out there
				//so we have chance to get guests calendar
				if ($this->bCache)
				{
					$cachePath = $this->cachePath.$iblockId."/calendars/".$guest_id."/4guests/";
					$cacheId = 'g_'.$guestSection.'_'.$iblockId.'_'.$guest_id;
					$cacheTime = 2592000; // 1 month
					$cache = new CPHPCache;
					if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
					{
						$res = $cache->GetVars();
						$arGuestCalendars = $res['calendars'];
					}
				}

				if (!$this->bCache || empty($res['calendars']))
				{
					$arGuestCalendars = $this->GetCalendars(array(
						'sectionId' => $guestSection,
						'iblockId' => $iblockId,
						'ownerType' => 'USER',
						'ownerId' => $guest_id,
						'bOwner' => true,
						'forExport' => true,
						'bOnlyID' => true
					));

					if ($this->bCache)
					{
						$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
						$cache->EndDataCache(array("calendars" => $arGuestCalendars));
					}
				}

				if(count($arGuestCalendars) > 0)
				{
					$arUserSet = CEventCalendar::GetUserSettings(array('static' => true, 'userId' => $guest_id));
					if ($arUserSet && isset($arUserSet['MeetCalId']) && in_array($arUserSet['MeetCalId'], $arGuestCalendars))
						$guestCalendarId = intVal($arUserSet['MeetCalId']);
					else
						$guestCalendarId = $arGuestCalendars[0];
				}
			}

			$eventId = $this->SaveEvent(array(
				'bOwner' => true,
				'ownerType' => "USER",
				'ownerId' => $guest_id,
				'iblockId' => $iblockId,
				'bNew' => true,
				'name' => $arParams['name'],
				'desc' => $arParams['desc'],
				'calendarId' => $guestCalendarId,
				'sectionId' => $guestSection,
				'dateFrom' => $arParams["dateFrom"],
				'dateTo' => $arParams["dateTo"],
				'prop' => $arParams["prop"],
				"CONFIRMED" => $bForOwner ? 'Y' : 'Q', // "Y" "Q" "N"
				"notDisplayCalendar" => true,
				"bCheckPermissions" => false,
				'isMeeting' => true
			));
			$arGuestConfirm[$guest_id] = $bForOwner ? 'Y' : 'Q';

			// Send message
			if (!$bForOwner)
			{
				CEventCalendar::SendInvitationMessage(array(
					'type' => "invite",
					'email' => $arGuest["EMAIL"],
					'name' => $arParams['name'],
					"from" => $arParams["dateFrom"],
					"to" => $arParams["dateTo"],
					"location" => $loc,
					"pathToUserCalendar" => $pathToUserCalendar,
					"meetingText" => $arParams['meetingText'],
					"guestId" => $guest_id,
					"guestName" => $arGuest["FULL_NAME"],
					"userId" => $userId,
					"eventId" => $eventId,
					"ownerName" => $ownerName
				));
			}
		}

		//Delete child events if guest was deleted from the list
		$obElement = new CIBlockElement;
		$arDeletedUsers = array();
		foreach($arOldGuests as $guest_id => $arOldEvent)
		{
			if($guest_id == $userId)
				continue;

			if(!array_key_exists($guest_id, $arAllGuests))
			{
				$res = CECEvent::Delete(array(
					'id' => $arOldEvent["ID"],
					'iblockId' => $iblockId,
					'ownerType' => "USER",
					'ownerId' => $guest_id,
					'userId' => $userId,
					'bJustDel' => true // Just delete iblock element  + exchange
				));
				if ($res !== true)
					return $this->ThrowError(strlen($res) > 0 ? $res : GetMessage('EC_EVENT_DEL_ERROR'));

				$arDeletedUsers[] = $arOldEvent["ID"];
				if ($arOldEvent["PROPERTY_VALUES"]["CONFIRMED"] != "N") //User
				{
					// Send message
					CEventCalendar::SendInvitationMessage(array(
						'type' => "cancel",
						'email' => $arOldEvent["CREATED_BY"]["EMAIL"],
						'name' => $arOldEvent['NAME'],
						"from" => $arOldEvent["ACTIVE_FROM"],
						"to" => $arOldEvent["ACTIVE_TO"],
						"desc" => $arOldEvent['DETAIL_TEXT'],
						"pathToUserCalendar" => $pathToUserCalendar,
						"guestId" => $guest_id,
						"guestName" => $arOldEvent["CREATED_BY"]["FULL_NAME"],
						"userId" => $userId,
						"ownerName" => $ownerName
					));
				}
			}
		}

		// Update info
		if(count($arUpdGuests) > 0)
		{
			$arCalendarEventProps = $arCalendarEvent["PROPERTY_VALUES"];
			unset($arCalendarEvent["PROPERTY_VALUES"]);

			//Check if we have to update child events
			foreach($arUpdGuests as $guest_id => $arOldEvent)
			{
				if($guest_id == $userId && $this->ownerType == 'USER')
					continue;

				$bReinvite = false;

				$bCH_from =  CEventCalendar::TrimTime($arOldEvent["ACTIVE_FROM"]) != CEventCalendar::TrimTime($arCalendarEvent["ACTIVE_FROM"]);
				$bCH_to = CEventCalendar::TrimTime($arOldEvent["ACTIVE_TO"]) != CEventCalendar::TrimTime($arCalendarEvent["ACTIVE_TO"]);
				$bTimeChanged = $bCH_from || $bCH_to;

				$bCH_name = $arOldEvent["NAME"] != $arCalendarEvent["NAME"];
				$bCH_desc = $arOldEvent["DETAIL_TEXT"] != $arCalendarEvent["DETAIL_TEXT"];
				$bFieldsChanged = $bCH_name || $bCH_desc;
				$bCH_loc = $arOldEvent["PROPERTY_VALUES"]["LOCATION"] != $arCalendarEventProps["LOCATION"];
				$bCH_repeat = $arOldEvent["PROPERTY_VALUES"]["PERIOD_TYPE"] != $arCalendarEventProps["PERIOD_TYPE"]
					|| $arOldEvent["PROPERTY_VALUES"]["PERIOD_COUNT"] != $arCalendarEventProps["PERIOD_COUNT"]
					|| $arOldEvent["PROPERTY_VALUES"]["EVENT_LENGTH"] != $arCalendarEventProps["EVENT_LENGTH"]
					|| $arOldEvent["PROPERTY_VALUES"]["PERIOD_ADDITIONAL"] != $arCalendarEventProps["PERIOD_ADDITIONAL"];

				$bCH_imp = $arOldEvent["PROPERTY_VALUES"]["IMPORTANCE"] != $arCalendarEventProps["IMPORTANCE"];
				$bCH_meettxt = $arOldEvent["PROPERTY_VALUES"]["MEETING_TEXT"] != $arCalendarEventProps["MEETING_TEXT"]['VALUE']['TEXT'];
				$bPropertyChanged = $bCH_repeat || $bCH_loc || $bCH_meettxt || $bCH_imp;

				if (count($arParams['reinviteParamsList']) > 0)
				{
					$bReinvite = in_array('name', $arParams['reinviteParamsList']) && $bCH_name;

					if (!$bReinvite)
						$bReinvite = in_array('desc', $arParams['reinviteParamsList']) && $bCH_desc;

					if (!$bReinvite)
						$bReinvite = in_array('from', $arParams['reinviteParamsList']) && $bCH_from;

					if (!$bReinvite)
						$bReinvite = in_array('to', $arParams['reinviteParamsList']) && $bCH_to;

					if (!$bReinvite)
						$bReinvite = in_array('location', $arParams['reinviteParamsList']) && $bCH_loc;

					if (!$bReinvite)
						$bReinvite = in_array('guest_list', $arParams['reinviteParamsList']) && (count($arDeletedUsers) > 0 || count($arNewGuests) > 0);

					if (!$bReinvite)
						$bReinvite = in_array('repeating', $arParams['reinviteParamsList']) && $bCH_repeat;

					if (!$bReinvite)
						$bReinvite = in_array('importance', $arParams['reinviteParamsList']) && $bCH_imp;

					if (!$bReinvite)
						$bReinvite = in_array('meet_text', $arParams['reinviteParamsList']) && $bCH_meettxt;
				}

				if($bTimeChanged || $bFieldsChanged || $bPropertyChanged)
				{
					if($guest_id != $userId)
					{
						if ($bReinvite)
						{
							$arCalendarEventProps["CONFIRMED"] = CEventCalendar::GetConfirmedID($iblockId, "Q");
							$arGuestConfirm[$guest_id] = 'Q';
						}

						$arFields = array(
							"ACTIVE_FROM" => $arCalendarEvent["ACTIVE_FROM"],
							"ACTIVE_TO" => $arCalendarEvent["ACTIVE_TO"],
							"NAME" => $arCalendarEvent["NAME"],
							"DETAIL_TEXT" => $arCalendarEvent["DETAIL_TEXT"],
							"DETAIL_TEXT_TYPE" => 'html',
							//"PROPERTY_VALUES" => $arCalendarEventProps
						);

						// If it's EXCHANGE - we try to save event to exchange
						if ($bExchange)
						{
							foreach($arCalendarEventProps as $prKey => $prVal)
								$arFields["PROPERTY_".$prKey] = $prVal;

							$calendarXmlId = CECCalendar::GetExchangeXmlId($arOldEvent["IBLOCK_ID"], $arOldEvent['IBLOCK_SECTION_ID']);
							if (strlen($calendarXmlId) > 0 && $calendarXmlId !== 0) // Synchronize with Exchange
							{
								$eventModLabel = CECEvent::GetExchModLabel($arOldEvent["IBLOCK_ID"], $arOldEvent["ID"]);
								$eventXmlId = CECEvent::GetExchangeXmlId($arOldEvent["IBLOCK_ID"], $arOldEvent["ID"]);

								$exchRes = CDavExchangeCalendar::DoUpdateItem($guest_id, $eventXmlId, $eventModLabel, $arFields);
								if (!is_array($exchRes) || !array_key_exists("XML_ID", $exchRes))
									return CEventCalendar::ThrowError(CEventCalendar::CollectExchangeErros($exchRes));

								// It's ok, we successfuly save event to exchange calendar - and save it to DB
								$arFields['XML_ID'] = $exchRes['XML_ID'];
								//$arFields['PROPERTY_VALUES']['BXDAVEX_LABEL'] = $exchRes['MODIFICATION_LABEL'];
								$arCalendarEventProps['BXDAVEX_LABEL'] = $exchRes['MODIFICATION_LABEL'];
							}
						}

						$obElement = new CIBlockElement;
						$obElement->SetPropertyValuesEx($arOldEvent["ID"], $arOldEvent["IBLOCK_ID"], $arCalendarEventProps, array("DoNotValidateLists" => true));
						if($bTimeChanged || $bFieldsChanged)
							$obElement->Update($arOldEvent["ID"], $arFields, false);
					}

					// Send message
					if ($guest_id != $userId)
					{
						CEventCalendar::SendInvitationMessage(array(
							'type' => "change",
							'email' => $arOldEvent["CREATED_BY"]["EMAIL"],
							'name' => $arOldEvent['NAME'],
							"from" => $arOldEvent["ACTIVE_FROM"],
							"to" => $arOldEvent["ACTIVE_TO"],
							"location" => $loc,
							"meetingText" => $arParams['meetingText'],
							"pathToUserCalendar" => $pathToUserCalendar,
							"guestId" => $guest_id,
							"guestName" => $arOldEvent["CREATED_BY"]["FULL_NAME"],
							"userId" => $userId,
							"eventId" => $arOldEvent["ID"],
							"ownerName" => $ownerName
						));
					}
				}
			}
		}

		$this->ClearCache($this->cachePath.'events/'.$iblockId.'/');

		return $arGuestConfirm;
	}

	function SendInvitationMessage($arParams)
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return;

		$rs = CUser::GetList(($by="id"), ($order="asc"), array("ID_EQUAL_EXACT"=>IntVal($arParams["guestId"]), "ACTIVE" => "Y"));
		if (!$rs->Fetch())
			return;

		$calendarUrl_ = str_replace('#user_id#', $arParams["guestId"], $arParams["pathToUserCalendar"]);
		$calendarUrl_ = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST'].$calendarUrl_;
		$calendarUrl = $calendarUrl_.((strpos($calendarUrl_, "?") === false) ? '?' : '&').'EVENT_ID='.intVal($arParams["eventId"]);

		if ($arParams['type'] == 'invite')
		{
			$mess = GetMessage('EC_MESS_INVITE', array('#OWNER_NAME#' => $arParams["ownerName"], '#TITLE#' => $arParams["name"], '#ACTIVE_FROM#' => $arParams["from"]));

			if (strlen($arParams['location']) > 0)
				$mess .= "\n\n".GetMessage('EC_LOCATION').': '.$arParams['location'];

			if (strlen(trim($arParams["meetingText"])) > 0)
				$mess .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array('#MEETING_TEXT#' => $arParams["meetingText"]));

			$mess .= "\n\n".GetMessage('EC_MESS_INVITE_CONF_Y', array('#LINK#' => $calendarUrl.'&CONFIRM=Y'));
			$mess .= "\n".GetMessage('EC_MESS_INVITE_CONF_N', array('#LINK#' => $calendarUrl.'&CONFIRM=N'));
			$mess .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS', array('#LINK#' => $calendarUrl.'&CLOSE_MESS=Y'));

			$title = GetMessage('EC_MESS_INVITE_TITLE', array('#OWNER_NAME#' => $arParams["ownerName"], '#TITLE#' => $arParams["name"]));
		}
		elseif($arParams['type'] == 'change')
		{
			$mess = GetMessage('EC_MESS_INVITE_CHANGED', array('#OWNER_NAME#' => $arParams["ownerName"], '#TITLE#' => $arParams["name"], '#ACTIVE_FROM#' => $arParams["from"]));

			if (strlen(trim($arParams["meetingText"])) > 0)
				$mess .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array('#MEETING_TEXT#' => $arParams["meetingText"]));

			$mess .= "\n\n".GetMessage('EC_MESS_INVITE_CONF_Y', array('#LINK#' => $calendarUrl.'&CONFIRM=Y'));
			$mess .= "\n".GetMessage('EC_MESS_INVITE_CONF_N', array('#LINK#' => $calendarUrl.'&CONFIRM=N'));
			$mess .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS', array('#LINK#' => $calendarUrl.'&CLOSE_MESS=Y'));

			$title = GetMessage('EC_MESS_INVITE_CHANGED_TITLE', array('#TITLE#' => $arParams["name"]));
		}
		elseif($arParams['type'] == 'cancel')
		{
			$mess = GetMessage('EC_MESS_INVITE_CANCEL', array('#OWNER_NAME#' => $arParams["ownerName"], '#TITLE#' => $arParams["name"], '#ACTIVE_FROM#' => $arParams["from"]));

			$mess .= "\n\n".GetMessage('EC_MESS_VIEW_OWN_CALENDAR', array('#LINK#' => $calendarUrl_));
			$title = GetMessage('EC_MESS_INVITE_CANCEL_TITLE', array('#TITLE#' => $arParams["name"]));
		}
		else
			return;

		$arMessageFields = array(
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $arParams["userId"],
			"TITLE" => $title,
			"TO_USER_ID" => $arParams["guestId"],
			"MESSAGE" => $mess,
			"EMAIL_TEMPLATE" => "CALENDAR_INVITATION"
		);

		$res = CSocNetMessages::Add($arMessageFields);

		$db_events = GetModuleEvents("intranet", "OnSendInvitationMessage");
		while($arEvent = $db_events->Fetch())
			ExecuteModuleEventEx($arEvent, array($arParams));

		/*
		// Send to e-mail
		$event = new CEvent;
		$arEvent = array(
			"GUEST_EMAIL" => $arParams["email"],
			"NAME" => $arParams["name"],
			"ACTIVE_FROM" => $arParams["from"],
			"ACTIVE_TO" => $arParams["to"],
			"GUEST_NAME" => $arParams["guestName"],
			"OWNER_NAME" => $arParams["ownerName"],
			"DETAIL_TEXT" => $arParams["desc"],
			"CALENDAR_URL" => $calendarUrl
		);
		$event->Send($arParams['type'], SITE_ID, $arEvent);
		*/
	}

	function GetUniqCalendarId()
	{
		$uniq = COption::GetOptionString("iblock", "~cal_uniq_id", "");
		if(strlen($uniq) <= 0)
		{
			$uniq = md5(uniqid(rand(), true));
			COption::SetOptionString("iblock", "~cal_uniq_id", $uniq);
		}
		return $uniq;
	}

	function GetSign($userId, $calendarId)
	{
		return md5($userId."||".$calendarId."||".$this->GetUniqCalendarId());
	}

	function CheckSign($sign, $userId, $calendarId)
	{
		return (md5($userId."||".$calendarId."||".$this->GetUniqCalendarId()) == $sign);
	}

	function SocNetLog($arParams)
	{
		$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
		$ownerId = isset($arParams['ownerId']) ? $arParams['ownerId'] : $this->ownerId;
		$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
		$pathToUserCalendar = isset($arParams['pathToUserCalendar']) ? $arParams['pathToUserCalendar'] : $this->pathToUserCalendar;
		$pathToGroupCalendar = isset($arParams['pathToGroupCalendar']) ? $arParams['pathToGroupCalendar'] : $this->pathToGroupCalendar;

		if (!class_exists('CSocNetLog') || !$ownerType || !$ownerId)
			return;

		$target = $arParams['target'];
		$id = $arParams['id'];
		$name = htmlspecialcharsex($arParams['name']);
		$from = htmlspecialcharsex($arParams['from']);
		$to = htmlspecialcharsex($arParams['to']);
		$desc = htmlspecialcharsex($arParams['desc']);
		$accessibility = htmlspecialcharsex($arParams['accessibility']);
		$importance = htmlspecialcharsex($arParams['importance']);
		$calendarId = $arParams['calendarId'];

		if ($ownerType == 'USER')
		{
			// Get user name
			$dbUser = CUser::GetByID($ownerId);
			if (!$arUser = $dbUser->Fetch())
				return;
			$owner_mess = GetMessage('EC_LOG_EV_USER', array('#USER_NAME#' => $arUser["NAME"]." ".$arUser["LAST_NAME"]));
			$url = preg_replace('/#user_id#/i', $ownerId, $pathToUserCalendar);
			$privateStatus = CECCalendar::GetPrivateStatus($iblockId, $calendarId, $ownerType);

			if (!$accessibility)
				$accessibility = 'busy';
			$accessibilityMess = GetMessage('EC_ACCESSIBILITY_'.strtoupper($accessibility));

			if ($privateStatus == 'private')
				return;
			elseif ($privateStatus == 'time' || $privateStatus == 'title')
			{
				if ($privateStatus == 'time')
				{
					$name = $accessibilityMess;
					$accessibility = '';
				}
				$desc = '';
			}
		}
		else
		{
			// Get group name
			if (!$arGroup = CSocNetGroup::GetByID($ownerId))
				return;
			$owner_mess = GetMessage('EC_LOG_EV_GROUP', array('#GROUP_NAME#' => $arGroup["NAME"]));
			$url = preg_replace('/#group_id#/i', $ownerId, $pathToGroupCalendar);
			$accessibility = '';
		}

		if ($calendarId <= 0)
			return;

		$rsData = CIBlockSection::GetByID($calendarId);
		if (!$arCalendar = $rsData->Fetch())
			return;

		$accessibility_mess = strlen($accessibility) ? '<br>'.GetMessage('EC_LOG_EV_ACCESS', array('#EV_ACCESS#' => $accessibilityMess)) : '';
		$importance_mess = strlen($importance) ? '<br>'.GetMessage('EC_LOG_EV_IMP', array('#EV_IMP#' => GetMessage('EC_IMPORTANCE_'.strtoupper($importance)))) : '';

		$desc_mess = strlen($desc) ? '<br>'.GetMessage('EC_LOG_EV_DESC', array('#EV_DESC#' => $desc)) : '';
		$calendarTitle = htmlspecialcharsex($arCalendar['NAME']);
		if ($target == 'add_event')
		{
			$title_template = GetMessage('EC_LOG_NEW_EV_TITLE');
			$mess = GetMessage('EC_LOG_NEW_EV_MESS', array('#EV_TITLE#' => $name, '#CAL_TITLE#' => $calendarTitle,  '#EV_FROM#' => $from, '#EV_TO#' => $to)).' '.$owner_mess.' '.$desc_mess.$accessibility_mess.$importance_mess;
			$url .= '?EVENT_ID='.$id;
		}
		elseif ($target == 'edit_event')
		{
			$title_template = GetMessage('EC_LOG_EDIT_EV_TITLE');
			$mess = GetMessage('EC_LOG_EDIT_EV_MESS', array('#EV_TITLE#' => $name, '#CAL_TITLE#' => $calendarTitle,  '#EV_FROM#' => $from, '#EV_TO#' => $to)).' '.$owner_mess.' '.$desc_mess.$accessibility_mess.$importance_mess;
			$url .= '?EVENT_ID='.$id;
		}
		elseif ($target == 'delete_event')
		{
			$title_template = GetMessage('EC_LOG_DEL_EV_TITLE');
			$mess = GetMessage('EC_LOG_DEL_EV_MESS', array('#EV_TITLE#' => $name, '#CAL_TITLE#' => $calendarTitle,  '#EV_FROM#' => $from, '#EV_TO#' => $to)).' '.$owner_mess.' '.$desc_mess;
		}

		$USER_ID = false;
		if ($GLOBALS["USER"]->IsAuthorized())
			$USER_ID = $GLOBALS["USER"]->GetID();

		$res = CSocNetLog::Add(
			array(
				"ENTITY_TYPE" 		=> $ownerType == 'GROUP' ? SONET_ENTITY_GROUP : SONET_ENTITY_USER,
				"ENTITY_ID" 		=> $ownerId,
				"EVENT_ID" 			=> "calendar",
				"=LOG_DATE" 		=> $GLOBALS["DB"]->CurrentTimeFunction(),
				"TITLE_TEMPLATE" 	=> $title_template,
				"TITLE" 			=> $name,
				"MESSAGE" 			=> $mess,
				"TEXT_MESSAGE" 		=> preg_replace(array("/<(\/)?b>/i", "/<br>/i"), array('', " \n"), $mess),
				"URL" 				=> $url,
				"MODULE_ID" 		=> false,
				"CALLBACK_FUNC" 	=> false,
				"USER_ID" 			=> $USER_ID
			)
		);
		if (intval($res) > 0)
			CSocNetLog::Update($res, array("TMP_ID" => $res));
	}

	function UpdateSectionId($sectionId)
	{
		?><script>window._bx_section_id = <?=intVal($sectionId)?>;</script><?
	}

	function GetUserSettings($arParams = array())
	{
		$bStatic = $arParams['static'] !== true;
		if (!$bStatic && $this->UserSettings)
			return $this->UserSettings;

		$userId = isset($arParams['userId']) ? $arParams['userId'] : $this->userId;

		$DefSettings = array(
			'tabId' => 'month',
			'CalendarSelCont' => false,
			'SPCalendarSelCont' => false,
			'MeetCalId' => false,
			'planner_scale' => 1,
			'planner_width' => 650,
			'planner_height' => 520,
			'blink' => true,
			'ShowBanner' => true
		);

		if (class_exists('CUserOptions'))
			$Settings = CUserOptions::GetOption("intranet", "event_calendar_settings", false, $userId);
		else
			$Settings = false;

		$UserSettings = $Settings && checkSerializedData($Settings) ? unserialize($Settings) : $DefSettings;
		if (!$bStatic)
			$this->UserSettings = $UserSettings;

		return $UserSettings;
	}

	function GetTabId()
	{
		if (!$this->UserSettings)
			$this->GetUserSettings();
		return $this->UserSettings['tabId'];
	}

	function SetUserSettings($Settings)
	{
		if (!class_exists('CUserOptions'))
			return;

		if ($Settings === false)
		{
			CUserOptions::SetOption("intranet", "event_calendar_settings", false, false, $this->userId);
			return $this->GetUserSettings();
		}

		if (!$this->UserSettings)
			$this->UserSettings = $this->GetUserSettings();

		$this->UserSettings['tabId'] = isset($Settings['tab_id']) && in_array($Settings['tab_id'], array('week', 'day')) ? $Settings['tab_id'] : 'month';
		$this->UserSettings['CalendarSelCont'] = isset($Settings['cal_sec']) && $Settings['cal_sec'];
		$this->UserSettings['SPCalendarSelCont'] = isset($Settings['sp_cal_sec']) && $Settings['sp_cal_sec'];

		if ($Settings['planner_scale'] !== false)
			$this->UserSettings['planner_scale'] = $Settings['planner_scale'];
		if ($Settings['planner_width'] !== false)
			$this->UserSettings['planner_width'] = $Settings['planner_width'];
		if ($Settings['planner_height'] !== false)
			$this->UserSettings['planner_height'] = $Settings['planner_height'];

		if (isset($Settings['ShowBanner']))
			$this->UserSettings['ShowBanner'] = $Settings['ShowBanner'];

		if ($this->ownerType == 'USER')
		{
			$this->UserSettings['MeetCalId'] = isset($Settings['MeetCalId']) && intVal($Settings['MeetCalId']) > 0 ? $Settings['MeetCalId'] : false;
			$this->UserSettings['blink'] = $Settings['blink'];
		}

		CUserOptions::SetOption("intranet", "event_calendar_settings", serialize($this->UserSettings), false, $this->userId);
	}

	function IsSocNet()
	{
		return (class_exists('CSocNetUserToGroup') && CBXFeatures::IsFeatureEnabled("Calendar"));
	}

	// Cache inside
	function GetConfirmedID($iblockId, $xml_id)
	{
		$bCache = true;
		if ($bCache)
		{
			$cache = new CPHPCache;
			$cachePath = "event_calendar/iblock_confirmed_id/";
			$cacheTime = 86400;
			$cacheId = $iblockId."_".$xml_id;

			if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$id = $res['id'];
			}
		}

		if (!$bCache || empty($res['id']))
		{
			$bStatic = !isset($this) || !is_a($this, "CEventCalendar");

			if (!$bStatic)
			{
				if(!is_array($this->arConfirmedID))
					$this->arConfirmedID = array();
				$arConfirmedID = $this->arConfirmedID;
			}
			else
			{
				$arConfirmedID = array();
			}

			if($bStatic || !array_key_exists($iblockId, $arConfirmedID))
			{
				$rsProperty = CIBlockProperty::GetList(array(), array(
					'IBLOCK_ID' => $iblockId,
					'CODE' => 'CONFIRMED',
				));
				$arProperty = $rsProperty->Fetch();
				if(!$arProperty)
				{
					$obProperty = new CIBlockProperty;
					$obProperty->Add(array(
						"IBLOCK_ID" => $iblockId,
						"ACTIVE" => "Y",
						"PROPERTY_TYPE" => "L",
						"MULTIPLE" => "N",
						"NAME" => GetMessage("EC_PROP_CONFIRMED_NAME"),
						"CODE" => "CONFIRMED",
						"VALUES" => array(
							array("SORT" => 10, "XML_ID" => "Q", "VALUE" => GetMessage("EC_PROP_CONFIRMED_UNK")),
							array("SORT" => 20, "XML_ID" => "Y", "VALUE" => GetMessage("EC_PROP_CONFIRMED_YES")),
							array("SORT" => 30, "XML_ID" => "N", "VALUE" => GetMessage("EC_PROP_CONFIRMED_NO")),
						),
					));
				}

				$arConfirmedID[$iblockId] = array();
				$rsEnumValues = CIBlockProperty::GetPropertyEnum("CONFIRMED", array(), array(
					"IBLOCK_ID" => $iblockId,
				));

				while($arEnum = $rsEnumValues->Fetch())
					$arConfirmedID[$iblockId][$arEnum["XML_ID"]] = $arEnum["ID"];
			}

			$id = $arConfirmedID[$iblockId][$xml_id];

			if ($bCache)
			{
				$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array("id" => $id));
			}
		}

		return $id;
	}

	function HandleUserSearch($name, $from, $to, $arFoundUsers = false, $eventId = false, &$bAddCurUser = false)
	{
		$eventId = intVal($eventId);
		if ($arFoundUsers === false)
			$arFoundUsers = CSocNetUser::SearchUser($name);

		if (!is_array($arFoundUsers) || count($arFoundUsers) <= 0)
			return array();

		$arUsers = array();
		foreach ($arFoundUsers as $userId => $userName)
		{
			$userId = intVal($userId);
			if ($userId == $this->userId)
				$bAddCurUser = true;

			if ($userId <= 0 || in_array($userId, $arUsers) || $userId == $this->userId)
				continue;

			$r = CUser::GetList(($by="id"), ($order="asc"), array("ID_EQUAL_EXACT"=>$userId, "ACTIVE" => "Y"));

			if (!$User = $r->Fetch())
				continue;
			$name = trim($User['NAME'].' '.$User['LAST_NAME']);
			if ($name == '')
				$name = trim($User['LOGIN']);

			$arUsers[] = array(
				'id' => $userId,
				'name' => $name,
				'status' => 'Q',
				'busy' => $this->GetGuestAccessibility(array('userId' => $userId, 'from' => $from, 'to' => $to))
			);
		}
		return $arUsers;
	}

	function GetGroupMembers($arParams, &$bAddCurUser = false)
	{
		// TODO: CHECK PERMISSIONS
		$dbMembers = CSocNetUserToGroup::GetList(
			array("RAND" => "ASC"),
			array(
				"GROUP_ID" => $arParams['groupId'],
				"<=ROLE" => SONET_ROLES_USER,
				"USER_ACTIVE" => "Y"
			),
			false,
			false,
			array("USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN")
		);

		$arMembers = array();
		if ($dbMembers)
		{
			while ($arMember = $dbMembers->GetNext())
			{
				if ($arMember["USER_ID"] == $this->userId)
				{
					$bAddCurUser = true;
					continue;
				}

				$name = trim($arMember['USER_NAME'].' '.$arMember['USER_LAST_NAME']);
				if ($name == '')
					$name = trim($arMember['USER_LOGIN']);

				$arMembers[] = array(
					'id' => $arMember["USER_ID"],
					'name' => $name,
					'status' => 'Q',
					'busy' => $this->GetGuestAccessibility(array('userId' => $arMember["USER_ID"], 'from' => $arParams['from'], 'to' => $arParams['to']))
				);
			}
		}

		return $arMembers;
	}

	function CheckGuestsAccessibility($arParams)
	{
		if (!isset($arParams['from']) || !is_array($arParams['arGuests']) || count($arParams['arGuests']) < 1)
			return false;

		$from = date(getDateFormat(), MakeTimeStamp($arParams['from'], getTSFormat()));
		$to = isset($arParams['to']) ? date(getDateFormat(), MakeTimeStamp($arParams['to'], getTSFormat())) : $from;

		$arGuests = array();
		foreach ($arParams['arGuests'] as $userId)
			$arGuests[$userId] = $userId;
		return $this->HandleUserSearch(false, $from, $to, $arGuests, $arParams['eventId']);
	}

	function GetGuestAccessibility($arParams)
	{
		$busy = false;

		if (!$arParams['from'] || !$arParams['to'])
			return $busy;

		$sectionId = $this->GetSectionIDByOwnerId($arParams['userId'], 'USER', $this->userIblockId);
		if ($sectionId)
		{
			$res = $this->GetEvents(array(
				'iblockId' => $this->userIblockId,
				'fromLimit' => $arParams['from'],
				'toLimit' => $arParams['to'],
				'sectionId' => $sectionId,
				'arCalendarIds' => false,
				'ownerType' => 'USER',
				'bNotFree' => true,
				'CREATED_BY' => $arParams['userId']
			));

			for ($i = 0, $l = count($res); $i < $l; $i++)
			{
				//if ($eventId && $res[$i]['HOST'] && $res[$i]['HOST']['parentId'] == $eventId)
				//	continue;
				$a = $res[$i]['ACCESSIBILITY'];
				if ($a == 'busy' || $a == 'absent')
				{
					$busy = $a;
					break;
				}
				$busy = $a;
			}
		}
		return $busy;
	}

	function AddAgent($remindTime, $arParams)
	{
		CEventCalendar::RemoveAgent($arParams);
		CAgent::AddAgent("CEventCalendar::SendRemindAgent(".$arParams['iblockId'].", ".$arParams['eventId'].", ".$arParams['userId'].", '".$arParams['pathToPage']."', '".$arParams['ownerType']."', ".$arParams['ownerId'].");", "intranet", "Y", 10, "", "Y", $remindTime);
	}

	function RemoveAgent($arParams)
	{
		CAgent::RemoveAgent("CEventCalendar::SendRemindAgent(".$arParams['iblockId'].", ".$arParams['eventId'].", ".$arParams['userId'].", '".$arParams['pathToPage']."', '".$arParams['ownerType']."', ".$arParams['ownerId'].");", "intranet");
	}

	function SendRemindAgent($iblockId, $eventId, $userId, $pathToPage, $ownerType, $ownerId)
	{
		if (!CModule::IncludeModule("iblock"))
			return;
		if (!CModule::IncludeModule("socialnetwork"))
			return;

		if (!isset($GLOBALS["USER"]) || !is_object($GLOBALS["USER"]))
		{
			$bTmpUser = True;
			$GLOBALS["USER"] = new CUser;
		}

		$rsEvent = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y",
				"ID" => $eventId,
				"CHECK_PERMISSIONS" => "N",
			),
			false,
			false,
			array("ID", "ACTIVE_FROM", "NAME", "IBLOCK_ID", "CREATED_BY", "IBLOCK_SECTION_ID")
		);
		if ($arEvent = $rsEvent->Fetch())
		{
			$MESS = GetMessage('EC_EVENT_REMINDER', Array('#EVENT_NAME#' => $arEvent["NAME"], '#DATE_FROM#' => $arEvent["ACTIVE_FROM"]));
			// Get Calendar Info:
			$rsCalendar = CIBlockSection::GetList(array('ID' => 'ASC'),
				array(
					"ID" => $arEvent['IBLOCK_SECTION_ID'],
					"IBLOCK_ID" => $iblockId,
					"ACTIVE" => "Y",
					"CHECK_PERMISSIONS" => "N"
				)
			);
			if (!$arCalendar = $rsCalendar->Fetch())
				return false;
			$calendarName = $arCalendar['NAME'];

			if ($ownerType == 'USER' && $ownerId == $userId)
			{
				$MESS .= ' '.GetMessage('EC_EVENT_REMINDER_IN_PERSONAL', Array('#CALENDAR_NAME#' => $calendarName));
			}
			else if($ownerType == 'USER')
			{
				// Get user name
				$dbUser = CUser::GetByID($ownerId);
				if (!$arUser = $dbUser->Fetch())
					return;
				$ownerName = $arUser["NAME"]." ".$arUser["LAST_NAME"];
				$MESS .= ' '.GetMessage('EC_EVENT_REMINDER_IN_USER', Array('#CALENDAR_NAME#' => $calendarName, '#USER_NAME#' => $ownerName));
			}
			else if($ownerType == 'GROUP')
			{
				// Get group name
				if (!$arGroup = CSocNetGroup::GetByID($ownerId))
					return;
				$ownerName = $arGroup["NAME"];
				$MESS .= ' '.GetMessage('EC_EVENT_REMINDER_IN_GROUP', Array('#CALENDAR_NAME#' => $calendarName, '#GROUP_NAME#' => $ownerName));
			}
			else
			{
				// Get iblock name
				$rsIblock = CIBlock::GetList(array(), array("ID"=>$iblockId, "CHECK_PERMISSIONS" => 'N'));
				if(!$arIblock = $rsIblock->Fetch())
					return;
				$iblockName = $arIblock['NAME'];
				$MESS .= ' '.GetMessage('EC_EVENT_REMINDER_IN_COMMON', Array('#CALENDAR_NAME#' => $calendarName, '#IBLOCK_NAME#' => $iblockName));
			}
			$MESS .= "\n".GetMessage('EC_EVENT_REMINDER_DETAIL', Array('#URL_VIEW#' => $pathToPage));

			$arMessageFields = array(
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
				"FROM_USER_ID" => $arEvent["CREATED_BY"],
				"TO_USER_ID" => $userId,
				"MESSAGE" => $MESS
			);
			CSocNetMessages::Add($arMessageFields);

			$db_events = GetModuleEvents("intranet", "OnRemindEventCalendar");
			while($arEvent = $db_events->Fetch())
				ExecuteModuleEventEx($arEvent, array(
					array(
						'iblockId' => $iblockId,
						'eventId' => $eventId,
						'userId' => $userId,
						'pathToPage' => $pathToPage,
						'ownerType' => $ownerType,
						'ownerId' => $ownerId
					)
				));
		}
		if ($bTmpUser)
			unset($GLOBALS["USER"]);
	}

	function GetAbsentEvents($arParams)
	{
		if (!isset($arParams['arUserIds'], $arParams['iblockId']))
			return false;

		$iblockId = $arParams['iblockId'];
		$arUserIds = $arParams['arUserIds'];
		$fromLimit = $arParams['fromLimit'];
		$toLimit = $arParams['toLimit'];
		$RESULT = array();
		$A_RESULT = array();

		//SELECT
		$arSelect = array(
			"ID",
			"IBLOCK_ID",
			"IBLOCK_SECTION_ID",
			"NAME",
			"ACTIVE_FROM",
			"ACTIVE_TO",
			"DETAIL_TEXT",
			"DETAIL_TEXT_TYPE",
			"TIMESTAMP_X",
			"CREATED_BY",
			"PROPERTY_*",
		);

		//WHERE
		$arFilter = array (
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => 'N',
			"PROPERTY_PRIVATE" => false,
			"PROPERTY_ACCESSIBILITY" => 'absent',
			"INCLUDE_SUBSECTIONS" => "Y"
		);

		if (isset($arParams['fromLimit']))
			$arFilter[">=DATE_ACTIVE_TO"] = $fromLimit;
		if (isset($arParams['toLimit']))
			$arFilter["<=DATE_ACTIVE_FROM"] = $toLimit;

		if (is_array($arUserIds) && count($arUserIds) > 0)
			$arFilter["CREATED_BY"] = $arUserIds;
		elseif ($arUserIds !== false)
			return false;

		$arCalendarPrivStatus = array();
		$arSort = Array('ACTIVE_FROM' => 'ASC');//Sort
		$rsElement = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
		while($obElement = $rsElement->GetNextElement())
		{
			$arItem = $obElement->GetFields();
			$props = $obElement->GetProperties();

			if (isset($props['CONFIRMED']) && ($props['CONFIRMED']['VALUE_XML_ID'] == 'Q' || $props['CONFIRMED']['VALUE_XML_ID'] == 'N'))
				continue;

			$calendarId = $arItem['IBLOCK_SECTION_ID'];
			if (!$arCalendarPrivStatus[$calendarId])
				$arCalendarPrivStatus[$calendarId] = CECCalendar::GetPrivateStatus($iblockId, $calendarId, 'USER');

			$privateStatus = $arCalendarPrivStatus[$calendarId];

			if ($privateStatus == 'private') // event in private calendar
				continue;

			//$props = $obElement->GetProperties();
			if (!isset($props['ACCESSIBILITY']['VALUE']) || $props['ACCESSIBILITY']['VALUE'] != 'absent')
				continue;

			if ($privateStatus == 'title')
			{
				$arItem['DETAIL_TEXT'] = '';
			}
			elseif ($privateStatus == 'time')
			{
				$arItem['~NAME'] = $arItem['NAME'] = GetMessage('EC_ACCESSIBILITY_ABSENT');
				$arItem['DETAIL_TEXT'] = '';
			}

			$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
			$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));
			$perType = (isset($props['PERIOD_TYPE']['VALUE']) && $props['PERIOD_TYPE']['VALUE'] != 'NONE') ? strtoupper($props['PERIOD_TYPE']['VALUE']) : false;

			if ($perType)
			{
				$count = (isset($props['PERIOD_COUNT']['VALUE'])) ? intval($props['PERIOD_COUNT']['VALUE']) : '';
				$length = (isset($props['EVENT_LENGTH']['VALUE'])) ? intval($props['EVENT_LENGTH']['VALUE']) : '';
				$additional = (isset($props['PERIOD_ADDITIONAL']['VALUE'])) ? $props['PERIOD_ADDITIONAL']['VALUE'] : '';

				$l1 = count($RESULT);
				CEventCalendar::DisplayPeriodicEvent($RESULT, array(
					'arItem' => $arItem,
					'perType' => $perType,
					'count' => $count,
					'length' => $length,
					'additional' => $additional,
					'fromLimit' => $fromLimit,
					'toLimit' => $toLimit,
					'bJS' => false,
					'bSuperposed' => false
				));
				for ($i = $l1; $i < count($RESULT); $i++)
					CEventCalendar::HandleAbsentEvent($RESULT[$i], $arItem['CREATED_BY'], $A_RESULT);
			}
			else
			{
				CEventCalendar::HandleElement($RESULT, $arItem, false, false, false);
				CEventCalendar::HandleAbsentEvent($RESULT[count($RESULT) - 1], $arItem['CREATED_BY'], $A_RESULT);
			}
		}
		if ($arParams['bList'])
			return $RESULT;
		return $A_RESULT;
	}

	function HandleAbsentEvent(&$event, $userId, &$a_result)
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return;

		if (
			!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $userId, "calendar")
			||
			!CSocNetFeaturesPerms::CanPerformOperation($GLOBALS['USER']->GetID(), SONET_ENTITY_USER, $userId, "calendar", 'view')
		)
			return;

		$event['USER_ID'] = $userId;
		unset($event['PERIOD'], $event['GUESTS'], $event['STATUS'], $event['HOST'], $event['ACCESSIBILITY'], $event['IMPORTANCE'],$event['PRIVATE'], $event['REMIND'], $event['IBLOCK_ID']);

		if (!isset($a_result[$userId]))
			$a_result[$userId] = array();

		$a_result[$userId][] = $event;
	}

	function GetOwnerName($arParams)
	{
		if($arParams['ownerType'] == 'USER')
		{
			// Get user name
			$dbUser = CUser::GetByID($arParams['ownerId']);
			if (!$arUser = $dbUser->Fetch())
				return;
			$ownerName = $arUser["NAME"]." ".$arUser["LAST_NAME"];
		}
		else if($arParams['ownerType'] == 'GROUP')
		{
			// Get group name
			if (!$arGroup = CSocNetGroup::GetByID($arParams['ownerId']))
				return;
			$ownerName = $arGroup["NAME"];
		}
		else
		{
			// Get iblock name
			$rsIblock = CIBlock::GetList(array(), array("ID"=>$arParams['iblockId'], "CHECK_PERMISSIONS" => 'N'));
			if(!$arIblock = $rsIblock->Fetch())
				return;
			$ownerName = $arIblock['NAME'];
		}

		return $ownerName;
	}

	function GetIntranetStructure($arParams = array())
	{
		$structure = false;

		if(IsModuleInstalled('intranet') && CModule::IncludeModule('iblock'))
		{
			if(($iblock_id = COption::GetOptionInt('intranet', 'iblock_structure', 0)) > 0)
			{
				$structure = array();
				$sec = CIBlockSection::GetList(Array("left_margin"=>"asc","SORT"=>"ASC"), Array("ACTIVE"=>"Y","CNT_ACTIVE"=>"Y","IBLOCK_ID"=>$iblock_id), true);
				while($ar = $sec->GetNext())
					$structure[] = $ar;

				//get users in the structure
				$usersInStructure = array();
				$arFilter = array('ACTIVE' => 'Y');
				$obUser = new CUser();
				$dbUsers = $obUser->GetList(($sort_by = 'last_name'), ($sort_dir = 'asc'), $arFilter, array('SELECT' => array('UF_*')));
				while ($arUser = $dbUsers->GetNext())
				{
					$arStructureUser = array(
						"USER_ID" => $arUser["ID"],
						"USER_NAME" => $arUser["NAME"],
						"USER_LAST_NAME" => $arUser["LAST_NAME"],
						"USER_PROFILE_URL" => $pu,
						"SHOW_PROFILE_LINK" => $canViewProfile,
						"PATH_TO_MESSAGES_CHAT" => str_replace("#user_id#", $arUser["ID"], $arParams["PATH_TO_MESSAGES_CHAT"]),
						"IS_ONLINE" => ($arUser["IS_ONLINE"] == "Y")
					);

					if(is_array($arUser["UF_DEPARTMENT"]) && !empty($arUser["UF_DEPARTMENT"]))
					{
						foreach($arUser["UF_DEPARTMENT"] as $dep_id)
							$usersInStructure[$dep_id][] = $arStructureUser;
					}
					else
						$usersInStructure["others"][] = $arStructureUser;
				}
			}
		}

		CEventCalendar::ShowStructureSection($structure, $usersInStructure, true);
	}

	function ShowStructureSection(&$arStructure, &$arUsersInStructure, $bUpper = false)
	{
		if (count($arStructure) <= 0 || count($arUsersInStructure) <= 0)
		{
			echo 'bx_ec_no_structure_data';
			return;
		}

		while(list($key, $department) = each($arStructure)):
	?>
	<div class="vcsd-user-section<?= $bUpper ? ' vcsd-user-section-upper' : ''?>" onclick="BxecCS_SwitchSection(document.getElementById('dep_<?=$department["ID"]?>_arrow'), 'dep_<?=$department["ID"]?>_block', arguments[0] || window.event);" title="<?= GetMessage("EC_OPEN_CLOSE_SECT")?>">
	<table>
	<tr>
		<td><div style="width: <?= (($department["DEPTH_LEVEL"] - 1) * 15)?>px"></div></td>
		<td class="vcsd-arrow-cell"><div id="dep_<?=$department["ID"]?>_arrow" class="vcsd-arrow-right"></div></td>
		<td><input type="checkbox" id="dep_<?=$department["ID"]?>" onclick="BxecCS_CheckGroup(this);" title='<?= GetMessage("EC_SELECT_SECTION", array('#SEC_TITLE#' => $department["NAME"]))?>' /></td>
		<td class="vcsd-contact-section"><?= $department["NAME"]?></td>
	</tr>
	</table>
	</div>
	<div style="display:none;" id="dep_<?=$department["ID"]?>_block" class="vcsd-user-contact-block">
	<?
			$bExit = false;
			if(list($key, $subdepartment) = each($arStructure))
			{
				prev($arStructure);
				if($subdepartment["DEPTH_LEVEL"] > $department["DEPTH_LEVEL"])
					CEventCalendar::ShowStructureSection($arStructure, $arUsersInStructure);
				if($subdepartment["DEPTH_LEVEL"] < $department["DEPTH_LEVEL"])
					$bExit = true;
			}
	?>
	<?
	if(is_array($arUsersInStructure[$department["ID"]])):
		foreach($arUsersInStructure[$department["ID"]] as $dep_user):?>
	<div class="vcsd-user-contact" onclick="BxecCS_SwitchUser('vscd_user_<?=$dep_user["USER_ID"]?>', arguments[0] || window.event);" title="<?= GetMessage("EC_SELECT_USER")?>">
	<table>
	<tr>
	<td><div style="width: <?= (($department["DEPTH_LEVEL"] - 1) * 15 + 21)?>px"></div></td>
		<td><input type="checkbox" value="<?=($dep_user["USER_ID"].'||'.$dep_user["USER_NAME"].' '.$dep_user["USER_LAST_NAME"])?>" id="vscd_user_<?=$dep_user["USER_ID"]?>" /></td>
		<td><?=$dep_user["USER_NAME"]?> <?=$dep_user["USER_LAST_NAME"]?></td>
	</tr>
	</table>
	</div>
	<?
		endforeach;
	endif;
	?>
	</div>
	<?
			if($bExit)
				return;
		endwhile;
	}

	function GetGuestsAccessability($Params)
	{
		$iblockId = $this->userIblockId;
		$curEventId = $Params['curEventId'] > 0 ? $Params['curEventId'] : false;
		$arSelect = array("ID", "NAME", "IBLOCK_ID", "ACTIVE_FROM", "ACTIVE_TO", "CREATED_BY", "PROPERTY_*");

		$arFilter = array (
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => 'N',
			"CREATED_BY" => $Params['users'],
			"PROPERTY_PRIVATE" => false,
			"!=PROPERTY_CONFIRMED" => $this->GetConfirmedID($iblockId, "N"),
			">=DATE_ACTIVE_TO" => $Params['from'],
			"<=DATE_ACTIVE_FROM" => $Params['to']
		);

		$arSort = Array('ACTIVE_FROM' => 'ASC');
		$rsElement = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);

		$arResult = array();
		while($obElement = $rsElement->GetNextElement())
		{
			$arItem = $obElement->GetFields();

			if ($curEventId == $arItem['ID'])
				continue;

			$props = $obElement->GetProperties(); // Get properties
			if ($curEventId > 0 && isset($props['PARENT']) && $props['PARENT']['VALUE'] ==  $curEventId)
				continue;

			$uid = $arItem['CREATED_BY'];
			if (!isset($arResult[$uid]))
				$arResult[$uid] = array();

			$arItem["ACCESSIBILITY"] = ($props['ACCESSIBILITY']['VALUE']) ? $props['ACCESSIBILITY']['VALUE'] : 'busy';
			$arItem["IMPORTANCE"] = ($props['IMPORTANCE']['VALUE']) ? $props['IMPORTANCE']['VALUE'] : 'normal';

			$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
			$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));

			$per_type = (isset($props['PERIOD_TYPE']['VALUE']) && $props['PERIOD_TYPE']['VALUE'] != 'NONE') ? strtoupper($props['PERIOD_TYPE']['VALUE']) : false;
			if ($per_type)
			{
				$count = (isset($props['PERIOD_COUNT']['VALUE'])) ? intval($props['PERIOD_COUNT']['VALUE']) : '';
				$length = (isset($props['EVENT_LENGTH']['VALUE'])) ? intval($props['EVENT_LENGTH']['VALUE']) : '';
				$additional = (isset($props['PERIOD_ADDITIONAL']['VALUE'])) ? $props['PERIOD_ADDITIONAL']['VALUE'] : '';

				$this->DisplayPeriodicEvent($arResult[$uid], array(
					'arItem' => $arItem,
					'perType' => $per_type,
					'count' => $count,
					'length' => $length,
					'additional' => $additional,
					'fromLimit' => $Params['from'],
					'toLimit' => $Params['to']
				));
			}
			else
			{
				$this->HandleElement($arResult[$uid], $arItem);
			}
		}

		if (count($arResult) > 0)
			CEventCalendar::DisplayJSGuestsAccessability($arResult);
	}

	function DisplayJSGuestsAccessability($arResult)
	{
		?><script><?
		foreach ($arResult as $uid => $arEvents)
		{
?>
window._bx_plann_events['<?= $uid?>'] = [
<?
			for ($i = 0, $l = count($arEvents); $i < $l; $i++):
				$fts = MakeTimeStamp($arEvents[$i]['DATE_FROM'], getTSFormat()) * 1000;
				$tts = MakeTimeStamp($arEvents[$i]['DATE_TO'], getTSFormat()) * 1000;
?>
{id: <?= $arEvents[$i]['ID']?>, from: <?= strval($fts)?>, to: <?= strval($tts)?>, imp: '<?= $arEvents[$i]['IMPORTANCE']?>', acc: '<?= $arEvents[$i]['ACCESSIBILITY']?>'}<?= ($i < $l - 1 ? ",\n" : "\n")?>
<?
			endfor;
?>
];
<?
		}
		?></script><?
	}

	function GetNearestEventsList($arParams)
	{
		if (!isset($arParams['userId']))
		{
			global $USER;
			// Get current user id
			$curUserId = $USER->IsAuthorized() ? $USER->GetID() : 0;
		}
		else
		{
			$curUserId = intval($arParams['userId']);
		}

		if ($arParams['bCurUserList'])
		{
			if ($curUserId <= 0 || (class_exists('CSocNetFeatures') && !CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $curUserId, "calendar")))
				return 'inactive_feature';

			// Get iblock id for users calendar from module-settings
			$iblockId = COption::GetOptionInt("intranet", 'iblock_calendar');
			// Get section id
			$sectionId = CEventCalendar::GetSectionIDByOwnerId($curUserId, "USER", $iblockId);
			// Expand filter
			$arFilterEx = array("CREATED_BY" => $curUserId, "SECTION_ID" => $sectionId, "INCLUDE_SUBSECTIONS" => "Y");
		}
		else
		{
			if (intVal($arParams['iblockSectionId']) > 0)
				$arFilterEx = array("SECTION_ID" => $arParams['iblockSectionId'], "INCLUDE_SUBSECTIONS" => "Y");
			$iblockId = $arParams['iblockId'];
		}

		// Check access
		$maxPerm = CIBlock::GetPermission($iblockId);
		$bAccess = $maxPerm >= 'R';
		if (CIBlock::GetPermission($iblockId) < 'R')
			return 'access_denied';

		$arSelect = array(
			"ID",
			"IBLOCK_ID",
			"IBLOCK_SECTION_ID",
			"NAME",
			"ACTIVE_FROM",
			"ACTIVE_TO",
			"DETAIL_TEXT",
			"DETAIL_TEXT_TYPE",
			"CREATED_BY",
			"PROPERTY_*",
		);

		$arFilter = array (
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => 'N',
			">=DATE_ACTIVE_TO" => $arParams['fromLimit'],
			"<=DATE_ACTIVE_FROM" => $arParams['toLimit'],
		);

		if (count($arFilterEx) > 0)
			$arFilter = array_merge($arFilter, $arFilterEx);

		$bCache = true;
		$arResult = false;
		if ($bCache)
		{
			$cache = new CPHPCache;
			$cachePath = 'event_calendar/events/'.$iblockId.'/';
			$cacheId = 'ne_'.serialize($arFilter);
			if(($tzOffset = CTimeZone::GetOffset()) <> 0)
				$cacheId .= "_".$tzOffset;
			$cacheTime = 36000000;

			if ($cache->InitCache($cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arResult = $res['events'];
			}
		}

		if (!$bCache || $arResult === false)
		{
			$arSort = Array('ACTIVE_FROM' => 'ASC');
			$rsElement = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
			$arResult = array();

			while($obElement = $rsElement->GetNextElement())
			{
				$arItem = $obElement->GetFields();
				$props = $obElement->GetProperties();
				$arItem["ACCESSIBILITY"] = (isset($props['ACCESSIBILITY']['VALUE'])) ? $props['ACCESSIBILITY']['VALUE'] : 'busy';
				$arItem["IMPORTANCE"] = (isset($props['IMPORTANCE']['VALUE'])) ? $props['IMPORTANCE']['VALUE'] : '';
				$arItem["PRIVATE"] = (isset($props['PRIVATE']['VALUE'])) ? $props['PRIVATE']['VALUE'] : '';

				if (isset($props['PARENT']) && $props['PARENT']['VALUE'] > 0)
				{
					$status = strtoupper(isset($props['CONFIRMED']) ? $props['CONFIRMED']['VALUE_XML_ID'] : 'Q');
					if ($status != 'Y' && $status != 'N')
						$status = 'Q';
					$arItem['STATUS'] = $status;
				}

				$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
				$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));
				$perType = (isset($props['PERIOD_TYPE']['VALUE']) && $props['PERIOD_TYPE']['VALUE'] != 'NONE') ? strtoupper($props['PERIOD_TYPE']['VALUE']) : false;

				if ($perType)
				{
					$count = (isset($props['PERIOD_COUNT']['VALUE'])) ? intval($props['PERIOD_COUNT']['VALUE']) : '';
					$length = (isset($props['EVENT_LENGTH']['VALUE'])) ? intval($props['EVENT_LENGTH']['VALUE']) : '';
					$additional = (isset($props['PERIOD_ADDITIONAL']['VALUE'])) ? $props['PERIOD_ADDITIONAL']['VALUE'] : '';

					CEventCalendar::DisplayPeriodicEvent($arResult, array(
						'arItem' => $arItem,
						'perType' => $perType,
						'count' => $count,
						'length' => $length,
						'additional' => $additional,
						'fromLimit' => $arParams['fromLimit'],
						'toLimit' => $arParams['toLimit'],
					));
				}
				else
				{
					CEventCalendar::HandleElement($arResult, $arItem);
				}
			}

			if ($bCache)
			{
				$cache->StartDataCache($cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array("events" => $arResult));
			}
		}

		return $arResult;
	}

	function AppendLangMessages()
	{
		$arLangMess = array(
			'DelMeetingConfirm' => 'EC_JS_DEL_MEETING_CONFIRM',
			'DelMeetingGuestConfirm' => 'EC_JS_DEL_MEETING_GUEST_CONFIRM',
			'DelEventConfirm' => 'EC_JS_DEL_EVENT_CONFIRM',
			'DelEventError' => 'EC_JS_DEL_EVENT_ERROR',
			'EventNameError' => 'EC_JS_EV_NAME_ERR',
			'EventSaveError' => 'EC_JS_EV_SAVE_ERR',
			'NewEvent' => 'EC_JS_NEW_EVENT',
			'EditEvent' => 'EC_JS_EDIT_EVENT',
			'DelEvent' => 'EC_JS_DEL_EVENT',
			'ViewEvent' => 'EC_JS_VIEW_EVENT',
			'From' => 'EC_JS_FROM',
			'To' => 'EC_JS_TO',
			'From_' => 'EC_JS_FROM_',
			'To_' => 'EC_JS_TO_',
			'EveryM' => 'EC_JS_EVERY_M',
			'EveryF' => 'EC_JS_EVERY_F',
			'EveryN' => 'EC_JS_EVERY_N',
			'EveryM_' => 'EC_JS_EVERY_M_',
			'EveryF_' => 'EC_JS_EVERY_F_',
			'EveryN_' => 'EC_JS_EVERY_N_',
			'DeDot' => 'EC_JS_DE_DOT',
			'DeAm' => 'EC_JS_DE_AM',
			'DeDes' => 'EC_JS_DE_DES',
			'_J' => 'EC_JS__J',
			'_U' => 'EC_JS__U',
			'WeekP' => 'EC_JS_WEEK_P',
			'DayP' => 'EC_JS_DAY_P',
			'MonthP' => 'EC_JS_MONTH_P',
			'YearP' => 'EC_JS_YEAR_P',
			'DateP_' => 'EC_JS_DATE_P_',
			'MonthP_' => 'EC_JS_MONTH_P_',
			'ShowPrevYear' => 'EC_JS_SHOW_PREV_YEAR',
			'ShowNextYear' => 'EC_JS_SHOW_NEXT_YEAR',
			'AddCalen' => 'EC_JS_ADD_CALEN',
			'AddCalenTitle' => 'EC_JS_ADD_CALEN_TITLE',
			'Edit' => 'EC_JS_EDIT',
			'Delete' => 'EC_JS_DELETE',
			'EditCalendarTitle' => 'EC_JS_EDIT_CALENDAR',
			'DelCalendarTitle' => 'EC_JS_DEL_CALENDAR',
			'NewCalenTitle' => 'EC_JS_NEW_CALEN_TITLE',
			'EditCalenTitle' => 'EC_JS_EDIT_CALEN_TITLE',
			'EventDiapStartError' => 'EC_JS_EV_FROM_ERR',
			'CalenNameErr' => 'EC_JS_CALEN_NAME_ERR',
			'CalenSaveErr' => 'EC_JS_CALEN_SAVE_ERR',
			'DelCalendarConfirm' => 'EC_JS_DEL_CALENDAR_CONFIRM',
			'DelCalendarErr' => 'EC_JS_DEL_CALEN_ERR',
			'AddNewEvent' => 'EC_JS_ADD_NEW_EVENT',
			'SelectMonth' => 'EC_JS_SELECT_MONTH',
			'ShowPrevMonth' => 'EC_JS_SHOW_PREV_MONTH',
			'ShowNextMonth' => 'EC_JS_SHOW_NEXT_MONTH',
			'LoadEventsErr' => 'EC_JS_LOAD_EVENTS_ERR',
			'MoreEvents' => 'EC_JS_MORE',
			'Item' => 'EC_JS_ITEM',
			'Export' => 'EC_JS_EXPORT',
			'ExportTitle' => 'EC_JS_EXPORT_TILE',
			'CalHide' => 'EC_CAL_HIDE',
			'CalHideTitle' => 'EC_CAL_HIDE_TITLE',
			'CalAdd2SP' => 'EC_ADD_TO_SP',
			'CalAdd2SPTitle' => 'EC_CAL_ADD_TO_SP_TITLE',
			'HideSPCalendarErr' => 'EC_HIDE_SP_CALENDAR_ERR',
			'AppendSPCalendarErr' => 'EC_APPEND_SP_CALENDAR_ERR',
			'FlipperHide' => 'EC_FLIPPER_HIDE',
			'FlipperShow' => 'EC_FLIPPER_SHOW',
			'SelectAll' => 'EC_SHOW_All_CALS',
			'DeSelectAll' => 'EC_HIDE_All_CALS',
			'ExpDialTitle' => 'EC_EXP_DIAL_TITLE',
			'ExpDialTitleSP' => 'EC_EXP_DIAL_TITLE_SP',
			'ExpText' => 'EC_EXP_TEXT',
			'ExpTextSP' => 'EC_EXP_TEXT_SP',
			'UserCalendars' => 'EC_USER_CALENDARS',
			'DeleteDynSPGroupTitle' => 'EC_DELETE_DYN_SP_GROUP_TITLE',
			'DeleteDynSPGroup' => 'EC_DELETE_DYN_SP_GROUP',
			'CalsAreAbsent' => 'EC_CALS_ARE_ABSENT',
			'DeleteAllUserCalendars' => 'EC_DELETE_ALL_USER_CALENDARS',
			'DelAllTrackingUsersConfirm' => 'EC_DEL_ALL_TRACK_USERS_CONF',
			'DelAllTrackingUsersConfirm' => 'EC_DEL_ALL_TRACK_USERS_CONF',
			'ShowPrevWeek' => 'EC_SHOW_PREV_WEEK',
			'ShowNextWeek' => 'EC_SHOW_NEXT_WEEK',
			'CurTime' => 'EC_CUR_TIME',
			'GoToDay' => 'EC_GO_TO_DAY',
			'DelGuestTitle' => 'EC_DEL_GUEST_TITLE',
			'DelGuestConf' => 'EC_DEL_GUEST_CONFIRM',
			'DelAllGuestsConf' => 'EC_DEL_ALL_GUESTS_CONFIRM',
			'GuestStatus_q' => 'EC_GUEST_STATUS_Q',
			'GuestStatus_y' => 'EC_GUEST_STATUS_Y',
			'GuestStatus_n' => 'EC_GUEST_STATUS_N',
			'UserProfile' => 'EC_USER_PROFILE',
			'AllGuests' => 'EC_ALL_GUESTS',
			'ShowAllGuests' => 'EC_ALL_GUESTS_TITLE',
			'DelEncounter' => 'EC_DEL_ENCOUNTER',
			'ConfirmEncY' => 'EC_EDEV_CONF_Y',
			'ConfirmEncN' => 'EC_EDEV_CONF_N',
			'ConfirmEncYTitle' => 'EC_EDEV_CONF_Y_TITLE',
			'ConfirmEncNTitle' => 'EC_EDEV_CONF_N_TITLE',
			'Confirmed' => 'EC_EDEV_CONFIRMED',
			'NotConfirmed' => 'EC_NOT_CONFIRMED',
			'NoLimits' => 'EC_T_DIALOG_NEVER',
			'Acc_busy' => 'EC_ACCESSIBILITY_B',
			'Acc_quest' => 'EC_ACCESSIBILITY_Q',
			'Acc_free' => 'EC_ACCESSIBILITY_F',
			'Acc_absent' => 'EC_ACCESSIBILITY_A',
			'Importance' => 'EC_IMPORTANCE',
			'Importance_high' => 'EC_IMPORTANCE_H',
			'Importance_normal' => 'EC_IMPORTANCE_N',
			'Importance_low' => 'EC_IMPORTANCE_L',
			'PrivateEvent' => 'EC_PRIVATE_EVENT',
			'LostSessionError' => 'EC_LOST_SESSION_ERROR',
			'ConnectToOutlook' => 'EC_CONNECT_TO_OUTLOOK',
			'ConnectToOutlookTitle' => 'EC_CONNECT_TO_OUTLOOK_TITLE',
			'UsersNotFound' => 'EC_USERS_NOT_FOUND',
			'UserBusy' => 'EC_USER_BUSY',
			'UsersNotAvailable' => 'EC_USERS_NOT_AVAILABLE',
			'UserAccessability' => 'EC_ACCESSIBILITY',
			'CantDelGuestTitle' => 'EC_CANT_DEL_GUEST_TITLE',
			'NoDesc' => 'EC_NO_DESC',
			'Host' => 'EC_EDEV_HOST',
			'ViewingEvent' => 'EC_T_VIEW_EVENT',
			'NoCompanyStructure' => 'EC_NO_COMPANY_STRUCTURE',
			'DelOwnerConfirm' => 'EC_DEL_OWNER_CONFIRM',
			'MeetTextChangeAlert' => 'EC_MEET_TEXT_CHANGE_ALERT',
			'ImpGuest' => 'EC_IMP_GUEST',
			'NotImpGuest' => 'EC_NOT_IMP_GUEST',
			'DurDefMin' => 'EC_EDEV_REM_MIN',
			'DurDefHour1' => 'EC_PL_DUR_HOUR1',
			'DurDefHour2' => 'EC_PL_DUR_HOUR2',
			'DurDefDay' => 'EC_JS_DAY_P',
			'SelectMR' => 'EC_PL_SEL_MEET_ROOM',
			'OpenMRPage' => 'EC_PL_OPEN_MR_PAGE',
			'Location' => 'EC_LOCATION',
			'FreeMR' => 'EC_MR_FREE',
			'MRNotReservedErr' => 'EC_MR_RESERVE_ERR_BUSY',
			'MRReserveErr' => 'EC_MR_RESERVE_ERR',
			'FirstInList' => 'EC_FIRST_IN_LIST',
			'UserSettings' => 'EC_USER_SET',
			'AddNewEventPl' => 'EC_JS_ADD_NEW_EVENT_PL',
			'DefMeetingName' => 'EC_DEF_MEETING_NAME',
			'NoGuestsErr' => 'EC_NO_GUESTS_ERR',
			'NoFromToErr' => 'EC_NO_FROM_TO_ERR',
			'MRNotExpireErr' => 'EC_MR_EXPIRE_ERR_BUSY',
			'CalDavEdit' => 'EC_CALDAV_EDIT',
			'NewExCalendar' => 'EC_NEW_EX_CAL',
			'CalDavDel' => 'EC_CALDAV_DEL',
			'CalDavCollapse' => 'EC_CALDAV_COLLAPSE',
			'CalDavRestore' => 'EC_CALDAV_RESTORE',
			'CalDavNoChange' => 'EC_CALDAV_NO_CHANGE',
			'CalDavTitle' => 'EC_MANAGE_CALDAV',
			'SyncOk' => 'EC_CALDAV_SYNC_OK',
			'SyncDate' => 'EC_CALDAV_SYNC_DATE',
			'SyncError' => 'EC_CALDAV_SYNC_ERROR',
			'AllCalendars' => 'EC_ALL_CALENDARS',
			'DelConCalendars' => 'DEL_CON_CALENDARS',
			'CloseBannerNotify' => 'EC_CLOSE_BANNER_NOTIFY',
			'ExchNoSync' => 'EC_BAN_EXCH_NO_SYNC',
			'ReserveRoom' => 'EC_RESERVE_NEW_ROOM',
			'ReserveRoomTitle' => 'EC_RESERVE_NEW_ROOM_TITLE'
		);

?>
var EC_MESS = {0:0<?foreach($arLangMess as $m1 => $m2){echo ', '.$m1." : '".addslashes(GetMessage($m2))."'";}?>};
<?
	}

	// Show html
	function BuildCalendarSceleton($arParams)
	{
		$id = $arParams['id'];
		$bCalDAV = CEventCalendar::IsCalDAVEnabled() && $arParams['ownerType'] == 'USER';
?>
<table class="BXECControls"><tr><td  style="vertical-align: top;">
<?if (!($arParams['bReadOnly'] && $arParams['arCalendarsCount'] == 0)):?>
<table class="bxec-calendar-bar">
<tr><td class="bxec-calendar-title bxec-cal-title-str"><img id="<?=$id?>_cal_bar_fliper" class="bxec-iconkit bxec-hide-arrow" src="/bitrix/images/1.gif"/><nobr><?=GetMessage('EC_T_CALENDARS')?></nobr>
</td>
<td class="bxec-calendar-title bxec-cal-title-ch"><img id="<?=$id?>_cal_bar_check" class="bxec-iconkit bxec-cal-bar-check" src="/bitrix/images/1.gif"/></td>
</tr>
<tr><td colSpan="2"><div class="bxec-calendar-cont" id="<?=$id?>_calendar_div"></div>
<?if(!$arParams['bReadOnly']):?>
<a id="<?=$id?>_add_calendar_link" class="bxec-add-calendar-link" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_CAL_TITLE')?>"><img class="bxec-iconkit bxec-addcal" src="/bitrix/images/1.gif"/><?=GetMessage('EC_ADD_CAL')?></a>
<? if ($bCalDAV):?>
<a id="<?=$id?>_external" class="bxec-add-calendar-link" href="javascript:void(0);" title="<?=GetMessage('EC_MANAGE_CALDAV_TITLE')?>"><img class="bxec-iconkit bxec-addcal" src="/bitrix/images/1.gif"/><?=GetMessage('EC_MANAGE_CALDAV')?></a>
<?endif;?>
<?endif;?>
</td></tr>
</table>
<?endif;?>

<?if($arParams['allowSuperpose']):?>
<table class="bxec-calendar-bar">
<tr><td class="bxec-calendar-title bxec-cal-title-str"><img id="<?=$id?>_sp_cal_bar_fliper" class="bxec-iconkit bxec-hide-arrow" src="/bitrix/images/1.gif"/><nobr><?=GetMessage('EC_T_SP_CALENDARS')?></nobr></td>
<td class="bxec-calendar-title bxec-cal-title-ch"><img id="<?=$id?>_sp_cal_bar_check" class="bxec-iconkit bxec-cal-bar-check" src="/bitrix/images/1.gif"/></td>
</tr>
<tr><td colSpan="2"><div class="bxec-calendar-cont" id="<?=$id?>_sp_calendar_div"></div>
	<table class="bxec-add-cal-link-tbl"><tr><td><a id="<?=$id?>_sp_add_calendar" class="bxec-add-calendar-link" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_EX_CAL_TITLE')?>"><img class="bxec-iconkit bxec-addcal-sp" src="/bitrix/images/1.gif"/><?=GetMessage('EC_ADD_EX_CAL')?></a></td><td align="right"><img id="<?=$id?>_export_sp_cals" class="bxec-iconkit bxec-export-sp" src="/bitrix/images/1.gif" title="<?=GetMessage('EC_EXPORT_SP_CALS')?>"/></td></tr></table>
</td></tr>
</table>
<?endif;?>

<?if($arParams['bShowBanner']):
$bExchange = CEventCalendar::IsExchangeEnabled() && $arParams['ownerType'] == 'USER';

if (!$bCalDAV && !$bExchange)
	$width = 110;
elseif($bCalDAV && !$bExchange)
	$width = 222;
elseif(!$bCalDAV && $bExchange)
	$width = 242;
else
	$width = 354;
?>
</td><td style="vertical-align: top;">

<div class="bxec-banner" id="<?=$id?>_banner" style="width:<?= $width?>px;">
	<div class="bxec-banner-elem bxec-ban-outlook">
		<div class="bxec-banner-icon"></div>
		<div class="bxec-banner-text" id="<?=$id?>_outl_sel"><div><?= GetMessage('EC_BAN_CONNECT_OUTL')?></div><div class="bxec-ban-arrow"></div></div>
	</div>
	<?if ($bCalDAV):?>
	<div class="bxec-banner-sep"></div>
	<div class="bxec-banner-elem bxec-ban-mobile">
		<div class="bxec-banner-icon"></div>
		<div class="bxec-banner-text" id="<?=$id?>_mob_sel"><div><?= GetMessage('EC_BAN_CONNECT_MOBI')?></div><div class="bxec-ban-arrow"></div></div>
	</div>
	<?endif;?>
	<?if ($bExchange):
	$bExchangeConnected = CDavExchangeCalendar::IsExchangeEnabledForUser($arParams['ownerId']);
	?>
	<div class="bxec-banner-sep"></div>
	<div class="bxec-banner-elem bxec-ban-exch" title="<?= ($bExchangeConnected ? GetMessage('EC_BAN_CONNECT_EXCH_TITLE') : GetMessage('EC_BAN_NOT_CONNECT_EXCH_TITLE'))?>">
		<div class="bxec-banner-icon"></div>
		<div class="bxec-banner-status-<?= ($bExchangeConnected ? 'ok' : 'warn')?>"></div>
		<div class="bxec-banner-text"><div>
		<?if ($bExchangeConnected):?>
			<?= GetMessage('EC_BAN_CONNECT_EXCH')?>
			<a href="javascript: void('');"  id="<?=$id?>_exch_sync" title="<?= GetMessage('EC_BAN_EXCH_SYNC_TITLE')?>"><?= GetMessage('EC_BAN_EXCH_SYNC')?></a>
		<?else:?>
			<?= GetMessage('EC_BAN_NOT_CONNECT_EXCH')?>
		<?endif;?>
		</div></div>
	</div>
	<?endif;?>
	<div class="bxec-close"  id="<?=$id?>_ban_close"></div>
</div>
<?endif;?>
</td></tr>
</table>

<table class="BXECSceleton" id="<?=$id?>_sceleton_table">
<tr class="bxec-tabs"><td>
<div class="bxec-tabs-cnt">
	<div class="bxec-tabs-div">
		<div class="bxec-set-but bxec-iconkit" title="<?=GetMessage('EC_MORE_BUT_TITLE')?>" id="<?=$id?>_more_but"></div>
		<div class="bxec-tab-div bxec-right" title="<?=GetMessage('EC_TAB_MONTH_TITLE')?>" id="<?=$id?>_tab_month">
			<div class="bxec-l"></div><div class="bxec-c"><?=GetMessage('EC_TAB_MONTH')?></div><div class="bxec-r"></div>
		</div>
		<div class="bxec-tab-div" title="<?=GetMessage('EC_TAB_WEEK_TITLE')?>" id="<?=$id?>_tab_week">
			<div class="bxec-l"></div><div class="bxec-c"><?=GetMessage('EC_TAB_WEEK')?></div><div class="bxec-r"></div>
		</div>
		<div class="bxec-tab-div" title="<?=GetMessage('EC_TAB_DAY_TITLE')?>" id="<?=$id?>_tab_day">
			<div class="bxec-l"></div><div class="bxec-c"><?=GetMessage('EC_TAB_DAY')?></div><div class="bxec-r"></div>
		</div>
	</div>

	<div id="<?=$id?>_buttons_cont" class="bxec-buttons-cont"></div>
</div>
</td></tr>
<tr class="bxec-title"><td><?=GetMessage('EC_T_EVENT_CALENDAR')?></td></tr>
<tr>
<td class="bxec-main">
	<div class="bxec-view-selector-cont">
		<div id="<?=$id?>_month_selector" class="bxec-month-selector-cont"></div>
		<div id="<?=$id?>_week_selector" class="bxec-wd-selector-cont"></div>
		<div id="<?=$id?>_day_selector" class="bxec-wd-selector-cont"></div>
	</div>
	<table class="BXEC-Calendar" cellPadding="0" cellSpacing="0" id="<?=$id?>_scel_table_month">
	<tr class="bxec-days-title"><td>
		<table class="bxec-title-table" id="<?=$id?>_days_title" cellPadding="0" cellSpacing="0"><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></table>
	</td></tr>
	<tr><td class="bxec-days-grid-td"><div id="<?=$id?>_days_grid" class="bxec-days-grid-cont"></div>
	</td></tr>
	</table>
	<table class="BXEC-Calendar-week" id="<?=$id?>_scel_table_week">
		<tr class="bxec-days-tbl-title"><td class="bxec-pad"><div class="bxec-day-t-event-holder"></div><img src="/bitrix/images/1.gif" width="40" height="1"/></td><td class="bxec-pad2"><img src="/bitrix/images/1.gif" width="16" height="1"/></td></tr>
		<tr class="bxec-days-tbl-more-ev"><td class="bxec-pad"></td><td class="bxec-pad2"></td></tr>
		<tr class="bxec-days-tbl-grid"><td class="bxec-cont"><div class="bxec-timeline-div"></div></td></tr>
	</table>
	<table class="BXEC-Calendar-week" id="<?=$id?>_scel_table_day">
		<tr class="bxec-days-tbl-title"><td class="bxec-pad"><div class="bxec-day-t-event-holder"></div><img src="/bitrix/images/1.gif" width="40" height="1" /></td><td class="bxec-pad2"><img src="/bitrix/images/1.gif" width="16" height="1" /></td></tr>
		<tr class="bxec-days-tbl-more-ev"><td class="bxec-pad"></td><td class="bxec-pad2"></td></tr>
		<tr class="bxec-days-tbl-grid"><td class="bxec-cont"><div class="bxec-timeline-div"></div></td></tr>
	</table>
</td>
</tr>
</table>

	<div id="<?=$id?>_dialogs_cont"><?CEventCalendar::BuildDialogsSceletons($arParams);?></div>

	<?
	}

	function BuildDialogsSceletons($arParams)
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools/clock.php");

		if (!$arParams['bReadOnly'])
		{
			CEventCalendar::BDS_EditEvent($arParams);
			CEventCalendar::BDS_SimpleAddEvent($arParams);
			CEventCalendar::BDS_EditCalendar($arParams);
			CEventCalendar::BDS_UserSettings($arParams);
			CEventCalendar::BDS_ExternalCalendars($arParams);
			//CEventCalendar::BDS_SortCalendar($arParams);
		}

		CEventCalendar::BDS_ViewEvent($arParams);
		CEventCalendar::BDS_ExportCalendar($arParams);
		CEventCalendar::BDS_MobileCon($arParams);

		if ($arParams['allowSuperpose'])
			CEventCalendar::BDS_Superpose($arParams);

		if(!$arParams['bReadOnly'] && $arParams['bSocNet'])
		{
			CEventCalendar::BDS_ViewCompanyStructure($arParams);
			CEventCalendar::BDS_Planner($arParams);
		}
	}

	function BDS_EditEvent($arParams)
	{
		global $APPLICATION;
		$id = $arParams['id'];
?>
<div id="bxec_edit_ed_<?=$id?>" class="bxec-edit-ed bxec-dialog"><form name="bxec_edit_ed_form_<?=$id?>"><table class="bxec-edit-ed-frame">
<tr><td class="bxec-title-cell">
<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_edit_ed_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif" /></td><td class="bxec-edit-ed-title"><div id="<?=$id?>_edit_ed_d_title"><?=GetMessage('EC_T_EDIT_EVENT')?></div></td><td id="<?=$id?>_edit_ed_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
</td>
<tr><td class="bxec-edit-ed-cont">
<div class="bxec-d-tabset" id="<?=$id?>_edit_ed_d_tabset">
<div class="bxec-d-tabs">
	<div class="bxec-d-tab bxec-d-tab-act" title="<?=GetMessage('EC_EDEV_EVENT_TITLE')?>" id="<?=$id?>_ed_tab_0">
		<div class="bxec-l"></div>
		<div class="bxec-c" style="width: 85px;"><span><?=GetMessage('EC_EDEV_EVENT')?></span></div>
		<div class="bxec-r"></div>
	</div>
	<div class="bxec-d-tab bxec-d-tab-act" title="<?=GetMessage('EC_T_DESC_TITLE')?>" id="<?=$id?>_ed_tab_1">
		<div class="bxec-l"></div>
		<div class="bxec-c" style="width: 95px;"><span><?=GetMessage('EC_T_DESC')?></span></div>
		<div class="bxec-r"></div>
	</div>
	<?if($arParams['bSocNet']):?>
	<div class="bxec-d-tab" title="<?=GetMessage('EC_EDEV_GUESTS_TITLE')?>" id="<?=$id?>_ed_tab_2">
		<div class="bxec-l"></div>
		<div class="bxec-c" style="width: 95px;"><span><?=GetMessage('EC_EDEV_GUESTS')?></span></div>
		<div class="bxec-r"></div>
	</div>
	<?endif;?>
	<div class="bxec-d-tab" title="<?=GetMessage('EC_EDEV_ADD_TAB_TITLE')?>" id="<?=$id?>_ed_tab_3">
		<div class="bxec-l"></div>
		<div class="bxec-c"><span><?=GetMessage('EC_EDEV_ADD_TAB')?></span></div>
		<div class="bxec-r"></div>
	</div>
</div>
<div class="bxec-d-cont"  id="<?=$id?>_edit_ed_d_tabcont">
	<?/* ####### TAB 0 : MAIN####### */?>
	<div id="<?=$id?>_ed_tab_cont_0" class="bxec-d-cont-div">
		<table>
			<tr><td class="dialog-par-name"><b><?=GetMessage('EC_EDEV_DATE_FROM')?>:</b></td><td class="bxec-ed-lp" style="width: 310px;">

			<input name="edit_event_from" />
			<? $APPLICATION->IncludeComponent(
			"bitrix:main.calendar",
			"",
			Array(
				"FORM_NAME" => "bxec_edit_ed_form_".$id,
				"INPUT_NAME" => "edit_event_from",
				"INPUT_VALUE" => "",
				"SHOW_TIME" => "N",
				"HIDE_TIMEBAR" => "Y"
			),
			false, array("HIDE_ICONS" => "Y"));?>

			<?CClock::Show(array('inputId' => $id.'_edev_time_from', 'inputTitle' => GetMessage('EC_EDEV_TIME_FROM')));?>
			</td></tr>
			<tr><td class="dialog-par-name"><?=GetMessage('EC_EDEV_DATE_TO')?>:</td><td class="bxec-ed-lp">
			<input name="edit_event_to" />
			<?$APPLICATION->IncludeComponent("bitrix:main.calendar", "",
			Array(
				"FORM_NAME" => "bxec_edit_ed_form_".$id,
				"INPUT_NAME" => "edit_event_to",
				"INPUT_VALUE" => "",
				"SHOW_TIME" => "N",
				"HIDE_TIMEBAR" => "Y"
			),
			false, array("HIDE_ICONS" => "Y"));?>
			<?CClock::Show(array('inputId' => $id.'_edev_time_to', 'inputTitle' => GetMessage('EC_EDEV_TIME_TO')));?>
			</td></tr>
			<tr><td class="dialog-par-name"><b><?=GetMessage('EC_T_NAME')?>:</b></td><td class="bxec-ed-lp"><input type="text" size="37" id="<?=$id?>_edit_ed_name" style="width: 260px;"/></td></tr>
			<tr><td class="dialog-par-name"><label for="<?=$id?>_planner_location1"><?=GetMessage('EC_LOCATION')?>:</label></td><td class="bxec-ed-lp">
			<div class="bxecpl-loc-cont">
				<input size="37" style="width: 246px;" id="<?=$id?>_planner_location1" type="text"  title="<?=GetMessage('EC_LOCATION_TITLE')?>" value="<?= GetMessage('EC_PL_SEL_MEET_ROOM')?>" class="ec-label" />
			</div>
			</td></tr>
			<?if($arParams['ownerType'] == 'USER'):?>
			<tr title="<?=GetMessage('EC_ACCESSIBILITY_TITLE')?>">
				<td class="dialog-par-name"><label for="<?=$id?>_bxec_accessibility"><?=GetMessage('EC_ACCESSIBILITY')?>:</label></td>
				<td class="bxec-ed-lp">
				<select id="<?=$id?>_bxec_accessibility" style="width: 210px;">
					<option value="busy" title="<?=GetMessage('EC_ACCESSIBILITY_B')?>"><?=GetMessage('EC_ACCESSIBILITY_B')?></option>
					<option value="quest" title="<?=GetMessage('EC_ACCESSIBILITY_Q')?>"><?=GetMessage('EC_ACCESSIBILITY_Q')?></option>
					<option value="free" title="<?=GetMessage('EC_ACCESSIBILITY_F')?>"><?=GetMessage('EC_ACCESSIBILITY_F')?></option>
					<option value="absent" title="<?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)"><?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)</option>
				</select>
				</td>
			</tr>
			<?endif;?>
			<tr><td class="dialog-par-name" style="height: 23px"><?=GetMessage('EC_T_CALENDAR')?>:</td><td class="bxec-cal-sel-cel"><span><?=GetMessage('EC_T_CREATE_DEF')?></span><select id="<?=$id?>_edit_ed_calend_sel"></select><span style="display: none;"><?=GetMessage('EC_T_CALEN_DIS_WARNING')?></span>
			</td></tr>
		</table>
	</div>
	<?/* ####### TAB 1 : DESCRIPTION ####### */?>
	<div id="<?=$id?>_ed_tab_cont_1" class="bxec-d-cont-div bxec-lhe">
	<?
		CModule::IncludeModule("fileman");
		$LHE = new CLightHTMLEditor;
		$LHE->Show(array(
			'id' => 'LHEEvDesc',
			'width' => '455',
			'height' => '285',
			'inputId' => $id.'_edit_ed_desc',
			'content' => '',
			'bUseFileDialogs' => false,
			'bFloatingToolbar' => false,
			'bArisingToolbar' => true,
			'toolbarConfig' => array(
				'Bold', 'Italic', 'Underline', 'RemoveFormat',
				'CreateLink', 'DeleteLink', 'Image',
				'BackColor', 'ForeColor',
				'JustifyLeft', 'JustifyCenter', 'JustifyRight',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent',
				'FontSizeList', 'HeaderList'
			),
			'jsObjName' => 'pLHEEvDesc',
			'bInitByJS' => true,
			'bSaveOnBlur' => false
		));
		?>
	</div>
	<?if($arParams['bSocNet']):?>
	<?/* ####### TAB 2 : GUESTS ####### */?>
	<div id="<?=$id?>_ed_tab_cont_2" class="bxec-d-cont-div" style="padding: 5px 8px;">
		<div style="padding: 0 0 6px 6px;">
		<a id="<?=$id?>_planner_link" href="javascript:void(0);" title="<?=GetMessage('EC_PLANNER_TITLE')?>" class="bxex-planner-link"><img src="/bitrix/images/1.gif" /><?=GetMessage('EC_PLANNER2')?></a>
		</div>
		<?=GetMessage('EC_ADD_GUEST')?>:
		<?
		if ($arParams['bExtranet'])
			$ExtraMode = 'E';
		elseif (CModule::IncludeModule('extranet'))
			$ExtraMode = 'I';
		else
			$ExtraMode = '';

		$APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.user_search_input",
			".default",
			array(
				"NAME" => "guest_search",
				"TEXT" => "size='25'",
				"EXTRANET" => $ExtraMode,
				"FUNCTION" => "EdEventAddGuest_".$id
			), false, array("HIDE_ICONS" => "Y"));

		// $APPLICATION->IncludeComponent(
			// "bitrix:intranet.user.selector",
			// ".default",
			// array(
				// "INPUT_NAME" => "guest_search",
				// //"TEXT" => "size='25'",
				// "EXTRANET" => $ExtraMode,
				// "SHOW_INPUT" => "Y",
				// "MULTIPLE" => "N",
				// "ONSELECT" => "EdEventAddGuest_".$id
			// ), false, array("HIDE_ICONS" => "Y"));
		?>
		<div id="<?=$id?>_edev_add_ex" class="bxec-add-ex">
		<?if (!$arParams['bExtranet']):?>
		<?if($arParams['ownerType'] == 'GROUP'):?>
		<a id="<?=$id?>_add_from_group" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_GROUP_MEMBER_TITLE')?>" class="bxex-add-ex-link"><img src="/bitrix/images/1.gif" /><?=GetMessage('EC_ADD_GROUP_MEMBER')?></a>
		<?endif;?>
		<a id="<?=$id?>_add_from_struc" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_MEMBERS_FROM_STR_TITLE')?>" class="bxex-add-ex-link"><img src="/bitrix/images/1.gif" /><?=GetMessage('EC_ADD_MEMBERS_FROM_STR')?></a>
		<?endif;?>
		</div>
		<div id="<?=$id?>_edev_uc_notice" class="bxec-eeuc-notice"></div>

		<div class="bxec-g-title"><?=GetMessage('EC_GUEST_LIST')?><a id="<?=$id?>_edev_del_all_guests" href="javascript:void(0)" title="<?=GetMessage('EC_DEL_ALL_GUESTS_TITLE')?>"><?=GetMessage('EC_DEL_ALL_GUESTS')?></a></div>
		<div class="bxec-g-table-cont">
		<table id="<?=$id?>_edev_guests_table" class="bxec-edev-guests">
			<tr class="bxec-g-empty"><td colSpan="3"> - <?=GetMessage('EC_EMPTY_LIST')?> - </td></tr>
		</table>
		</div>
		<div class="bxec-add-meet-text"><a id="<?=$id?>_add_meet_text" href="javascript:void(0);"><?=GetMessage('EC_ADD_METTING_TEXT')?></a></div>
		<div class="bxec-meet-text" id="<?=$id?>_meet_text_cont">
			<div class="bxec-mt-d"><?=GetMessage('EC_METTING_TEXT')?> (<a id="<?=$id?>_hide_meet_text" href="javascript:void(0);" title="<?=GetMessage('EC_HIDE_METTING_TEXT_TITLE')?>"><?=GetMessage('EC_HIDE')?></a>): </div><br />
			<textarea  class="bxec-mt-t" cols="63" id="<?=$id?>_meeting_text" rows="3"></textarea>
		</div>
	</div>
	<?endif;?>
	<?/* ####### TAB 3 ####### */?>
	<div id="<?=$id?>_ed_tab_cont_3" class="bxec-d-cont-div" style="padding: 5px 8px;">
		<table class="bxec-reminder-table">
			<tr class="bxec-edev-ad-title"><td colSpan="2"><?=GetMessage('EC_T_REPEATING')?></td></tr>
			<?/* Repeat row start*/?>
			<tr id="<?=$id?>_edit_ed_rep_tr"  class="bxec-edit-ed-rep"><td class="bxec-edit-ed-repeat"><?=GetMessage('EC_T_REPEAT')?>:</td><td class="bxec-ed-lp">
			<select id="<?=$id?>_edit_ed_rep_sel">
				<option value="none"><?=GetMessage('EC_T_REPEAT_NONE')?></option>
				<option value="daily"><?=GetMessage('EC_T_REPEAT_DAILY')?></option>
				<option value="weekly"><?=GetMessage('EC_T_REPEAT_WEEKLY')?></option>
				<option value="monthly"><?=GetMessage('EC_T_REPEAT_MONTHLY')?></option>
				<option value="yearly"><?=GetMessage('EC_T_REPEAT_YEARLY')?></option>
			</select>
			<div id="<?=$id?>_edit_ed_repeat_sect" style="display: none; width: 310px;">
			<span id="<?=$id?>_edit_ed_rep_phrase1"></span>
			<select id="<?=$id?>_edit_ed_rep_count">
				<?for ($i = 1; $i < 36; $i++):?>
					<option value="<?=$i?>"><?=$i?></option>
				<?endfor;?>
			</select>
			<span id="<?=$id?>_edit_ed_rep_phrase2"></span>
			<br>
			<div id="<?=$id?>_edit_ed_rep_week_days" class="bxec-rep-week-days">
				<?for($i = 0; $i < 7; $i++):
					$id_ = $id.'bxec_week_day_'.$i;?>
				<input id="<?=$id_?>" type="checkbox" value="Y">
				<label for="<?=$id_?>" title="<?=$arParams['week_days'][$i][0]?>"><?=$arParams['week_days'][$i][1]?></label>
				<?endfor;?>
			</div>
			<?=GetMessage('EC_T_DIALOG_STOP_REPEAT')?>:
			<input name="date_calendar" size="19" />
			<?$APPLICATION->IncludeComponent(
			"bitrix:main.calendar",
			"",
			Array(
				"FORM_NAME" => "bxec_edit_ed_form_".$id,
				"INPUT_NAME" => "date_calendar",
				"INPUT_VALUE" => "",
				"SHOW_TIME" => "N",
				"HIDE_TIMEBAR" => "Y"
			),
			false, array("HIDE_ICONS" => "Y"));?>
			<div>
			</td></tr>
			<?/* Repeat row end*/?>

			<?if($arParams['bSocNet']):?>
			<tr class="bxec-edev-ad-title"><td colSpan="2"><?=GetMessage('EC_EDEV_REMINDER')?></td></tr>
			<tr>
				<td colspan="2">
				<input id="<?=$id?>_bxec_reminder" type="checkbox" value="Y">
				<label for="<?=$id?>_bxec_reminder"><?=GetMessage('EC_EDEV_REMIND_EVENT')?></label>
				<span id="<?=$id?>_bxec_rem_cont" style="display: none;">
				<?=GetMessage('EC_EDEV_FOR')?>
				<input id="<?=$id?>_bxec_rem_count" type="text" style="width: 30px" size="2">
				<select id="<?=$id?>_bxec_rem_type">
					<option value="min" selected="true"><?=GetMessage('EC_EDEV_REM_MIN')?></option>
					<option value="hour"><?=GetMessage('EC_EDEV_REM_HOUR')?></option>
					<option value="day"><?=GetMessage('EC_EDEV_REM_DAY')?></option>
				</select>
				<?=GetMessage('EC_JS_DE_VORHER')?>
				</span>
				<a id="<?=$id?>_bxec_rem_save"  title="<?=GetMessage('EC_EDEV_REM_SAVE_TITLE')?>" href="javascript:void(0);" class="bxec-rem-save"><?=GetMessage('EC_EDEV_REM_SAVE')?></a>
				</td>
			</tr>
			<?endif;?>
			<tr class="bxec-edev-ad-title"><td colSpan="2"><?=GetMessage('EC_EDDIV_SPECIAL_NOTES')?></td></tr>
			<tr>
				<td colspan="2">
				<?=GetMessage('EC_IMPORTANCE_TITLE')?>:
				<select id="<?=$id?>_bxec_importance">
					<option value="high" style="font-weight: bold;"><?=GetMessage('EC_IMPORTANCE_H')?></option>
					<option value="normal" selected="true"><?=GetMessage('EC_IMPORTANCE_N')?></option>
					<option value="low" style="color: #909090;"><?=GetMessage('EC_IMPORTANCE_L')?></option>
				</select>
				</td>
			</tr>
			<?if($arParams['ownerType'] == 'USER'):?>
			<tr>
				<td colspan="2">
				<input id="<?=$id?>_bxec_private" type="checkbox" value="Y" title="<?=GetMessage('EC_PRIVATE_TITLE')?>">
				<label for="<?=$id?>_bxec_private" title="<?=GetMessage('EC_PRIVATE_TITLE')?>"><?=GetMessage('EC_PRIVATE_EVENT')?></label>
				</td>
			</tr>
			<?endif;?>
		</table>
		</div>
	</div>
</div>
</div>
</td></tr>
<tr><td class="bxec-edit-ed-buttons">
<a id="<?=$id?>_edit_ed_delete" href="javascript:void(0);" title="<?=GetMessage('EC_T_DELETE_EVENT')?>"><img class="bxec-iconkit bxec-delevent" src="/bitrix/images/1.gif" /><?=GetMessage('EC_T_DELETE')?></a>
<input id="<?=$id?>_edit_ed_save" type="button" value="<?=GetMessage('EC_T_SAVE')?>">
<input id="<?=$id?>_edit_ed_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
</td></tr>
</table>
</form>
</div>
	<?
	}

	function BDS_SimpleAddEvent($arParams)
	{
		$id = $arParams['id'];
?>
<div id="bxec_add_ed_<?=$id?>" class="bxec-add-ed bxec-dialog"><table cellPadding="0" cellSpacing="0" class="bxec-add-ed-frame">
	<tr><td class="bxec-title-cell" colSpan="2">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_add_ed_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-add-ed-title bxec-ed-lp"><?=GetMessage('EC_T_NEW_EVENT')?></td><td id="<?=$id?>_add_ed_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td>
	<tr><td colSpan="2" class="bxec-add-ed-per" id="<?=$id?>_add_ed_per">
	<div class="bxec-txt" id="<?=$id?>_add_ed_per_text">&nbsp;</div>
	</td></tr>
	<tr><td class="dialog-par-name" style="height: 23px"><label for="<?=$id?>_add_ed_name"><b><?=GetMessage('EC_T_NAME')?>:</b></label></td><td class="bxec-ed-lp"><input type="text" size="30" id="<?=$id?>_add_ed_name"  style="width: 210px"/></td></tr>
	<tr><td class="dialog-par-name" style="height: 60px"><label for="<?=$id?>_add_ed_desc"><?=GetMessage('EC_T_DESC')?>:</label></td><td class="bxec-ed-lp"><textarea cols="27" rows="2" id="<?=$id?>_add_ed_desc" style="width: 210px; resize: none;"></textarea></td></tr>
	<?if($arParams['ownerType'] == 'USER'):?>
	<tr title="<?=GetMessage('EC_ACCESSIBILITY_TITLE')?>"><td class="dialog-par-name"><label for="<?=$id?>_add_ed_acc"><?=GetMessage('EC_ACCESSIBILITY_S')?>:</label></td><td class="bxec-ed-lp">
		<select id="<?=$id?>_add_ed_acc" style="width:210px;">
			<option value="busy" title="<?=GetMessage('EC_ACCESSIBILITY_B')?>"><?=GetMessage('EC_ACCESSIBILITY_B')?></option>
			<option value="quest" title="<?=GetMessage('EC_ACCESSIBILITY_Q')?>"><?=GetMessage('EC_ACCESSIBILITY_Q')?></option>
			<option value="free" title="<?=GetMessage('EC_ACCESSIBILITY_F')?>"><?=GetMessage('EC_ACCESSIBILITY_F')?></option>
			<option value="absent" title="<?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)"><?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)</option>
		</select>
		</td>
	</tr>
	<?endif;?>
	<tr><td class="dialog-par-name" style="height: 23px; padding-top:4px;"><label for="<?=$id?>_add_ed_calend_sel"><?=GetMessage('EC_T_CALENDAR')?>:</label></td><td class="bxec-cal-sel-cel" style="padding: 2px 0 0 7px;"><span><?=GetMessage('EC_T_CREATE_DEF')?></span><select id="<?=$id?>_add_ed_calend_sel"></select><span class="bxec-warn" style="display: none;"><?=GetMessage('EC_T_CALEN_DIS_WARNING')?></span>
	</td></tr>
	<tr><td colSpan="2" class="bxec-add-ed-buttons bxec-ed-lp">
	<a  id="<?=$id?>_ext_dialog_mode"  href="javascript:void(0)" title="<?=GetMessage('EC_GO_TO_EXT_DIALOG')?>"><?=GetMessage('EC_EXT_DIAL')?></a>
	<input id="<?=$id?>_add_ed_save" type="button" value="<?=GetMessage('EC_T_ADD')?>">
	<input id="<?=$id?>_add_ed_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<?
	}

	function BDS_ViewEvent($arParams)
	{
		$id = $arParams['id'];
?>
<div id="bxec_view_ed_<?=$id?>" class="bxec-view-ed bxec-dialog"><table class="bxec-view-ed-frame"  id="<?=$id?>_view_ed_tbl">
	<tr><td class="bxec-title-cell">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_view_ed_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-view-ed-title"><?=GetMessage('EC_T_VIEW_EVENT')?></td><td id="<?=$id?>_view_ed_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td>
	<tr><td class="bxec-view-d-cont">
	<div class="bxec-d-tabset" id="<?=$id?>_view_d_tabset">
	<div class="bxec-d-tabs">
		<div class="bxec-d-tab bxec-d-tab-act" title="<?=GetMessage('EC_BASIC_TITLE')?>" id="<?=$id?>_view_tab_0">
			<div class="bxec-l"></div>
			<div class="bxec-c"><span><?=GetMessage('EC_BASIC')?></span></div>
			<div class="bxec-r"></div>
		</div>
		<div class="bxec-d-tab" title="<?=GetMessage('EC_T_DESC_TITLE')?>" id="<?=$id?>_view_tab_1">
			<div class="bxec-l"></div>
			<div class="bxec-c"><span><?=GetMessage('EC_T_DESC')?></span></div>
			<div class="bxec-r"></div>
		</div>
		<div class="bxec-d-tab" title="<?=GetMessage('EC_EDEV_ADD_TAB_TITLE')?>" id="<?=$id?>_view_tab_2">
			<div class="bxec-l"></div>
			<div class="bxec-c"><span><?=GetMessage('EC_EDEV_ADD_TAB')?></span></div>
			<div class="bxec-r"></div>
		</div>
	</div>
	<div class="bxec-d-cont"  id="<?=$id?>_view_d_tabcont">
	<?/* ####### TAB 0 ####### */?>
		<div id="<?=$id?>_view_tab_cont_0" class="bxec-d-cont-div"><table>
				<tr><td align="left" class="bxec-ed-lp" style="height: 23px; width: 60px;"><?=GetMessage('EC_T_NAME')?>:</td><td class="bxec-ed-lp" style="width: 380px;"><div class="bxec-view-name"></div></td></tr>
				<tr><td align="left" class="bxec-ed-lp" style="height: 23px; width: 60px;"><?=GetMessage('EC_T_CREATED_BY_NAME')?>:</td><td class="bxec-ed-lp" style="width: 380px;"><div class="bxec-view-name"></div></td></tr>
				<tr><td colSpan="2" class="bxec-view-ed-per">&nbsp;</td></tr>
				<tr><td class="bxec-par-name"><?=GetMessage('EC_T_REPEAT')?>:</td><td class="bxec-par-cont"></td></tr>
				<tr><td align="left" class="bxec-ed-lp" title="<?=GetMessage('EC_LOCATION_TITLE')?>" style="white-space: nowrap;"><?=GetMessage('EC_LOCATION')?>:</td><td class="bxec-ed-lp" style="padding-left: 5px;"></td></tr>
				<tr><td class="bxec-par-name" colSpan="2"><?=GetMessage('EC_MEETING_TEXT2')?>:<div class="bxec-vd-meet-text" id="<?=$id?>_view_ed_meet_text"></div></td></tr>
				<tr><td class="bxec-par-name" colSpan="2"><?=GetMessage('EC_EDEV_GUESTS')?><span></span>:<div class="bxec-guests-div" id="<?=$id?>_view_ed_guest_div"></div></td></tr>
				<tr><td class="bxec-par-name" style="white-space: nowrap;"><?=GetMessage('EC_EDEV_CONFIRM')?>:</td><td></td></tr>
			</table>
		</div>
		<?/* ####### TAB 1 ####### */?>
		<div id="<?=$id?>_view_tab_cont_1" class="bxec-d-cont-div">
			<span><?=GetMessage('EC_T_DESC')?>:</span>
			<div class="bxec-view-ed-desc-cont" id="<?=$id?>_view_ed_desc"><span class="no-desc"><?=GetMessage('EC_NO_DESC')?></span></div>
		</div>
		<?/* ####### TAB 2 ####### */?>
		<div id="<?=$id?>_view_tab_cont_2" class="bxec-d-cont-div"><table>
				<tr><td class="bxec-par-name"><?=GetMessage('EC_T_CALENDAR')?>:</td><td class="bxec-par-cont"></td></tr>
				<tr class="bxec-edev-ad-title"><td colSpan="2"><?=GetMessage('EC_EDDIV_SPECIAL_NOTES')?></td></tr>
				<tr><td class="bxec-par-name"  colSpan="2"><?=GetMessage('EC_IMPORTANCE_TITLE')?>:&nbsp;&nbsp;<span id="<?=$id?>_view_ed_imp"></span></td></tr>
				<tr><td class="bxec-par-name"  colSpan="2"><?=GetMessage('EC_ACCESSIBILITY_TITLE')?>:&nbsp;&nbsp;<span id="<?=$id?>_view_ed_accessibility"></span></td></tr>
				<tr><td class="bxec-par-name"  colSpan="2" style="font-weight: bold;"><?=GetMessage('EC_PRIVATE_EVENT')?></td></tr>
			</table>
		</div>
	</div>
	</div>
	</td></tr>
	<tr><td class="bxec-view-ed-buttons">
	<a id="<?=$id?>_view_ed_edit" href="javascript:void(0);"><img class="bxec-iconkit bxec-edevent" src="/bitrix/images/1.gif" /><?=GetMessage('EC_T_EDIT')?></a>
	<a id="<?=$id?>_view_ed_delete" href="javascript:void(0);"><img class="bxec-iconkit bxec-delevent" src="/bitrix/images/1.gif" /><?=GetMessage('EC_T_DELETE')?></a>
	<input id="<?=$id?>_view_ed_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<?
	}

	function BDS_EditCalendar($arParams)
	{
		$id = $arParams['id'];
?>
<div id="bxec_edcal_<?=$id?>" class="bxec-dialog bxec-edcal"><table class="bxec-edcal-frame">
	<tr><td class="bxec-title-cell" colSpan="2">
	<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_edcal_<?=$id?>'));" cellPadding="0" cellSpacing="0"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-edcal-title" id="<?=$id?>_edcal_d_title"></td><td id="<?=$id?>_edcal_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td>
	<tr><td class="dialog-par-name" style="height: 23px"><b><?=GetMessage('EC_T_NAME')?>:</b> </td><td class="bxec-ed-lp"><input type="text" size="35" id="<?=$id?>_edcal_name" style="width: 240px;" /></td></tr>
	<tr><td class="dialog-par-name" style="height: 60px"><?=GetMessage('EC_T_DESC')?>:</td><td class="bxec-ed-lp"><textarea cols="32" id="<?=$id?>_edcal_desc" rows="2" style="width: 240px; resize: none;"></textarea></td></tr>
	<tr><td class="dialog-par-name" style="height: 23px; padding-top: 12px;"><?=GetMessage('EC_T_COLOR')?>:</td><td class="bxec-ed-lp"><input type="text" size="8" id="<?=$id?>_edcal_color" style="float: left; margin: 6px 10px 0 0;"/>
	<table class="bxec-color-selector" id="<?=$id?>_edcal_color_table">
	<tr><td  class="bxec-big-color" rowSpan="2">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
	<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
	</table>
	</td></tr>
	<?if($arParams['ownerType'] == 'USER'):?>
	<tr><td class="bxec-ed-lp" style="height: 23px" colSpan="2"><?=GetMessage('EC_CAL_STATUS')?>:
		<select id="<?=$id?>_cal_priv_status" style="width: 230px">
			<option value="private" title="<?=GetMessage('EC_CAL_STATUS_PRIVATE')?>"><?=GetMessage('EC_CAL_STATUS_PRIVATE')?></option>
			<option value="time" title="<?=GetMessage('EC_CAL_STATUS_TIME')?>"><?=GetMessage('EC_CAL_STATUS_TIME')?></option>
			<option value="title" title="<?=GetMessage('EC_CAL_STATUS_TITLE')?>"><?=GetMessage('EC_CAL_STATUS_TITLE')?></option>
			<option value="full" selected="selected" title="<?=GetMessage('EC_CAL_STATUS_FULL')?>"><?=GetMessage('EC_CAL_STATUS_FULL')?></option>
		</select>
	</td></tr>
	<tr><td class="bxec-ed-lp" colSpan="2" style="padding-right: 30px;">
		<input id="<?=$id?>_bxec_meeting_calendar" type="checkbox" value="Y"><label for="<?=$id?>_bxec_meeting_calendar"><?=GetMessage('EC_MEETING_CALENDAR')?></label>
	</td></tr>
	<?endif;?>
	<tr><td colSpan="2" class="bxec-ed-lp">
		<input id="<?=$id?>_bxec_cal_exp_allow" type="checkbox" value="Y"><label for="<?=$id?>_bxec_cal_exp_allow"><?=GetMessage('EC_T_ALLOW_CALEN_EXP')?></label>
		<div id="<?=$id?>_bxec_calen_exp_div" style="margin-top: 4px;">
		<?=GetMessage('EC_T_CALEN_EXP_SET')?>:
		<select id="<?=$id?>_bxec_calen_exp_set">
			<option value="all"><?=GetMessage('EC_T_CALEN_EXP_SET_ALL')?></option>
			<option value="3_9"><?=GetMessage('EC_T_CALEN_EXP_SET_3_9')?></option>
			<option value="6_12"><?=GetMessage('EC_T_CALEN_EXP_SET_6_12')?></option>
		</select>
		</div>
	</td></tr>

	<?if (CEventCalendar::IsExchangeEnabled() && $arParams['ownerType'] == 'USER'):?>
	<tr><td colSpan="2" class="bxec-ed-lp">
		<input id="<?=$id?>_bxec_cal_exch" type="checkbox" value="Y" checked="checked"><label for="<?=$id?>_bxec_cal_exch"><?=GetMessage('EC_CALENDAR_TO_EXCH')?></label>
	</td></tr>
	<?endif;?>

	<?if($arParams['allowSuperpose']):?>
	<tr id="<?=$id?>_bxec_cal_add2sp_cont"><td colSpan="2" class="bxec-ed-lp">
		<input id="<?=$id?>_bxec_cal_add2sp" type="checkbox" value="Y"><label for="<?=$id?>_bxec_cal_add2sp"><?=GetMessage('EC_T_ADD_TO_SP')?></label>
	</td></tr>
	<?endif;?>
	<tr><td colSpan="2" class="bxec-edcal-buttons"><div style="float: left; margin: 3px 5px 0px 10px;">
	<a id="<?=$id?>_edcal_delete" href="javascript:void(0);"><img class="bxec-iconkit bxec-delcal" src="/bitrix/images/1.gif" /><?=GetMessage('EC_T_DELETE_CALENDAR')?></a></div>
	<input id="<?=$id?>_edcal_save" type="button" value="<?=GetMessage('EC_T_SAVE')?>"><input id="<?=$id?>_edcal_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">

	</td></tr>
</table>
</div>
<?
	}

	function BDS_ExportCalendar($arParams)
	{
		$id = $arParams['id'];
?>
<div id="bxec_excal_<?=$id?>" class="bxec-excal bxec-dialog"><table class="bxec-excal-frame">
	<tr><td class="bxec-title-cell" colSpan="2">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_excal_<?=$id?>'));" ><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-excal-title" id="<?=$id?>_excal_dial_title"></td><td id="<?=$id?>_excal_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td></tr>
	<tr><td colSpan="2" class="bxec-ed-lp" style="padding-left: 10px;">
		<span id="<?=$id?>_excal_text"></span><br />
		<div class="bxec-exp-link-cont">
			<a href="javascript:void(0);" target="_blank" id="<?=$id?>_excal_link">&ndsp;</a>
			<span id="<?=$id?>_excal_warning" class="bxec-export-warning"><?=GetMessage('EC_EDEV_EXP_WARN')?></span>
		</div>
		<span>
			<a title="<?=GetMessage('EC_T_EXPORT_NOTICE_OUTLOOK_TITLE')?>" href="javascript:void(0);" id="<?=$id?>_excal_link_outlook"><?=GetMessage('EC_T_EXPORT_NOTICE_OUTLOOK_LINK')?></a>
			<div class="bxec-excal-notice-outlook"><?=GetMessage('EC_T_EXPORT_NOTICE_OUTLOOK')?></div>
		</span>
	</td></tr>
	<tr><td colSpan="2" class="bxec-excal-buttons">
	<input id="<?=$id?>_excal_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<?
	}

	function BDS_Superpose($arParams)
	{
		global $APPLICATION;
		$id = $arParams['id'];
?>
		<div id="bxec_sprpose_<?=$id?>" class="bxec-dialog bxec-sprpose"><table class="bxec-sprpose-frame">
	<tr><td class="bxec-title-cell" colSpan="2">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_sprpose_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-sprpose-title"><?=GetMessage('EC_T_SUPERPOSE_TITLE')?></td><td id="<?=$id?>_sprpose_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td></tr>
	<tr><td colSpan="2" class="bxec-ed-lp" style="padding-left: 10px;">
		<div id="<?=$id?>_sprpose_cont" class="bxec-sprpose-cont"></div>
	</td></tr>
	<tr><td colSpan="2" class="bxec-sprpose-buttons">
	<input id="<?=$id?>_sprpose_save" type="button" value="<?=GetMessage('EC_T_SAVE')?>">
	<input id="<?=$id?>_sprpose_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<div id="<?=$id?>_sp_user_search_input_cont" style="display: none; margin-top: -5px;">
<div id="<?=$id?>_sp_user_nf_notice" class="bxec-sprpose-users-nf"><?=GetMessage('EC_SP_DIALOG_USERS_NOT_FOUND')?></div>
<?=GetMessage('EC_USER_SEARCH')?>:
<?
if ($arParams['bExtranet'])
	$ExtraMode = 'E';
elseif (CModule::IncludeModule('extranet'))
	$ExtraMode = 'I';
else
	$ExtraMode = '';

$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.user_search_input",
	".default",
	array(
		"NAME" => "spd_usearch",
		"TEXT" => "size='40'",
		"EXTRANET" => $ExtraMode,
		"FUNCTION" => "SPAddUser_".$id
	), false, array("HIDE_ICONS" => "Y"));
?>
</div>
<?
	}

	function BDS_ViewCompanyStructure($arParams)
	{
		$GLOBALS['APPLICATION']->IncludeComponent('bitrix:intranet.user.search', '', array(
			'SHOW_INPUT' => 'N',
			'SHOW_BUTTON'=>'N',
			'NAME' => 'oECUserContrEdEv',
			'ONSELECT' => 'oECUserContrEdEvOnSave',
			'GET_FULL_INFO' => 'Y',
			'MULTIPLE' => 'Y'
		));
	}

	function BDS_Planner($arParams)
	{
		global $APPLICATION;
		$id = $arParams['id'];
?>
<div id="bxec_plan_<?=$id?>" class="bxec-dialog bxec-edcal"><table class="bxec-edcal-frame">
	<tr><td class="bxec-title-cell" colSpan="2">
	<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_plan_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-edcal-title"><?=GetMessage('EC_PLANNER2')?></td><td id="<?=$id?>_plan_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td>
	<tr><td  colSpan="2" style="padding: 5px 10px;">
	<div id="<?=$id?>_plan_cont" class="bxec-plan-cont bxecpl-empty">
	<div id="<?=$id?>_plan_top_cont"  class="bxec-plan-top-cont">
	<form name="bxec_planner_form_<?=$id?>">
	<table style="width: 630px">
		<tr>
			<td title="<?=GetMessage('EC_EDEV_DATE_FROM')?>" style="padding-top:4px;"><label for="bxec_planner_from_<?=$id?>"><?=GetMessage('EC_FROM')?>:</label></td>
			<td title="<?=GetMessage('EC_EDEV_DATE_FROM')?>">
				<input name="bxec_planner_from" style="width: 80px;"/>
				<?$APPLICATION->IncludeComponent(
				"bitrix:main.calendar",
				"",
				Array(
					"FORM_NAME" => "bxec_planner_form_".$id,
					"INPUT_NAME" => "bxec_planner_from",
					"INPUT_VALUE" => "",
					"SHOW_TIME" => "N",
					"HIDE_TIMEBAR" => "Y"
				),
				false, array("HIDE_ICONS" => "Y"));?>
				<?CClock::Show(array('inputId' => 'bxec_pl_time_f_'.$id, 'inputTitle' => GetMessage('EC_EDEV_TIME_FROM')));?>
			</td>
			<td title="<?=GetMessage('EC_EVENT_DURATION_TITLE')?>" style="padding-top:4px;"><label for="<?=$id?>_pl_dur"><?=GetMessage('EC_EVENT_DURATION')?>:</label></td>
			<td>
				<input style="width: 60px; float: left;" id="<?=$id?>_pl_dur" type="text" title="<?=GetMessage('EC_EVENT_DURATION_TITLE')?>"/>
				<select id="<?=$id?>_pl_dur_type" style="width: 70px;  float: left;">
					<option value="min"><?=GetMessage('EC_EDEV_REM_MIN')?></option>
					<option value="hour" selected="true"><?=GetMessage('EC_EDEV_REM_HOUR')?></option>
					<option value="day"><?=GetMessage('EC_EDEV_REM_DAY')?></option>
				</select>
				<img src="/bitrix/images/1.gif" class="bxecpl-lock-dur" id="<?=$id?>_pl_dur_lock" title="<?=GetMessage('EC_EVENT_DUR_LOCK')?>"/>
			</td>
		</tr>
		<tr>
			<td title="<?=GetMessage('EC_EDEV_DATE_TO')?>"  style="padding-top:4px;"><label for="bxec_planner_to_<?=$id?>"><?=GetMessage('EC_TO')?>:</label></td>
			<td  title="<?=GetMessage('EC_EDEV_DATE_TO')?>">
				<input name="bxec_planner_to" style="width: 80px;"/>
				<?$APPLICATION->IncludeComponent(
				"bitrix:main.calendar",
				"",
				Array(
					"FORM_NAME" => "bxec_planner_form_".$id,
					"INPUT_NAME" => "bxec_planner_to",
					"INPUT_VALUE" => "",
					"SHOW_TIME" => "N",
					"HIDE_TIMEBAR" => "Y"
				),
				false, array("HIDE_ICONS" => "Y"));?>
				<?CClock::Show(array('inputId' => 'bxec_pl_time_t_'.$id, 'inputTitle' => GetMessage('EC_EDEV_TIME_TO')));?>
			</td>
			<td title="<?=GetMessage('EC_LOCATION_TITLE')?>"  style="padding-top:4px;"><label for="<?=$id?>_planner_location2"><?=GetMessage('EC_LOCATION')?>:</label></td>
			<td>
			<div class="bxecpl-loc-cont">
				<input style="width: 165px;" id="<?=$id?>_planner_location2" type="text"  title="<?=GetMessage('EC_LOCATION_TITLE')?>" value="<?= GetMessage('EC_PL_SEL_MEET_ROOM')?>" class="ec-label" />
			</div>
			</td>
		</tr>
	</table>
	</form>
	</div>
	<div id="<?=$id?>_plan_grid_cont" class="bxec-plan-grid-cont"><table class="bxec-plan-grid-tbl">
			<tr class="bxec-header">
				<td class="bxec-scale-cont"><label for="<?=$id?>_plan_scale_sel"><?=GetMessage('EC_SCALE')?>:</label>
					<select id="<?=$id?>_plan_scale_sel">
						<option value="0">30 <?= GetMessage('EC_EDEV_REM_MIN')?></option>
						<option value="1">1 <?= GetMessage('EC_PL_DUR_HOUR1')?></option>
						<option value="2">2 <?= GetMessage('EC_PL_DUR_HOUR2')?></option>
						<option value="3">1 <?= GetMessage('EC_JS_DAY_P')?></option>
					</select>
				</td>
				<td class="bxec-separator-gr" rowSpan="2"></td>
				<td rowSpan="2"><div class="bxec-grid-cont-title"></div></td>
			</tr>
			<tr class="bxec-header">
				<td class="bxec-user"><div><?=GetMessage('EC_EDEV_GUESTS')?>  <span class="bxec-pl-clear-all">(<a id="<?=$id?>_planner_del_all" href="javascript:void(0);" title="<?=GetMessage('EC_DEL_ALL_GUESTS_TITLE')?>" class="bxec-pl-link"><?=GetMessage('EC_PL_CLEAN_LIST')?></a>)<span>
				</div></td>
			</tr>
			<tr>
				<td><div class="bxec-user-list-div"><div class="bxec-empty-list"> - <?=GetMessage('EC_EMPTY_LIST')?> - </div></div></td>
				<td class="bxec-separator"></td>
				<td><div class="bxec-grid-cont"><div class="bxec-gacc-cont"></div>
					<div class="bxecp-selection" id="<?=$id?>_plan_selection"  title="<?=GetMessage('EC_PL_EVENT')?>"><img src="/bitrix/images/1.gif" class="bxecp-sel-left" title="<?=GetMessage('EC_PL_EVENT_MOVE_LEFT')?>" /><img src="/bitrix/images/1.gif" class="bxecp-sel-right" title="<?=GetMessage('EC_PL_EVENT_MOVE_RIGHT')?>" /><img src="/bitrix/images/1.gif" class="bxecp-sel-mover" title="<?=GetMessage('EC_PL_EVENT_MOVE')?>" /></div>
				</div>
				<div class="bxec-empty-list2"><?= GetMessage('EC_NO_GUEST_MESS')?></div>
				</td>
			</tr>
		</table>
	</div>
	<div id="<?=$id?>_plan_bottom_cont" class="bxec-plan-bottom-cont">
		<?=GetMessage('EC_ADD_GUEST')?>:
		<?
		if ($arParams['bExtranet'])
			$ExtraMode = 'E';
		elseif (CModule::IncludeModule('extranet'))
			$ExtraMode = 'I';
		else
			$ExtraMode = '';

		$APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.user_search_input",
			".default",
			array(
				"NAME" => "planner_usearch",
				"TEXT" => "size='25'",
				"EXTRANET" => $ExtraMode,
				"FUNCTION" => "PlannerAddGuest_".$id
			),false, array("HIDE_ICONS" => "Y"));?>
		<div class="bxec-planner-add-ex">
		<?if (!$arParams['bExtranet']):?>
		<?if($arParams['ownerType'] == 'GROUP'):?>
		<a id="<?=$id?>_planner_add_from_group" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_GROUP_MEMBER_TITLE')?>" class="bxex-add-ex-link"><img src="/bitrix/images/1.gif" /><?=GetMessage('EC_ADD_GROUP_MEMBER')?></a>
		<?endif;?>
		<a id="<?=$id?>_planner_add_from_struc" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_MEMBERS_FROM_STR_TITLE')?>" class="bxex-add-ex-link"><img src="/bitrix/images/1.gif" /><?=GetMessage('EC_ADD_MEMBERS_FROM_STR')?></a>
		<?endif;?>
		</div>
	</div>
	<div class="bxec-planner-auto-cont">
		<input id="<?=$id?>_plan_auto_back" type="button" value="<<" />
		<input id="<?=$id?>_plan_auto_but" type="button" value="<?= GetMessage('EC_AUTO_SEL')?>" />
	</div>

	<div class="bxec-planner-leg-cont">
		<div>
			<span><?= GetMessage('EC_PL_LEGEND')?></span>
			<a id="<?=$id?>_p" href="javascript:void(0);" title="<?=GetMessage('EC_PL_SHOW_LEGEND')?>" class="bxex-add-ex-link"><?=GetMessage('EC_PL_SHOW_LEGEND')?></a>
		</div>
		<div class="bxec-planner-legend">
		</div>
	</div>

	</div>
	</td></tr>
	<tr><td colSpan="2" class="bxec-plan-buttons">
		<input id="<?=$id?>_plan_save" type="button" value="<?=GetMessage('EC_T_SAVE')?>" />
		<input id="<?=$id?>_plan_next" type="button" value="<?=GetMessage('EC_NEXT')?>" />
		<input id="<?=$id?>_plan_apply" type="button" value="<?=GetMessage('EC_APPLY')?>" />
		<input id="<?=$id?>_plan_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>" />
	</td></tr>
</table>
<div id="<?=$id?>_plan_resizer" class="bxec-plan-resizer"></div>
</div>
<?
	}

	function BDS_UserSettings($arParams)
	{
		$id = $arParams['id'];
?>
<div id="bxec_uset_<?=$id?>" class="bxec-dialog bxec-edcal"><table class="bxec-edcal-frame">
	<tr><td class="bxec-title-cell" colSpan="2">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_uset_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-edcal-title"><?= GetMessage('EC_USER_SET')?></td><td id="<?=$id?>_uset_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td>
	<tr><td align="left" style="padding: 14px 5px 0 10px; white-space: nowrap;"><label for="<?=$id?>_uset_calend_sel"><?=GetMessage('EC_ADV_MEETING_CAL')?>:</label></td><td style="padding: 10px 5px;">
	<select id="<?=$id?>_uset_calend_sel" style="width: 200px;"></select>
	</td></tr>
	<tr><td align="left" style="padding: 0 10px 15px 10px;" colSpan="2"><input id="<?=$id?>_uset_blink" type="checkbox" /><label for="<?=$id?>_uset_blink"><?=GetMessage('EC_BLINK_SET')?></label>
	</td></tr>
	<tr><td colSpan="2" class="bxec-edcal-buttons">
	<div style="float: left; margin: 3px 5px 0px 10px; height: 20px;">
	<a id="<?=$id?>_uset_clear" href="javascript:void(0);" title="<?=GetMessage('EC_CLEAR_SET_TITLE')?>"><img class="bxec-iconkit bxec-delevent" src="/bitrix/images/1.gif" /><?=GetMessage('EC_CLEAR_SET')?></a>
	</div>
	<input id="<?=$id?>_uset_save" type="button" value="<?=GetMessage('EC_T_SAVE')?>"><input id="<?=$id?>_uset_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<?
	}

	function BDS_ExternalCalendars($arParams)
	{
		$id = $arParams['id'];
?>
<div id="bxec_cdav_<?=$id?>" class="bxec-dialog bxec-edcal"><table class="bxec-edcal-frame">
	<tr><td class="bxec-title-cell">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_cdav_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-edcal-title"><?= GetMessage('EC_CALDAV_TITLE')?></td><td id="<?=$id?>_cdav_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td></tr>
	<tr><td style="height: 304px;">
	<div class="bxec-dav-list" id="<?=$id?>_bxec_dav_list"></div>
	<div class="bxec-dav-notice"><?= GetMessage('EC_CALDAV_NOTICE')?><br><?= GetMessage('EC_CALDAV_NOTICE_GOOGLE')?></div>
	<div class="bxec-dav-new" id="<?=$id?>_bxec_dav_new">
		<table>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_name"><?= GetMessage('EC_ADD_CALDAV_NAME')?>:</label></td>
				<td class="bxec-dav-inp"><input type="text" id="<?=$id?>_bxec_dav_name" value="" style="width: 300px;" size="47"/></td>
			</tr>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_link"><?= GetMessage('EC_ADD_CALDAV_LINK')?>:</label></td>
				<td class="bxec-dav-inp"><input type="text" id="<?=$id?>_bxec_dav_link" value="" style="width: 300px;" size="47"/></td>
			</tr>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_username"><?= GetMessage('EC_ADD_CALDAV_USER_NAME')?>:</label></td>
				<td class="bxec-dav-inp"><input type="text" id="<?=$id?>_bxec_dav_username" value="" style="width: 200px;" size="30"/></td>
			</tr>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_password"><?= GetMessage('EC_ADD_CALDAV_PASS')?>:</label></td>
				<td class="bxec-dav-inp"><input type="password" id="<?=$id?>_bxec_dav_password" value="" style="width: 200px;" size="30"/></td>
			</tr>
		</table>
	</div>
	</td></tr>
	<tr><td class="bxec-edcal-buttons">
	<div style="float: left; margin: 3px 5px 0px 10px; height: 20px;"><a href="javascript: void(0);" id="<?=$id?>_add_new" class="bxec-dav-add-link"><?=GetMessage('EC_ADD_CALDAV')?></a></div>
	<input id="<?=$id?>_cdav_save" type="button" value="<?=GetMessage('EC_T_SAVE')?>"><input id="<?=$id?>_cdav_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<?
	}

	function BDS_MobileCon($arParams)
	{
		$id = $arParams['id'];
?>
<div id="bxec_mobile_<?=$id?>" class="bxec-dialog bxec-edcal"><table class="bxec-edcal-frame">
	<tr><td class="bxec-title-cell">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_mobile_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-edcal-title"><?= GetMessage('EC_MOBILE_HELP_TITLE')?></td><td id="<?=$id?>_mobile_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td></tr>
	<tr><td style="height: 280px;">
		<div class="bxec-mobile-cont">
			<div class="bxec-mobile-header"><?= GetMessage('EC_MOBILE_HELP_HEADER');?></div>
			<a id="bxec_mob_link_iphone_<?=$id?>" class="bxec-mobile-link bxec-link-hidden" href="javascript: void(0)"><div class="bxec-iconkit bxec-arrow"></div><?= GetMessage('EC_MOBILE_APPLE');?></a>
			<div id="bxec_mobile_iphone_all<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_IPHONE_ALL_HELP')?></div>
			<div id="bxec_mobile_iphone_one<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_IPHONE_ONE_HELP')?></div>
			<a id="bxec_mob_link_bird_<?=$id?>" class="bxec-mobile-link bxec-link-hidden" href="javascript: void(0)"><div class="bxec-iconkit bxec-arrow"></div><?= GetMessage('EC_MOBILE_SUNBIRD');?></a>
			<div id="bxec_mobile_sunbird_all<?=$id?>" style="display: none;"><?= GetMessage("EC_MOBILE_HELP_SUNBIRD_ALL_HELP")?></div>
			<div id="bxec_mobile_sunbird_one<?=$id?>" style="display: none;"><?= GetMessage("EC_MOBILE_HELP_SUNBIRD_ONE_HELP")?></div>
		</div>
	</td></tr>
	<tr><td class="bxec-edcal-buttons">
	<input id="<?=$id?>_mobile_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<?
	}

	function BDS_SortCalendar($arParams)
	{
		return;
		$id = $arParams['id'];
?>
<div id="bxec_csort_<?=$id?>" class="bxec-dialog bxec-edcal"><table class="bxec-edcal-frame">
	<tr><td class="bxec-title-cell" colSpan="2">
	<table cellPadding="0" cellSpacing="0" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxec_csort_<?=$id?>'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="bxec-iconkit bxec-dd-dot" src="/bitrix/images/1.gif"></td><td class="bxec-edcal-title"><?= GetMessage('EC_USER_SET')?></td><td id="<?=$id?>_csort_close" class="bxec-close" title="<?=GetMessage('EC_T_CLOSE')?>"><img class="bxec-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
	</td>
	<tr><td>
		<div class="bxec-csort-cnt">
			<table id="bxec_cs_tbl_<?=$id?>">
				<tr class="bxec-title-row">
					<td class="bxec-cs-gap"></td>
					<td class="bxec-cs-name"><div>Name</div></td>
					<td class="bxec-cs-date"><div>Date</div></td>
					<td class="bxec-cs-sort"><div>Sort Id</div></td>
					<td class="bxec-cs-gap2"></td>
				</tr>
			</table>
		</div>
	</td></tr>
	<tr><td colSpan="2" class="bxec-edcal-buttons">
	<input id="<?=$id?>_csort_save" type="button" value="<?=GetMessage('EC_T_SAVE')?>"><input id="<?=$id?>_csort_cancel" type="button" value="<?=GetMessage('EC_T_CLOSE')?>">
	</td></tr>
</table>
</div>
<?
	}

	/* * * * RESERVE MEETING ROOMS  * * * */
	function GetMeetingRoomList()
	{
		$MRList = Array();
		if (IntVal($this->RMiblockId) > 0 && CIBlock::GetPermission($this->RMiblockId) >= "R")
		{
			$arOrderBy = array("NAME" => "ASC", "ID" => "DESC");
			$arFilter = array("IBLOCK_ID" => $this->RMiblockId, "ACTIVE" => "Y");
			$arSelectFields = array("IBLOCK_ID","ID","NAME","DESCRIPTION","UF_FLOOR","UF_PLACE","UF_PHONE");
			$res = CIBlockSection::GetList($arOrderBy, $arFilter, false, $arSelectFields );
			while ($arMeeting = $res->GetNext())
			{
				$MRList[] = array(
					'ID' => $arMeeting['ID'],
					'NAME' => $arMeeting['NAME'],
					'DESCRIPTION' => $arMeeting['DESCRIPTION'],
					'UF_PLACE' => $arMeeting['UF_PLACE'],
					'UF_PHONE' => $arMeeting['UF_PHONE'],
					'URL' => str_replace("#id#", $arMeeting['ID'], $this->RMPath)
				);
			}
		}

		if(IntVal($this->VMiblockId) > 0 && CIBlock::GetPermission($this->VMiblockId) >= "R")
		{
			$arFilter = array("IBLOCK_ID" => $this->VMiblockId, "ACTIVE" => "Y");
			$arSelectFields = array("ID", "NAME", "DESCRIPTION", "IBLOCK_ID");
			$res = CIBlockSection::GetList(Array(), $arFilter, false, $arSelectFields);
			if($arMeeting = $res->GetNext())
			{
				$MRList[] = array(
					'ID' => $this->VMiblockId,
					'NAME' => $arMeeting["NAME"],
					'DESCRIPTION' => $arMeeting['DESCRIPTION'],
					'URL' => str_replace("#id#", $arMeeting['ID'], $this->VMPath),
				);
			}
		}

		return $MRList;
	}

	function GetMRAccessability($Params)
	{
		global $USER;
		if ($this->RMiblockId > 0 && $this->allowResMeeting)
		{
			$curEventId = $Params['curEventId'] > 0 ? $Params['curEventId'] : false;
			$arSelect = array("ID", "NAME", "IBLOCK_ID", "ACTIVE_FROM", "ACTIVE_TO", "PROPERTY_*");
			$arFilter = array(
				"IBLOCK_ID" => $this->RMiblockId,
				"IBLOCK_SECTION_ID" => $Params['id'],
				"SECTION_ID" => array($Params['id']),
				"INCLUDE_SUBSECTIONS" => "Y",
				"ACTIVE" => "Y",
				"CHECK_PERMISSIONS" => 'N',
				">=DATE_ACTIVE_TO" => $Params['from'],
				"<=DATE_ACTIVE_FROM" => $Params['to']
			);
			if(IntVal($curEventId) > 0)
				$arFilter["!ID"] = IntVal($curEventId);

			$arSort = Array('ACTIVE_FROM' => 'ASC');

			$rsElement = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
			$arResult = array();
			while($obElement = $rsElement->GetNextElement())
			{
				$arItem = $obElement->GetFields();
				//if ($curEventId == $arItem['ID'])
					//continue;

				$props = $obElement->GetProperties(); // Get properties
				$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
				$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));

				$per_type = (isset($props['PERIOD_TYPE']['VALUE']) && $props['PERIOD_TYPE']['VALUE'] != 'NONE') ? strtoupper($props['PERIOD_TYPE']['VALUE']) : false;
				if ($per_type)
				{
					$count = (isset($props['PERIOD_COUNT']['VALUE'])) ? intval($props['PERIOD_COUNT']['VALUE']) : '';
					$length = (isset($props['EVENT_LENGTH']['VALUE'])) ? intval($props['EVENT_LENGTH']['VALUE']) : '';
					$additional = (isset($props['PERIOD_ADDITIONAL']['VALUE'])) ? $props['PERIOD_ADDITIONAL']['VALUE'] : '';

					$this->DisplayPeriodicEvent($arResult, array(
						'arItem' => $arItem,
						'perType' => $per_type,
						'count' => $count,
						'length' => $length,
						'additional' => $additional,
						'fromLimit' => $Params['from'],
						'toLimit' => $Params['to']
					));
				}
				else
				{
					$this->HandleElement($arResult, $arItem);
				}
			}
		}

		if ($this->VMiblockId > 0 && $this->allowVideoMeeting)
		{
			$curEventId = $Params['curEventId'] > 0 ? $Params['curEventId'] : false;
			$arSelect = array("ID", "NAME", "IBLOCK_ID", "ACTIVE_FROM", "ACTIVE_TO", "PROPERTY_*");
			$arFilter = array(
				"IBLOCK_ID" => $this->VMiblockId,
				//"IBLOCK_SECTION_ID" => $Params['id'],
				//"SECTION_ID" => array($Params['id']),
				//"INCLUDE_SUBSECTIONS" => "Y",
				"ACTIVE" => "Y",
				"CHECK_PERMISSIONS" => 'N',
				">=DATE_ACTIVE_TO" => $Params['from'],
				"<=DATE_ACTIVE_FROM" => $Params['to']
			);
			if(IntVal($curEventId) > 0)
				$arFilter["!ID"] = IntVal($curEventId);

			$arSort = Array('ACTIVE_FROM' => 'ASC');

			$rsElement = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
			$arResult = array();
			$arRes = Array();
			while($obElement = $rsElement->GetNextElement())
			{
				$arItem = $obElement->GetFields();
				//if ($curEventId == $arItem['ID'])
					//continue;

				$props = $obElement->GetProperties(); // Get properties
				$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
				$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(getDateFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));

				$per_type = (isset($props['PERIOD_TYPE']['VALUE']) && $props['PERIOD_TYPE']['VALUE'] != 'NONE') ? strtoupper($props['PERIOD_TYPE']['VALUE']) : false;
				if ($per_type)
				{
					$count = (isset($props['PERIOD_COUNT']['VALUE'])) ? intval($props['PERIOD_COUNT']['VALUE']) : '';
					$length = (isset($props['EVENT_LENGTH']['VALUE'])) ? intval($props['EVENT_LENGTH']['VALUE']) : '';
					$additional = (isset($props['PERIOD_ADDITIONAL']['VALUE'])) ? $props['PERIOD_ADDITIONAL']['VALUE'] : '';

					$this->DisplayPeriodicEvent($arResult, array(
						'arItem' => $arItem,
						'perType' => $per_type,
						'count' => $count,
						'length' => $length,
						'additional' => $additional,
						'fromLimit' => $Params['from'],
						'toLimit' => $Params['to']
					));
				}
				else
				{
					$vParams = Array(
						"allowVideoMeeting" => true,
						"dateFrom" => $arItem["ACTIVE_FROM"],
						"dateTo" => $arItem["ACTIVE_TO"],
						"VMiblockId" => $arItem["IBLOCK_ID"],
						//"ID" => $arItem["ID"],
						"regularity" => "NONE",
					);
					$check = false;
					$check = CEventCalendar::CheckVR($vParams);

					if ($check !== true && $check == "reserved")
					{
						//todo make only factical reserved, not any time
						$this->HandleElement($arResult, $arItem);
					}
				}
			}
		}

		CEventCalendar::DisplayJSMRAccessability($Params['id'], $arResult);
	}

	function GetMeetingRoomById($Params)
	{
		if (IntVal($Params['RMiblockId']) > 0 && CIBlock::GetPermission($Params['RMiblockId']) >= "R")
		{
			$arFilter = array("IBLOCK_ID" => $Params['RMiblockId'], "ACTIVE" => "Y", "ID" => $Params['id']);
			$arSelectFields = array("NAME");
			$res = CIBlockSection::GetList(array(), $arFilter, false, array("NAME"));
			if ($arMeeting = $res->GetNext())
				return $arMeeting;
		}

		if(IntVal($Params['VMiblockId']) > 0 && CIBlock::GetPermission($Params['VMiblockId']) >= "R")
		{
			$arFilter = array("IBLOCK_ID" => $Params['VMiblockId'], "ACTIVE" => "Y");
			$arSelectFields = array("ID", "NAME", "DESCRIPTION", "IBLOCK_ID");
			$res = CIBlockSection::GetList(Array(), $arFilter, false, $arSelectFields);
			if($arMeeting = $res->GetNext())
			{
				return array(
					'ID' => $Params['VMiblockId'],
					'NAME' => $arMeeting["NAME"],
					'DESCRIPTION' => $arMeeting['DESCRIPTION'],
				);
			}
		}

		return false;
	}

	function DisplayJSMRAccessability($mrid, $arEvents)
	{
		?><script>
window._bx_plann_mr['<?= $mrid?>'] = [
<?
			for ($i = 0, $l = count($arEvents); $i < $l; $i++):
?>
{id: <?= $arEvents[$i]['ID']?>, from: <?= MakeTimeStamp($arEvents[$i]['DATE_FROM'], getTSFormat()) * 1000?>, to: <?= MakeTimeStamp($arEvents[$i]['DATE_TO'], getTSFormat()) * 1000?>, name: '<?= $arEvents[$i]['NAME']?>'}<?= ($i < $l - 1 ? ",\n" : "\n")?>
<?
			endfor;
?>
];

</script><?
	}

	function ReserveMR($Params)
	{
		$tst = MakeTimeStamp($Params['dateTo']);
		if (date("H:i", $tst) == '00:00')
			$Params['dateTo'] = CIBlockFormatProperties::DateFormat(getDateFormat(true), $tst + (23 * 60 + 59) * 60);

		$check = CEventCalendar::CheckMR($Params);
		if ($check !== true)
			return $check;

		$arFields = array(
			"IBLOCK_ID" => $Params['RMiblockId'],
			"IBLOCK_SECTION_ID" => $Params['mrid'],
			"NAME" => $Params['name'],
			"DATE_ACTIVE_FROM" => $Params['dateFrom'],
			"DATE_ACTIVE_TO" => $Params['dateTo'],
			"CREATED_BY" => $GLOBALS["USER"]->GetID(),
			"DETAIL_TEXT" => $Params['description'],
			"PROPERTY_VALUES" => array(
				"UF_PERSONS" => $Params['persons'],
				"PERIOD_TYPE" => $Params['regularity'],
				"PERIOD_COUNT" => $Params['regularity_count'],
				"EVENT_LENGTH" => $Params['regularity_length'],
				"PERIOD_ADDITIONAL" => $Params['regularity_additional']
			),
			"ACTIVE" => "Y"
		);

		$bs = new CIBlockElement;
		$id = $bs->Add($arFields);

		return $id;
	}

	function ReleaseMR($Params)
	{
		global $USER;
		if (!$Params['allowResMeeting'])
			return false;

		$arFilter = array(
			"ID" => $Params['mrevid'],
			"IBLOCK_ID" => $Params['RMiblockId'],
			"IBLOCK_SECTION_ID" => $Params['mrid'],
			"SECTION_ID" => array($Params['mrid'])
		);

		$res = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID"));
		if($arElement = $res->Fetch())
		{
			$obElement = new CIBlockElement;
			$obElement->Delete($Params['mrevid']);
		}
	}

	function CheckMR($Params)
	{
		global $USER, $DB;

		if (!$Params['allowResMeeting'])
			return false;

		if ($Params['regularity'] == "NONE")
		{
			$fromDateTime = MakeTimeStamp($Params['dateFrom']);
			$toDateTime = MakeTimeStamp($Params['dateTo']);

			$arFilter = array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $Params['RMiblockId'],
				"SECTION_ID" => $Params['mrid'],
				"<DATE_ACTIVE_FROM" => date(getDateFormat(), $toDateTime),
				">DATE_ACTIVE_TO" => date(getDateFormat(), $fromDateTime),
				"PROPERTY_PERIOD_TYPE" => "NONE",
			);

			if ($Params['mrevid_old'] > 0)
				$arFilter["!=ID"] = $Params['mrevid_old'];

			$dbElements = CIBlockElement::GetList(array("DATE_ACTIVE_FROM" => "ASC"), $arFilter, false, false, array('ID'));
			if ($arElements = $dbElements->GetNext())
				return 'reserved';

			include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.reserve_meeting/init.php");
			$arPeriodicElements = __IRM_SearchPeriodic($fromDateTime, $toDateTime, $Params['RMiblockId'], $Params['mrid']);

			for ($i = 0, $l = count($arPeriodicElements); $i < $l; $i++)
				if (!$Params['mrevid_old'] || $arPeriodicElements[$i]['ID'] != $Params['mrevid_old'])
					return 'reserved';
		}
		return true;
	}

	function ReserveVR($Params)
	{
		$tst = MakeTimeStamp($Params['dateTo']);
		if (date("H:i", $tst) == '00:00')
			$Params['dateTo'] = CIBlockFormatProperties::DateFormat(getDateFormat(true), $tst + (23 * 60 + 59) * 60);

		//$maxUsers = COption::GetOptionInt("video", "video-room-users", 6);
		//if(count($Params['members']) > $maxUsers)
		//	return "max_users_".$maxUsers;

		$check = CEventCalendar::CheckVR($Params);
		if ($check !== true)
			return $check;

		$sectionID = 0;
		$dbItem = CIBlockSection::GetList(Array(), Array("IBLOCK_ID" => $Params['VMiblockId'], "ACTIVE" => "Y"));
		if($arItem = $dbItem->Fetch())
			$sectionID = $arItem["ID"];

		$arFields = array(
			"IBLOCK_ID" => $Params['VMiblockId'],
			"IBLOCK_SECTION_ID" => $sectionID,
			"NAME" => $Params['name'],
			"DATE_ACTIVE_FROM" => $Params['dateFrom'],
			"DATE_ACTIVE_TO" => $Params['dateTo'],
			"CREATED_BY" => $GLOBALS["USER"]->GetID(),
			"DETAIL_TEXT" => $Params['description'],
			"PROPERTY_VALUES" => array(
				"UF_PERSONS" => $Params['persons'],
				"PERIOD_TYPE" => $Params['regularity'],
				"PERIOD_COUNT" => $Params['regularity_count'],
				"EVENT_LENGTH" => $Params['regularity_length'],
				"PERIOD_ADDITIONAL" => $Params['regularity_additional'],
				"MEMBERS" => $Params['members'],
			),
			"ACTIVE" => "Y"
		);

		$bs = new CIBlockElement;
		$id = $bs->Add($arFields);

		return $id;
	}

	function ReleaseVR($Params)
	{
		global $USER;
		if (!$Params['allowVideoMeeting'])
			return false;

		$arFilter = array(
			"ID" => $Params['mrevid'],
			"IBLOCK_ID" => $Params['VMiblockId'],
			//"IBLOCK_SECTION_ID" => $Params['mrid'],
			//"SECTION_ID" => array($Params['mrid'])
		);

		$res = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID"));
		if($arElement = $res->Fetch())
		{
			$obElement = new CIBlockElement;
			$obElement->Delete($Params['mrevid']);
		}
	}

	function CheckVR($Params)
	{
		if (!$Params['allowVideoMeeting'])
			return false;
		if(CModule::IncludeModule("video"))
		{
			$vParams = Array(
				"regularity" => $Params["regularity"],
				"dateFrom" => $Params["dateFrom"],
				"dateTo" => $Params["dateTo"],
				"iblockId" => $Params["VMiblockId"],
				"ID" => $Params["ID"],
			);

			return CVideo::CheckRooms($vParams);
		}
		return false;
	}

	function ParseLocation($str = '')
	{
		$res = array('mrid' => false, 'mrevid' => false, 'str' => $str);
		if (strlen($str) > 5 && substr($str, 0, 5) == 'ECMR_')
		{
			$ar_ = explode('_', $str);
			if (count($ar_) >= 2)
			{
				if (intVal($ar_[1]) > 0)
					$res['mrid'] = intVal($ar_[1]);
				if (intVal($ar_[2]) > 0)
					$res['mrevid'] = intVal($ar_[2]);
			}
		}

		return $res;
	}

	function GenEventDynClose($evId)
	{
		/* try to close annoying message window*/
		?>
		<script>
			var _BXECIter = 0;
			window._BXEC_EvDynCloseInt = setInterval(function()
			{
				try{
					if (_BXECIter > 20)
						clearInterval(window._BXEC_EvDynCloseInt);

					var
						i,l,arLinks,href,
						div = document.getElementById('sonet_events_ms_message'),
						but = document.getElementById('sonet_events_ms_close');

					if (div && but)
					{
						arLinks = div.getElementsByTagName('A');
						for(i = 0, l = arLinks.length; i < l; i++)
						{
							href = arLinks[i].getAttribute('href').toUpperCase();
							if (href.indexOf('EVENT_ID=<?= $evId?>&CONFIRM=') > 0 || href.indexOf('EVENT_ID=<?= $evId?>&CLOSE_MESS=Y') > 0)
							{
								clearInterval(window._BXEC_EvDynCloseInt);
								window._BXEC_EvDynCloseInt_onclick = true;
								if (but.fireEvent && document.createEventObject)
								{
									but.fireEvent('onclick', document.createEventObject());
								}
								else
								{
									var event = document.createEvent('MouseEvents');
									event.initMouseEvent('click', true, true, window, 1, 100, 100, 100, 100, false, false, false, false, 0, null);
									but.dispatchEvent(event);
								}
								setTimeout(function(){window._BXEC_EvDynCloseInt_onclick = false;}, 200);
								break;
							}
						}
					}
					_BXECIter++;
				}
				catch(e){clearInterval(window._BXEC_EvDynCloseInt);}
			}, 500);
		</script>
		<?
	}

	function ThrowError($str)
	{
		global $APPLICATION;
		echo '<!-- BX_EVENT_CALENDAR_ACTION_ERROR:'.$str.'-->';
		return $APPLICATION->ThrowException($str);
	}

	function GetFullUserName($arUser)
	{
		$fullName = trim($arUser['NAME'].' '.$arUser['LAST_NAME']);

		if ($fullName == '')
			$fullName = trim($arUser['LOGIN']);

		return $fullName;
	}

	// * * * * * * * * * * * * CalDAV * * * * * * * * * * * * * * * * * * * * * * * *
	public static function IsCalDAVEnabled()
	{
		return IsModuleInstalled('dav') && CModule::IncludeModule('dav') && CDavGroupdavClientCalendar::IsCalDAVEnabled();
	}

	public static function IsExchangeEnabled()
	{
		return IsModuleInstalled('dav') && CModule::IncludeModule('dav') && CDavExchangeCalendar::IsExchangeEnabled();
	}

	public static function GetUserCalendarIBlockId($siteId)
	{
		return COption::GetOptionString('intranet', "iblock_calendar", "0", $siteId);
	}

	public static function GetGroupCalendarIBlockId($siteId)
	{
		return COption::GetOptionString('intranet', "iblock_group_calendar", "0", $siteId);
	}

	public static function GetCalendarModificationLabel($calendarId)
	{
		list($iblockId, $sectionId, $subSectionId) = $calendarId;

		$arFilter = array(
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "N",
			"INCLUDE_SUBSECTIONS" => "Y",
		);
		if ($sectionId > 0)
			$arFilter["SECTION_ID"] = ($subSectionId > 0 ? $subSectionId : $sectionId);

		$dbResult = CIBlockElement::GetList(array('TIMESTAMP_X' => 'DESC'), $arFilter, false, false, array("ID", "IBLOCK_ID", "TIMESTAMP_X"));
		if ($arResult = $dbResult->Fetch())
			return $arResult["TIMESTAMP_X"];

		return "";
	}

	public static function DeleteCalendarEvent($calendarId, $eventId, $userId)
	{
		list($iblockId, $sectionId, $subSectionId, $ownerType, $ownerId) = $calendarId;

		return CECEvent::Delete(array(
			'id' => $eventId,
			'iblockId' => $iblockId,
			'ownerType' => strtoupper($ownerType),
			'ownerId' => $ownerId,
			'userId' => $userId,
			'bCheckPermissions' => false,
			'bSyncDAV' => false
		));
	}

	public static function GetCalendarEventsList($calendarId, $arFilter = array())
	{
		list($iblockId, $sectionId, $subSectionId) = $calendarId;

		$arSelect = array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "NAME", "ACTIVE_FROM", "ACTIVE_TO", "DETAIL_TEXT", "DETAIL_TEXT_TYPE", "TIMESTAMP_X", "DATE_CREATE", "CREATED_BY", "XML_ID", "PROPERTY_*");

		$arFilter1 = array(
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "N",
			"INCLUDE_SUBSECTIONS" => "Y",
		);
		if ($sectionId > 0)
			$arFilter1["SECTION_ID"] = ($subSectionId > 0 ? $subSectionId : $sectionId);

		$arFilter = array_merge($arFilter1, $arFilter);

		if (isset($arFilter['DATE_START']))
		{
			$arFilter['!ACTIVE_TO'] = $arFilter['DATE_START'];
			unset($arFilter['DATE_START']);
		}
		if (isset($arFilter['DATE_END']))
		{
			$arFilter['!ACTIVE_FROM'] = $arFilter['DATE_END'];
			unset($arFilter['DATE_END']);
		}

		$arResult = array();

		$dbEvents = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
		while ($obEvent = $dbEvents->GetNextElement())
		{
			$arEvent = $obEvent->GetFields();

			$arEventProperties = $obEvent->GetProperties();
			foreach ($arEventProperties as $key => $value)
				$arEvent["PROPERTY_".$key] = $value["VALUE"];

			$arResult[] = $arEvent;
		}

		return $arResult;
	}

	private static $instance;
	private static function GetInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	public static function GetAccountRootSectionId($ownerId, $ownerType, $iblockId)
	{
		$cal = self::GetInstance();
		return $cal->GetSectionIDByOwnerId($ownerId, strtoupper($ownerType), $iblockId);
	}

	// return array('bAccess' => true/false, 'bReadOnly' => true/false, 'privateStatus' => 'time'/'title');
	public static function GetUserPermissionsForCalendar($calendarId, $userId)
	{
		list($iblockId, $sectionId, $subSectionId, $ownerType, $owberId) = $calendarId;

		$ownerType = strtoupper($ownerType);

		$arParams = array(
			'iblockId' => $iblockId,
			'userId' => $userId,
			'bOwner' => false,
			'ownerId' => null,
			'ownerType' => null,
			'bCheckSocNet' => false,
			'setProperties' => false,
		);
		if (!empty($ownerType))
		{
			$arParams['bCheckSocNet'] = true;
			$arParams['ownerType'] = $ownerType;
			$arParams['bOwner'] = true;

			$arFilter = Array(
				"ID" => $sectionId,
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y",
			);

			$dbSection = CIBlockSection::GetList(array('ID' => 'ASC'), $arFilter);
			if ($arSection = $dbSection->Fetch())
			{
				if ($ownerType == 'USER')
					$arParams['ownerId'] = $arSection['CREATED_BY'];
				elseif ($ownerType == 'GROUP')
					$arParams['ownerId'] = $arSection['SOCNET_GROUP_ID'];
			}
		}

		CModule::IncludeModule("socialnetwork");

		$cal = self::GetInstance();
		$cal->Init(array(
				'iblockId' => $iblockId,
				'ownerId' => $arParams['ownerId'],
				'ownerType' => $ownerType,
		));

		$arPermissions = $cal->GetPermissions($arParams);

		if ($ownerType == 'USER' && $arParams['ownerId'] != $userId)
		{
			$privateStatus = CECCalendar::GetPrivateStatus($iblockId, ($subSectionId > 0 ? $subSectionId : $sectionId), "USER");
			if ($privateStatus == 'private')
				$arPermissions = array('bAccess' => false, 'bReadOnly' => true);

			$arPermissions["privateStatus"] = $privateStatus;
		}

		return $arPermissions;
	}

	public static function ModifyEvent($calendarId, $arFields)
	{
		list($iblockId, $sectionId, $subSectionId, $ownerType, $ownerId) = $calendarId;
		$ownerType = strtoupper($ownerType);

		$cal = self::GetInstance();

		$arFields["ACTIVE"] = "Y";
		$arFields["IBLOCK_SECTION"] = ($subSectionId > 0 ? $subSectionId : $sectionId);
		$arFields["IBLOCK_ID"] = $iblockId;

		if ($arFields["NAME"] == '')
			$arFields["NAME"] = GetMessage('EC_NONAME_EVENT');

		$eventId = ((isset($arFields["ID"]) && (intval($arFields["ID"]) > 0)) ? intval($arFields["ID"]) : 0);
		unset($arFields["ID"]);

		if($ownerType == 'USER' && $ownerId > 0)
			$arFields['CREATED_BY'] = $ownerId;

		$arFieldsNew = array();
		$arPropertiesNew = array();
		$len = strlen("PROPERTY_");
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, $len) == "PROPERTY_")
				$arPropertiesNew[substr($key, $len)] = $value;
			else
				$arFieldsNew[$key] = $value;
		}

		$accessibility = CECEvent::GetAccessibility($iblockId, $eventId);
		if ($accessibility == 'absent' && $accessibility == 'quest' && $arPropertiesNew['ACCESSIBILITY'] != $accessibility)
			$arPropertiesNew['ACCESSIBILITY'] = $accessibility;

		if (count($arPropertiesNew) > 0)
			$arFieldsNew["PROPERTY_VALUES"] = $arPropertiesNew;

		$bs = new CIBlockElement;
		$res = false;

		if ($eventId > 0)
		{
			$res = $bs->Update($eventId, $arFieldsNew, false);
		}
		else
		{
			$eventId = $bs->Add($arFieldsNew, false);
			$eventId = intval($eventId);
			$res = ($eventId > 0);
		}

		CEventCalendar::ClearCache('event_calendar/events/'.$iblockId.'/');
		return $res ? $eventId : $bs->LAST_ERROR;
	}

	public static function GetCalendarList($calendarId)
	{
		list($iblockId, $sectionId, $subSectionId, $ownerType) = $calendarId;

		$arFilter = array('IBLOCK_ID' => $iblockId, "ACTIVE" => "Y", "CHECK_PERMISSIONS" => 'Y');
		if (empty($ownerType))
		{
			if ($sectionId > 0)
				$arFilter['ID'] = $sectionId;
		}
		else
		{
			$arFilter["SECTION_ID"] = $sectionId;
			if ($subSectionId > 0)
				$arFilter['ID'] = $subSectionId;
		}

		$arCalendars = array();

		$dbSections = CIBlockSection::GetList(array('ID' => 'ASC'), $arFilter);
		while ($arSection = $dbSections->Fetch())
		{
			$privateStatus = CECCalendar::GetPrivateStatus($iblockId, $arSection['ID'], strtoupper($ownerType));

			$arCalendars[] = array(
				"ID" => intVal($arSection['ID']),
				"IBLOCK_ID" => $iblockId,
				"IBLOCK_SECTION_ID" => intVal($arSection['IBLOCK_SECTION_ID']),
				"NAME" => htmlspecialcharsex($arSection['NAME']),
				"DESCRIPTION" => htmlspecialcharsex($arSection['DESCRIPTION']),
				"COLOR" => CECCalendar::GetColor($iblockId, $arSection['ID'], strtoupper($ownerType)),
				"PRIVATE_STATUS" => $privateStatus,
				"DATE_CREATE" => date("d.m.Y H:i", MakeTimeStamp($arSection['DATE_CREATE'], getTSFormat()))
			);
		}

		return $arCalendars;
	}

	public static function SyncCalendars($connectionType, $arCalendars, $entityType, $entityId, $siteId, $connectionId = null)
	{
		//Array(
		//	[0] => Array(
		//		[XML_ID] => calendar
		//		[NAME] => calendar
		//	)
		//	[1] => Array(
		//		[XML_ID] => AQATAGFud...
		//		[NAME] => geewgvwe 1
		//		[DESCRIPTION] => gewgvewgvw
		//		[COLOR] => #FF0000
		//		[MODIFICATION_LABEL] => af720e7c7b6a
		//	)
		//)
		$entityType = strtoupper($entityType);

		if ($entityType == "USER")
			$iblockId = self::GetUserCalendarIBlockId($siteId);
		else
			$iblockId = self::GetGroupCalendarIBlockId($siteId);

		$accountRootSectionId = CEventCalendar::GetAccountRootSectionId($entityId, $entityType, $iblockId);
		if (intval($accountRootSectionId) <= 0)
		{
			$cal = self::GetInstance();
			$accountRootSectionId = $cal->CreateSectionForOwner($entityId, $entityType, $iblockId);
		}
		$sectionObject = new CIBlockSection();

		$arCalendarNames = array();
		foreach ($arCalendars as $value)
			$arCalendarNames[$value["XML_ID"]] = $value;

		if ($connectionType == 'exchange')
			$xmlIdField = "UF_BXDAVEX_EXCH";
		elseif ($connectionType == 'caldav')
			$xmlIdField = "UF_BXDAVEX_CDAV";
		else
			return array();

		$arResult = array();

		$arCalFilter = array(
			'IBLOCK_ID' => $iblockId,
			"SECTION_ID" => $accountRootSectionId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => 'N',
			"!".$xmlIdField => false
		);

		if ($connectionType == 'caldav')
			$arCalFilter["UF_BXDAVEX_CDAV_COL"] = $connectionId;

		$dbSections = CIBlockSection::GetList(
			array('ID' => 'ASC'),
			$arCalFilter,
			false,
			array("ID", "NAME", "DESCRIPTION", "UF_USER_CAL_COL", $xmlIdField, $xmlIdField."_LBL")
		);
		while ($arSection = $dbSections->Fetch())
		{
			$xmlId = $arSection[$xmlIdField];
			$modificationLabel = $arSection[$xmlIdField."_LBL"];

			if (empty($xmlId))
				continue;

			if (!array_key_exists($xmlId, $arCalendarNames))
			{
				CIBlockSection::Delete($arSection["ID"], false);
			}
			else
			{
				if ($modificationLabel != $arCalendarNames[$xmlId]["MODIFICATION_LABEL"])
				{
					$sectionObject->Update(
						$arSection["ID"],
						array(
							"NAME" => $arCalendarNames[$xmlId]["NAME"],
							"DESCRIPTION" => $arCalendarNames[$xmlId]["DESCRIPTION"],
							"UF_USER_CAL_COL" => $arCalendarNames[$xmlId]["COLOR"],
							"CHECK_PERMISSIONS" => "N",
							$xmlIdField."_LBL" => $arCalendarNames[$xmlId]["MODIFICATION_LABEL"],
						)
					);
				}

				if (empty($modificationLabel) || ($modificationLabel != $arCalendarNames[$xmlId]["MODIFICATION_LABEL"]))
				{
					$arResult[] = array(
						"XML_ID" => $xmlId,
						"CALENDAR_ID" => array($iblockId, $accountRootSectionId, $arSection["ID"], strtolower($entityType), $entityId)
					);
				}

				unset($arCalendarNames[$xmlId]);
			}
		}

		foreach ($arCalendarNames as $key => $value)
		{
			$arFields = array(
				"IBLOCK_ID" => $iblockId,
				"IBLOCK_SECTION_ID" => $accountRootSectionId,
				"NAME" => $value["NAME"],
				"DESCRIPTION" => $value["DESCRIPTION"],
				"UF_USER_CAL_COL" => $value["COLOR"],
				$xmlIdField => $key,
				"CHECK_PERMISSIONS" => "N",
				$xmlIdField."_LBL" => $value["MODIFICATION_LABEL"],
			);

			if ($connectionType == 'caldav')
				$arFields["UF_BXDAVEX_CDAV_COL"] = $connectionId;

			if ($entityType == 'USER')
				$arFields["CREATED_BY"] = $entityId;
			elseif ($entityType == 'GROUP')
				$arFields['SOCNET_GROUP_ID'] = $entityId;

			$id = $sectionObject->Add($arFields);

			$arResult[] = array(
				"XML_ID" => $key,
				"CALENDAR_ID" => array($iblockId, $accountRootSectionId, $id, strtolower($entityType), $entityId)
			);
		}

		return $arResult;
	}

	public static function SyncCalendarItems($connectionType, $calendarId, $arCalendarItems)
	{
		// $arCalendarItems:
		//Array(
		//	[0] => Array(
		//		[XML_ID] => AAATAGFudGlfYn...
		//		[MODIFICATION_LABEL] => DwAAABYAAA...
		//	)
		//	[1] => Array(
		//		[XML_ID] => AAATAGFudGlfYnVn...
		//		[MODIFICATION_LABEL] => DwAAABYAAAAQ...
		//	)
		//)

		list($iblockId, $sectionId, $subSectionId, $entityType, $entityId) = $calendarId;
		$entityType = strtoupper($entityType);

		if ($connectionType == 'exchange')
			$xmlIdField = "BXDAVEX_LABEL";
		elseif ($connectionType == 'caldav')
			$xmlIdField = "BXDAVCD_LABEL";
		else
			return array();

		$arCalendarItemsMap = array();
		foreach ($arCalendarItems as $value)
			$arCalendarItemsMap[$value["XML_ID"]] = $value["MODIFICATION_LABEL"];

		$arModified = array();

		$arSelect = array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "XML_ID", "PROPERTY_".$xmlIdField);
		$arFilter = array(
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "N",
			"INCLUDE_SUBSECTIONS" => "Y",
			"SECTION_ID" => ($subSectionId > 0 ? $subSectionId : $sectionId)
		);
		$dbEvents = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
		while ($arEvent = $dbEvents->Fetch())
		{
			if (array_key_exists($arEvent["XML_ID"], $arCalendarItemsMap))
			{
				if ($arEvent["PROPERTY_".$xmlIdField."_VALUE"] != $arCalendarItemsMap[$arEvent["XML_ID"]])
					$arModified[$arEvent["XML_ID"]] = $arEvent["ID"];

				unset($arCalendarItemsMap[$arEvent["XML_ID"]]);
			}
			else
			{
				self::DeleteCalendarEvent($calendarId, $arEvent["ID"], $userId);
			}
		}

		$arResult = array();
		foreach ($arCalendarItems as $value)
		{
			if (array_key_exists($value["XML_ID"], $arModified))
			{
				$arResult[] = array(
					"XML_ID" => $value["XML_ID"],
					"ID" => $arModified[$value["XML_ID"]]
				);
			}
		}

		foreach ($arCalendarItemsMap as $key => $value)
		{
			$arResult[] = array(
				"XML_ID" => $key,
				"ID" => 0
			);
		}

		return $arResult;
	}

	public static function InitCalendarEntry($siteId)
	{
		$iblockId = self::GetUserCalendarIBlockId($siteId);

		$arRequiredProps = array(
			"BXDAVEX_LABEL" => array(
				"PROPERTY_TYPE" => "S",
				"NAME" => "Exchange sync label",
			),
			"BXDAVCD_LABEL" => array(
				"PROPERTY_TYPE" => "S",
				"NAME" => "CalDAV sync label",
			),
		);

		$dbProperty = CIBlockProperty::GetList(
			array(),
			array(
				"IBLOCK_ID" => $iblockId,
				"TYPE" => "E",
				"CHECK_PERMISSIONS" => "N"
			)
		);
		while ($arProperty = $dbProperty->Fetch())
		{
			if (array_key_exists($arProperty["CODE"], $arRequiredProps))
				unset($arRequiredProps[$arProperty["CODE"]]);
		}

		$property = new CIBlockProperty;
		foreach ($arRequiredProps as $requiredPropKey => $requiredPropValue)
		{
			$arFields = array(
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y",
				"USER_TYPE" => false,
				"MULTIPLE" => 'N',
				"CODE" => $requiredPropKey,
				"CHECK_PERMISSIONS" => "N"
			);

			$property->Add(array_merge($arFields, $requiredPropValue));
		}

		$arRequiredFields = array(
			"UF_BXDAVEX_EXCH" => array(
				"USER_TYPE_ID" => "string",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "Exchange calendar",
			),
			"UF_BXDAVEX_EXCH_LBL" => array(
				"USER_TYPE_ID" => "string",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "Exchange calendar modification label",
			),
			"UF_BXDAVEX_CDAV_COL" => array(
				"USER_TYPE_ID" => "integer",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "CalDAV connection",
			),
			"UF_BXDAVEX_CDAV" => array(
				"USER_TYPE_ID" => "string",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "CalDAV calendar",
			),
			"UF_BXDAVEX_CDAV_LBL" => array(
				"USER_TYPE_ID" => "string",
				"SORT" => 100,
				"EDIT_FORM_LABEL_DEFAULT_MESSAGE" => "CalDAV calendar modification label",
			),
		);

		$arUserCustomFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_".$iblockId."_SECTION");
		foreach ($arUserCustomFields as $key => $value)
		{
			if (array_key_exists($key, $arRequiredFields))
				unset($arRequiredFields[$key]);
		}

		foreach ($arRequiredFields as $requiredFieldKey => $requiredFieldValue)
		{
			$arFields = array(
				"ENTITY_ID" => "IBLOCK_".$iblockId."_SECTION",
				"FIELD_NAME" => $requiredFieldKey,
				"SHOW_IN_LIST" => "N",
				"IS_SEARCHABLE" => "N",
				"SHOW_FILTER" => "N",
				"EDIT_IN_LIST" => "N",
			);
			$dbLang = CLanguage::GetList($b = "", $o = "", array());
			while ($arLang = $dbLang->Fetch())
				$arFields["EDIT_FORM_LABEL"][$arLang["LID"]] = $requiredFieldValue["EDIT_FORM_LABEL_DEFAULT_MESSAGE"];

			$obUserField = new CUserTypeEntity;
			$obUserField->Add(array_merge($arFields, $requiredFieldValue));
		}
	}

	public static function CollectExchangeErros($arErrors = array())
	{
		if (count($arErrors) == 0 || !is_array($arErrors))
			return 'NoExchangeServer';

		$str = "";
		for($i = 0; $i < count($arErrors); $i++)
			$str .= "[".$arErrors[$i][0]."] ".$arErrors[$i][1]."\n";

		return $str;
	}

	public static function CollectCalDAVErros($arErrors = array())
	{
		if (count($arErrors) == 0 || !is_array($arErrors))
			return 'No CalDAV responce';

		$str = "";
		for($i = 0; $i < count($arErrors); $i++)
			$str .= "[".$arErrors[$i][0]."] ".$arErrors[$i][1]."\n";

		return $str;
	}

	public static function SyncClearCache($site)
	{
		$iblockId = self::GetUserCalendarIBlockId($site);

		self::ClearCache('event_calendar/events/'.$iblockId.'/');
		self::ClearCache('event_calendar/'.$iblockId."/calendars/");
		self::ClearCache('event_calendar/sp_user');
	}

	public static function CheckCalDavUrl($url, $username, $password)
	{
		$arServer = parse_url($url);
		return CDavGroupdavClientCalendar::DoCheckCalDAVServer($arServer["scheme"], $arServer["host"], $arServer["port"], $username, $password, $arServer["path"]);
	}
}

class CECEvent
{
	function CheckPermission($arParams, $bOnlyUser = false)
	{
		if (isset($GLOBALS['USER']) && $GLOBALS['USER']->CanDoOperation('edit_php'))
			return true;
		$ownerType = isset($arParams['ownerType']) ? $arParams['ownerType'] : $this->ownerType;
		if ($ownerType == 'USER' || $ownerType == 'GROUP')
		{
			$ownerId = isset($arParams['ownerId']) ? $arParams['ownerId'] : $this->ownerId;
			$SONET_ENT = $ownerType == 'USER' ? SONET_ENTITY_USER : SONET_ENTITY_GROUP;
			if (!CSocNetFeatures::IsActiveFeature($SONET_ENT, $ownerId, "calendar") ||
				!CSocNetFeaturesPerms::CanPerformOperation($this->userId, $SONET_ENT, $ownerId, "calendar", 'write'))
				return false;
			if ($bOnlyUser)
				return true;
			$calendarId = isset($arParams['calendarId']) ? intVal($arParams['calendarId']) : 0;
			$sectionId = isset($arParams['sectionId']) ? $arParams['sectionId'] : $this->sectionId;
			$iblockId = isset($arParams['iblockId']) ? $arParams['iblockId'] : $this->iblockId;
			$arFilter = Array(
				"ID" => $calendarId,
				"SECTION_ID" => $sectionId,
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y"
			);
			if ($ownerType == 'USER')
				$arFilter["CREATED_BY"] = $ownerId;
			else
				$arFilter["SOCNET_GROUP_ID"] = $ownerId;
			$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'), $arFilter);

			$arRes = $rsData->Fetch();
			if (!$arRes)
				return false;
		}
		return true;
	}

	function AddReminder($arParams)
	{
		$fullUrl = $arParams['fullUrl'];
		$ownerType = $arParams['ownerType'];
		$ownerId = $arParams['ownerId'];
		$userId = $arParams['userId'];

		$url = $fullUrl.(strpos($fullUrl, '?') === FALSE ? '?' : '&').'EVENT_ID='.$arParams["id"];
		$remAgentParams = array('iblockId' => $arParams['iblockId'], 'eventId' => $arParams["id"], 'userId' => $userId, 'pathToPage' => $url, 'ownerType' => $ownerType, 'ownerId' => $ownerId ? $ownerId : 'false');
		if($arParams["remind"] !== false)
		{
			$rem_ts = MakeTimeStamp($arParams['dateFrom'], getTSFormat());
			$delta = intVal($arParams["remind"]['count']) * 60; //Minute
			if ($arParams["remind"]['type'] == 'hour')
				$delta = $delta * 60; //Hour
			elseif ($arParams["remind"]['type'] == 'day')
				$delta =  $delta * 60 * 24; //Day
			$rem_ts -= $delta;
			$remindTime = date(getDateFormat(), $rem_ts);

			if ($rem_ts >= (time() - 60 * 5)) // Inaccuracy - 5 min
				CEventCalendar::AddAgent($remindTime, $remAgentParams);
			else
				CEventCalendar::RemoveAgent($remAgentParams);
		}
		else if(!$arParams['bNew'])
		{
			CEventCalendar::RemoveAgent($remAgentParams);
		}
	}

	function IsMeeting($iblockId, $id)
	{
		$res = 'N';
		$dbProp = CIBlockElement::GetProperty($iblockId, $id, 'sort', 'asc', array('CODE' => 'IS_MEETING'));
		if ($arProp = $dbProp->Fetch())
			$res = intval($arProp['VALUE']);

		return $res == 'Y';
	}

	function GetExchModLabel($iblockId, $id)
	{
		$dbProp = CIBlockElement::GetProperty($iblockId, intVal($id), 'sort', 'asc', array('CODE' => 'BXDAVEX_LABEL'));
		if ($arProp = $dbProp->Fetch())
			return $arProp['VALUE'];

		return 0;
	}

	function GetCalDAVModLabel($iblockId, $id)
	{
		$dbProp = CIBlockElement::GetProperty($iblockId, intVal($id), 'sort', 'asc', array('CODE' => 'BXDAVCD_LABEL'));
		if ($arProp = $dbProp->Fetch())
			return $arProp['VALUE'];

		return 0;
	}

	function GetAccessibility($iblockId, $id)
	{
		$dbProp = CIBlockElement::GetProperty($iblockId, intVal($id), 'sort', 'asc', array('CODE' => 'ACCESSIBILITY'));
		if ($arProp = $dbProp->Fetch())
			return $arProp['VALUE'];
		return 'busy';
	}

	function GetExchangeXmlId($iblockId, $id)
	{
		$res = CIBlockElement::GetList(
			Array("SORT"=>"ASC"),
			Array("ID" => $id, "IBLOCK_ID" => $iblockId, "CHECK_PERMISSIONS" => "N"),
			false,
			false,
			Array("XML_ID")
		);

		if ($ar = $res->GetNext())
			return $ar["XML_ID"];

		return 0;
	}

	function HostIsAbsent($iblockId, $id)
	{
		$res = 'N';
		$dbProp = CIBlockElement::GetProperty($iblockId, $id, 'sort', 'asc', array('CODE' => 'HOST_IS_ABSENT'));
		if ($arProp = $dbProp->Fetch())
			$res = $arProp['VALUE'];

		return $res === 'Y';
	}

	function GetGuests($iblockId, $id, $arParams = array())
	{
		$arResult = array();
		$bOnlyOwner = false;

		if ($arParams && $arParams['bCheckOwner'] && ($arParams['ownerType'] == 'USER' || !$arParams['bHostIsAbsent']))
		{
			$rsHost = CIBlockElement::GetList(array(), array("=ID" => $id,), false, false, array("CREATED_BY"));
			if($arHost = $rsHost->Fetch())
			{
				$rsHostUser = CUser::GetByID($arHost["CREATED_BY"]);
				if($arHostUser = $rsHostUser->Fetch())
				{
					$arHostUser["FULL_NAME"] = CEventCalendar::GetFullUserName($arHostUser);
					$arResult[$arHost["CREATED_BY"]] = array(
						'CREATED_BY' => $arHostUser,
						'PROPERTY_VALUES' => array('CONFIRMED' => 'Y'),
						'IS_HOST' => true
					);
					$bOnlyOwner = true;
				}
			}
		}

		if($ar = CEventCalendar::GetLinkIBlock($iblockId))
		{
			$rsGuests = CIBlockElement::GetList(array(), array(
				"IBLOCK_ID" => $iblockId,
				"PROPERTY_".$ar["ID"] => $id,
			), false, false, array(
				"ID",
				"IBLOCK_ID",
				"CREATED_BY",
				"NAME",
				"ACTIVE_FROM",
				"ACTIVE_TO",
				"DETAIL_TEXT",
				"DETAIL_TEXT_TYPE",
				"IBLOCK_SECTION_ID"
			));

			while($arGuest = $rsGuests->Fetch())
			{
				$guest_id = intval($arGuest["CREATED_BY"]);
				if($guest_id > 0)
				{
					$rsUser = CUser::GetList($o, $b, array(
						"ID_EQUAL_EXACT" => $guest_id
					));
					$arUser = $rsUser->Fetch();
					if($arUser)
					{
						$arUser["FULL_NAME"] = CEventCalendar::GetFullUserName($arUser);
						$arGuest["CREATED_BY"] = $arUser;

						$arGuest["PROPERTY_VALUES"] = array();
						$rsProp = CIBlockElement::GetProperty($iblockId, $arGuest["ID"], array("EMPTY"=>"N"));
						while($arProp = $rsProp->Fetch())
						{
							if(strlen($arProp["CODE"]) > 0)
								$prop_id = $arProp["CODE"];
							else
								$prop_id = $arProp["ID"];

							if($arProp["PROPERTY_TYPE"] == "L")
								$value = $arProp["VALUE_XML_ID"];
							else
								$value = $arProp["VALUE"];

							if($arProp["MULTIPLE"] == "Y")
								$arGuest["PROPERTY_VALUES"][$prop_id][$arProp["PROPERTY_VALUE_ID"]] = $value;
							else
								$arGuest["PROPERTY_VALUES"][$prop_id] = $value;
						}
						$arResult[$guest_id] = $arGuest;
						$bOnlyOwner = false;
					}
				}
			}
		}

		return $bOnlyOwner && $arParams['DontReturnOnlyOwner'] ? array() : $arResult;
	}

	function Delete($arParams)
	{
		global $DB, $USER;
		$iblockId = $arParams['iblockId'];
		$ownerType = $arParams['ownerType'];
		$ownerId = $arParams['ownerId'];
		$ID = $arParams['id'];
		$userId = $arParams['userId'];

		if ($USER)
		{
			$ownerName = $USER->GetFullName();
		}
		else
		{
			$rs = CUser::GetByID($userId);
			if($arUser = $rs->Fetch())
				$name = trim($arUser['NAME'].' '.$arUser['LAST_NAME']);
		}

		$pathToUserCalendar = $arParams['pathToUserCalendar'];
		$arFilter = array("ID" => $ID, "IBLOCK_ID" => $iblockId);
		if ($arParams['bCheckPermissions'] !== false)
			$arFilter[$ownerType == 'USER' ? "CREATED_BY" : "SOCNET_GROUP_ID"] = $ownerId;

		$rsElement = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "XML_ID", "IBLOCK_ID", "PROPERTY_PRIVATE", "PROPERTY_ACCESSIBILITY", "PROPERTY_IMPORTANCE", "PROPERTY_PARENT", "PROPERTY_LOCATION", "NAME", "DETAIL_TEXT", "IBLOCK_SECTION_ID", "ACTIVE_FROM", "ACTIVE_TO", "CREATED_BY", "PROPERTY_BXDAVEX_LABEL", "PROPERTY_BXDAVCD_LABEL"));

		if($arElement = $rsElement->Fetch())
		{
			$DB->StartTransaction();
			// PROPERTY_PARENT_VALUE - id of parent iblock element in meeting
			if(strlen($arElement["PROPERTY_PARENT_VALUE"]) > 0 && $arParams['bJustDel'] !== true)
			{
				if ($ownerType == 'USER')
				{
					$rsHost = CIBlockElement::GetList(array(), array(
							"=ID" => $arElement["PROPERTY_PARENT_VALUE"],
							"CREATED_BY" => $arElement['CREATED_BY'],
						), false, false, array(
							"ID",
							"IBLOCK_ID"
						)
					);

					// Owner delete mirror of the original event in the personal calendar
					if ($arHost = $rsHost->Fetch())
					{
						CECEvent::Delete(array(
							'bCheckPermissions' => false,
							'id' => intVal($arHost['ID']),
							'iblockId' => intVal($arHost['IBLOCK_ID']),
							'ownerType' => '',
							'ownerId' => 0,
							'userId' => $userId,
							'pathToUserCalendar' => $arParams['pathToUserCalendar'],
							'pathToGroupCalendar' => $arParams['pathToGroupCalendar'],
							'userIblockId' => $arParams['userIblockId'],
							'RMiblockId' => $arParams['RMiblockId'],
							'allowResMeeting' => $arParams['allowResMeeting'],
							'VMiblockId' => $arParams['VMiblockId'],
							'allowVideoMeeting' => $arParams['allowVideoMeeting'],
						));

						$this->ClearCache($this->cachePath.'events/'.$arHost['IBLOCK_ID'].'/');
						return true;
					}
				}

				// If no exchange
				CIBlockElement::SetPropertyValues(
					$arElement["ID"],
					$arElement["IBLOCK_ID"],
					CEventCalendar::GetConfirmedID($iblockId, "N"),
					"CONFIRMED"
				);
			}
			else
			{
				if ($arParams['bSyncDAV'] !== false)
				{
					// Exchange
					if (CEventCalendar::IsExchangeEnabled() && $ownerType == 'USER' && strlen($arElement['PROPERTY_BXDAVEX_LABEL_VALUE']) > 0)
					{
						$eventXmlId = $arElement['XML_ID'];
						$exchRes = CDavExchangeCalendar::DoDeleteItem($ownerId, $eventXmlId);
						if ($exchRes !== true)
							return CEventCalendar::CollectExchangeErros($exchRes);
					}

					if (CEventCalendar::IsCalDAVEnabled() && $ownerType == 'USER' && strlen($arElement['PROPERTY_BXDAVCD_LABEL_VALUE']) > 0 )
					{
						$connectionId = CECCalendar::GetCalDAVConnectionId($iblockId, $arElement['IBLOCK_SECTION_ID']);
						$calendarCalDAVXmlId = CECCalendar::GetCalDAVXmlId($iblockId, $arElement['IBLOCK_SECTION_ID']);
						$DAVRes = CDavGroupdavClientCalendar::DoDeleteItem($connectionId, $calendarCalDAVXmlId, $arElement['XML_ID']);

						if ($DAVRes !== true)
							return CEventCalendar::CollectCalDAVErros($DAVRes);
					}
				}

				if(strlen($arElement["PROPERTY_LOCATION_VALUE"]) > 0)
				{
					$loc = CEventCalendar::ParseLocation($arElement["PROPERTY_LOCATION_VALUE"]);
					if($loc['mrid'] == $arParams['VMiblockId'] && $loc['mrevid'] > 0) // video meeting
					{
						CEventCalendar::ReleaseVR(array(
							'mrevid' => $loc['mrevid'],
							'mrid' => $loc['mrid'],
							'VMiblockId' => $arParams['VMiblockId'],
							'allowVideoMeeting' => $arParams['allowVideoMeeting'],
						));
					}
					elseif ($loc['mrid'] > 0 && $loc['mrevid'] > 0)
					{
						CEventCalendar::ReleaseMR(array(
							'mrevid' => $loc['mrevid'],
							'mrid' => $loc['mrid'],
							'RMiblockId' => $arParams['RMiblockId'],
							'allowResMeeting' => $arParams['allowResMeeting'],
						));
					}
				}

				$arGuests = CECEvent::GetGuests($arParams['userIblockId'], $ID);
				$obElement = new CIBlockElement;
				foreach($arGuests as $guest_id => $arCalendarEvent)
				{
					$res = CECEvent::Delete(array(
						'id' => $arCalendarEvent["ID"],
						'iblockId' => $arParams['userIblockId'],
						'ownerType' => "USER",
						'ownerId' => $guest_id,
						'userId' => $userId,
						'bJustDel' => true // Just delete iblock element  + exchange
					));

					if ($userId == $guest_id)
						continue;

					if ($arCalendarEvent["PROPERTY_VALUES"]["CONFIRMED"] != "N")
					{
						// Send message
						CEventCalendar::SendInvitationMessage(array(
							'type' => "cancel",
							'email' => $arCalendarEvent["CREATED_BY"]["EMAIL"],
							'name' => $arCalendarEvent['NAME'],
							"from" => $arCalendarEvent["ACTIVE_FROM"],
							"to" => $arCalendarEvent["ACTIVE_TO"],
							"desc" => $arCalendarEvent['DETAIL_TEXT'],
							"pathToUserCalendar" => $pathToUserCalendar,
							"guestId" => $guest_id,
							"guestName" => $arCalendarEvent["CREATED_BY"]["FULL_NAME"],
							"userId" => $userId,
							"ownerName" => $ownerName
						));
					}
				}

				if ($ownerType != 'USER')
					CEventCalendar::ClearCache('event_calendar/events/'.$arParams['userIblockId'].'/');

				// Deleting
				if(!CIBlockElement::Delete($ID))
				{
					$DB->Rollback();
					return '[ECD1]'.GetMessage('EC_EVENT_ERROR_DEL');
				}
			}

			// log changes for socnet
/*			if($this->bSocNetLog && $ownerType && !$arElement["PROPERTY_PRIVATE_VALUE"] && !$arParams['dontLogEvent'])
			{
				CEventCalendar::SocNetLog(
					array(
						'target' => 'delete_event',
						'id' => $ID,
						'name' => $arElement['NAME'],
						'desc' => $arElement['DETAIL_TEXT'],
						'from' => $arElement['ACTIVE_FROM'],
						'to' => $arElement['ACTIVE_TO'],
						'calendarId' => $arElement['IBLOCK_SECTION_ID'],
						'accessibility' => $arElement["PROPERTY_ACCESSIBILITY_VALUE"],
						'importance' => $arElement["PROPERTY_IMPORTANCE_VALUE"],
						'pathToGroupCalendar' =>  $arParams["pathToGroupCalendar"],
						'pathToUserCalendar' =>  $arParams["pathToUserCalendar"],
						'iblockId' => $iblockId,
						'ownerType' => $ownerType,
						'ownerId' => $ownerId
					)
				);
			}*/
			$DB->Commit();
		}
		else
		{
			return '[ECD2]'.GetMessage('EC_EVENT_NOT_FOUND');
		}

		return true;
	}
}

class CECCalendar
{
	function GetList($arParams)
	{
		$sectionId = isset($arParams['sectionId']) && $arParams['sectionId'] !== false ? $arParams['sectionId'] : 0;
		$iblockId = $arParams['iblockId'];
		$xmlId = isset($arParams['xmlId']) && $arParams['xmlId'] !== false ? $arParams['xmlId'] : 0;
		$forExport = $arParams['forExport'] == true;
		$checkPermissions = $forExport ? 'N' : 'Y';
		$bOwner = $arParams['bOwner'] === true;
		$arFilter = Array(
			"SECTION_ID" => $sectionId,
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => $checkPermissions
		);

		if ($bOwner)
		{
			$ownerType = $arParams['ownerType'];
			$ownerId = $arParams['ownerId'];
			if ($ownerType == 'USER')
			{
				$arFilter["CREATED_BY"] = $ownerId;
				$userId = $arParams['userId'] ? intVal($arParams['userId']) : $GLOBALS['USER']->GetID();
				$bCurUserOwner = $ownerId == $userId;
			}
			elseif ($ownerType == 'GROUP')
			{
				$arFilter["SOCNET_GROUP_ID"] = $ownerId;
				$bCurUserOwner = true;
			}
		}
		else
		{
			$ownerType = false;
			$ownerId = false;
			$bCurUserOwner = true;
		}

		/* modified by wladart */
		// get superpose calendars
		//if (!$bOwner && CModule::IncludeModule('extranet'))
		if (CModule::IncludeModule('extranet'))
		{
			if (CExtranet::IsExtranetSite())
			{
				$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(SITE_ID);
				$arPublicUsersID = CExtranet::GetPublicUsers();
				$arUsersToFilter = array_merge($arUsersInMyGroupsID, $arPublicUsersID);
				$arFilter["CREATED_BY"]  = $arUsersToFilter;
			}
			else
			{
				$arFilter["CREATED_BY"] = CExtranet::GetIntranetUsers();
			}
		}
		/* --modified by wladart */

		if ($xmlId !== 0)
		{
			$arFilter['XML_ID'] = $xmlId;
			if ($sectionId === 0)
				unset($arFilter['SECTION_ID']);
		}

		$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'), $arFilter);
		$arCalendars = array();

		if (!$arParams['bSuperposed'] && !$arParams['bOnlyID'])
		{
			$outerUrl = $GLOBALS['APPLICATION']->GetCurPageParam('', array("action", "bx_event_calendar_request", "clear_cache", "bitrix_include_areas", "bitrix_show_mode", "back_url_admin", "SEF_APPLICATION_CUR_PAGE_URL"), false);
		}

		while($arRes = $rsData->Fetch())
		{
			$privateStatus = CECCalendar::GetPrivateStatus($iblockId, $arRes['ID'], $ownerType);

			if ($privateStatus == 'private' && !$bCurUserOwner)
				continue;

			if ($arParams['bOnlyID']) // We need only IDs of the calendars
			{
				$arCalendars[] = intVal($arRes['ID']);
				continue;
			}

			$calendar = array(
				"ID" => intVal($arRes['ID']),
				"IBLOCK_ID" => $iblockId,
				"IBLOCK_SECTION_ID" => intVal($arRes['IBLOCK_SECTION_ID']),
				"NAME" => htmlspecialcharsex($arRes['NAME']),
				"DESCRIPTION" => htmlspecialcharsex($arRes['DESCRIPTION']),
				"COLOR" => CECCalendar::GetColor($iblockId, $arRes['ID'], $ownerType),
				"PRIVATE_STATUS" => $privateStatus
			);

			if (!$arParams['bSuperposed'])
			{
				$calendar["OUTLOOK_JS"] = CECCalendar::GetOutlookLink(array('ID' => intVal($arRes['ID']), 'XML_ID' => $arRes['XML_ID'], 'IBLOCK_ID' => $iblockId, 'NAME' => htmlspecialcharsex($arRes['NAME']), 'PREFIX' => CEventCalendar::GetOwnerName(array('iblockId' => $iblockId, 'ownerType' => $ownerType, 'ownerId' => $ownerId)), 'LINK_URL' => $outerUrl));
				$arExport = CECCalendar::GetExportParams($iblockId, $arRes['ID'], $ownerType, $ownerId);

				$calendar["EXPORT"] = $arExport['ALLOW'];
				$calendar["EXPORT_SET"] = $arExport['SET'];
				$calendar["EXPORT_LINK"] = $arExport['LINK'];
			}

			$arCalendars[] = $calendar;
		}
		return $arCalendars;
	}

	function Edit($arParams, &$newSectionId, $bDisplay = true)
	{
		global $DB;
		$iblockId = $arParams['iblockId'];
		$ownerId = $arParams['ownerId'];
		$ownerType = $arParams['ownerType'];
		$sectionId = $arParams['sectionId'];
		$arFields = $arParams['arFields'];

		if ($sectionId === 'none')
		{
			$sectionId = CEventCalendar::CreateSectionForOwner($ownerId, $ownerType, $iblockId); // Creating section for owner
			if ($sectionId === false)
				return false;

			if ($bDisplay)
			{
				?><script>window._bx_section_id = <?=intVal($sectionId)?>;</script><?
			}
			$newSectionId = $sectionId;
		}

		$ID = $arFields['ID'];
		$DB->StartTransaction();
		$bs = new CIBlockSection;

		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';
		$key_color = "UF_".$ownerType."_CAL_COL";
		$key_export = "UF_".$ownerType."_CAL_EXP";
		$key_status = "UF_".$ownerType."_CAL_STATUS";

		$EXPORT = $arFields['EXPORT'] ? $arFields['EXPORT_SET'] : '';

		$arFields = Array(
			"ACTIVE"=>"Y",
			"IBLOCK_SECTION_ID"=>$sectionId,
			"IBLOCK_ID"=>$iblockId,
			"NAME"=>$arFields['NAME'],
			"DESCRIPTION"=>$arFields['DESCRIPTION'],
			$key_color => $arFields['COLOR'],
			$key_export => $EXPORT,
			$key_status => $arFields['PRIVATE_STATUS']
		);
		$GLOBALS[$key_color] = $COLOR;
		$GLOBALS[$key_export] = $EXPORT;
		$GLOBALS[$key_status] = $arFields['PRIVATE_STATUS'];
		$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$iblockId."_SECTION", $arFields);

		if ($ownerType == 'GROUP' && $ownerId > 0)
			$arFields['SOCNET_GROUP_ID'] = $ownerId;

		if(isset($ID) && $ID > 0)
		{
			$res = $bs->Update($ID, $arFields);
		}
		else
		{
			$ID = $bs->Add($arFields);
			$res = ($ID > 0);
			if($res)
			{
				//This sets appropriate owner if section created by owner of the meeting and this calendar belongs to guest which is not current user
				if ($ownerType == 'USER' && $ownerId > 0)
					$DB->Query("UPDATE b_iblock_section SET CREATED_BY = ".intval($ownerId)." WHERE ID = ".intval($ID));
			}
		}
		if(!$res)
		{
			$DB->Rollback();
			return false;
		}

		$DB->Commit();
		return $ID;
	}

	function Delete($ID, $arEvIds = false)
	{
		global $DB;
		if (!$this->CheckPermissionForEvent(array(), true))
			return CEventCalendar::ThrowError('EC_ACCESS_DENIED');
		@set_time_limit(0);
		$DB->StartTransaction();
		if(!CIBlockSection::Delete($ID))
		{
			$DB->Rollback();
			return false;
		}
		$DB->Commit();
		return true;
	}

	function CreateDefault($arParams = array(), $bDisplay = true, &$newSectionId = 'none')
	{
		$iblockId = $arParams['iblockId'];
		$ownerId = $arParams['ownerId'];
		$ownerType = $arParams['ownerType'];
		$sectionId = $arParams['sectionId'];

		if ($ownerType == 'USER')
			$name = GetMessage('EC_DEF_SECT_USER_CAL');
		else
			$name = GetMessage('EC_DEF_SECT_GROUP_CAL');

		$arFields = Array(
			'ID' => 0,
			'NAME' => $name,
			'DESCRIPTION' => '',
			"COLOR" => "#CEE669",
			"EXPORT" => true,
			"EXPORT_SET" => 'all'
		);
		$arParams['arFields'] = $arFields;
		$ID = CECCalendar::Edit($arParams, $newSectionId, $bDisplay);

		if ($ID > 0 && $bDisplay) // Display created calendar for js
		{
			$arEx = CECCalendar::GetExportParams($iblockId, $ID, $ownerType, $ownerId);
			$outlookJs = CECCalendar::GetOutlookLink(array(
				'ID' => intVal($ID),
				'PREFIX' => CEventCalendar::GetOwnerName(array('iblockId' => $iblockId, 'ownerType' => $ownerType, 'ownerId' => $ownerId))
			));
			?>
<script>window._bx_def_calendar = {
	ID: <?=intVal($ID)?>,
	NAME: '<?=$arFields['NAME']?>',
	COLOR: '<?=$arFields['COLOR']?>',
	EXPORT: <?=$arEx['ALLOW'] ? 'true' : 'false'?>,
	EXPORT_SET: '<?=$arEx['SET']?>',
	EXPORT_LINK: '<?=$arEx['LINK']?>',
	PRIVATE_STATUS: 'full',
	bNew: true,
	OUTLOOK_JS: '<?=addslashes(htmlspecialcharsex($outlookJs))?>'
};</script>
			<?
		}

		// Clear cache
		CEventCalendar::ClearCache("event_calendar/".$iblockId."/calendars/".($ownerId > 0 ? $ownerId : 0)."/");
		if ($ownerType == 'GROUP')
			CEventCalendar::ClearCache('event_calendar/sp_groups/');
		elseif($ownerType == 'USER')
			CEventCalendar::ClearCache('event_calendar/sp_user/');
		else
			CEventCalendar::ClearCache('event_calendar/sp_common/');

		return $ID;
	}

	function GetPrivateStatus($iblockId, $sectionId, $ownerType = false)
	{
		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';
		$key = "UF_".$ownerType."_CAL_STATUS";
		$ent_id = "IBLOCK_".$iblockId."_SECTION";
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($ent_id, $sectionId);
		if (isset($arUF[$key]) && strlen($arUF[$key]['VALUE']) > 0)
			return $arUF[$key]['VALUE'];
		return 'full';
	}

	function GetColor($iblockId, $sectionId, $ownerType = false)
	{
		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';
		$key = "UF_".$ownerType."_CAL_COL";
		$ent_id = "IBLOCK_".$iblockId."_SECTION";
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($ent_id, $sectionId);
		if (isset($arUF[$key]) && strlen($arUF[$key]['VALUE']) > 0)
			return colorReplace($arUF[$key]['VALUE']);
		return '#CEE669';
	}

	public static function GetExchangeXmlId($iblockId, $calendarId)
	{
		$key = "UF_BXDAVEX_EXCH";
		$ent_id = "IBLOCK_".$iblockId."_SECTION";

		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($ent_id, $calendarId);
		if (isset($arUF[$key]) && (strlen($arUF[$key]['VALUE']) > 0))
			return $arUF[$key]['VALUE'];

		return 0;
	}

	public static function GetExchModLabel($iblockId, $calendarId)
	{
		$key = "UF_BXDAVEX_EXCH_LBL";
		$ent_id = "IBLOCK_".$iblockId."_SECTION";

		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($ent_id, $calendarId);
		if (isset($arUF[$key]) && (strlen($arUF[$key]['VALUE']) > 0))
			return $arUF[$key]['VALUE'];

		return 0;
	}

	public static function GetCalDAVModLabel($iblockId, $calendarId)
	{
		$key = "UF_BXDAVEX_CDAV_LBL";
		$ent_id = "IBLOCK_".$iblockId."_SECTION";

		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($ent_id, $calendarId);
		if (isset($arUF[$key]) && (strlen($arUF[$key]['VALUE']) > 0))
			return $arUF[$key]['VALUE'];

		return 0;
	}

	function GetCalDAVConnectionId($iblockId, $calendarId)
	{
		$key = "UF_BXDAVEX_CDAV_COL";
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_".$iblockId."_SECTION", $calendarId);
		if (isset($arUF[$key]) && (strlen($arUF[$key]['VALUE']) > 0))
			return $arUF[$key]['VALUE'];
		return 0;
	}

	function GetCalDAVXmlId($iblockId, $calendarId)
	{
		$key = "UF_BXDAVEX_CDAV";
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_".$iblockId."_SECTION", $calendarId);
		if (isset($arUF[$key]) && (strlen($arUF[$key]['VALUE']) > 0))
			return $arUF[$key]['VALUE'];
		return 0;
	}

	function GetExportParams($iblockId, $calendarId, $ownerType = false, $ownerId = false)
	{
		if ($ownerType != 'USER' && $ownerType != 'GROUP')
			$ownerType = '';
		$key = "UF_".$ownerType."_CAL_EXP";
		$ent_id = "IBLOCK_".$iblockId."_SECTION";

		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($ent_id, $calendarId);
		if (isset($arUF[$key]) && strlen($arUF[$key]['VALUE']) > 0)
			return array('ALLOW' => true, 'SET' => $arUF[$key]['VALUE'], 'LINK' => CECCalendar::GetExportLink($calendarId, $ownerType, $ownerId, $iblockId));
		else
			return array('ALLOW' => false, 'SET' => false, 'LINK' => false);
	}

	function GetExportLink($calendarId, $ownerType = false, $ownerId = false, $iblockId = false)
	{
		global $USER;
		$userId = $USER->IsAuthorized() ? $USER->GetID() : '';
		$params_ = '';
		if ($ownerType !== false)
			$params_ .=  '&owner_type='.strtolower($ownerType);
		if ($ownerId !== false)
			$params_ .=  '&owner_id='.intVal($ownerId);
		if ($iblockId !== false)
			$params_ .=  '&ibl='.strtolower($iblockId);
		return $params_.'&user_id='.$userId.'&calendar_id='.intVal($calendarId).'&sign='.CECCalendar::GetSign($userId, $calendarId);
	}

	function GetSPExportLink()
	{
		global $USER;
		$userId = $USER->IsAuthorized() ? $USER->GetID() : '';
		return '&user_id='.$userId.'&sign='.CECCalendar::GetSign($userId, 'superposed_calendars');
	}

	function GetOutlookLink($arParams)
	{
		return CIntranetUtils::GetStsSyncURL($arParams);
	}

	function GetUniqCalendarId()
	{
		$uniq = COption::GetOptionString("iblock", "~cal_uniq_id", "");
		if(strlen($uniq) <= 0)
		{
			$uniq = md5(uniqid(rand(), true));
			COption::SetOptionString("iblock", "~cal_uniq_id", $uniq);
		}
		return $uniq;
	}

	function GetSign($userId, $calendarId)
	{
		return md5($userId."||".$calendarId."||".CECCalendar::GetUniqCalendarId());
	}

	function CheckSign($sign, $userId, $calendarId)
	{
		return (md5($userId."||".$calendarId."||".CECCalendar::GetUniqCalendarId()) == $sign);
	}

	function GetHidden($userId)
	{
		$res = array();
		if (class_exists('CUserOptions'))
		{
			$str = CUserOptions::GetOption("intranet", "ec_hidden_calendars", false, $userId);
			if ($str !== false && checkSerializedData($str))
				$res = unserialize($str);
		}
		return $res;
	}

	function SetHidden($userId, $ar = array())
	{
		if (class_exists('CUserOptions'))
			return CUserOptions::SetOption("intranet", "ec_hidden_calendars", serialize($ar));
		return false;
	}
}

//?
function CheckCalendarIds($OWNER_TYPE, $OWNER_ID, $iblockId, $SECTION_ID, &$arCalendarIds)
{
	if ($OWNER_TYPE != 'USER' && $OWNER_TYPE != 'GROUP')
		return true;
	$arFilter = Array(
		"ID" => $arCalendarIds,
		"SECTION_ID" => $SECTION_ID,
		"IBLOCK_ID" => $iblockId,
		"ACTIVE" => "Y"
	);
	if ($OWNER_TYPE == 'USER')
		$arFilter["CREATED_BY"] = $OWNER_ID;
	else
		$arFilter["SOCNET_GROUP_ID"] = $OWNER_ID;
	$rsData = CIBlockSection::GetList(Array('ID' => 'ASC'), $arFilter);
	$ar = array();
	while($arRes = $rsData->Fetch())
	{
		if (in_array($arRes['ID'], $arCalendarIds))
			$ar[] = $arRes['ID'];
	}
	$arCalendarIds = $ar;
}

function trim_(&$item, $key, $prefix = '')
{
	$item = trim($item);
}

function colorTrim_(&$item, $key, $prefix)
{
	$item = '#'.ltrim(trim($item), "#");
}

function colorReplace($color)
{
	$color = preg_replace('/[^\d|\w|#]/', '', $color);
	return $color;
}

function intval_(&$item, $key = '', $prefix = '')
{
	$item = intval($item);
}

function convertDayInd($i)
{
	if ($i == 0)
		return 6;
	return $i - 1;
}

function getTSFormat()
{
	return CSite::GetDateFormat("FULL");
}

function getDateFormat($bTime = true)
{
	$str = CSite::GetDateFormat($bTime ? "FULL" : "SHORT", SITE_ID);
	$str =  str_replace(array('DD', 'MM', 'YYYY', 'HH', 'MI', 'SS'), array('d', 'm', 'Y', 'H', 'i', 's'), $str);
	return $str;
}

function cutZeroTime($date)
{
	$date = trim($date);
	if (substr($date, -9) == ' 00:00:00')
		return substr($date, 0, -9);
	if (substr($date, -3) == ':00')
		return substr($date, 0, -3);
	return $date;
}

if (!function_exists('eventsSort'))
{
	function eventsSort($a, $b)
	{
		if ($a['_FROM_TS'] == $b['_FROM_TS'])
			return 0;
		if ($a['_FROM_TS'] < $b['_FROM_TS'])
			return -1;
		return 1;
	}
}

function ec_addslashes($str)
{
	if (strlen($str) <= 0)
		return $str;
	$str = str_replace("script>","script_>", $str);
	$pos2 = strpos(strtolower($str), "\n");
	if ($pos2 !== FALSE)
	{
		$str = str_replace("\r","",$str);
		$str = str_replace("\n","\\n",$str);
	}
	$str = CUtil::addslashes($str);
	return $str;
}
?>
