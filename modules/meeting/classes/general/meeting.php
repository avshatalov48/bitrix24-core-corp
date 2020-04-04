<?
abstract class CAllMeeting
{
	const STATE_PREPARE = 'P';
	const STATE_ACTION = 'A';
	const STATE_CLOSED = 'C';

	const ROLE_OWNER = 'O';
	const ROLE_KEEPER = 'K';
	const ROLE_MEMBER = 'M';
	const ROLE_HEAD = 'H';

	const MEETING_ROOM_PREFIX = 'mr';

	abstract public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array());

	public static function GetItems($ID, $type = false)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$query = "
SELECT ins.*, ".$DB->DateToCharFunction("ins.DEADLINE", "FULL")." as DEADLINE, it.TITLE, it.DESCRIPTION
FROM b_meeting_instance ins
LEFT JOIN b_meeting_item it ON ins.ITEM_ID=it.ID
WHERE ins.MEETING_ID='".$ID."'
ORDER BY ins.INSTANCE_PARENT_ID, ins.SORT
";

		return $DB->Query($query);
	}

	public static function GetByID($ID)
	{
		return CMeeting::GetList(array(), array('ID' => intval($ID)));
	}

	public static function Add($arFields)
	{
		global $DB;

		foreach(GetModuleEvents("meeting", "OnBeforeMeetingAdd", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array(&$arFields)))
				return false;
		}

		if (!self::CheckFields('ADD', $arFields))
			return false;

		$ID = $DB->Add('b_meeting', $arFields, array('DESCRIPTION', 'PROTOCOL_TEXT'));
		if ($ID > 0)
		{
			$arFields['ID'] = $ID;

			if (isset($arFields['USERS']))
			{
				self::SetUsers($ID, $arFields['USERS'], false);
			}

			if (isset($arFields['FILES']))
			{
				self::SetFiles($ID, $arFields['FILES']);
			}

			foreach(GetModuleEvents("meeting", "OnAfterMeetingAdd", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arFields));
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		if ($ID <= 0)
			return false;

		$arFields['ID'] = $ID;

		foreach (GetModuleEvents('meeting', 'OnBeforeMeetingUpdate', true) as $a)
		{
			if (false === ExecuteModuleEventEx($a, array(&$arFields)))
			{
				return false;
			}
		}

		if (!self::CheckFields('UPDATE', $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate('b_meeting', $arFields);
		$query = 'UPDATE b_meeting SET '.$strUpdate.' WHERE ID=\''.intval($ID).'\'';

		$arBind = array();
		if(isset($arFields['DESCRIPTION']))
		{
			$arBind['DESCRIPTION'] = $arFields['DESCRIPTION'];
		}
		if(isset($arFields['PROTOCOL_TEXT']))
		{
			$arBind['PROTOCOL_TEXT'] = $arFields['PROTOCOL_TEXT'];
		}

		$dbRes = $DB->QueryBind($query, $arBind);
		if ($dbRes)
		{
			if (isset($arFields['USERS']))
			{
				self::SetUsers($ID, $arFields['USERS']);
			}

			if (isset($arFields['FILES']))
			{
				self::SetFiles($ID, $arFields['FILES']);
			}

			foreach (GetModuleEvents('meeting', 'OnAfterMeetingUpdate', true) as $a)
				ExecuteModuleEventEx($a, array($ID, $arFields));

			return $ID;
		}

		return false;
	}

	public static function SetUsers($ID, $arUsers = null, $bClear = true)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		if ($bClear)
		{
			$query = "DELETE FROM b_meeting_users WHERE MEETING_ID='".$ID."'";
			if (count($arUsers) > 0)
				$query .= " AND (USER_ROLE='".self::ROLE_MEMBER."' OR USER_ROLE='".self::ROLE_KEEPER."')";
			$DB->Query($query);
		}

		$cnt = 0;
		if (is_array($arUsers))
		{
			foreach ($arUsers as $USER_ID => $USER_ROLE)
			{
				$USER_ID = intval($USER_ID);
				if ($USER_ID <= 0)
					continue;

				if ($USER_ROLE != self::ROLE_OWNER && $USER_ROLE != self::ROLE_KEEPER)
					$USER_ROLE = self::ROLE_MEMBER;

				if ($DB->Query("INSERT INTO b_meeting_users (MEETING_ID, USER_ID, USER_ROLE) VALUES ('".$ID."', '".$USER_ID."', '".$USER_ROLE."')", true))
				{
					$cnt++;
				}
			}
		}

		return $cnt;
	}

	public static function GetUsers($ID)
	{
		global $DB;

		$arUsers = array();

		$ID = intval($ID);
		if ($ID > 0)
		{
			$dbRes = $DB->Query("SELECT USER_ID, USER_ROLE FROM b_meeting_users WHERE MEETING_ID='".$ID."'");
			while ($arRes = $dbRes->Fetch())
			{
				$arUsers[$arRes['USER_ID']] = $arRes['USER_ROLE'];
			}
		}

		return $arUsers;
	}

	public static function SetFiles($ID, $arFiles, $src = null)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return;

		if (count($arFiles) <= 0)
		{
			$DB->Query("DELETE FROM b_meeting_files WHERE MEETING_ID='".intval($ID)."'");
		}

		if (count($arFiles) > 0)
		{
			foreach ($arFiles as $FILE_ID)
			{
				$FILE_ID = intval($FILE_ID);
				if ($FILE_ID > 0)
				{
					$DB->Query("INSERT INTO b_meeting_files (MEETING_ID, FILE_ID, FILE_SRC) VALUES ('".$ID."', '".$FILE_ID."', '".intval($src)."')", true);
				}
			}
		}
	}

	public static function GetFiles($ID, $fileId = null)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return;

		$query = "SELECT FILE_ID, FILE_SRC FROM b_meeting_files WHERE MEETING_ID='".$ID."'";

		if ($fileId > 0)
		{
			$query .= " AND FILE_ID='".intval($fileId)."'";
		}

		$query .= " ORDER BY FILE_ID ASC";

		return $DB->Query($query);
	}

	public static function DeleteFiles($ID)
	{
		$dbFiles = self::GetFiles($ID);
		while ($arRes = $dbFiles->Fetch())
		{
			CFile::Delete($arRes['FILE_ID']);
		}
		self::SetFiles($ID, array());
	}

	public static function DeleteFilesBySrc($FILE_SRC)
	{
		global $DB;

		$FILE_SRC = intval($FILE_SRC);
		if ($FILE_SRC > 0)
		{
			$dbRes = $DB->Query("SELECT * FROM b_meeting_files WHERE FILE_SRC='".$FILE_SRC."'");
			while ($arRes = $dbRes->Fetch())
				CFile::Delete($arRes['FILE_ID']);
			$DB->Query("DELETE FROM b_meeting_files WHERE FILE_SRC='".$FILE_SRC."'");
		}
	}

	public static function GetUserRole($ID, $USER_ID = false, $bCheckHead = true)
	{
		global $DB, $USER;

		$role = false;

		$ID = intval($ID);
		$USER_ID = intval($USER_ID);

		if ($ID > 0)
		{
			if ($USER_ID <= 0)
				$USER_ID = $USER->GetID();

			$sqlFilter = "AND USER_ID='".$USER_ID."'";
			if ($bCheckHead)
			{
				$arSubIDs = array($USER_ID);
				$dbUsers = CIntranetUtils::GetSubordinateEmployees($USER_ID, true, 'Y', array('ID'));
				while ($arUser = $dbUsers->Fetch())
				{
					$arSubIDs[] = $arUser['ID'];
				}
				$sqlFilter = "AND USER_ID IN ('".implode("', '", $arSubIDs)."')";
			}

			$dbRes = $DB->Query("SELECT USER_ID, USER_ROLE FROM b_meeting_users WHERE MEETING_ID='".$ID."' ".$sqlFilter);

			if ($bCheckHead)
			{
				while ($arRes = $dbRes->Fetch())
				{
					$role = CMeeting::ROLE_HEAD;
					if ($arRes['USER_ID'] == $USER_ID)
					{
						$role = $arRes['USER_ROLE'];
						break;
					}
				}

			}
			else
			{
				if ($arRes = $dbRes->Fetch())
					$role =  $arRes['USER_ROLE'];
			}
		}

		return $role;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$dbRes = CMeeting::GetByID($ID);
		if ($arMeeting = $dbRes->Fetch())
		{
			foreach (GetModuleEvents("meeting", "OnBeforeMeetingDelete", true) as $arEvent)
			{
				if (false === ExecuteModuleEventEx($arEvent, array($ID, $arMeeting)))
				{
					return false;
				}
			}

			if ($arMeeting['EVENT_ID'] > 0)
			{
				self::DeleteEvent($arMeeting['EVENT_ID']);
			}

			self::SetUsers($ID);
			self::DeleteFiles($ID);

			CMeetingInstance::DeleteByMeetingID($ID);

			if ($DB->Query("DELETE FROM b_meeting WHERE ID='".$ID."'"))
			{
				$DB->Query("UPDATE b_meeting SET PARENT_ID=NULL WHERE PARENT_ID='".$ID."'");

				foreach(GetModuleEvents("meeting", "OnAfterMeetingDelete", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array($ID));

				return true;
			}
		}

		return false;
	}

	public static function MakeDateTime($date, $time, $duration = 0)
	{
		global $DB;

		if (!IsAmPmMode())
		{
			$date_start = $date.' '.$time.':00';
			$date_start = FormatDate(
				$DB->DateFormatToPhp(FORMAT_DATETIME),
				MakeTimeStamp(
					$date.' '.$time,
					FORMAT_DATE.' HH:MI'
				) + intval($duration)
			);
		}
		else
		{
			$date_start = FormatDate(
				$DB->DateFormatToPhp(FORMAT_DATETIME),
				MakeTimeStamp(
					$date.' '.$time,
					FORMAT_DATE.' H:MI T'
				) + intval($duration)
			);
		}

		return $date_start;
	}

	public static function MakePlace($iblockId, $roomId)
	{
		return self::MEETING_ROOM_PREFIX.'_'.intval($iblockId).'_'.intval($roomId);
	}

	public static function CheckPlace($place)
	{
		if(strlen($place) > 0)
		{
			$matches = array();
			if(preg_match('/^'.self::MEETING_ROOM_PREFIX.'_([\d]+)_([\d]+)$/', $place, $matches))
			{
				return array(
					'ROOM_IBLOCK' => intval($matches[1]),
					'ROOM_ID' => intval($matches[2])
				);
			}
		}

		return false;
	}

	public static function IsNewCalendar()
	{
		return COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule('calendar');
	}

	public static function AddEvent($MEETING_ID, $arFields, $arParams = array())
	{
		global $USER;

		$EventID = false;

		if (self::IsNewCalendar())
		{
			$arEventFields = array(
				'ID' => $arFields['EVENT_ID'],
				'CAL_TYPE' => 'user',
				'OWNER_ID' => $arFields['OWNER_ID'],
				'DT_FROM' => $arFields['DATE_START'],
				'DT_TO' => ConvertTimeStamp(MakeTimeStamp($arFields['DATE_START']) + $arFields['DURATION'], 'FULL'),
				'NAME' => $arFields['TITLE'],
				'DESCRIPTION' => CCalendar::ParseHTMLToBB($arFields['DESCRIPTION']),
				'IS_MEETING' => true,
				'MEETING_HOST' => $arFields['OWNER_ID'],
				'MEETING' => array(
					'HOST_NAME' => CCalendar::GetUserName($arFields['OWNER_ID'])
				),
				'ATTENDEES' => array_keys($arFields['USERS']),
			);

			if($arFields['CURRENT_STATE'] == CMeeting::STATE_CLOSED)
			{
				$arEventFields['DT_TO'] = MakeTimeStamp($arFields['DATE_FINISH']) > MakeTimeStamp($arFields['DATE_START']) ? $arFields['DATE_FINISH'] : $arEventFields['DT_TO'];
			}

			$matches = array();
			if(preg_match('/^mr_([\d]+)_([\d]+)$/', $arFields["PLACE"], $matches))
			{
				$location = 'ECMR_'.$matches[2];
				if($arFields['EVENT_ID'] > 0)
				{
					$arCurrentEvent = CCalendarEvent::GetById($arFields['EVENT_ID']);
					if($arCurrentEvent['LOCATION'])
					{
						$res = CCalendar::ParseLocation($arCurrentEvent['LOCATION']);
						if($res['mrevid'])
						{
							$location .= '_'.$res['mrevid'];
						}
					}
				}
				$arEventFields['LOCATION'] = array('NEW' => $location);
			}
			else
			{
				$arEventFields['LOCATION'] = array('NEW' => $arFields['PLACE']);
			}

			if (isset($arFields['REINVITE']))
				$arEventFields['MEETING']['REINVITE'] = $arFields['REINVITE'];
			else
				$arEventFields['MEETING']['REINVITE'] = false;

			if (isset($arFields['NOTIFY']))
				$arEventFields['MEETING']['NOTIFY'] = $arFields['NOTIFY'];

			$EventID = CCalendar::SaveEvent(array(
				'arFields' => $arEventFields,
				'userId' => $arFields['OWNER_ID'],
				'autoDetectSection' => true,
				'autoCreateSection' => true
			));
		}
		elseif (!$arFields['EVENT_ID'])
		{

			$iblockId = $arParams['CALENDAR_IBLOCK_ID'] ? $arParams['CALENDAR_IBLOCK_ID'] : COption::GetOptionInt('intranet', 'iblock_calendar', 0, SITE_ID);

			$obCalendar = new CEventCalendar();
			$obCalendar->Init(array(
				'ownerType' => 'USER',
				'ownerId' => $USER->GetID(),
				'bOwner' => true,
				'iblockId' => $iblockId,
				'userIblockId' => $iblockId,
				'bCache' => false,
				'pathToUserCalendar' => '/company/personal/user/#user_id#/calendar/' // temporary hack until new calendars'll be ready
			));

			$guestCalendarId = false;
			$guestSection = $obCalendar->GetSectionIDByOwnerId($USER->GetID(), 'USER', $iblockId);
			$arGuestCalendars = array();

			if(!$guestSection) // Guest does not have any calendars
			{
				$guestSection = $obCalendar->CreateSectionForOwner($USER->GetID(), "USER", $iblockId);
			}

			$arGuestCalendars = $obCalendar->GetCalendars(array(
				'sectionId' => $guestSection,
				'iblockId' => $iblockId,
				'ownerType' => 'USER',
				'ownerId' => $USER->GetID(),
				'bOwner' => 1,
				'forExport' => true,
				'bOnlyID' => true
			));

			if(count($arGuestCalendars) > 0)
			{
				$arUserSet = $obCalendar->GetUserSettings(array('static' => false, 'userId' => $USER->GetID()));
				if ($arUserSet && isset($arUserSet['MeetCalId']) && in_array($arUserSet['MeetCalId'], $arGuestCalendars))
					$guestCalendarId = intVal($arUserSet['MeetCalId']);
				else
					$guestCalendarId = $arGuestCalendars[0];
			}

			//$bGroup = $arParams['GROUP_ID'] > 0;

			$arPermissions = $obCalendar->GetPermissions(
				array(
					'setProperties' => true
				)
			);

			$arEventFields = array(
				'iblockId' => $obCalendar->iblockId,
				'ownerType' => $obCalendar->ownerType,
				'ownerId' => $obCalendar->ownerId,
				'RMiblockId' => self::__getRMIblockID(),
				'allowResMeeting' => true,
				'bNew' => true,
				'fullUrl' => $obCalendar->fullUrl,
				'userId' => $obCalendar->userId,
				'pathToUserCalendar' => $obCalendar->pathToUserCalendar,
				'pathToGroupCalendar' => $obCalendar->pathToGroupCalendar,
				'userIblockId' => $obCalendar->iblockId,
				'calendarId' => $guestCalendarId,
				'sectionId' => $guestSection,
				'dateFrom' => $arFields['DATE_START'],
				'dateTo' => $arFields['DATE_FINISH'] ? $arFields['DATE_FINISH'] : ConvertTimeStamp(MakeTimeStamp($arFields['DATE_START']) + $arFields['DURATION'], 'FULL'),
				'name' => $arFields['TITLE'],
				'desc' => $arFields['DESCRIPTION'],
				'prop' => array(),
				'isMeeting' => true,
				'guests' => array_keys($arFields['USERS']),
				'notDisplayCalendar' => true,
			);

			if ($EventID = $obCalendar->SaveEvent($arEventFields))
			{
				CEventCalendar::ClearCache('/event_calendar/events/'.$arEventFields['iblockId'].'/');
				CEventCalendar::ClearCache('/event_calendar/events/'.$arEventFields['userIblockId'].'/');
			}
		}

		if ($EventID)
		{
			self::Update($MEETING_ID, array('EVENT_ID' => $EventID));
		}

		return $EventID;
	}

	public static function GetEvent($eventId)
	{
		if (self::IsNewCalendar())
		{
			$arEvent = CCalendarEvent::GetByID($eventId);
			if ($arEvent['LOCATION'])
				$arEvent['LOCATION'] = CCalendar::ParseLocation($arEvent['LOCATION']);
			return $arEvent;
		}
	}

	public static function GetEventGuests($eventId, $userId)
	{
		if (self::IsNewCalendar())
		{
			$res = array();

			$arAttendees = CCalendarEvent::GetAttendees($eventId);

			if (is_array($arAttendees) && is_array($arAttendees[$eventId]))
			{
				foreach ($arAttendees[$eventId] as $arGuest)
					$res[] = array('id' => $arGuest['USER_ID'], 'status' => $arGuest['STATUS']);
			}

			return $res;
		}
		else
		{
			$dbRes = CIBlockElement::GetByID($eventId);
			if ($arRes = $dbRes->Fetch())
			{
				$calIblockSection = $arRes['IBLOCK_SECTION_ID'];
				$calIblock = $arRes['IBLOCK_ID'];
			}

			CModule::IncludeModule('socialnetwork');

			$obCalendar = new CEventCalendar();
			$obCalendar->Init(array(
				'ownerType' => 'USER',
				'ownerId' => $userId,
				'bOwner' => true,
				'iblockId' => $calIblock,
				'userIblockId' => COption::GetOptionInt('intranet', 'iblock_calendar', 0, SITE_ID)
			));

			$arPermissions = $obCalendar->GetPermissions(
				array(
					'setProperties' => true,
				)
			);

			$arEvents = $obCalendar->GetEvents(array(
				'iblockId' => $calIblock,
				'sectionId' => $calIblockSection,
				'eventId' => $eventId,
				'bLoadAll' => true,
				'ownerType' => 'USER'
			));
			if ($event = $arEvents[0])
				return is_array($event['GUESTS']) ? array_values($event['GUESTS']) : array();
		}
	}

	public static function DeleteEvent($eventId)
	{
		if (self::IsNewCalendar())
		{
			CCalendarEvent::Delete(array(
				'id' => $eventId,
				'bMarkDeleted' => true,
			));
		}
	}

	public static function GetFilesData($arInput, $arFrom = null)
	{
		$arFiles = array();
		if (is_array($arInput) && count($arInput) > 0)
		{
			$dbFiles = CFile::GetList(array(), array("@ID" => implode(",", array_keys($arInput))));
			while ($arFile = $dbFiles->GetNext())
			{
				$fileSrc = intval($arInput[$arFile['ID']]);
				$fileUrl = CFile::GetFileSRC($arFile);
				$fileLink = $fileUrl;
				if (is_array($arFrom))
				{
					$fileLink = '/bitrix/tools/ajax_meeting.php?fileId='.$arFile['ID'];
					if ($arFrom['REPORT'])
						$fileLink .= '&reportId='.intval($arFrom['REPORT']);
					elseif ($arFrom['ITEM'])
						$fileLink .= '&itemId='.intval($arFrom['ITEM']);
					elseif ($arFrom['MEETING'])
						$fileLink .= '&meetingId='.intval($arFrom['MEETING']);
				}

				$arFiles[] = array(
					'ID' => $arFile['ID'],
					'ORIGINAL_NAME' => $arFile['ORIGINAL_NAME'],
					'FILE_SIZE' => $arFile['FILE_SIZE'],
					//'URL' => CHTTP::URN2URI($fileUrl),
					'DOWNLOAD_URL' => CHTTP::URN2URI($fileLink),
					'FILE_SIZE_FORMATTED' => CFile::FormatSize($arFile['FILE_SIZE']),
					'FILE_SRC' => $fileSrc,
				);
			}
		}
		return $arFiles;
	}

	protected static function __getRMIblockID()
	{
		static $RMIblockID = false;
		if ($RMIblockID === false)
		{
			$dbRes = CIBlock::GetList(array('SORT' => 'ASC'), array('CODE' => 'meeting_rooms'), false);
			if ($arRes = $dbRes->Fetch())
				$RMIblockID = $arRes['ID'];
		}

		return $RMIblockID;
	}

	protected static function CheckFields($action, &$arFields)
	{
		global $DB;

		if (isset($arFields['CURRENT_STATE']) && !in_array($arFields['CURRENT_STATE'], array(
			self::STATE_PREPARE,
			self::STATE_ACTION,
			self::STATE_CLOSED,
		)))
			unset($arFields['CURRENT_STATE']);

		unset($arFields['ID']);
		unset($arFields['TIMESTAMP_X']);

		if ($action == 'UPDATE')
			$arFields['~TIMESTAMP_X'] = $DB->GetNowFunction();

		return true;
	}

	protected static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	protected static function PrepareSql(&$arFields, $arOrder, &$arFilter, $arGroupBy, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
			{
				$cntField = count($arFieldsKeys);
				for ($i = 0; $i < $cntField; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if (strlen($strSqlSelect) > 0)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$cntFilter = count($filter_keys);
		for ($i = 0; $i < $cntFilter; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);
			else
				$vals = array_values($vals);

			$key = $filter_keys[$i];
			$key_res = self::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				$cVals = count($vals);
				for ($j = 0; $j < $cVals; $j++)
				{
					$val = $vals[$j];
					if (isset($arFields[$key]["WHERE"]))
					{
						$arSqlSearch_tmp1 = call_user_func_array(
								$arFields[$key]["WHERE"],
								array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
							);
						if ($arSqlSearch_tmp1 !== false)
							$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
					}
					else
					{
						if ($arFields[$key]["TYPE"] == "int")
						{
							if ((IntVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".IntVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "double")
						{
							$val = str_replace(",", ".", $val);

							if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						{
							if ($strOperation == "QUERY")
							{
								$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
							}
							else
							{
								if ((strlen($val) == 0) && (strpos($strOperation, "=") !== False))
									$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "datetime")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
						}
						elseif ($arFields[$key]["TYPE"] == "date")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				$c_tmp = count($arSqlSearch_tmp);
				for ($j = 0; $j < $c_tmp; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					else
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " (1=1) " : " (1=0) ");
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		$cntSearch = count($arSqlSearch);
		for ($i = 0; $i < $cntSearch; $i++)
		{
			if (strlen($strSqlWhere) > 0)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		$cntOrder = count($arSqlOrder);
		for ($i=0; $i<$cntOrder; $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			if(strtoupper($DB->type)=="ORACLE")
			{
				if(substr($arSqlOrder[$i], -3)=="ASC")
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
				else
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}
}
?>