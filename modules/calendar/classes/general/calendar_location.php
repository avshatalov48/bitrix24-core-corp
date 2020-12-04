<?
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class CCalendarLocation
{
	const TYPE = 'location';

	public static function enabled()
	{
		return true;
	}

	public static function getList($params = [])
	{
		$arFilter =  array(
			'CAL_TYPE' => self::TYPE
		);

		$sectionList = CCalendarSect::GetList(array('arFilter' => $arFilter));

		$res = array();
		foreach($sectionList as $sect)
		{
			$res[] = array(
				'ID' => $sect['ID'],
				'NAME' => $sect['NAME'],
				'PERM' => $sect['PERM'],
				'ACCESS' => $sect['ACCESS']
			);
		}
		return $res;
	}

	public static function getById($id)
	{
		$arFilter =  array(
			'CAL_TYPE' => self::TYPE,
			'ID' => intval($id)
		);
		$sectionList = CCalendarSect::GetList(array('arFilter' => $arFilter));

		$res = false;
		foreach($sectionList as $sect)
		{
			$res = array(
				'ID' => $sect['ID'],
				'NAME' => $sect['NAME'],
				'PERM' => $sect['PERM'],
				'ACCESS' => $sect['ACCESS']
			);
			break;
		}
		return $res;
	}

	public static function update($params = array())
	{
		CCalendarSect::Edit(array(
			'arFields' => array(
				'CAL_TYPE' => self::TYPE,
				'ID' => $params['id'],
				'NAME' => $params['name'],
				'ACCESS' => array()
			)
		));
	}

	public static function getRoomAccessibility($roomId, $from, $to, $params = array())
	{
		if (!isset($params['checkPermissions']))
		{
			$params['checkPermissions'] = true;
		}

		$accessibility = array();

		$roomEntries = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"FROM_LIMIT" => $from,
					"TO_LIMIT" => $to,
					"CAL_TYPE" => self::TYPE,
					"ACTIVE_SECTION" => "Y",
					"SECTION" => $roomId
				),
				'parseRecursion' => true,
				'fetchSection' => true,
				'setDefaultLimit' => false
			)
		);

		foreach($roomEntries as $roomEntry)
		{
			$accessibility[] = array(
				"ID" => $roomEntry["ID"],
				"NAME" => $roomEntry["NAME"],
				"DATE_FROM" => $roomEntry["DATE_FROM"],
				"DATE_TO" => $roomEntry["DATE_TO"],
				"~USER_OFFSET_FROM" => $roomEntry["~USER_OFFSET_FROM"],
				"~USER_OFFSET_TO" => $roomEntry["~USER_OFFSET_TO"],
				"DT_SKIP_TIME" => $roomEntry["DT_SKIP_TIME"],
				"TZ_FROM" => $roomEntry["TZ_FROM"],
				"TZ_TO" => $roomEntry["TZ_TO"],
				"ACCESSIBILITY" => $roomEntry["ACCESSIBILITY"],
				"IMPORTANCE" => $roomEntry["IMPORTANCE"],
				"EVENT_TYPE" => $roomEntry["EVENT_TYPE"]
			);
		}

		return $accessibility;
	}


	public static function delete($id)
	{
		CCalendarSect::Delete($id);
	}

	public static function clearCache($params = array())
	{
		CCalendar::ClearCache(array('section_list'));
	}

	public static function releaseRoom($params = array())
	{
		return CCalendar::DeleteEvent(intval($params['room_event_id']), false, ['checkPermissions' => false]);
	}

	public static function reserveRoom($params = array())
	{
		$roomEventId = CCalendarEvent::Edit([
			'arFields' => [
				'ID' => $params['room_event_id'],
				'CAL_TYPE' => self::TYPE,
				'SECTIONS' => $params['room_id'],
				'DATE_FROM' => $params['parentParams']['arFields']['DATE_FROM'],
				'DATE_TO' => $params['parentParams']['arFields']['DATE_TO'],
				'TZ_FROM' => $params['parentParams']['arFields']['TZ_FROM'],
				'TZ_TO' => $params['parentParams']['arFields']['TZ_TO'],
				'SKIP_TIME' => $params['parentParams']['arFields']['SKIP_TIME'],
				'NAME' => Loc::getMessage('EC_EDEV_EVENT').': '.$params['parentParams']['arFields']['NAME'],
				'RRULE' => $params['parentParams']['arFields']['RRULE'],
				'EXDATE' => $params['parentParams']['arFields']['EXDATE']
			]
		]);
		return $roomEventId;
	}

	public static function checkAccessibility($location = '', $params = [])
	{
		$location = CCalendar::ParseLocation($location);

		$res = true;
		if ($location['room_id'] || $location['mrid'])
		{
			$fromTs = CCalendar::Timestamp($params['fields']["DATE_FROM"]);
			$toTs = CCalendar::Timestamp($params['fields']["DATE_TO"]);
			$from = CCalendar::Date($fromTs, false);
			$to = CCalendar::Date($fromTs, false);

			$curUserId = CCalendar::GetCurUserId();
			$deltaOffset = isset($params['timezone']) ? (CCalendar::GetTimezoneOffset($params['timezone']) - CCalendar::GetCurrentOffsetUTC($curUserId)) : 0;

			if($location['mrid'])
			{
				$meetingRoomRes = CCalendar::GetAccessibilityForMeetingRoom(array(
					'allowReserveMeeting' => true,
					'id' => $location['mrid'],
					'from' => CCalendar::Date($fromTs - CCalendar::DAY_LENGTH, false),
					'to' => CCalendar::Date($toTs + CCalendar::DAY_LENGTH, false),
					'curEventId' => $location['mrevid']
				));

				foreach($meetingRoomRes as $entry)
				{
					if ($entry['ID'] != $location['mrevid'])
					{
						$entryfromTs = CCalendar::Timestamp($entry['DT_FROM']);
						$entrytoTs = CCalendar::Timestamp($entry['DT_TO']);

						if ($entryfromTs < $toTs && $entrytoTs > $fromTs)
						{
							$res = false;
							break;
						}
					}
				}
			}
			elseif ($location['room_id'])
			{
				$entries = CCalendarLocation::getRoomAccessibility($location['room_id'], $from, $to);
				foreach($entries as $entry)
				{
					if ($entry['ID'] != $location['room_event_id'])
					{
						$entryfromTs = CCalendar::Timestamp($entry['DATE_FROM']);
						$entrytoTs = CCalendar::Timestamp($entry['DATE_TO']);
						if($entry['DT_SKIP_TIME'] !== "Y")
						{
							$entryfromTs -= $entry['~USER_OFFSET_FROM'];
							$entrytoTs -= $entry['~USER_OFFSET_TO'];
							$entryfromTs += $deltaOffset;
							$entrytoTs += $deltaOffset;
						}

						if ($entryfromTs < $toTs && $entrytoTs > $fromTs)
						{
							$res = false;
							break;
						}
					}
				}
			}
		}

		return $res;
	}
}
?>