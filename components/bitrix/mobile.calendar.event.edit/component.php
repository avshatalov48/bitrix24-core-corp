<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('calendar') || (!(isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized())))
	return;

$userId = $GLOBALS['USER']->GetID();
$eventId = intval($arParams['EVENT_ID']);
$arResult['NEW'] = !$eventId;

if (isset($_REQUEST['app_calendar_action']))
{
	if($_REQUEST['app_calendar_action'] == 'from_to_control')
	{
		$arResult['GET_FROM_TO_MODE'] = 'Y';
		$this->IncludeComponentTemplate();
		return;
	}

	$APPLICATION->RestartBuffer();
	if ($_REQUEST['app_calendar_action'] == 'save_event' && check_bitrix_sessid())
	{
		// Save event info
		$type = 'user';
		$ownerId = $userId;
		$id = intval($_POST['event_id']);
		$sectId = intval($_POST['sect_id']);
		$newMeeting = $_POST['new_meeting'] == 'Y';

		$arFields = array(
			"ID" => $id,
			"CAL_TYPE" => $_POST['cal_type'],
			"OWNER_ID" => $_POST['owner_id'],
			"DATE_FROM" => $_POST['from_date'],
			"DATE_TO" => $_POST['to_date'],
			"SKIP_TIME" => isset($_POST['skip_time']) && $_POST['skip_time'] == 'Y',
			'NAME' => trim($_POST['name']),
			'DESCRIPTION' => trim($_POST['desc']),
			'SECTIONS' => array($sectId),
			'ACCESSIBILITY' => $_POST['accessibility'],
			'IMPORTANCE' => $_POST['importance'],
			'PRIVATE_EVENT' => $_POST['private_event'] == "Y",
			"REMIND" => $_POST['remind'],
			'LOCATION' => array(),
			"IS_MEETING" => !empty($_POST['attendees'])
		);

		// LOCATION
		if (is_array($_POST['location']) && !empty($_POST['location']))
		{
			$arFields['LOCATION'] = $_POST['location'];
			$arFields['LOCATION']['CHANGED'] = $arFields['LOCATION']['CHANGED'] == 'Y';

			if ($arFields['LOCATION']['CHANGED'])
			{
				$loc = CCalendar::UnParseTextLocation($arFields['LOCATION']['NEW']);
				$arFields['LOCATION']['NEW'] = $loc['NEW'];
			}
		}

		if (isset($_POST['rrule']) && $_POST['rrule'] == '')
			$arFields['RRULE'] = '';

		if ($arFields['IS_MEETING'])
		{
			$arFields['ATTENDEES_CODES'] = array();
			$attendees = $_POST['attendees'];
			if (is_array($attendees))
			{
				foreach($attendees as $attId)
				{
					$arFields['ATTENDEES_CODES'][] = 'U'.intval($attId);
				}
			}

			if ($newMeeting && !in_array($ownerId, $attendees))
				$arFields['ATTENDEES_CODES'][] = 'U'.intval($ownerId);

			$arFields['ATTENDEES'] = CCalendar::GetDestinationUsers($arFields['ATTENDEES_CODES']);

			$arFields['MEETING_HOST'] = $ownerId;
			$arFields['MEETING'] = array(
				'HOST_NAME' => CCalendar::GetUserName($ownerId),
				'TEXT' => '',
				'OPEN' => false,
				'NOTIFY' => true,
				'REINVITE' => true
			);
		}

		$newId = CCalendar::SaveEvent(array(
			'arFields' => $arFields,
			'autoDetectSection' => true,
			'autoCreateSection' => true
		));
	}
	elseif($_REQUEST['app_calendar_action'] == 'drop_event' && check_bitrix_sessid())
	{
		$res = CCalendar::DeleteEvent(intval($_POST['event_id']));
	}

	die();
}

$calType = 'user';
$ownerId = $userId;

if ($arResult['NEW'])
{
}
else
{
	$event = CCalendarEvent::GetList(
		array(
			'arFilter' => array(
				"ID" => $eventId,
				"OWNER_ID" => $userId,
				"DELETED" => "N"
			),
			'parseRecursion' => false,
			'fetchAttendees' => true,
			'fetchMeetings' => true,
			'checkPermissions' => true,
			'setDefaultLimit' => false
		)
	);

	if ($event && is_array($event[0]))
	{
		$event = $event[0];

		if ($event['DT_SKIP_TIME'] !== "Y")
		{
			$fromTs = CCalendar::Timestamp($event['DATE_FROM']);
			$toTs = CCalendar::Timestamp($event['DATE_TO']);

			$fromTs -= $event['~USER_OFFSET_FROM'];
			$toTs -= $event['~USER_OFFSET_TO'];

			$event['DATE_FROM'] = CCalendar::Date($fromTs);
			$event['DATE_TO'] = CCalendar::Date($toTs);
		}

		if ($event['IS_MEETING'])
		{
			$arResult['ATTENDEES'] = [];
			if (is_array($event['ATTENDEE_LIST']))
			{
				$userIndex = CCalendarEvent::getUserIndex();
				foreach($event['ATTENDEE_LIST'] as $attendee)
				{
					if (isset($userIndex[$attendee['id']]))
					{
						$userIndex[$attendee['id']]['STATUS'] = $attendee['status'];
						$userIndex[$attendee['id']]['USER_ID'] = $attendee['id'];
						$arResult['ATTENDEES'][] = $userIndex[$attendee['id']];
					}
				}
			}
			elseif (is_array($event['~ATTENDEES']))
			{
				foreach($event['~ATTENDEES'] as $attendee)
				{
					$attendee['DISPLAY_NAME'] = CCalendar::GetUserName($attendee);
					$arResult['ATTENDEES'][] = $attendee;
				}
				unset($event['~ATTENDEES']);
			}
		}

		$event['~LOCATION'] = $event['LOCATION'] !== '' ? CCalendar::GetTextLocation($event["LOCATION"]) : '';
		if ($event['RRULE'] !== '')
		{
			if (!is_array($event['RRULE']))
				$event['RRULE'] = CCalendarEvent::ParseRRULE($event['RRULE']);
			if (is_array($event['RRULE']) && !isset($event['RRULE']['UNTIL']))
				$event['RRULE']['UNTIL'] = CCalendar::GetMaxDate();
		}

		$arResult['EVENT'] = $event;

		$calType = $event['CAL_TYPE'];
		$ownerId = $event['OWNER_ID'];
	}
	else
	{
		$event = array(); // Event is not found
		$arResult['DELETED'] = "Y";
		$arResult['EVENT_ID'] = $eventId;
	}
}

$arResult['CAL_TYPE'] = $calType;
$arResult['OWNER_ID'] = $ownerId;
$arResult['USER_ID'] = $userId;
$arResult['SECTIONS'] = array();

$sections = CCalendar::GetSectionList(array('CAL_TYPE' => $calType, 'OWNER_ID' => $ownerId));
if (empty($sections))
{
	$sections = array(CCalendarSect::CreateDefault(array(
		'type' => $calType,
		'ownerId' => $ownerId
	)));
}

foreach($sections as $sect)
{
	$arResult['SECTIONS'][] = array(
		'ID' => $sect['ID'],
		'NAME' => $sect['NAME']
	);
}

$this->IncludeComponentTemplate();
?>