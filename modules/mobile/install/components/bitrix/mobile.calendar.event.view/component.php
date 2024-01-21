<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('calendar') || (!(isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized())))
{
	return;
}

$event = false;
$userId = $GLOBALS['USER']->GetID();
if (isset($_REQUEST['app_calendar_action']) && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();
	if (
		$_REQUEST['app_calendar_action'] === 'change_meeting_status'
		&& !empty($userId)
		&& (int)$userId === (int)$_REQUEST['user_id']
	)
	{
		CCalendarEvent::SetMeetingStatus([
			'userId' => (int)$userId,
			'eventId' => (int)$_REQUEST['event_id'],
			'status' => $_REQUEST['status'] === 'Y' ? 'Y' : 'N'
		]);
	}
	\Bitrix\Main\Application::getInstance()->end();
}

$eventId = (int)$arParams['EVENT_ID'];
if (isset($_REQUEST['date_from']))
{
	$fromTs = CCalendar::Timestamp($_REQUEST['date_from']);
	$event = CCalendarEvent::GetList(
		array(
			'arFilter' => array(
				"PARENT_ID" => $eventId,
				"OWNER_ID" => $userId,
				"IS_MEETING" => 1,
				"DELETED" => "N",
				"FROM_LIMIT" => CCalendar::Date($fromTs - 3600),
				"TO_LIMIT" => CCalendar::Date($fromTs + CCalendar::GetDayLen(), false, false)
			),
			'parseRecursion' => true,
			'fetchAttendees' => true,
			'maxInstanceCount' => 1,
			'checkPermissions' => true,
			'setDefaultLimit' => false
		)
	);

	if (!$event || !is_array($event[0]))
	{
		$event = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $eventId,
					"DELETED" => "N",
					"FROM_LIMIT" => CCalendar::Date($fromTs - 3600),
					"TO_LIMIT" => CCalendar::Date($fromTs + CCalendar::GetDayLen(), false, false)
				),
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'maxInstanceCount' => 1,
				'checkPermissions' => true,
				'setDefaultLimit' => false
			)
		);
	}
}

if (!$event || !is_array($event[0]))
{
	$event = CCalendarEvent::GetList(
		array(
			'arFilter' => array(
				"PARENT_ID" => $eventId,
				"OWNER_ID" => $userId,
				"IS_MEETING" => 1,
				"DELETED" => "N"
			),
			'parseRecursion' => false,
			'fetchAttendees' => true,
			'checkPermissions' => true,
			'setDefaultLimit' => false
		)
	);
}

if (!$event || !is_array($event[0]))
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
			'checkPermissions' => true,
			'setDefaultLimit' => false
		)
	);
}

if ($event && is_array($event[0]))
{
	$event = $event[0];
	$event['DT_FROM_TS'] = CCalendar::Timestamp($event['DATE_FROM']);
	$event['DT_TO_TS'] = CCalendar::Timestamp($event['DATE_TO']);
	if ($event['DT_SKIP_TIME'] !== "Y")
	{
		$event['DT_FROM_TS'] -= $event['~USER_OFFSET_FROM'];
		$event['DT_TO_TS'] -= $event['~USER_OFFSET_TO'];

		$event['DATE_FROM'] = CCalendar::Date($event['DT_FROM_TS']);
		$event['DATE_TO'] = CCalendar::Date($event['DT_TO_TS']);
	}

	if ($event['IS_MEETING'])
	{
		$arResult['ATTENDEES'] = ['count' => 0,'Y' => [],'N' => [],'Q' => []];

		if (is_array($event['ATTENDEE_LIST']))
		{
			$userIndex = CCalendarEvent::getUserIndex();
			foreach($event['ATTENDEE_LIST'] as $attendee)
			{
				if (isset($userIndex[$attendee['id']]))
				{
					$userIndex[$attendee['id']]['STATUS'] = $attendee['status'];
					$userIndex[$attendee['id']]['USER_ID'] = $attendee['id'];
					$arResult['ATTENDEES'][$attendee['status']][] = $userIndex[$attendee['id']];
					$arResult['ATTENDEES']['count']++;
				}
			}
		}
		else
		{
			$attendees = $event['~ATTENDEES'];
			if (empty($attendees) && $event['PARENT_ID'])
			{
				$attRes = CCalendarEvent::GetAttendees(array($event['PARENT_ID']));
				if ($attRes && isset($attRes[$event['PARENT_ID']]))
				{
					$attendees =  $attRes[$event['PARENT_ID']];
				}
			}

			if (is_array($attendees))
			{
				foreach($attendees as $attendee)
				{
					$attendee['DISPLAY_NAME'] = CCalendar::GetUserName($attendee);
					$arResult['ATTENDEES'][$attendee['STATUS']][] = $attendee;
				}
				$arResult['ATTENDEES']['count'] = count($attendees);
			}
			unset($event['~ATTENDEES']);
		}

		if (!$arResult['ATTENDEES']['count'])
		{
			$event['IS_MEETING'] = false;
		}
	}

	if ($event['LOCATION'] !== '')
	{
		$event['LOCATION'] = CCalendar::GetTextLocation($event["LOCATION"]);
	}

	if ($event['RRULE'] !== '')
	{
		$event['RRULE'] = CCalendarEvent::ParseRRULE($event['RRULE']);
	}
}
else
{
	$event = array(); // Event is not found
	$arResult['DELETED'] = "Y";
}

$arResult['EVENT'] = $event;
$arResult['USER_ID'] = $userId;

$event = new \Bitrix\Main\Event(
	'calendar',
	'onViewEvent',
	array(
		'eventId' => $arResult['EVENT']['ID'],
	)
);
$event->send();

$this->IncludeComponentTemplate();
?>