<?
IncludeModuleLangFile(__FILE__);

class CDavICalendarTimeZone
{
	public static function convertDateToTimeZone($date, $timeZoneId)
	{
		if (!isset(self::$arTimeZones[$timeZoneId]))
			return $date;

		$t = new CDavICalendarComponent();
		$t->InitializeFromString(self::$arTimeZones[$timeZoneId]);

		$offset = 0;
		$arTimeMap = array();

		$comps = $t->GetComponents();
		foreach ($comps as $comp)
		{
			$t = self::ParseVTimezone($comp, intval(date("Y", $date)));
			if ($t !== false)
				$arTimeMap[] = $t;
		}

		if ($arTimeMap)
		{
			usort($arTimeMap, function($a, $b) {return ($a['time'] > $b['time']) ? 1 : (($a['time'] < $b['time']) ? -1 : 0);});

			if ($date < $arTimeMap[0]['time'])
			{
				$offset = $arTimeMap[0]['from'];
			}
			else
			{
				$fl = true;
				for ($i = 0, $n = count($arTimeMap); $i < $n - 1; $i++)
				{
					if (($date >= $arTimeMap[$i]['time']) && ($date < $arTimeMap[$i + 1]['time']))
					{
						$fl = false;
						$offset = $arTimeMap[$i]['to'];
					}
				}

				if ($fl)
				{
					if ($date >= $arTimeMap[$n - 1]['time'])
						$offset = $arTimeMap[$n - 1]['to'];
				}
			}
		}

		return $date + $offset - date('Z');
	}

	public static function getTimeZoneId($userId = null, $date = null)
	{
		$dateKey = $date === null ? 0 : $date;
		$userIdKey = $userId === null ? 0 : $userId;
		if ($userId === null)
			$userId = $GLOBALS["USER"]->GetId();

		static $timezoneCache = array();
		if (isset($timezoneCache[$userIdKey]) && isset($timezoneCache[$userIdKey][$dateKey]))
			return $timezoneCache[$userIdKey][$dateKey];

		$autoTimeZone = $userZone = '';
		$factOffset = 0;

		if ($date === null)
			$date = time();

		static $userCache = array();

		if ($userId === null)
		{
			$autoTimeZone = trim($GLOBALS["USER"]->GetParam("AUTO_TIME_ZONE"));
			$userZone = $GLOBALS["USER"]->GetParam("TIME_ZONE");
		}
		else
		{
			if (!isset($userCache[$userId]))
			{
				$dbUser = CUser::GetList(($by = "id"), ($order = "asc"), array("ID_EQUAL_EXACT" => intval($userId)), array("FIELDS" => array("AUTO_TIME_ZONE", "TIME_ZONE", "TIME_ZONE_OFFSET")));
				if (($arUser = $dbUser->Fetch()))
				{
					$userCache[$userId] = array(
						"AUTO_TIME_ZONE" => trim($arUser["AUTO_TIME_ZONE"]),
						"TIME_ZONE" => $arUser["TIME_ZONE"],
						"TIME_ZONE_OFFSET" => $arUser["TIME_ZONE_OFFSET"]
					);
				}
			}

			if (isset($userCache[$userId]))
			{
				$autoTimeZone = $userCache[$userId]["AUTO_TIME_ZONE"];
				$userZone = $userCache[$userId]["TIME_ZONE"];
				$factOffset = $userCache[$userId]["TIME_ZONE_OFFSET"];
			}
		}

		if (CTimeZone::IsAutoTimeZone($autoTimeZone))
		{
			static $userOffsetCache = array();

			if (!isset($userOffsetCache[$userId === null ? 0 : $userId]))
				$userOffsetCache[$userIdKey] = CTimeZone::GetOffset($userId);

			$userOffset = $userOffsetCache[$userIdKey];

			$localTime = new DateTime();
			$localOffset = $localTime->getOffset();

			$timezoneCache[$userIdKey][$dateKey] = CDavICalendarTimeZone::getTimezoneByOffset($date, $userOffset + $localOffset);
		}
		else
		{
			if ($userZone != '' && isset(self::$arTimeZones[$userZone]))
			{
				$timezoneCache[$userIdKey][$dateKey] = $userZone;
			}
			else
			{
				$localTime = new DateTime();
				$localOffset = $localTime->getOffset();

				$timezoneCache[$userIdKey][$dateKey] = CDavICalendarTimeZone::getTimezoneByOffset($date, $factOffset + $localOffset);
			}
		}

		return $timezoneCache[$userIdKey][$dateKey];
	}

	public static function GetFormattedServerDateTime($text, $tzid = false, CDavICalendar $calendar = null)
	{
		$date = CDavICalendarTimeZone::ParseDateTime($text, $tzid, $calendar);
		return date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $date);
	}

	public static function GetFormattedServerDate($text)
	{
		if (preg_match('/(\+|-)([0-9]{2}):?([0-9]{2})([0-9]{2})?$/', $text, $match))
			$text = substr($text, 0, -strlen($match[0]));

		$date = CDavICalendarTimeZone::ParseDateTime($text, false, null);
		return date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $date);
	}

	public static function ParseDateTime($text, $tzid = false, CDavICalendar $calendar = null)
	{
		$arDateParts = explode('T', $text);

		if (count($arDateParts) != 2 && !empty($text))
		{
			if (!preg_match('/^(\d{4}-?\d{2}-?\d{2})(Z)?$/', $text, $match))
				return $text;

			$arDateParts = explode('T', $match[1].'T000000'.$match[2]);
		}

		if (($arDate = self::ParseDate($arDateParts[0])) == null)
			return $text;
		if (($arTime = self::ParseTime($arDateParts[1])) == null)
			return $text;

		$tzoffset = false;
		if ($arTime["zone"] == 'Local' && $tzid)
			$tzoffset = self::GetVTimezoneOffset($arDate, $arTime, $tzid, $calendar);

		$result = @mktime($arTime["hours"], $arTime["minutes"], $arTime["seconds"], $arDate["month"], $arDate["mday"], $arDate["year"]);

		if ($tzoffset)
			$result -= $tzoffset;

/*		if ($arTime["zone"] == 'UTC' || $tzoffset !== false)
		{
			$result = @gmmktime($arTime["hours"], $arTime["minutes"], $arTime["seconds"], $arDate["month"], $arDate["mday"], $arDate["year"]);
			if ($tzoffset)
				$result -= $tzoffset;
		}
		else
		{
			$result = @mktime($arTime["hours"], $arTime["minutes"], $arTime["seconds"], $arDate["month"], $arDate["mday"], $arDate["year"]);
		}*/

		if ($arTime["zone"] != 'Local' || $tzid)
			$result += date('Z');

		return ($result !== false) ? $result : $text;
	}

	public static function getTimezoneByOffset($date, $offset)
	{
		foreach (self::$arTimeZones as $timeZoneCode => $timeZoneText)
		{
			$t = new CDavICalendarComponent();
			$t->InitializeFromString($timeZoneText);
			if ($t)
			{
				$arTimeMap = array();

				$comps = $t->GetComponents();
				foreach ($comps as $comp)
				{
					$t = self::ParseVTimezone($comp, intval(date("Y", $date)));
					if ($t !== false)
						$arTimeMap[] = $t;
				}

				if ($arTimeMap)
				{
					usort($arTimeMap, function($a, $b) {return ($a['time'] > $b['time']) ? 1 : (($a['time'] < $b['time']) ? -1 : 0);});

					if ($date < $arTimeMap[0]['time'] && $arTimeMap[0]['from'] == $offset)
						return $timeZoneCode;

					for ($i = 0, $n = count($arTimeMap); $i < $n - 1; $i++)
					{
						if (($date >= $arTimeMap[$i]['time']) && ($date < $arTimeMap[$i + 1]['time']) && $arTimeMap[$i]['to'] == $offset)
							return $timeZoneCode;
					}

					if ($date >= $arTimeMap[$n - 1]['time'] && $arTimeMap[$n - 1]['to'] == $offset)
						return $timeZoneCode;
				}
			}
		}

		return false;
	}

	private static function GetVTimezoneOffset($arDate, $arTime, $tzid, $calendar)
	{
		$arVTimezones = $calendar->GetComponentsByProperty('VTIMEZONE', 'TZID', $tzid);
		if (!$arVTimezones)
			return false;

		$arTimeMap = array();
		foreach ($arVTimezones as $vtimezone)
		{
			foreach ($vtimezone->GetComponents() as $comp)
			{
				$t = self::ParseVTimezone($comp, $arDate["year"]);
				if ($t !== false)
					$arTimeMap[] = $t;
			}
		}

		if (!$arTimeMap)
			return false;

		sort($arTimeMap);

		$t = @gmmktime($arDate["hours"], $arDate["minutes"], $arDate["seconds"], $arDate["month"], $arDate["mday"], $arDate["year"]);

		if ($t < $arTimeMap[0]['time'])
			return $arTimeMap[0]['from'];

		for ($i = 0, $n = count($arTimeMap); $i < $n - 1; $i++)
		{
			if (($t >= $arTimeMap[$i]['time']) && ($t < $arTimeMap[$i + 1]['time']))
				return $arTimeMap[$i]['to'];
		}

		if ($t >= $arTimeMap[$n - 1]['time'])
			return $arTimeMap[$n - 1]['to'];

		return false;
	}

	private static function ParseDate($text)
	{
		$parts = explode('T', $text);
		if (count($parts) > 0)
			$text = $parts[0];

		if (!preg_match('/^(\d{4})-?(\d{2})-?(\d{2})$/', $text, $match))
			return null;

		return array("year" => $match[1], "month" => $match[2], "mday" => $match[3]);
	}

	private static function ParseTime($text)
	{
		if (!preg_match('/([0-9]{2}):?([0-9]{2}):?([0-9]{2})(Z)?/', $text, $match))
			return null;

		return array("hours" => intval($match[1]), "minutes" => intval($match[2]), "seconds" => intval($match[3]), "zone" => isset($match[4]) ? 'UTC' : 'Local');
	}

	private static function ParseUtcOffset($text)
	{
		if (strlen($text) <= 0)
			return null;
		if (!preg_match('/(\+|-)([0-9]{2})([0-9]{2})([0-9]{2})?/', $text, $match))
			return null;

		return array(
			"ahead" => ($match[1] == '+'),
			"hours" => intval($match[2]),
			"minutes" => intval($match[3]),
			"seconds" => (isset($match[4])) ? intval($match[4]) : 0
		);
	}

	private static function ParseVTimezone(CDavICalendarComponent $vtimezone, $year)
	{
		$result['time'] = 0;
		$rruleInterval = 0; // 0 undefined, 1 yearly, 12 monthly

		$t = self::ParseUtcOffset($vtimezone->GetPropertyValue('TZOFFSETFROM'));
		if ($t == null)
			return false;
		$result['from'] = ($t["hours"] * 60 * 60 + $t["minutes"] * 60) * ($t["ahead"] ? 1 : -1);

		$t = self::ParseUtcOffset($vtimezone->GetPropertyValue('TZOFFSETTO'));
		if ($t == null)
			return false;
		$result['to'] = ($t["hours"] * 60 * 60 + $t["minutes"] * 60) * ($t["ahead"] ? 1 : -1);

		$t = $vtimezone->GetPropertyValue('DTSTART');
		if ($t == null)
			return false;
		$switchTime = self::ParseDateTime($t);
		if (!is_int($switchTime))
			return false;

		$rrules = $vtimezone->GetPropertyValue('RRULE');
		if ($rrules == null)
		{
			$t = getdate($switchTime);
			$result['time'] = @gmmktime($t['hours'], $t['minutes'], $t['seconds'], $t['mon'], $t['mday'], $t['year']);
			return $result;
		}

		$switchYear = date("Y", $switchTime);
		if ($switchYear > $year)
			return false;

		$rrules = explode(';', $rrules);
		foreach ($rrules as $rrule)
		{
			$t = explode('=', $rrule);
			switch ($t[0])
			{
				case 'FREQ':
					switch($t[1])
					{
						case 'YEARLY':
							if ($rruleInterval == 12)
								return false;
							$rruleInterval = 1;
							break;
						case 'MONTHLY':
							if ($rruleInterval == 1)
								return false;
							$rruleInterval = 12;
							break;
						default:
							return false;
					}
					break;

				case 'INTERVAL':
					if ($rruleInterval && $t[1] != $rruleInterval)
						return false;
					$rruleInterval = intval($t[1]);
					if ($rruleInterval != 1 && $rruleInterval != 12)
						return false;
					break;

				case 'COUNT':
					if ($switchYear + intval($t[1]) < intval($year))
						return false;
					break;

				case 'BYMONTH':
					$month = intval($t[1]);
					break;

				case 'BYDAY':
					$len = strspn($t[1], '1234567890-+');
					if ($len == 0)
						return false;
					$weekday = substr($t[1], $len);
					$weekdays = array(
						'SU' => 0,
						'MO' => 1,
						'TU' => 2,
						'WE' => 3,
						'TH' => 4,
						'FR' => 5,
						'SA' => 6
					);
					$weekday = $weekdays[$weekday];
					$which = intval(substr($t[1], 0, $len));
					break;

				case 'UNTIL':
					if (intval($year) > intval(substr($t[1], 0, 4)))
						return false;
					break;
			}
		}

		if ($rruleInterval == 12)
			$month = date("n", $switchTime);

		if (empty($month) || !isset($weekday))
			return false;

		$switchTime = strftime('%H:%M:%S', $switchTime);
		$switchTime = explode(':', $switchTime);

		$when = gmmktime($switchTime[0], $switchTime[1], $switchTime[2], $month, 1, $year);
		$firstOfMonthWeekday = intval(gmstrftime('%w', $when));

		if ($weekday >= $firstOfMonthWeekday)
			$weekday -= 7;

		$when -= ($firstOfMonthWeekday - $weekday) * 60 * 60 * 24;

		if ($which < 0)
		{
			do
			{
				$when += 60*60*24*7;
			}
			while (intval(gmstrftime('%m', $when)) == $month);
		}

		$when += $which * 60 * 60 * 24 * 7;

		$result['time'] = $when;

		return $result;
	}

	public static function GetTimezone($tzid)
	{
		if (array_key_exists($tzid, self::$arTimeZones))
			return self::$arTimeZones[$tzid];

		return "";
	}

	public static function GetTimezoneList()
	{
		$ar = array();

		foreach (self::$arTimeZones as $key => $value)
		{
			$name = GetMessage("DAV_TZ_".$key);
			$ar[$key] = (!empty($name) > 0 ? $name : $key);
		}

		return $ar;
	}

	private static $arTimeZones = array(
	'Africa/Abidjan' => 'BEGIN:VTIMEZONE
TZID:Africa/Abidjan
X-LIC-LOCATION:Africa/Abidjan
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Accra' => 'BEGIN:VTIMEZONE
TZID:Africa/Accra
X-LIC-LOCATION:Africa/Accra
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Addis_Ababa' => 'BEGIN:VTIMEZONE
TZID:Africa/Addis_Ababa
X-LIC-LOCATION:Africa/Addis_Ababa
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Algiers' => 'BEGIN:VTIMEZONE
TZID:Africa/Algiers
X-LIC-LOCATION:Africa/Algiers
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Asmara' => 'BEGIN:VTIMEZONE
TZID:Africa/Asmara
X-LIC-LOCATION:Africa/Asmara
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Bamako' => 'BEGIN:VTIMEZONE
TZID:Africa/Bamako
X-LIC-LOCATION:Africa/Bamako
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Bangui' => 'BEGIN:VTIMEZONE
TZID:Africa/Bangui
X-LIC-LOCATION:Africa/Bangui
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Banjul' => 'BEGIN:VTIMEZONE
TZID:Africa/Banjul
X-LIC-LOCATION:Africa/Banjul
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Bissau' => 'BEGIN:VTIMEZONE
TZID:Africa/Bissau
X-LIC-LOCATION:Africa/Bissau
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Blantyre' => 'BEGIN:VTIMEZONE
TZID:Africa/Blantyre
X-LIC-LOCATION:Africa/Blantyre
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Brazzaville' => 'BEGIN:VTIMEZONE
TZID:Africa/Brazzaville
X-LIC-LOCATION:Africa/Brazzaville
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Bujumbura' => 'BEGIN:VTIMEZONE
TZID:Africa/Bujumbura
X-LIC-LOCATION:Africa/Bujumbura
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Cairo' => 'BEGIN:VTIMEZONE
TZID:Africa/Cairo
X-LIC-LOCATION:Africa/Cairo
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19700924T235959
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=-1TH
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700424T010000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=-1FR
END:DAYLIGHT
END:VTIMEZONE
',
	'Africa/Casablanca' => 'BEGIN:VTIMEZONE
TZID:Africa/Casablanca
X-LIC-LOCATION:Africa/Casablanca
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:WET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Africa/Ceuta' => 'BEGIN:VTIMEZONE
TZID:Africa/Ceuta
X-LIC-LOCATION:Africa/Ceuta
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Africa/Conakry' => 'BEGIN:VTIMEZONE
TZID:Africa/Conakry
X-LIC-LOCATION:Africa/Conakry
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Dakar' => 'BEGIN:VTIMEZONE
TZID:Africa/Dakar
X-LIC-LOCATION:Africa/Dakar
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Dar_es_Salaam' => 'BEGIN:VTIMEZONE
TZID:Africa/Dar_es_Salaam
X-LIC-LOCATION:Africa/Dar_es_Salaam
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Djibouti' => 'BEGIN:VTIMEZONE
TZID:Africa/Djibouti
X-LIC-LOCATION:Africa/Djibouti
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Douala' => 'BEGIN:VTIMEZONE
TZID:Africa/Douala
X-LIC-LOCATION:Africa/Douala
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/El_Aaiun' => 'BEGIN:VTIMEZONE
TZID:Africa/El_Aaiun
X-LIC-LOCATION:Africa/El_Aaiun
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:WET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Africa/Freetown' => 'BEGIN:VTIMEZONE
TZID:Africa/Freetown
X-LIC-LOCATION:Africa/Freetown
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Gaborone' => 'BEGIN:VTIMEZONE
TZID:Africa/Gaborone
X-LIC-LOCATION:Africa/Gaborone
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Harare' => 'BEGIN:VTIMEZONE
TZID:Africa/Harare
X-LIC-LOCATION:Africa/Harare
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Johannesburg' => 'BEGIN:VTIMEZONE
TZID:Africa/Johannesburg
X-LIC-LOCATION:Africa/Johannesburg
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:SAST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Juba' => 'BEGIN:VTIMEZONE
TZID:Africa/Juba
X-LIC-LOCATION:Africa/Juba
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Kampala' => 'BEGIN:VTIMEZONE
TZID:Africa/Kampala
X-LIC-LOCATION:Africa/Kampala
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Khartoum' => 'BEGIN:VTIMEZONE
TZID:Africa/Khartoum
X-LIC-LOCATION:Africa/Khartoum
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Kigali' => 'BEGIN:VTIMEZONE
TZID:Africa/Kigali
X-LIC-LOCATION:Africa/Kigali
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Kinshasa' => 'BEGIN:VTIMEZONE
TZID:Africa/Kinshasa
X-LIC-LOCATION:Africa/Kinshasa
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Lagos' => 'BEGIN:VTIMEZONE
TZID:Africa/Lagos
X-LIC-LOCATION:Africa/Lagos
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Libreville' => 'BEGIN:VTIMEZONE
TZID:Africa/Libreville
X-LIC-LOCATION:Africa/Libreville
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Lome' => 'BEGIN:VTIMEZONE
TZID:Africa/Lome
X-LIC-LOCATION:Africa/Lome
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Luanda' => 'BEGIN:VTIMEZONE
TZID:Africa/Luanda
X-LIC-LOCATION:Africa/Luanda
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Lubumbashi' => 'BEGIN:VTIMEZONE
TZID:Africa/Lubumbashi
X-LIC-LOCATION:Africa/Lubumbashi
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Lusaka' => 'BEGIN:VTIMEZONE
TZID:Africa/Lusaka
X-LIC-LOCATION:Africa/Lusaka
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Malabo' => 'BEGIN:VTIMEZONE
TZID:Africa/Malabo
X-LIC-LOCATION:Africa/Malabo
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Maputo' => 'BEGIN:VTIMEZONE
TZID:Africa/Maputo
X-LIC-LOCATION:Africa/Maputo
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:CAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Maseru' => 'BEGIN:VTIMEZONE
TZID:Africa/Maseru
X-LIC-LOCATION:Africa/Maseru
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:SAST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Mbabane' => 'BEGIN:VTIMEZONE
TZID:Africa/Mbabane
X-LIC-LOCATION:Africa/Mbabane
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:SAST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Mogadishu' => 'BEGIN:VTIMEZONE
TZID:Africa/Mogadishu
X-LIC-LOCATION:Africa/Mogadishu
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Monrovia' => 'BEGIN:VTIMEZONE
TZID:Africa/Monrovia
X-LIC-LOCATION:Africa/Monrovia
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Nairobi' => 'BEGIN:VTIMEZONE
TZID:Africa/Nairobi
X-LIC-LOCATION:Africa/Nairobi
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Ndjamena' => 'BEGIN:VTIMEZONE
TZID:Africa/Ndjamena
X-LIC-LOCATION:Africa/Ndjamena
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Niamey' => 'BEGIN:VTIMEZONE
TZID:Africa/Niamey
X-LIC-LOCATION:Africa/Niamey
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Nouakchott' => 'BEGIN:VTIMEZONE
TZID:Africa/Nouakchott
X-LIC-LOCATION:Africa/Nouakchott
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Ouagadougou' => 'BEGIN:VTIMEZONE
TZID:Africa/Ouagadougou
X-LIC-LOCATION:Africa/Ouagadougou
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Porto-Novo' => 'BEGIN:VTIMEZONE
TZID:Africa/Porto-Novo
X-LIC-LOCATION:Africa/Porto-Novo
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Sao_Tome' => 'BEGIN:VTIMEZONE
TZID:Africa/Sao_Tome
X-LIC-LOCATION:Africa/Sao_Tome
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Tripoli' => 'BEGIN:VTIMEZONE
TZID:Africa/Tripoli
X-LIC-LOCATION:Africa/Tripoli
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Tunis' => 'BEGIN:VTIMEZONE
TZID:Africa/Tunis
X-LIC-LOCATION:Africa/Tunis
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Africa/Windhoek' => 'BEGIN:VTIMEZONE
TZID:Africa/Windhoek
X-LIC-LOCATION:Africa/Windhoek
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:WAST
DTSTART:19700906T020000
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:WAT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Adak' => 'BEGIN:VTIMEZONE
TZID:America/Adak
X-LIC-LOCATION:America/Adak
BEGIN:DAYLIGHT
TZOFFSETFROM:-1000
TZOFFSETTO:-0900
TZNAME:HADT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0900
TZOFFSETTO:-1000
TZNAME:HAST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Anchorage' => 'BEGIN:VTIMEZONE
TZID:America/Anchorage
X-LIC-LOCATION:America/Anchorage
BEGIN:DAYLIGHT
TZOFFSETFROM:-0900
TZOFFSETTO:-0800
TZNAME:AKDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0800
TZOFFSETTO:-0900
TZNAME:AKST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Anguilla' => 'BEGIN:VTIMEZONE
TZID:America/Anguilla
X-LIC-LOCATION:America/Anguilla
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Antigua' => 'BEGIN:VTIMEZONE
TZID:America/Antigua
X-LIC-LOCATION:America/Antigua
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Araguaina' => 'BEGIN:VTIMEZONE
TZID:America/Araguaina
X-LIC-LOCATION:America/Araguaina
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Buenos_Aires' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Buenos_Aires
X-LIC-LOCATION:America/Argentina/Buenos_Aires
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Catamarca' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Catamarca
X-LIC-LOCATION:America/Argentina/Catamarca
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Cordoba' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Cordoba
X-LIC-LOCATION:America/Argentina/Cordoba
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Jujuy' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Jujuy
X-LIC-LOCATION:America/Argentina/Jujuy
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/La_Rioja' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/La_Rioja
X-LIC-LOCATION:America/Argentina/La_Rioja
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Mendoza' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Mendoza
X-LIC-LOCATION:America/Argentina/Mendoza
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Rio_Gallegos' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Rio_Gallegos
X-LIC-LOCATION:America/Argentina/Rio_Gallegos
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Salta' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Salta
X-LIC-LOCATION:America/Argentina/Salta
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/San_Juan' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/San_Juan
X-LIC-LOCATION:America/Argentina/San_Juan
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/San_Luis' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/San_Luis
X-LIC-LOCATION:America/Argentina/San_Luis
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Tucuman' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Tucuman
X-LIC-LOCATION:America/Argentina/Tucuman
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Argentina/Ushuaia' => 'BEGIN:VTIMEZONE
TZID:America/Argentina/Ushuaia
X-LIC-LOCATION:America/Argentina/Ushuaia
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Aruba' => 'BEGIN:VTIMEZONE
TZID:America/Aruba
X-LIC-LOCATION:America/Aruba
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Asuncion' => 'BEGIN:VTIMEZONE
TZID:America/Asuncion
X-LIC-LOCATION:America/Asuncion
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:PYST
DTSTART:19701004T000000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:PYT
DTSTART:19700322T000000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=4SU
END:STANDARD
END:VTIMEZONE
',
	'America/Atikokan' => 'BEGIN:VTIMEZONE
TZID:America/Atikokan
X-LIC-LOCATION:America/Atikokan
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Bahia' => 'BEGIN:VTIMEZONE
TZID:America/Bahia
X-LIC-LOCATION:America/Bahia
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Bahia_Banderas' => 'BEGIN:VTIMEZONE
TZID:America/Bahia_Banderas
X-LIC-LOCATION:America/Bahia_Banderas
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/Barbados' => 'BEGIN:VTIMEZONE
TZID:America/Barbados
X-LIC-LOCATION:America/Barbados
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Belem' => 'BEGIN:VTIMEZONE
TZID:America/Belem
X-LIC-LOCATION:America/Belem
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Belize' => 'BEGIN:VTIMEZONE
TZID:America/Belize
X-LIC-LOCATION:America/Belize
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Blanc-Sablon' => 'BEGIN:VTIMEZONE
TZID:America/Blanc-Sablon
X-LIC-LOCATION:America/Blanc-Sablon
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Boa_Vista' => 'BEGIN:VTIMEZONE
TZID:America/Boa_Vista
X-LIC-LOCATION:America/Boa_Vista
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Bogota' => 'BEGIN:VTIMEZONE
TZID:America/Bogota
X-LIC-LOCATION:America/Bogota
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:COT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Boise' => 'BEGIN:VTIMEZONE
TZID:America/Boise
X-LIC-LOCATION:America/Boise
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Cambridge_Bay' => 'BEGIN:VTIMEZONE
TZID:America/Cambridge_Bay
X-LIC-LOCATION:America/Cambridge_Bay
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Campo_Grande' => 'BEGIN:VTIMEZONE
TZID:America/Campo_Grande
X-LIC-LOCATION:America/Campo_Grande
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:AMST
DTSTART:19701018T000000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=3SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AMT
DTSTART:19700215T000000
RRULE:FREQ=YEARLY;BYMONTH=2;BYDAY=3SU
END:STANDARD
END:VTIMEZONE
',
	'America/Cancun' => 'BEGIN:VTIMEZONE
TZID:America/Cancun
X-LIC-LOCATION:America/Cancun
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Caracas' => 'BEGIN:VTIMEZONE
TZID:America/Caracas
X-LIC-LOCATION:America/Caracas
BEGIN:STANDARD
TZOFFSETFROM:-0430
TZOFFSETTO:-0430
TZNAME:VET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Cayenne' => 'BEGIN:VTIMEZONE
TZID:America/Cayenne
X-LIC-LOCATION:America/Cayenne
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:GFT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Cayman' => 'BEGIN:VTIMEZONE
TZID:America/Cayman
X-LIC-LOCATION:America/Cayman
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Chicago' => 'BEGIN:VTIMEZONE
TZID:America/Chicago
X-LIC-LOCATION:America/Chicago
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Chihuahua' => 'BEGIN:VTIMEZONE
TZID:America/Chihuahua
X-LIC-LOCATION:America/Chihuahua
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Costa_Rica' => 'BEGIN:VTIMEZONE
TZID:America/Costa_Rica
X-LIC-LOCATION:America/Costa_Rica
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Creston' => 'BEGIN:VTIMEZONE
TZID:America/Creston
X-LIC-LOCATION:America/Creston
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Cuiaba' => 'BEGIN:VTIMEZONE
TZID:America/Cuiaba
X-LIC-LOCATION:America/Cuiaba
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:AMST
DTSTART:19701018T000000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=3SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AMT
DTSTART:19700215T000000
RRULE:FREQ=YEARLY;BYMONTH=2;BYDAY=3SU
END:STANDARD
END:VTIMEZONE
',
	'America/Curacao' => 'BEGIN:VTIMEZONE
TZID:America/Curacao
X-LIC-LOCATION:America/Curacao
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Danmarkshavn' => 'BEGIN:VTIMEZONE
TZID:America/Danmarkshavn
X-LIC-LOCATION:America/Danmarkshavn
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Dawson' => 'BEGIN:VTIMEZONE
TZID:America/Dawson
X-LIC-LOCATION:America/Dawson
BEGIN:DAYLIGHT
TZOFFSETFROM:-0800
TZOFFSETTO:-0700
TZNAME:PDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Dawson_Creek' => 'BEGIN:VTIMEZONE
TZID:America/Dawson_Creek
X-LIC-LOCATION:America/Dawson_Creek
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Denver' => 'BEGIN:VTIMEZONE
TZID:America/Denver
X-LIC-LOCATION:America/Denver
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Detroit' => 'BEGIN:VTIMEZONE
TZID:America/Detroit
X-LIC-LOCATION:America/Detroit
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Dominica' => 'BEGIN:VTIMEZONE
TZID:America/Dominica
X-LIC-LOCATION:America/Dominica
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Edmonton' => 'BEGIN:VTIMEZONE
TZID:America/Edmonton
X-LIC-LOCATION:America/Edmonton
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Eirunepe' => 'BEGIN:VTIMEZONE
TZID:America/Eirunepe
X-LIC-LOCATION:America/Eirunepe
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:ACT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/El_Salvador' => 'BEGIN:VTIMEZONE
TZID:America/El_Salvador
X-LIC-LOCATION:America/El_Salvador
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Fortaleza' => 'BEGIN:VTIMEZONE
TZID:America/Fortaleza
X-LIC-LOCATION:America/Fortaleza
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Glace_Bay' => 'BEGIN:VTIMEZONE
TZID:America/Glace_Bay
X-LIC-LOCATION:America/Glace_Bay
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:ADT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Godthab' => 'BEGIN:VTIMEZONE
TZID:America/Godthab
X-LIC-LOCATION:America/Godthab
BEGIN:DAYLIGHT
TZOFFSETFROM:-0300
TZOFFSETTO:-0200
TZNAME:WGST
DTSTART:19700328T220000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SA
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0200
TZOFFSETTO:-0300
TZNAME:WGT
DTSTART:19701024T230000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SA
END:STANDARD
END:VTIMEZONE
',
	'America/Goose_Bay' => 'BEGIN:VTIMEZONE
TZID:America/Goose_Bay
X-LIC-LOCATION:America/Goose_Bay
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:ADT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/Grand_Turk' => 'BEGIN:VTIMEZONE
TZID:America/Grand_Turk
X-LIC-LOCATION:America/Grand_Turk
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Grenada' => 'BEGIN:VTIMEZONE
TZID:America/Grenada
X-LIC-LOCATION:America/Grenada
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Guadeloupe' => 'BEGIN:VTIMEZONE
TZID:America/Guadeloupe
X-LIC-LOCATION:America/Guadeloupe
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Guatemala' => 'BEGIN:VTIMEZONE
TZID:America/Guatemala
X-LIC-LOCATION:America/Guatemala
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Guayaquil' => 'BEGIN:VTIMEZONE
TZID:America/Guayaquil
X-LIC-LOCATION:America/Guayaquil
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:ECT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Guyana' => 'BEGIN:VTIMEZONE
TZID:America/Guyana
X-LIC-LOCATION:America/Guyana
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:GYT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Halifax' => 'BEGIN:VTIMEZONE
TZID:America/Halifax
X-LIC-LOCATION:America/Halifax
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:ADT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Havana' => 'BEGIN:VTIMEZONE
TZID:America/Havana
X-LIC-LOCATION:America/Havana
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:CST
DTSTART:19701101T010000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:CDT
DTSTART:19700308T000000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/Hermosillo' => 'BEGIN:VTIMEZONE
TZID:America/Hermosillo
X-LIC-LOCATION:America/Hermosillo
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Indianapolis' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Indianapolis
X-LIC-LOCATION:America/Indiana/Indianapolis
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Knox' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Knox
X-LIC-LOCATION:America/Indiana/Knox
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Marengo' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Marengo
X-LIC-LOCATION:America/Indiana/Marengo
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Petersburg' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Petersburg
X-LIC-LOCATION:America/Indiana/Petersburg
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Tell_City' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Tell_City
X-LIC-LOCATION:America/Indiana/Tell_City
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Vevay' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Vevay
X-LIC-LOCATION:America/Indiana/Vevay
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Vincennes' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Vincennes
X-LIC-LOCATION:America/Indiana/Vincennes
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Indiana/Winamac' => 'BEGIN:VTIMEZONE
TZID:America/Indiana/Winamac
X-LIC-LOCATION:America/Indiana/Winamac
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/Inuvik' => 'BEGIN:VTIMEZONE
TZID:America/Inuvik
X-LIC-LOCATION:America/Inuvik
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Iqaluit' => 'BEGIN:VTIMEZONE
TZID:America/Iqaluit
X-LIC-LOCATION:America/Iqaluit
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Jamaica' => 'BEGIN:VTIMEZONE
TZID:America/Jamaica
X-LIC-LOCATION:America/Jamaica
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Juneau' => 'BEGIN:VTIMEZONE
TZID:America/Juneau
X-LIC-LOCATION:America/Juneau
BEGIN:DAYLIGHT
TZOFFSETFROM:-0900
TZOFFSETTO:-0800
TZNAME:AKDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0800
TZOFFSETTO:-0900
TZNAME:AKST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Kentucky/Louisville' => 'BEGIN:VTIMEZONE
TZID:America/Kentucky/Louisville
X-LIC-LOCATION:America/Kentucky/Louisville
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Kentucky/Monticello' => 'BEGIN:VTIMEZONE
TZID:America/Kentucky/Monticello
X-LIC-LOCATION:America/Kentucky/Monticello
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Kralendijk' => 'BEGIN:VTIMEZONE
TZID:America/Kralendijk
X-LIC-LOCATION:America/Kralendijk
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/La_Paz' => 'BEGIN:VTIMEZONE
TZID:America/La_Paz
X-LIC-LOCATION:America/La_Paz
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:BOT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Lima' => 'BEGIN:VTIMEZONE
TZID:America/Lima
X-LIC-LOCATION:America/Lima
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:PET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Los_Angeles' => 'BEGIN:VTIMEZONE
TZID:America/Los_Angeles
X-LIC-LOCATION:America/Los_Angeles
BEGIN:DAYLIGHT
TZOFFSETFROM:-0800
TZOFFSETTO:-0700
TZNAME:PDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Lower_Princes' => 'BEGIN:VTIMEZONE
TZID:America/Lower_Princes
X-LIC-LOCATION:America/Lower_Princes
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Maceio' => 'BEGIN:VTIMEZONE
TZID:America/Maceio
X-LIC-LOCATION:America/Maceio
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Managua' => 'BEGIN:VTIMEZONE
TZID:America/Managua
X-LIC-LOCATION:America/Managua
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Manaus' => 'BEGIN:VTIMEZONE
TZID:America/Manaus
X-LIC-LOCATION:America/Manaus
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Marigot' => 'BEGIN:VTIMEZONE
TZID:America/Marigot
X-LIC-LOCATION:America/Marigot
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Martinique' => 'BEGIN:VTIMEZONE
TZID:America/Martinique
X-LIC-LOCATION:America/Martinique
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Matamoros' => 'BEGIN:VTIMEZONE
TZID:America/Matamoros
X-LIC-LOCATION:America/Matamoros
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Mazatlan' => 'BEGIN:VTIMEZONE
TZID:America/Mazatlan
X-LIC-LOCATION:America/Mazatlan
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Menominee' => 'BEGIN:VTIMEZONE
TZID:America/Menominee
X-LIC-LOCATION:America/Menominee
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Merida' => 'BEGIN:VTIMEZONE
TZID:America/Merida
X-LIC-LOCATION:America/Merida
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Metlakatla' => 'BEGIN:VTIMEZONE
TZID:America/Metlakatla
X-LIC-LOCATION:America/Metlakatla
BEGIN:STANDARD
TZOFFSETFROM:-0800
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Mexico_City' => 'BEGIN:VTIMEZONE
TZID:America/Mexico_City
X-LIC-LOCATION:America/Mexico_City
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Miquelon' => 'BEGIN:VTIMEZONE
TZID:America/Miquelon
X-LIC-LOCATION:America/Miquelon
BEGIN:DAYLIGHT
TZOFFSETFROM:-0300
TZOFFSETTO:-0200
TZNAME:PMDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0200
TZOFFSETTO:-0300
TZNAME:PMST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Moncton' => 'BEGIN:VTIMEZONE
TZID:America/Moncton
X-LIC-LOCATION:America/Moncton
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:ADT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Monterrey' => 'BEGIN:VTIMEZONE
TZID:America/Monterrey
X-LIC-LOCATION:America/Monterrey
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Montevideo' => 'BEGIN:VTIMEZONE
TZID:America/Montevideo
X-LIC-LOCATION:America/Montevideo
BEGIN:DAYLIGHT
TZOFFSETFROM:-0300
TZOFFSETTO:-0200
TZNAME:UYST
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0200
TZOFFSETTO:-0300
TZNAME:UYT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:STANDARD
END:VTIMEZONE
',
	'America/Montreal' => 'BEGIN:VTIMEZONE
TZID:America/Montreal
X-LIC-LOCATION:America/Montreal
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Montserrat' => 'BEGIN:VTIMEZONE
TZID:America/Montserrat
X-LIC-LOCATION:America/Montserrat
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Nassau' => 'BEGIN:VTIMEZONE
TZID:America/Nassau
X-LIC-LOCATION:America/Nassau
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/New_York' => 'BEGIN:VTIMEZONE
TZID:America/New_York
X-LIC-LOCATION:America/New_York
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Nipigon' => 'BEGIN:VTIMEZONE
TZID:America/Nipigon
X-LIC-LOCATION:America/Nipigon
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Nome' => 'BEGIN:VTIMEZONE
TZID:America/Nome
X-LIC-LOCATION:America/Nome
BEGIN:DAYLIGHT
TZOFFSETFROM:-0900
TZOFFSETTO:-0800
TZNAME:AKDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0800
TZOFFSETTO:-0900
TZNAME:AKST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Noronha' => 'BEGIN:VTIMEZONE
TZID:America/Noronha
X-LIC-LOCATION:America/Noronha
BEGIN:STANDARD
TZOFFSETFROM:-0200
TZOFFSETTO:-0200
TZNAME:FNT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/North_Dakota/Beulah' => 'BEGIN:VTIMEZONE
TZID:America/North_Dakota/Beulah
X-LIC-LOCATION:America/North_Dakota/Beulah
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/North_Dakota/Center' => 'BEGIN:VTIMEZONE
TZID:America/North_Dakota/Center
X-LIC-LOCATION:America/North_Dakota/Center
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/North_Dakota/New_Salem' => 'BEGIN:VTIMEZONE
TZID:America/North_Dakota/New_Salem
X-LIC-LOCATION:America/North_Dakota/New_Salem
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Ojinaga' => 'BEGIN:VTIMEZONE
TZID:America/Ojinaga
X-LIC-LOCATION:America/Ojinaga
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Panama' => 'BEGIN:VTIMEZONE
TZID:America/Panama
X-LIC-LOCATION:America/Panama
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Pangnirtung' => 'BEGIN:VTIMEZONE
TZID:America/Pangnirtung
X-LIC-LOCATION:America/Pangnirtung
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Paramaribo' => 'BEGIN:VTIMEZONE
TZID:America/Paramaribo
X-LIC-LOCATION:America/Paramaribo
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:SRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Phoenix' => 'BEGIN:VTIMEZONE
TZID:America/Phoenix
X-LIC-LOCATION:America/Phoenix
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Port-au-Prince' => 'BEGIN:VTIMEZONE
TZID:America/Port-au-Prince
X-LIC-LOCATION:America/Port-au-Prince
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Port_of_Spain' => 'BEGIN:VTIMEZONE
TZID:America/Port_of_Spain
X-LIC-LOCATION:America/Port_of_Spain
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Porto_Velho' => 'BEGIN:VTIMEZONE
TZID:America/Porto_Velho
X-LIC-LOCATION:America/Porto_Velho
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Puerto_Rico' => 'BEGIN:VTIMEZONE
TZID:America/Puerto_Rico
X-LIC-LOCATION:America/Puerto_Rico
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Rainy_River' => 'BEGIN:VTIMEZONE
TZID:America/Rainy_River
X-LIC-LOCATION:America/Rainy_River
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Rankin_Inlet' => 'BEGIN:VTIMEZONE
TZID:America/Rankin_Inlet
X-LIC-LOCATION:America/Rankin_Inlet
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Recife' => 'BEGIN:VTIMEZONE
TZID:America/Recife
X-LIC-LOCATION:America/Recife
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Regina' => 'BEGIN:VTIMEZONE
TZID:America/Regina
X-LIC-LOCATION:America/Regina
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Resolute' => 'BEGIN:VTIMEZONE
TZID:America/Resolute
X-LIC-LOCATION:America/Resolute
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/Rio_Branco' => 'BEGIN:VTIMEZONE
TZID:America/Rio_Branco
X-LIC-LOCATION:America/Rio_Branco
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0500
TZNAME:ACT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Santa_Isabel' => 'BEGIN:VTIMEZONE
TZID:America/Santa_Isabel
X-LIC-LOCATION:America/Santa_Isabel
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0800
TZOFFSETTO:-0700
TZNAME:PDT
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/Santarem' => 'BEGIN:VTIMEZONE
TZID:America/Santarem
X-LIC-LOCATION:America/Santarem
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Santiago' => 'BEGIN:VTIMEZONE
TZID:America/Santiago
X-LIC-LOCATION:America/Santiago
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:CLT
DTSTART:19700426T000000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:CLST
DTSTART:19700906T000000
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/Santo_Domingo' => 'BEGIN:VTIMEZONE
TZID:America/Santo_Domingo
X-LIC-LOCATION:America/Santo_Domingo
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Sao_Paulo' => 'BEGIN:VTIMEZONE
TZID:America/Sao_Paulo
X-LIC-LOCATION:America/Sao_Paulo
BEGIN:DAYLIGHT
TZOFFSETFROM:-0300
TZOFFSETTO:-0200
TZNAME:BRST
DTSTART:19701018T000000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=3SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:BRT
DTSTART:19700215T000000
RRULE:FREQ=YEARLY;BYMONTH=2;BYDAY=3SU
END:STANDARD
END:VTIMEZONE
',
	'America/Scoresbysund' => 'BEGIN:VTIMEZONE
TZID:America/Scoresbysund
X-LIC-LOCATION:America/Scoresbysund
BEGIN:DAYLIGHT
TZOFFSETFROM:-0100
TZOFFSETTO:+0000
TZNAME:EGST
DTSTART:19700329T000000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:-0100
TZNAME:EGT
DTSTART:19701025T010000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Sitka' => 'BEGIN:VTIMEZONE
TZID:America/Sitka
X-LIC-LOCATION:America/Sitka
BEGIN:DAYLIGHT
TZOFFSETFROM:-0900
TZOFFSETTO:-0800
TZNAME:AKDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0800
TZOFFSETTO:-0900
TZNAME:AKST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/St_Barthelemy' => 'BEGIN:VTIMEZONE
TZID:America/St_Barthelemy
X-LIC-LOCATION:America/St_Barthelemy
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/St_Johns' => 'BEGIN:VTIMEZONE
TZID:America/St_Johns
X-LIC-LOCATION:America/St_Johns
BEGIN:STANDARD
TZOFFSETFROM:-0230
TZOFFSETTO:-0330
TZNAME:NST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0330
TZOFFSETTO:-0230
TZNAME:NDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
END:VTIMEZONE
',
	'America/St_Kitts' => 'BEGIN:VTIMEZONE
TZID:America/St_Kitts
X-LIC-LOCATION:America/St_Kitts
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/St_Lucia' => 'BEGIN:VTIMEZONE
TZID:America/St_Lucia
X-LIC-LOCATION:America/St_Lucia
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/St_Thomas' => 'BEGIN:VTIMEZONE
TZID:America/St_Thomas
X-LIC-LOCATION:America/St_Thomas
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/St_Vincent' => 'BEGIN:VTIMEZONE
TZID:America/St_Vincent
X-LIC-LOCATION:America/St_Vincent
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Swift_Current' => 'BEGIN:VTIMEZONE
TZID:America/Swift_Current
X-LIC-LOCATION:America/Swift_Current
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Tegucigalpa' => 'BEGIN:VTIMEZONE
TZID:America/Tegucigalpa
X-LIC-LOCATION:America/Tegucigalpa
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Thule' => 'BEGIN:VTIMEZONE
TZID:America/Thule
X-LIC-LOCATION:America/Thule
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:ADT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Thunder_Bay' => 'BEGIN:VTIMEZONE
TZID:America/Thunder_Bay
X-LIC-LOCATION:America/Thunder_Bay
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Tijuana' => 'BEGIN:VTIMEZONE
TZID:America/Tijuana
X-LIC-LOCATION:America/Tijuana
BEGIN:DAYLIGHT
TZOFFSETFROM:-0800
TZOFFSETTO:-0700
TZNAME:PDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Toronto' => 'BEGIN:VTIMEZONE
TZID:America/Toronto
X-LIC-LOCATION:America/Toronto
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Tortola' => 'BEGIN:VTIMEZONE
TZID:America/Tortola
X-LIC-LOCATION:America/Tortola
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'America/Vancouver' => 'BEGIN:VTIMEZONE
TZID:America/Vancouver
X-LIC-LOCATION:America/Vancouver
BEGIN:DAYLIGHT
TZOFFSETFROM:-0800
TZOFFSETTO:-0700
TZNAME:PDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Whitehorse' => 'BEGIN:VTIMEZONE
TZID:America/Whitehorse
X-LIC-LOCATION:America/Whitehorse
BEGIN:DAYLIGHT
TZOFFSETFROM:-0800
TZOFFSETTO:-0700
TZNAME:PDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0700
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Winnipeg' => 'BEGIN:VTIMEZONE
TZID:America/Winnipeg
X-LIC-LOCATION:America/Winnipeg
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:CDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:CST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Yakutat' => 'BEGIN:VTIMEZONE
TZID:America/Yakutat
X-LIC-LOCATION:America/Yakutat
BEGIN:DAYLIGHT
TZOFFSETFROM:-0900
TZOFFSETTO:-0800
TZNAME:AKDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0800
TZOFFSETTO:-0900
TZNAME:AKST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'America/Yellowknife' => 'BEGIN:VTIMEZONE
TZID:America/Yellowknife
X-LIC-LOCATION:America/Yellowknife
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Casey' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Casey
X-LIC-LOCATION:Antarctica/Casey
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:AWST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Davis' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Davis
X-LIC-LOCATION:Antarctica/Davis
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:DAVT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/DumontDUrville' => 'BEGIN:VTIMEZONE
TZID:Antarctica/DumontDUrville
X-LIC-LOCATION:Antarctica/DumontDUrville
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:DDUT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Macquarie' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Macquarie
X-LIC-LOCATION:Antarctica/Macquarie
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:MIST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Mawson' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Mawson
X-LIC-LOCATION:Antarctica/Mawson
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:MAWT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/McMurdo' => 'BEGIN:VTIMEZONE
TZID:Antarctica/McMurdo
X-LIC-LOCATION:Antarctica/McMurdo
BEGIN:DAYLIGHT
TZOFFSETFROM:+1200
TZOFFSETTO:+1300
TZNAME:NZDT
DTSTART:19700927T020000
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1300
TZOFFSETTO:+1200
TZNAME:NZST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Palmer' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Palmer
X-LIC-LOCATION:Antarctica/Palmer
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:CLT
DTSTART:19700426T000000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:CLST
DTSTART:19700906T000000
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Antarctica/Rothera' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Rothera
X-LIC-LOCATION:Antarctica/Rothera
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:ROTT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Syowa' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Syowa
X-LIC-LOCATION:Antarctica/Syowa
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:SYOT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Troll' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Troll
X-LIC-LOCATION:Antarctica/Troll
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0000
TZNAME:UTC
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Antarctica/Vostok' => 'BEGIN:VTIMEZONE
TZID:Antarctica/Vostok
X-LIC-LOCATION:Antarctica/Vostok
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:VOST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Arctic/Longyearbyen' => 'BEGIN:VTIMEZONE
TZID:Arctic/Longyearbyen
X-LIC-LOCATION:Arctic/Longyearbyen
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Asia/Aden' => 'BEGIN:VTIMEZONE
TZID:Asia/Aden
X-LIC-LOCATION:Asia/Aden
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Almaty' => 'BEGIN:VTIMEZONE
TZID:Asia/Almaty
X-LIC-LOCATION:Asia/Almaty
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:ALMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Amman' => 'BEGIN:VTIMEZONE
TZID:Asia/Amman
X-LIC-LOCATION:Asia/Amman
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700326T235959
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1TH
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701030T010000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1FR
END:STANDARD
END:VTIMEZONE
',
	'Asia/Anadyr' => 'BEGIN:VTIMEZONE
TZID:Asia/Anadyr
X-LIC-LOCATION:Asia/Anadyr
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:ANAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Aqtau' => 'BEGIN:VTIMEZONE
TZID:Asia/Aqtau
X-LIC-LOCATION:Asia/Aqtau
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:AQTT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Aqtobe' => 'BEGIN:VTIMEZONE
TZID:Asia/Aqtobe
X-LIC-LOCATION:Asia/Aqtobe
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:AQTT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Ashgabat' => 'BEGIN:VTIMEZONE
TZID:Asia/Ashgabat
X-LIC-LOCATION:Asia/Ashgabat
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:TMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Baghdad' => 'BEGIN:VTIMEZONE
TZID:Asia/Baghdad
X-LIC-LOCATION:Asia/Baghdad
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Bahrain' => 'BEGIN:VTIMEZONE
TZID:Asia/Bahrain
X-LIC-LOCATION:Asia/Bahrain
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Baku' => 'BEGIN:VTIMEZONE
TZID:Asia/Baku
X-LIC-LOCATION:Asia/Baku
BEGIN:DAYLIGHT
TZOFFSETFROM:+0400
TZOFFSETTO:+0500
TZNAME:AZST
DTSTART:19700329T040000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0400
TZNAME:AZT
DTSTART:19701025T050000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Asia/Bangkok' => 'BEGIN:VTIMEZONE
TZID:Asia/Bangkok
X-LIC-LOCATION:Asia/Bangkok
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:ICT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Beirut' => 'BEGIN:VTIMEZONE
TZID:Asia/Beirut
X-LIC-LOCATION:Asia/Beirut
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T000000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T000000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Asia/Bishkek' => 'BEGIN:VTIMEZONE
TZID:Asia/Bishkek
X-LIC-LOCATION:Asia/Bishkek
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:KGT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Brunei' => 'BEGIN:VTIMEZONE
TZID:Asia/Brunei
X-LIC-LOCATION:Asia/Brunei
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:BNT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Chita' => 'BEGIN:VTIMEZONE
TZID:Asia/Chita
X-LIC-LOCATION:Asia/Chita
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:IRKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Choibalsan' => 'BEGIN:VTIMEZONE
TZID:Asia/Choibalsan
X-LIC-LOCATION:Asia/Choibalsan
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Colombo' => 'BEGIN:VTIMEZONE
TZID:Asia/Colombo
X-LIC-LOCATION:Asia/Colombo
BEGIN:STANDARD
TZOFFSETFROM:+0530
TZOFFSETTO:+0530
TZNAME:IST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Damascus' => 'BEGIN:VTIMEZONE
TZID:Asia/Damascus
X-LIC-LOCATION:Asia/Damascus
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701030T000000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1FR
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700327T000000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1FR
END:DAYLIGHT
END:VTIMEZONE
',
	'Asia/Dhaka' => 'BEGIN:VTIMEZONE
TZID:Asia/Dhaka
X-LIC-LOCATION:Asia/Dhaka
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:BDT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Dili' => 'BEGIN:VTIMEZONE
TZID:Asia/Dili
X-LIC-LOCATION:Asia/Dili
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:TLT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Dubai' => 'BEGIN:VTIMEZONE
TZID:Asia/Dubai
X-LIC-LOCATION:Asia/Dubai
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:GST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Dushanbe' => 'BEGIN:VTIMEZONE
TZID:Asia/Dushanbe
X-LIC-LOCATION:Asia/Dushanbe
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:TJT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Gaza' => 'BEGIN:VTIMEZONE
TZID:Asia/Gaza
X-LIC-LOCATION:Asia/Gaza
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700326T235959
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1TH
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19700925T000000
RRULE:FREQ=YEARLY;BYMONTH=9;BYMONTHDAY=21,22,23,24,25,26,27;BYDAY=FR
END:STANDARD
END:VTIMEZONE
',
	'Asia/Hebron' => 'BEGIN:VTIMEZONE
TZID:Asia/Hebron
X-LIC-LOCATION:Asia/Hebron
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700326T235959
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1TH
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19700925T000000
RRULE:FREQ=YEARLY;BYMONTH=9;BYMONTHDAY=21,22,23,24,25,26,27;BYDAY=FR
END:STANDARD
END:VTIMEZONE
',
	'Asia/Ho_Chi_Minh' => 'BEGIN:VTIMEZONE
TZID:Asia/Ho_Chi_Minh
X-LIC-LOCATION:Asia/Ho_Chi_Minh
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:ICT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Hong_Kong' => 'BEGIN:VTIMEZONE
TZID:Asia/Hong_Kong
X-LIC-LOCATION:Asia/Hong_Kong
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:HKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Hovd' => 'BEGIN:VTIMEZONE
TZID:Asia/Hovd
X-LIC-LOCATION:Asia/Hovd
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:HOVT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Irkutsk' => 'BEGIN:VTIMEZONE
TZID:Asia/Irkutsk
X-LIC-LOCATION:Asia/Irkutsk
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:IRKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Istanbul' => 'BEGIN:VTIMEZONE
TZID:Asia/Istanbul
X-LIC-LOCATION:Asia/Istanbul
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Asia/Jakarta' => 'BEGIN:VTIMEZONE
TZID:Asia/Jakarta
X-LIC-LOCATION:Asia/Jakarta
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:WIB
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Jayapura' => 'BEGIN:VTIMEZONE
TZID:Asia/Jayapura
X-LIC-LOCATION:Asia/Jayapura
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:WIT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Jerusalem' => 'BEGIN:VTIMEZONE
TZID:Asia/Jerusalem
X-LIC-LOCATION:Asia/Jerusalem
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:IDT
DTSTART:19700327T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYMONTHDAY=23,24,25,26,27,28,29;BYDAY=FR
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:IST
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Asia/Kabul' => 'BEGIN:VTIMEZONE
TZID:Asia/Kabul
X-LIC-LOCATION:Asia/Kabul
BEGIN:STANDARD
TZOFFSETFROM:+0430
TZOFFSETTO:+0430
TZNAME:AFT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Kamchatka' => 'BEGIN:VTIMEZONE
TZID:Asia/Kamchatka
X-LIC-LOCATION:Asia/Kamchatka
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:PETT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Karachi' => 'BEGIN:VTIMEZONE
TZID:Asia/Karachi
X-LIC-LOCATION:Asia/Karachi
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:PKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Kathmandu' => 'BEGIN:VTIMEZONE
TZID:Asia/Kathmandu
X-LIC-LOCATION:Asia/Kathmandu
BEGIN:STANDARD
TZOFFSETFROM:+0545
TZOFFSETTO:+0545
TZNAME:NPT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Khandyga' => 'BEGIN:VTIMEZONE
TZID:Asia/Khandyga
X-LIC-LOCATION:Asia/Khandyga
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:YAKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Kolkata' => 'BEGIN:VTIMEZONE
TZID:Asia/Kolkata
X-LIC-LOCATION:Asia/Kolkata
BEGIN:STANDARD
TZOFFSETFROM:+0530
TZOFFSETTO:+0530
TZNAME:IST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Krasnoyarsk' => 'BEGIN:VTIMEZONE
TZID:Asia/Krasnoyarsk
X-LIC-LOCATION:Asia/Krasnoyarsk
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:KRAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Kuala_Lumpur' => 'BEGIN:VTIMEZONE
TZID:Asia/Kuala_Lumpur
X-LIC-LOCATION:Asia/Kuala_Lumpur
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:MYT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Kuching' => 'BEGIN:VTIMEZONE
TZID:Asia/Kuching
X-LIC-LOCATION:Asia/Kuching
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:MYT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Kuwait' => 'BEGIN:VTIMEZONE
TZID:Asia/Kuwait
X-LIC-LOCATION:Asia/Kuwait
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Macau' => 'BEGIN:VTIMEZONE
TZID:Asia/Macau
X-LIC-LOCATION:Asia/Macau
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Magadan' => 'BEGIN:VTIMEZONE
TZID:Asia/Magadan
X-LIC-LOCATION:Asia/Magadan
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:MAGT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Makassar' => 'BEGIN:VTIMEZONE
TZID:Asia/Makassar
X-LIC-LOCATION:Asia/Makassar
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:WITA
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Manila' => 'BEGIN:VTIMEZONE
TZID:Asia/Manila
X-LIC-LOCATION:Asia/Manila
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:PHT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Muscat' => 'BEGIN:VTIMEZONE
TZID:Asia/Muscat
X-LIC-LOCATION:Asia/Muscat
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:GST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Nicosia' => 'BEGIN:VTIMEZONE
TZID:Asia/Nicosia
X-LIC-LOCATION:Asia/Nicosia
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Asia/Novokuznetsk' => 'BEGIN:VTIMEZONE
TZID:Asia/Novokuznetsk
X-LIC-LOCATION:Asia/Novokuznetsk
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:KRAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Novosibirsk' => 'BEGIN:VTIMEZONE
TZID:Asia/Novosibirsk
X-LIC-LOCATION:Asia/Novosibirsk
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:NOVT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Omsk' => 'BEGIN:VTIMEZONE
TZID:Asia/Omsk
X-LIC-LOCATION:Asia/Omsk
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:OMST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Oral' => 'BEGIN:VTIMEZONE
TZID:Asia/Oral
X-LIC-LOCATION:Asia/Oral
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:ORAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Phnom_Penh' => 'BEGIN:VTIMEZONE
TZID:Asia/Phnom_Penh
X-LIC-LOCATION:Asia/Phnom_Penh
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:ICT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Pontianak' => 'BEGIN:VTIMEZONE
TZID:Asia/Pontianak
X-LIC-LOCATION:Asia/Pontianak
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:WIB
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Pyongyang' => 'BEGIN:VTIMEZONE
TZID:Asia/Pyongyang
X-LIC-LOCATION:Asia/Pyongyang
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:KST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Qatar' => 'BEGIN:VTIMEZONE
TZID:Asia/Qatar
X-LIC-LOCATION:Asia/Qatar
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Qyzylorda' => 'BEGIN:VTIMEZONE
TZID:Asia/Qyzylorda
X-LIC-LOCATION:Asia/Qyzylorda
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:QYZT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Rangoon' => 'BEGIN:VTIMEZONE
TZID:Asia/Rangoon
X-LIC-LOCATION:Asia/Rangoon
BEGIN:STANDARD
TZOFFSETFROM:+0630
TZOFFSETTO:+0630
TZNAME:MMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Riyadh' => 'BEGIN:VTIMEZONE
TZID:Asia/Riyadh
X-LIC-LOCATION:Asia/Riyadh
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:AST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Sakhalin' => 'BEGIN:VTIMEZONE
TZID:Asia/Sakhalin
X-LIC-LOCATION:Asia/Sakhalin
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:SAKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Samarkand' => 'BEGIN:VTIMEZONE
TZID:Asia/Samarkand
X-LIC-LOCATION:Asia/Samarkand
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:UZT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Seoul' => 'BEGIN:VTIMEZONE
TZID:Asia/Seoul
X-LIC-LOCATION:Asia/Seoul
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:KST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Shanghai' => 'BEGIN:VTIMEZONE
TZID:Asia/Shanghai
X-LIC-LOCATION:Asia/Shanghai
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Singapore' => 'BEGIN:VTIMEZONE
TZID:Asia/Singapore
X-LIC-LOCATION:Asia/Singapore
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:SGT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Srednekolymsk' => 'BEGIN:VTIMEZONE
TZID:Asia/Srednekolymsk
X-LIC-LOCATION:Asia/Srednekolymsk
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:SRET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Taipei' => 'BEGIN:VTIMEZONE
TZID:Asia/Taipei
X-LIC-LOCATION:Asia/Taipei
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:CST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Tashkent' => 'BEGIN:VTIMEZONE
TZID:Asia/Tashkent
X-LIC-LOCATION:Asia/Tashkent
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:UZT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Tbilisi' => 'BEGIN:VTIMEZONE
TZID:Asia/Tbilisi
X-LIC-LOCATION:Asia/Tbilisi
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:GET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Tehran' => 'BEGIN:VTIMEZONE
TZID:Asia/Tehran
X-LIC-LOCATION:Asia/Tehran
BEGIN:STANDARD
TZOFFSETFROM:+0330
TZOFFSETTO:+0330
TZNAME:IRST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Thimphu' => 'BEGIN:VTIMEZONE
TZID:Asia/Thimphu
X-LIC-LOCATION:Asia/Thimphu
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:BTT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Tokyo' => 'BEGIN:VTIMEZONE
TZID:Asia/Tokyo
X-LIC-LOCATION:Asia/Tokyo
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:JST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Ulaanbaatar' => 'BEGIN:VTIMEZONE
TZID:Asia/Ulaanbaatar
X-LIC-LOCATION:Asia/Ulaanbaatar
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:ULAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Urumqi' => 'BEGIN:VTIMEZONE
TZID:Asia/Urumqi
X-LIC-LOCATION:Asia/Urumqi
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:XJT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Ust-Nera' => 'BEGIN:VTIMEZONE
TZID:Asia/Ust-Nera
X-LIC-LOCATION:Asia/Ust-Nera
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:VLAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Vientiane' => 'BEGIN:VTIMEZONE
TZID:Asia/Vientiane
X-LIC-LOCATION:Asia/Vientiane
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:ICT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Vladivostok' => 'BEGIN:VTIMEZONE
TZID:Asia/Vladivostok
X-LIC-LOCATION:Asia/Vladivostok
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:VLAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Yakutsk' => 'BEGIN:VTIMEZONE
TZID:Asia/Yakutsk
X-LIC-LOCATION:Asia/Yakutsk
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:YAKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Yekaterinburg' => 'BEGIN:VTIMEZONE
TZID:Asia/Yekaterinburg
X-LIC-LOCATION:Asia/Yekaterinburg
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:YEKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Asia/Yerevan' => 'BEGIN:VTIMEZONE
TZID:Asia/Yerevan
X-LIC-LOCATION:Asia/Yerevan
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:AMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Azores' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Azores
X-LIC-LOCATION:Atlantic/Azores
BEGIN:DAYLIGHT
TZOFFSETFROM:-0100
TZOFFSETTO:+0000
TZNAME:AZOST
DTSTART:19700329T000000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:-0100
TZNAME:AZOT
DTSTART:19701025T010000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Bermuda' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Bermuda
X-LIC-LOCATION:Atlantic/Bermuda
BEGIN:DAYLIGHT
TZOFFSETFROM:-0400
TZOFFSETTO:-0300
TZNAME:ADT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0400
TZNAME:AST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Canary' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Canary
X-LIC-LOCATION:Atlantic/Canary
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:WEST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:WET
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Cape_Verde' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Cape_Verde
X-LIC-LOCATION:Atlantic/Cape_Verde
BEGIN:STANDARD
TZOFFSETFROM:-0100
TZOFFSETTO:-0100
TZNAME:CVT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Faroe' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Faroe
X-LIC-LOCATION:Atlantic/Faroe
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:WEST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:WET
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Madeira' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Madeira
X-LIC-LOCATION:Atlantic/Madeira
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:WEST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:WET
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Reykjavik' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Reykjavik
X-LIC-LOCATION:Atlantic/Reykjavik
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/South_Georgia' => 'BEGIN:VTIMEZONE
TZID:Atlantic/South_Georgia
X-LIC-LOCATION:Atlantic/South_Georgia
BEGIN:STANDARD
TZOFFSETFROM:-0200
TZOFFSETTO:-0200
TZNAME:GST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/St_Helena' => 'BEGIN:VTIMEZONE
TZID:Atlantic/St_Helena
X-LIC-LOCATION:Atlantic/St_Helena
BEGIN:STANDARD
TZOFFSETFROM:+0000
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Atlantic/Stanley' => 'BEGIN:VTIMEZONE
TZID:Atlantic/Stanley
X-LIC-LOCATION:Atlantic/Stanley
BEGIN:STANDARD
TZOFFSETFROM:-0300
TZOFFSETTO:-0300
TZNAME:FKST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Australia/Adelaide' => 'BEGIN:VTIMEZONE
TZID:Australia/Adelaide
X-LIC-LOCATION:Australia/Adelaide
BEGIN:STANDARD
TZOFFSETFROM:+1030
TZOFFSETTO:+0930
TZNAME:ACST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0930
TZOFFSETTO:+1030
TZNAME:ACDT
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Australia/Brisbane' => 'BEGIN:VTIMEZONE
TZID:Australia/Brisbane
X-LIC-LOCATION:Australia/Brisbane
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:AEST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Australia/Broken_Hill' => 'BEGIN:VTIMEZONE
TZID:Australia/Broken_Hill
X-LIC-LOCATION:Australia/Broken_Hill
BEGIN:STANDARD
TZOFFSETFROM:+1030
TZOFFSETTO:+0930
TZNAME:ACST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0930
TZOFFSETTO:+1030
TZNAME:ACDT
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Australia/Currie' => 'BEGIN:VTIMEZONE
TZID:Australia/Currie
X-LIC-LOCATION:Australia/Currie
BEGIN:DAYLIGHT
TZOFFSETFROM:+1000
TZOFFSETTO:+1100
TZNAME:AEDT
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1000
TZNAME:AEST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'Australia/Darwin' => 'BEGIN:VTIMEZONE
TZID:Australia/Darwin
X-LIC-LOCATION:Australia/Darwin
BEGIN:STANDARD
TZOFFSETFROM:+0930
TZOFFSETTO:+0930
TZNAME:ACST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Australia/Eucla' => 'BEGIN:VTIMEZONE
TZID:Australia/Eucla
X-LIC-LOCATION:Australia/Eucla
BEGIN:STANDARD
TZOFFSETFROM:+0845
TZOFFSETTO:+0845
TZNAME:ACWST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Australia/Hobart' => 'BEGIN:VTIMEZONE
TZID:Australia/Hobart
X-LIC-LOCATION:Australia/Hobart
BEGIN:DAYLIGHT
TZOFFSETFROM:+1000
TZOFFSETTO:+1100
TZNAME:AEDT
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1000
TZNAME:AEST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'Australia/Lindeman' => 'BEGIN:VTIMEZONE
TZID:Australia/Lindeman
X-LIC-LOCATION:Australia/Lindeman
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:AEST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Australia/Lord_Howe' => 'BEGIN:VTIMEZONE
TZID:Australia/Lord_Howe
X-LIC-LOCATION:Australia/Lord_Howe
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1030
TZNAME:LHST
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+1030
TZOFFSETTO:+1100
TZNAME:LHDT
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Australia/Melbourne' => 'BEGIN:VTIMEZONE
TZID:Australia/Melbourne
X-LIC-LOCATION:Australia/Melbourne
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1000
TZNAME:AEST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+1000
TZOFFSETTO:+1100
TZNAME:AEDT
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Australia/Perth' => 'BEGIN:VTIMEZONE
TZID:Australia/Perth
X-LIC-LOCATION:Australia/Perth
BEGIN:STANDARD
TZOFFSETFROM:+0800
TZOFFSETTO:+0800
TZNAME:AWST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Australia/Sydney' => 'BEGIN:VTIMEZONE
TZID:Australia/Sydney
X-LIC-LOCATION:Australia/Sydney
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1000
TZNAME:AEST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+1000
TZOFFSETTO:+1100
TZNAME:AEDT
DTSTART:19701004T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Europe/Amsterdam' => 'BEGIN:VTIMEZONE
TZID:Europe/Amsterdam
X-LIC-LOCATION:Europe/Amsterdam
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Andorra' => 'BEGIN:VTIMEZONE
TZID:Europe/Andorra
X-LIC-LOCATION:Europe/Andorra
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Athens' => 'BEGIN:VTIMEZONE
TZID:Europe/Athens
X-LIC-LOCATION:Europe/Athens
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Belgrade' => 'BEGIN:VTIMEZONE
TZID:Europe/Belgrade
X-LIC-LOCATION:Europe/Belgrade
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Berlin' => 'BEGIN:VTIMEZONE
TZID:Europe/Berlin
X-LIC-LOCATION:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Bratislava' => 'BEGIN:VTIMEZONE
TZID:Europe/Bratislava
X-LIC-LOCATION:Europe/Bratislava
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Brussels' => 'BEGIN:VTIMEZONE
TZID:Europe/Brussels
X-LIC-LOCATION:Europe/Brussels
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Bucharest' => 'BEGIN:VTIMEZONE
TZID:Europe/Bucharest
X-LIC-LOCATION:Europe/Bucharest
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Budapest' => 'BEGIN:VTIMEZONE
TZID:Europe/Budapest
X-LIC-LOCATION:Europe/Budapest
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Busingen' => 'BEGIN:VTIMEZONE
TZID:Europe/Busingen
X-LIC-LOCATION:Europe/Busingen
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Chisinau' => 'BEGIN:VTIMEZONE
TZID:Europe/Chisinau
X-LIC-LOCATION:Europe/Chisinau
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Copenhagen' => 'BEGIN:VTIMEZONE
TZID:Europe/Copenhagen
X-LIC-LOCATION:Europe/Copenhagen
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Dublin' => 'BEGIN:VTIMEZONE
TZID:Europe/Dublin
X-LIC-LOCATION:Europe/Dublin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:IST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Gibraltar' => 'BEGIN:VTIMEZONE
TZID:Europe/Gibraltar
X-LIC-LOCATION:Europe/Gibraltar
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Guernsey' => 'BEGIN:VTIMEZONE
TZID:Europe/Guernsey
X-LIC-LOCATION:Europe/Guernsey
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:BST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Helsinki' => 'BEGIN:VTIMEZONE
TZID:Europe/Helsinki
X-LIC-LOCATION:Europe/Helsinki
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Isle_of_Man' => 'BEGIN:VTIMEZONE
TZID:Europe/Isle_of_Man
X-LIC-LOCATION:Europe/Isle_of_Man
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:BST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Istanbul' => 'BEGIN:VTIMEZONE
TZID:Europe/Istanbul
X-LIC-LOCATION:Europe/Istanbul
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Europe/Jersey' => 'BEGIN:VTIMEZONE
TZID:Europe/Jersey
X-LIC-LOCATION:Europe/Jersey
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:BST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Kaliningrad' => 'BEGIN:VTIMEZONE
TZID:Europe/Kaliningrad
X-LIC-LOCATION:Europe/Kaliningrad
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Europe/Kiev' => 'BEGIN:VTIMEZONE
TZID:Europe/Kiev
X-LIC-LOCATION:Europe/Kiev
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Lisbon' => 'BEGIN:VTIMEZONE
TZID:Europe/Lisbon
X-LIC-LOCATION:Europe/Lisbon
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:WET
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:WEST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Europe/Ljubljana' => 'BEGIN:VTIMEZONE
TZID:Europe/Ljubljana
X-LIC-LOCATION:Europe/Ljubljana
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/London' => 'BEGIN:VTIMEZONE
TZID:Europe/London
X-LIC-LOCATION:Europe/London
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:BST
DTSTART:19700329T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:GMT
DTSTART:19701025T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Luxembourg' => 'BEGIN:VTIMEZONE
TZID:Europe/Luxembourg
X-LIC-LOCATION:Europe/Luxembourg
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Madrid' => 'BEGIN:VTIMEZONE
TZID:Europe/Madrid
X-LIC-LOCATION:Europe/Madrid
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Malta' => 'BEGIN:VTIMEZONE
TZID:Europe/Malta
X-LIC-LOCATION:Europe/Malta
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Mariehamn' => 'BEGIN:VTIMEZONE
TZID:Europe/Mariehamn
X-LIC-LOCATION:Europe/Mariehamn
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Minsk' => 'BEGIN:VTIMEZONE
TZID:Europe/Minsk
X-LIC-LOCATION:Europe/Minsk
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:MSK
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Europe/Monaco' => 'BEGIN:VTIMEZONE
TZID:Europe/Monaco
X-LIC-LOCATION:Europe/Monaco
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Moscow' => 'BEGIN:VTIMEZONE
TZID:Europe/Moscow
X-LIC-LOCATION:Europe/Moscow
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:MSK
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Europe/Nicosia' => 'BEGIN:VTIMEZONE
TZID:Europe/Nicosia
X-LIC-LOCATION:Europe/Nicosia
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Europe/Oslo' => 'BEGIN:VTIMEZONE
TZID:Europe/Oslo
X-LIC-LOCATION:Europe/Oslo
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Paris' => 'BEGIN:VTIMEZONE
TZID:Europe/Paris
X-LIC-LOCATION:Europe/Paris
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Podgorica' => 'BEGIN:VTIMEZONE
TZID:Europe/Podgorica
X-LIC-LOCATION:Europe/Podgorica
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Prague' => 'BEGIN:VTIMEZONE
TZID:Europe/Prague
X-LIC-LOCATION:Europe/Prague
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Riga' => 'BEGIN:VTIMEZONE
TZID:Europe/Riga
X-LIC-LOCATION:Europe/Riga
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Rome' => 'BEGIN:VTIMEZONE
TZID:Europe/Rome
X-LIC-LOCATION:Europe/Rome
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Samara' => 'BEGIN:VTIMEZONE
TZID:Europe/Samara
X-LIC-LOCATION:Europe/Samara
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:SAMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Europe/San_Marino' => 'BEGIN:VTIMEZONE
TZID:Europe/San_Marino
X-LIC-LOCATION:Europe/San_Marino
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Sarajevo' => 'BEGIN:VTIMEZONE
TZID:Europe/Sarajevo
X-LIC-LOCATION:Europe/Sarajevo
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Simferopol' => 'BEGIN:VTIMEZONE
TZID:Europe/Simferopol
X-LIC-LOCATION:Europe/Simferopol
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:MSK
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Europe/Skopje' => 'BEGIN:VTIMEZONE
TZID:Europe/Skopje
X-LIC-LOCATION:Europe/Skopje
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Sofia' => 'BEGIN:VTIMEZONE
TZID:Europe/Sofia
X-LIC-LOCATION:Europe/Sofia
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Stockholm' => 'BEGIN:VTIMEZONE
TZID:Europe/Stockholm
X-LIC-LOCATION:Europe/Stockholm
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Tallinn' => 'BEGIN:VTIMEZONE
TZID:Europe/Tallinn
X-LIC-LOCATION:Europe/Tallinn
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Tirane' => 'BEGIN:VTIMEZONE
TZID:Europe/Tirane
X-LIC-LOCATION:Europe/Tirane
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Uzhgorod' => 'BEGIN:VTIMEZONE
TZID:Europe/Uzhgorod
X-LIC-LOCATION:Europe/Uzhgorod
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Vaduz' => 'BEGIN:VTIMEZONE
TZID:Europe/Vaduz
X-LIC-LOCATION:Europe/Vaduz
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Vatican' => 'BEGIN:VTIMEZONE
TZID:Europe/Vatican
X-LIC-LOCATION:Europe/Vatican
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Vienna' => 'BEGIN:VTIMEZONE
TZID:Europe/Vienna
X-LIC-LOCATION:Europe/Vienna
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Vilnius' => 'BEGIN:VTIMEZONE
TZID:Europe/Vilnius
X-LIC-LOCATION:Europe/Vilnius
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Volgograd' => 'BEGIN:VTIMEZONE
TZID:Europe/Volgograd
X-LIC-LOCATION:Europe/Volgograd
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:MSK
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Europe/Warsaw' => 'BEGIN:VTIMEZONE
TZID:Europe/Warsaw
X-LIC-LOCATION:Europe/Warsaw
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Zagreb' => 'BEGIN:VTIMEZONE
TZID:Europe/Zagreb
X-LIC-LOCATION:Europe/Zagreb
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Zaporozhye' => 'BEGIN:VTIMEZONE
TZID:Europe/Zaporozhye
X-LIC-LOCATION:Europe/Zaporozhye
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Europe/Zurich' => 'BEGIN:VTIMEZONE
TZID:Europe/Zurich
X-LIC-LOCATION:Europe/Zurich
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
',
	'Indian/Antananarivo' => 'BEGIN:VTIMEZONE
TZID:Indian/Antananarivo
X-LIC-LOCATION:Indian/Antananarivo
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Chagos' => 'BEGIN:VTIMEZONE
TZID:Indian/Chagos
X-LIC-LOCATION:Indian/Chagos
BEGIN:STANDARD
TZOFFSETFROM:+0600
TZOFFSETTO:+0600
TZNAME:IOT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Christmas' => 'BEGIN:VTIMEZONE
TZID:Indian/Christmas
X-LIC-LOCATION:Indian/Christmas
BEGIN:STANDARD
TZOFFSETFROM:+0700
TZOFFSETTO:+0700
TZNAME:CXT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Cocos' => 'BEGIN:VTIMEZONE
TZID:Indian/Cocos
X-LIC-LOCATION:Indian/Cocos
BEGIN:STANDARD
TZOFFSETFROM:+0630
TZOFFSETTO:+0630
TZNAME:CCT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Comoro' => 'BEGIN:VTIMEZONE
TZID:Indian/Comoro
X-LIC-LOCATION:Indian/Comoro
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Kerguelen' => 'BEGIN:VTIMEZONE
TZID:Indian/Kerguelen
X-LIC-LOCATION:Indian/Kerguelen
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:TFT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Mahe' => 'BEGIN:VTIMEZONE
TZID:Indian/Mahe
X-LIC-LOCATION:Indian/Mahe
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:SCT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Maldives' => 'BEGIN:VTIMEZONE
TZID:Indian/Maldives
X-LIC-LOCATION:Indian/Maldives
BEGIN:STANDARD
TZOFFSETFROM:+0500
TZOFFSETTO:+0500
TZNAME:MVT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Mauritius' => 'BEGIN:VTIMEZONE
TZID:Indian/Mauritius
X-LIC-LOCATION:Indian/Mauritius
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:MUT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Mayotte' => 'BEGIN:VTIMEZONE
TZID:Indian/Mayotte
X-LIC-LOCATION:Indian/Mayotte
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:EAT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Indian/Reunion' => 'BEGIN:VTIMEZONE
TZID:Indian/Reunion
X-LIC-LOCATION:Indian/Reunion
BEGIN:STANDARD
TZOFFSETFROM:+0400
TZOFFSETTO:+0400
TZNAME:RET
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Apia' => 'BEGIN:VTIMEZONE
TZID:Pacific/Apia
X-LIC-LOCATION:Pacific/Apia
BEGIN:STANDARD
TZOFFSETFROM:+1400
TZOFFSETTO:+1300
TZNAME:WSST
DTSTART:19700405T040000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+1300
TZOFFSETTO:+1400
TZNAME:WSDT
DTSTART:19700927T030000
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
',
	'Pacific/Auckland' => 'BEGIN:VTIMEZONE
TZID:Pacific/Auckland
X-LIC-LOCATION:Pacific/Auckland
BEGIN:DAYLIGHT
TZOFFSETFROM:+1200
TZOFFSETTO:+1300
TZNAME:NZDT
DTSTART:19700927T020000
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1300
TZOFFSETTO:+1200
TZNAME:NZST
DTSTART:19700405T030000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Bougainville' => 'BEGIN:VTIMEZONE
TZID:Pacific/Bougainville
X-LIC-LOCATION:Pacific/Bougainville
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:BST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Chatham' => 'BEGIN:VTIMEZONE
TZID:Pacific/Chatham
X-LIC-LOCATION:Pacific/Chatham
BEGIN:DAYLIGHT
TZOFFSETFROM:+1245
TZOFFSETTO:+1345
TZNAME:CHADT
DTSTART:19700927T024500
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1345
TZOFFSETTO:+1245
TZNAME:CHAST
DTSTART:19700405T034500
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Chuuk' => 'BEGIN:VTIMEZONE
TZID:Pacific/Chuuk
X-LIC-LOCATION:Pacific/Chuuk
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:CHUT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Easter' => 'BEGIN:VTIMEZONE
TZID:Pacific/Easter
X-LIC-LOCATION:Pacific/Easter
BEGIN:STANDARD
TZOFFSETFROM:-0500
TZOFFSETTO:-0600
TZNAME:EAST
DTSTART:19700425T220000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=4SA
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
TZNAME:EASST
DTSTART:19700905T220000
RRULE:FREQ=YEARLY;BYMONTH=9;BYDAY=1SA
END:DAYLIGHT
END:VTIMEZONE
',
	'Pacific/Efate' => 'BEGIN:VTIMEZONE
TZID:Pacific/Efate
X-LIC-LOCATION:Pacific/Efate
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:VUT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Enderbury' => 'BEGIN:VTIMEZONE
TZID:Pacific/Enderbury
X-LIC-LOCATION:Pacific/Enderbury
BEGIN:STANDARD
TZOFFSETFROM:+1300
TZOFFSETTO:+1300
TZNAME:PHOT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Fakaofo' => 'BEGIN:VTIMEZONE
TZID:Pacific/Fakaofo
X-LIC-LOCATION:Pacific/Fakaofo
BEGIN:STANDARD
TZOFFSETFROM:+1300
TZOFFSETTO:+1300
TZNAME:TKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Fiji' => 'BEGIN:VTIMEZONE
TZID:Pacific/Fiji
X-LIC-LOCATION:Pacific/Fiji
BEGIN:DAYLIGHT
TZOFFSETFROM:+1200
TZOFFSETTO:+1300
TZNAME:FJST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1300
TZOFFSETTO:+1200
TZNAME:FJT
DTSTART:19700118T030000
RRULE:FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=18,19,20,21,22,23,24;BYDAY=SU
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Funafuti' => 'BEGIN:VTIMEZONE
TZID:Pacific/Funafuti
X-LIC-LOCATION:Pacific/Funafuti
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:TVT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Galapagos' => 'BEGIN:VTIMEZONE
TZID:Pacific/Galapagos
X-LIC-LOCATION:Pacific/Galapagos
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0600
TZNAME:GALT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Gambier' => 'BEGIN:VTIMEZONE
TZID:Pacific/Gambier
X-LIC-LOCATION:Pacific/Gambier
BEGIN:STANDARD
TZOFFSETFROM:-0900
TZOFFSETTO:-0900
TZNAME:GAMT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Guadalcanal' => 'BEGIN:VTIMEZONE
TZID:Pacific/Guadalcanal
X-LIC-LOCATION:Pacific/Guadalcanal
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:SBT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Guam' => 'BEGIN:VTIMEZONE
TZID:Pacific/Guam
X-LIC-LOCATION:Pacific/Guam
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:ChST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Honolulu' => 'BEGIN:VTIMEZONE
TZID:Pacific/Honolulu
X-LIC-LOCATION:Pacific/Honolulu
BEGIN:STANDARD
TZOFFSETFROM:-1000
TZOFFSETTO:-1000
TZNAME:HST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Johnston' => 'BEGIN:VTIMEZONE
TZID:Pacific/Johnston
X-LIC-LOCATION:Pacific/Johnston
BEGIN:STANDARD
TZOFFSETFROM:-1000
TZOFFSETTO:-1000
TZNAME:HST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Kiritimati' => 'BEGIN:VTIMEZONE
TZID:Pacific/Kiritimati
X-LIC-LOCATION:Pacific/Kiritimati
BEGIN:STANDARD
TZOFFSETFROM:+1400
TZOFFSETTO:+1400
TZNAME:LINT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Kosrae' => 'BEGIN:VTIMEZONE
TZID:Pacific/Kosrae
X-LIC-LOCATION:Pacific/Kosrae
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:KOST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Kwajalein' => 'BEGIN:VTIMEZONE
TZID:Pacific/Kwajalein
X-LIC-LOCATION:Pacific/Kwajalein
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:MHT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Majuro' => 'BEGIN:VTIMEZONE
TZID:Pacific/Majuro
X-LIC-LOCATION:Pacific/Majuro
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:MHT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Marquesas' => 'BEGIN:VTIMEZONE
TZID:Pacific/Marquesas
X-LIC-LOCATION:Pacific/Marquesas
BEGIN:STANDARD
TZOFFSETFROM:-0930
TZOFFSETTO:-0930
TZNAME:MART
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Midway' => 'BEGIN:VTIMEZONE
TZID:Pacific/Midway
X-LIC-LOCATION:Pacific/Midway
BEGIN:STANDARD
TZOFFSETFROM:-1100
TZOFFSETTO:-1100
TZNAME:SST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Nauru' => 'BEGIN:VTIMEZONE
TZID:Pacific/Nauru
X-LIC-LOCATION:Pacific/Nauru
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:NRT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Niue' => 'BEGIN:VTIMEZONE
TZID:Pacific/Niue
X-LIC-LOCATION:Pacific/Niue
BEGIN:STANDARD
TZOFFSETFROM:-1100
TZOFFSETTO:-1100
TZNAME:NUT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Norfolk' => 'BEGIN:VTIMEZONE
TZID:Pacific/Norfolk
X-LIC-LOCATION:Pacific/Norfolk
BEGIN:STANDARD
TZOFFSETFROM:+1130
TZOFFSETTO:+1130
TZNAME:NFT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Noumea' => 'BEGIN:VTIMEZONE
TZID:Pacific/Noumea
X-LIC-LOCATION:Pacific/Noumea
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:NCT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Pago_Pago' => 'BEGIN:VTIMEZONE
TZID:Pacific/Pago_Pago
X-LIC-LOCATION:Pacific/Pago_Pago
BEGIN:STANDARD
TZOFFSETFROM:-1100
TZOFFSETTO:-1100
TZNAME:SST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Palau' => 'BEGIN:VTIMEZONE
TZID:Pacific/Palau
X-LIC-LOCATION:Pacific/Palau
BEGIN:STANDARD
TZOFFSETFROM:+0900
TZOFFSETTO:+0900
TZNAME:PWT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Pitcairn' => 'BEGIN:VTIMEZONE
TZID:Pacific/Pitcairn
X-LIC-LOCATION:Pacific/Pitcairn
BEGIN:STANDARD
TZOFFSETFROM:-0800
TZOFFSETTO:-0800
TZNAME:PST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Pohnpei' => 'BEGIN:VTIMEZONE
TZID:Pacific/Pohnpei
X-LIC-LOCATION:Pacific/Pohnpei
BEGIN:STANDARD
TZOFFSETFROM:+1100
TZOFFSETTO:+1100
TZNAME:PONT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Port_Moresby' => 'BEGIN:VTIMEZONE
TZID:Pacific/Port_Moresby
X-LIC-LOCATION:Pacific/Port_Moresby
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:PGT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Rarotonga' => 'BEGIN:VTIMEZONE
TZID:Pacific/Rarotonga
X-LIC-LOCATION:Pacific/Rarotonga
BEGIN:STANDARD
TZOFFSETFROM:-1000
TZOFFSETTO:-1000
TZNAME:CKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Saipan' => 'BEGIN:VTIMEZONE
TZID:Pacific/Saipan
X-LIC-LOCATION:Pacific/Saipan
BEGIN:STANDARD
TZOFFSETFROM:+1000
TZOFFSETTO:+1000
TZNAME:ChST
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Tahiti' => 'BEGIN:VTIMEZONE
TZID:Pacific/Tahiti
X-LIC-LOCATION:Pacific/Tahiti
BEGIN:STANDARD
TZOFFSETFROM:-1000
TZOFFSETTO:-1000
TZNAME:TAHT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Tarawa' => 'BEGIN:VTIMEZONE
TZID:Pacific/Tarawa
X-LIC-LOCATION:Pacific/Tarawa
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:GILT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Tongatapu' => 'BEGIN:VTIMEZONE
TZID:Pacific/Tongatapu
X-LIC-LOCATION:Pacific/Tongatapu
BEGIN:STANDARD
TZOFFSETFROM:+1300
TZOFFSETTO:+1300
TZNAME:TOT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Wake' => 'BEGIN:VTIMEZONE
TZID:Pacific/Wake
X-LIC-LOCATION:Pacific/Wake
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:WAKT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',
	'Pacific/Wallis' => 'BEGIN:VTIMEZONE
TZID:Pacific/Wallis
X-LIC-LOCATION:Pacific/Wallis
BEGIN:STANDARD
TZOFFSETFROM:+1200
TZOFFSETTO:+1200
TZNAME:WFT
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
',

	);
}
?>