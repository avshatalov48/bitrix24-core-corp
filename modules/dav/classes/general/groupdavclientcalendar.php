<?php

use Bitrix\Calendar\Util;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Web\HttpClient;

define("DAV_CALDAV_DEBUG", false);

class CDavGroupdavClientCalendar extends CDavGroupdavClient
{
	public function __construct($scheme, $server, $port, $userName, $userPassword, $siteId = null)
	{
		parent::__construct($scheme, $server, $port, $userName, $userPassword);
		$this->SetCurrentEncoding($siteId);
	}

	private function GetCalendarListByPath($path = '/', $logger = null)
	{
		$this->Connect();

		$result = $this->Propfind(
			$path,
			array(
				array("calendar-home-set", "urn:ietf:params:xml:ns:caldav"),
				array("getctag", "http://calendarserver.org/ns/"),
				"displayname",
				array("calendar-description", "urn:ietf:params:xml:ns:caldav"),
				array("calendar-color", "http://apple.com/ns/ical/"),
				array("supported-calendar-component-set", "urn:ietf:params:xml:ns:caldav"),
				"resourcetype",
				"owner",
				"current-user-principal",
				"principal-URL",
			),
			null,
			1,
			$logger
		);

		$this->Disconnect();

		if (is_null($result) || $this->getError())
		{
			return null;
		}

		$xmlDoc = $result->GetBodyXml();

		$arCalendars = [];
		$calendarHomeSet = null;
		$currentUserPrincipal = null;
		$principalUrl = null;

		$arResponse = $xmlDoc->GetPath("/*/response");

		foreach ($arResponse as $response)
		{
			$arResourceType = $response->GetPath("/response/propstat/prop/resourcetype/calendar");
			if (!empty($arResourceType))
			{
				$arHref = $response->GetPath("/response/href");
				if (!empty($arHref))
				{
					$arCalendar = [
						"href" => urldecode($arHref[0]->GetContent()),
					];

					$arProps = $response->GetPath("/response/propstat/prop/*");
					foreach ($arProps as $prop)
					{
						$s = $prop->GetContent();
						if (is_string($s) || is_numeric($s))
						{
							$arCalendar[$prop->GetTag()] = $this->Encode($s);
						}
						else if ($prop->GetTag() === 'supported-calendar-component-set')
						{
							$type = $s[0]->GetAttribute('name');
							if (is_string($type))
							{
								$arCalendar[$prop->GetTag()] = $this->Encode($type);
							}
						}
					}

					$arCalendars[] = $arCalendar;
				}
			}

			if (is_null($calendarHomeSet))
			{
				$arCalendarHomeSet = $response->GetPath("/response/propstat/prop/calendar-home-set/href");
				if (!empty($arCalendarHomeSet))
				{
					$calendarHomeSet = urldecode($arCalendarHomeSet[0]->GetContent());
				}
			}

			if (is_null($currentUserPrincipal))
			{
				$arCurrentUserPrincipal = $response->GetPath("/response/propstat/prop/current-user-principal/href");
				if (!empty($arCurrentUserPrincipal))
				{
					$currentUserPrincipal = urldecode($arCurrentUserPrincipal[0]->GetContent());
				}
			}

			if (is_null($principalUrl))
			{
				$arPrincipalUrl = $response->GetPath("/response/propstat/prop/principal-URL/href");
				if (!empty($arPrincipalUrl))
				{
					$principalUrl = urldecode($arPrincipalUrl[0]->GetContent());
				}
			}
		}

		if ($arCalendars)
		{
			return $arCalendars;
		}

		if (!is_null($calendarHomeSet) && ($path != $calendarHomeSet))
		{
			return $calendarHomeSet;
		}
		if (!is_null($principalUrl) && ($path != $principalUrl))
		{
			return $principalUrl;
		}
		if (!is_null($currentUserPrincipal) && ($path != $currentUserPrincipal))
		{
			return $currentUserPrincipal;
		}

		return null;
	}

	public function GetCalendarList($path = '/', $logger = null)
	{
		$this->ClearErrors();

		$i = 0;
		do
		{
			$i++;

			$result = $this->GetCalendarListByPath($path, $logger);
			if (is_null($result) || is_array($result))
			{
				return $result;
			}

			$path = $result;
		}
		while ($i < 5);

		return null;
	}

	public function GetCalendarModificationLabel($path = '/')
	{
		$this->ClearErrors();

		$this->Connect();

		$result = $this->Propfind(
			$path,
			array(
				array("getctag", "http://calendarserver.org/ns/")
			),
			null,
			0
		);

		$this->Disconnect();

		if (is_null($result) || $this->getError())
		{
			return null;
		}

		$xmlDoc = $result->GetBodyXml();

		$getctag = null;

		$arPropstat = $xmlDoc->GetPath("/*/response/propstat");
		foreach ($arPropstat as $propstat)
		{
			$arStatus = $propstat->GetPath("/propstat/status");
			if (!empty($arStatus) && preg_match("#\s200\s+OK#i", $arStatus[0]->GetContent()))
			{
				$arGetCTag = $propstat->GetPath("/propstat/prop/getctag");
				if (!empty($arGetCTag))
				{
					$getctag = $arGetCTag[0]->GetContent();
				}
			}
		}

		return $getctag;
	}

	public function GetCalendarItemsList(
		$path = '/',
		$arHrefs = null,
		$calendarData = false,
		$depth = 1,
		$arFilter = [],
		$logger = null
	)
	{
		$this->ClearErrors();

		$this->Connect();

		if (!is_array($arHrefs))
		{
			$arHrefs = array($arHrefs);
		}

		$arHrefsNew = [];
		foreach ($arHrefs as $value)
		{
			if (!empty($value))
			{
				$arHrefsNew[] = $value;
			}
		}

		$arProperties = array(
			"getcontenttype",
			"resourcetype",
			"getetag",
		);
		if ($calendarData && !empty($arHrefsNew))
		{
			$arProperties[] = array("calendar-data", "urn:ietf:params:xml:ns:caldav");
		}


		$arFilterNew = [];
		if (array_key_exists("start", $arFilter))
		{
			$arFilterNew = array("time-range" => array("start" => ConvertDateTime($arFilter["start"], "YYYYMMDD\THHMISS\Z")));
		}

		if (!empty($arHrefsNew))
		{
			$result = $this->Report(
				$path,
				$arProperties,
				$arFilterNew,
				$arHrefsNew,
				$depth,
				$logger
			);
		}
		else
		{
			$result = $this->Propfind(
				$path,
				$arProperties,
				$arFilterNew,
				$depth,
				$logger
			);
		}

		$this->Disconnect();

		if (is_null($result) || $this->getError())
		{
			return null;
		}

		$xmlDoc = $result->GetBodyXml();

		$arItems = [];

		$arResponse = $xmlDoc->GetPath("/*/response");
		foreach ($arResponse as $response)
		{
			$arHref = $response->GetPath("/response/href");
			if (!empty($arHref))
			{
				$arItem = array(
					"href" => urldecode($arHref[0]->GetContent()),
				);

				$arProps = $response->GetPath("/response/propstat/prop/*");
				foreach ($arProps as $prop)
				{
					$s = $prop->GetContent();
					if (is_string($s) || is_numeric($s))
					{
						$arItem[$prop->GetTag()] = $this->Encode($s);
					}
				}

				if ($calendarData)
				{
					$arCalendarData = $response->GetPath("/response/propstat/prop/calendar-data");
					if (!empty($arCalendarData))
					{
						$cal = new CDavICalendar($this->Encode($arCalendarData[0]->GetContent()));

						if (!$cal->getComponent())
						{
							continue;
						}

						$arEvents = $cal->GetComponents('VTIMEZONE', false);
						if (!empty($arEvents))
						{
							$arItem["calendar-data"] = $this->ConvertICalToArray($arEvents[0], $cal);
							if (count($arEvents) > 1)
							{
								$arItem["calendar-data-ex"] = [];
								$eventsCount = count($arEvents) - 1;
								for ($i = 1; $i <= $eventsCount; $i++)
								{
									$arItem["calendar-data-ex"][] = $this->ConvertICalToArray($arEvents[$i], $cal);
								}
							}
						}
					}
				}

				$arItems[] = $arItem;
			}
		}

		return $arItems;
	}

	public function GetCalendarItemsBySyncToken($path, $syncToken = null, $logger = null)
	{
		$this->Connect();
		$prop = [
			'getetag',
			'getcontenttype',
			'resourcetype',
			['calendar-data', 'urn:ietf:params:xml:ns:caldav']
		];

		$result = $this->SyncReport($path, $prop, $syncToken, $logger);
		$this->Disconnect();

		if (is_null($result) || $this->getError())
		{
			return null;
		}

		$xmlDoc = $result->GetBodyXml();

		$arItems = [];

		$arResponse = $xmlDoc->GetPath("/*/response");
		foreach ($arResponse as $response)
		{
			$arHref = $response->GetPath("/response/href");
			if (!empty($arHref))
			{
				$arItem = [
					"href" => urldecode($arHref[0]->GetContent()),
				];

				$arProps = $response->GetPath("/response/propstat/prop/*");
				foreach ($arProps as $prop)
				{
					$s = $prop->GetContent();
					if (is_string($s) || is_numeric($s))
					{
						$arItem[$prop->GetTag()] = $this->Encode($s);
					}
				}

				$status = $response->GetPath('/response/propstat/status');
				if ($status)
				{
					$arItem['status'] = '200';
				}
				else
				{
					$arItem['status'] = '404';
				}

				$arItems[] = $arItem;
			}
		}

		return $arItems;
	}

	private function ConvertICalToArray(CDavICalendarComponent $event, CDavICalendar $calendar)
	{
		static $arWeekDayMap = array("SU" => 6, "MO" => 0, "TU" => 1, "WE" => 2, "TH" => 3, "FR" => 4, "SA" => 5);

		$arFields = array(
			"NAME" => $event->GetPropertyValue("SUMMARY"),
			"VERSION" => $event->GetPropertyValue("SEQUENCE"),
			"PROPERTY_LOCATION" => $event->GetPropertyValue("LOCATION"),
			"DETAIL_TEXT" => $event->GetPropertyValue("DESCRIPTION"),
			"DETAIL_TEXT_TYPE" => 'text',
			"DATE_FROM" => CDavICalendarTimeZone::GetFormattedServerDateTime(
				$event->GetPropertyValue("DTSTART"),
				false,
				$calendar
			),
			"TZ_FROM" => $event->GetPropertyParameter("DTSTART", "TZID"),
			"DATE_TO" => CDavICalendarTimeZone::GetFormattedServerDateTime(
				$event->GetPropertyValue("DTEND"),
				false,
				$calendar
			),
			"TZ_TO" => $event->GetPropertyParameter("DTEND", "TZID"),
			"SKIP_TIME" => $event->GetPropertyParameter("DTSTART", "VALUE") === "DATE"
				&& $event->GetPropertyParameter("DTEND", "VALUE") === "DATE",
			"XML_ID" => $event->GetPropertyValue("UID"),
			"DATE_CREATE" => CDavICalendarTimeZone::GetFormattedServerDateTime($event->GetPropertyValue("CREATED")),
			"PROPERTY_CATEGORY" => $event->GetPropertyValue("CATEGORIES"),
			"ORGANIZER" => $event->GetPropertyValue("ORGANIZER"),
			"ORGANIZER_ENTITY" => $event->GetProperties("ORGANIZER"),
			"ATTACH" => $event->GetProperties("ATTACH"),
			"ATTENDEE" => $event->GetProperties("ATTENDEE"),
			"URL" => $event->GetPropertyValue("URL"),
		);

		if ($priority = $event->GetPropertyValue("PRIORITY"))
		{
			if ($priority <= 3)
			{
				$arFields["PROPERTY_IMPORTANCE"] = "high";
			}

			elseif ($priority <= 6)
			{
				$arFields["PROPERTY_IMPORTANCE"] = "normal";
			}
			else
			{
				$arFields["PROPERTY_IMPORTANCE"] = "low";
			}

		}
		else
		{
			$arFields["PROPERTY_IMPORTANCE"] = "normal";
		}

		if (($transp = $event->GetPropertyValue("TRANSP")) && $transp === 'TRANSPARENT')
		{
			$arFields["PROPERTY_ACCESSIBILITY"] = "free";
		}
		else
		{
			$arFields["PROPERTY_ACCESSIBILITY"] = "busy";
		}

		$arVAlarm = $event->GetComponents("VALARM");
		if (count($arVAlarm) > 0 && $event->GetPropertyValue("X-MOZ-LASTACK") == null)
		{
			foreach ($arVAlarm as $alarm)
			{
				$trigger = $alarm->GetPropertyValue("TRIGGER");
				$arPeriodMapTmp = array("M" => "min", "H" => "hour", "D" => "day", "S" => "min");
				if (
					preg_match('/^-PT(\d+)([HMD])$/i', $trigger, $arMatches)
					|| preg_match('/^PT(0+)(S)$/i', $trigger, $arMatches)
					|| preg_match('/^-P(\d+)(D)$/i', $trigger, $arMatches)
				)
				{
					$arFields["PROPERTY_REMIND_SETTINGS"][] = $arMatches[1]."_".$arPeriodMapTmp[$arMatches[2]];
				}
				else if (preg_match('/^(\d+)T(\d+)Z$/i', $trigger, $arMatches))
				{
					$arFields["PROPERTY_REMIND_SETTINGS"][] = $arMatches[0] . '_' . 'date';
				}
				else if (preg_match('/^PT(\d+)(H)$/i', $trigger, $arMatches))
				{
					$arFields["PROPERTY_REMIND_SETTINGS"][] = $arMatches[1]
						. '_'. $arPeriodMapTmp[$arMatches[2]] . '_' .'daybefore';
				}
				else if (preg_match('/^-P(\d+)(D)T(\d+)(H)$/i', $trigger, $arMatches))
				{
					$arFields["PROPERTY_REMIND_SETTINGS"][] = $arMatches[1] . '_' . $arPeriodMapTmp[$arMatches[2]]
						. '_' . $arMatches[3] . '_' . $arPeriodMapTmp[$arMatches[4]];
				}
			}
		}

		if ($rrule = $event->GetPropertyValueParsed("RRULE"))
		{
			// RRULE:FREQ=WEEKLY;COUNT=5;INTERVAL=2;BYDAY=TU,SA
			$arFields["PROPERTY_PERIOD_TYPE"] = $rrule["FREQ"];
			$arFields["PROPERTY_PERIOD_COUNT"] = $rrule["INTERVAL"] ?? 1;

			if ($arFields["PROPERTY_PERIOD_TYPE"] === "WEEKLY")
			{
				if (isset($rrule["BYDAY"]))
				{
					$byDays = explode(",", $rrule["BYDAY"]);
					$byDayResult = [];
					foreach ($byDays as $day)
					{
						$byDayResult[] = $arWeekDayMap[mb_strtoupper($day)];
					}
					$arFields["PROPERTY_PERIOD_ADDITIONAL"] = implode(",", $byDayResult);
				}
				else
				{
					$arFields["PROPERTY_PERIOD_ADDITIONAL"] = date("w", MakeTimeStamp($arFields["DATE_FROM"])) - 1;
					if ($arFields["PROPERTY_PERIOD_ADDITIONAL"] < 0)
					{
						$arFields["PROPERTY_PERIOD_ADDITIONAL"] = 6;
					}
				}
			}

			if (isset($rrule["COUNT"]))
			{
				$arFields["PROPERTY_RRULE_COUNT"] = $rrule["COUNT"];
			}
			elseif (isset($rrule["UNTIL"]))
			{
				$arFields["PROPERTY_PERIOD_UNTIL"] = CDavICalendarTimeZone::GetFormattedServerDateTime(
					$rrule["UNTIL"],
					$event->GetPropertyParameter("DTSTART", "TZID"),
					$calendar,
				);
			}
			else
			{
				$arFields["PROPERTY_PERIOD_UNTIL"] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), mktime(0, 0, 0, 1, 1, 2038));
			}
		}

		if ($recurrenceId = $event->GetPropertyValue("RECURRENCE-ID"))
		{
			$arFields["RECURRENCE_ID_DATE"] = CDavICalendarTimeZone::GetFormattedServerDateTime(
				$recurrenceId,
				false,
				$calendar
			);
		}


		$exDatesVal = $event->GetProperties("EXDATE");
		if (count($exDatesVal) > 0)
		{
			$arFields["EXDATE"] = [];
			foreach ($exDatesVal as $val)
			{
				$date = CDavICalendarTimeZone::GetFormattedServerDate($val->Value());
				if ($shift = $this->getExDateShift(
					$arFields['DATE_FROM'],
					$arFields['TZ_FROM'],
					$val->Parameter('TZID')
				))
				{
					$arFields["EXDATE"][] = $this->shiftDate($date, $shift);
				}
				else
				{
					$arFields["EXDATE"][] = $date;
				}
			}
		}

		return $arFields;
	}

	/**
	 * @param string $date
	 * @param int $shift
	 *
	 * @return string
	 */
	private function shiftDate(string $date, int $shift): string
	{
		$format = $GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE);
		$date = \DateTime::createFromFormat($format, $date);
		return $date->modify($shift . ' day')->format($format);
	}

	/**
	 * @param string $startTime
	 * @param $startTz
	 * @param $excludeTz
	 *
	 * @return int
	 */
	private function getExDateShift(string $startTime, $startTz, $excludeTz): int
	{
		static $start;
		try
		{
			if (empty($start))
			{
				$tzFrom = new DateTimeZone($startTz);
				$start = new \DateTime($startTime, $tzFrom);
			}

			$tzTo = new DateTimeZone($excludeTz);
			$startDateCode = $start->format('Ymd');
			$excludeDateCode = (clone $start)->setTimezone($tzTo)->format('Ymd');

			return $startDateCode <=> $excludeDateCode;
		}
		catch(\Exception $e)
		{
		    return 0;
		}
	}

	public function PutCalendarItem($path = '/', $siteId = null, $arData = [])
	{
		if (!array_key_exists("DAV_XML_ID", $arData))
		{
			$arData["DAV_XML_ID"] = self::GenerateNewCalendarItemName();
		}

		if (mb_substr($path, -mb_strlen("/".$arData["DAV_XML_ID"].".ics")) != "/".$arData["DAV_XML_ID"].".ics")
		{
			$path = rtrim($path, "/");
			$path .= "/".$arData["DAV_XML_ID"].".ics";
		}

		$data = $this->GetICalContent($arData, $siteId);
		$result = $this->Put($path, $this->Decode($data));
		if ($result)
		{
			$result = $result->GetStatus();

			if ($result == 201 || $result == 204)
			{
				$result = $this->GetCalendarItemsList($path);
				if (is_array($result) && count($result) > 0)
				{
					return [
						"XML_ID" => self::getBasenameWithoutExtension($result[0]["href"]),
						"MODIFICATION_LABEL" => $result[0]["getetag"],
					];
				}
			}
		}

		return null;
	}

	public function DeleteCalendarItem($path)
	{
		$result = $this->Delete($path);
		if ($result)
		{
			$code = $result->GetStatus();

			if ($code === 200 || $code === 201 || $code === 204)
			{
				return true;
			}
		}

		return false;
	}

	public function GetICalContent(array $event, $siteId = null)
	{
		$oneDay = 86400; //24*60*60
		$dateFrom = date('Ymd\\THis', MakeTimeStamp($event['DATE_FROM']));
		$dateTo = date('Ymd\\THis', MakeTimeStamp($event['DATE_TO']));
		$tzFrom = $event['TZ_FROM'];
		$tzTo = $event['TZ_TO'];

		$iCalEvent = [
			'TYPE' => 'VEVENT',
			'CREATED' => date('Ymd\\THis\\Z', MakeTimeStamp($event['DATE_CREATE'])),
			'LAST-MODIFIED' => date('Ymd\\THis\\Z', MakeTimeStamp($event['TIMESTAMP_X'])),
			'DTSTAMP' => date('Ymd\\THis\\Z', MakeTimeStamp($event['TIMESTAMP_X'])),
			'UID' => $event['DAV_XML_ID'],
			'SUMMARY' => $event['NAME']
		];

		if ($event['DT_SKIP_TIME'] === 'Y')
		{
			$iCalEvent['DTSTART'] = [
				'VALUE' => date('Ymd', MakeTimeStamp($event['DATE_FROM'])),
				'PARAMETERS' => ['VALUE' => 'DATE']
			];
			$iCalEvent['DTEND'] = [
				'VALUE' => date('Ymd', MakeTimeStamp($event['DATE_TO']) + $oneDay),
				'PARAMETERS' => ['VALUE' => 'DATE']
			];

		}
		else
		{
			$iCalEvent['DTSTART'] = [
				'VALUE' => $dateFrom,
				'PARAMETERS' => ['TZID' => $tzFrom]
			];
			$iCalEvent['DTEND'] = [
				'VALUE' => $dateTo,
				'PARAMETERS' => ['TZID' => $tzTo]
			];
		}

		if (
			isset($event['ACCESSIBILITY'])
			&& (
				$event['ACCESSIBILITY'] === 'free'
				|| $event['ACCESSIBILITY'] === 'quest'
			)
		)
		{
			$iCalEvent['TRANSP'] = 'TRANSPARENT';
		}
		else
		{
			$iCalEvent['TRANSP'] = 'OPAQUE';
		}

		if (
			isset($event['LOCATION'], $event['LOCATION']['NEW'])
			&& is_array($event['LOCATION'])
			&& $event['LOCATION']['NEW']
		)
		{
			$iCalEvent['LOCATION'] = $event['LOCATION']['NEW'];
		}

		if (isset($event['IMPORTANCE']))
		{
			if ($event['IMPORTANCE'] === 'low')
			{
				$iCalEvent['PRIORITY'] = 9;
			}
			elseif ($event['IMPORTANCE'] === 'high')
			{
				$iCalEvent['PRIORITY'] = 1;
			}
			else
			{
				$iCalEvent['PRIORITY'] = 5;
			}
		}

		if (isset($event['DESCRIPTION']) && $event['DESCRIPTION'])
		{
			$iCalEvent['DESCRIPTION'] = $event['DESCRIPTION'];
		}

		if (isset($event['PROPERTY_REMIND_SETTINGS']) && $event['PROPERTY_REMIND_SETTINGS'])
		{
			$arPeriodMapTmp = [
				'min' => 'M',
				'hour' => 'H',
				'day' => 'D'
			];
			$ar = explode('_', $event['PROPERTY_REMIND_SETTINGS']);

			$iCalEvent['@VALARM'] = [
				'TYPE' => 'VALARM',
				'ACTION' => 'DISPLAY',
				'TRIGGER' => [
					'PARAMETERS' => ['VALUE' => 'DURATION'],
					'VALUE' => '-PT' . $ar[0] . $arPeriodMapTmp[$ar[1]]
				]
			];
		}

		if (isset($event['RRULE']) && is_array($event['RRULE']))
		{
			$val = 'FREQ=' . $event['RRULE']['FREQ'];
			if (isset($event['RRULE']['INTERVAL']) && $event['RRULE']['INTERVAL'] !== '')
			{
				$val .= ';INTERVAL=' . $event['RRULE']['INTERVAL'];
			}
			if (isset($event['RRULE']['BYDAY']) && $event['RRULE']['BYDAY'] !== '')
			{
				$val .= ';BYDAY=' . $event['RRULE']['BYDAY'];
			}

			if (isset($event['RRULE']['COUNT']) && $event['RRULE']['COUNT'] > 2)
			{
				$val .= ';COUNT=' . (int)$event['RRULE']['COUNT'];
			}
			elseif (isset($event['RRULE']['UNTIL']))
			{
				if ($event['RRULE']['UNTIL'] != '' && (int)$event['RRULE']['UNTIL'] == $event['RRULE']['UNTIL'])
				{
					$val .= ';UNTIL=' . date('Ymd\\THis\\Z', $event['RRULE']['UNTIL']);
				}
				else if($event['RRULE']['UNTIL'] != '')
				{
					$val .= ';UNTIL=' . date('Ymd', MakeTimeStamp($event['RRULE']['UNTIL'])) . 'T235959Z';
				}
			}
			else
			{
				$val .= ';UNTIL=' . date('Ymd\\THis\\Z', $event['DATE_TO_TS_UTC'] + (int)date('Z'));
			}

			$iCalEvent['RRULE'] = $val;
		}

		// TODO: we have to update SEQUENCE corresponding to rfc5546
		$iCalEvent['SEQUENCE'] = $event['VERSION'];

		if (
			isset($event['EXDATE'], $event['RRULE'])
			&& $event['EXDATE']
			&& $event['RRULE']
		)
		{
			$event['EXDATE'] = explode(';', $event['EXDATE']);

			$exdate = [];
			foreach ($event['EXDATE'] as $date)
			{
				if ($event['DT_SKIP_TIME'] === 'Y')
				{
					$exdate[] = date('Ymd', MakeTimeStamp($date));
				}
				else
				{
					$exdate[] = date('Ymd', MakeTimeStamp($date)) . 'T' . date('His', MakeTimeStamp($event['DATE_FROM']));
				}
			}

			if (!empty($exdate))
			{
				if ($event['DT_SKIP_TIME'] === 'Y')
				{
					$iCalEvent['EXDATE'] = [
						'VALUE' => implode(',', $exdate),
						'PARAMETERS' => [
							'VALUE' => 'DATE'
						]
					];
				}
				else
				{
					$iCalEvent['EXDATE'] = [
						'VALUE' => implode(',', $exdate),
						'PARAMETERS' => [
							'TZID' => $tzFrom,
							'VALUE' => 'DATE-TIME'
						]
					];
				}
			}
		}

		return (new CDavICalendar($iCalEvent, $siteId))->Render();
	}

	public static function GenerateNewCalendarItemName()
	{
		return str_replace(".", "-", uniqid("BX-", true));
	}

	public static function InitUserEntity()
	{
		if (!CModule::IncludeModule("calendar"))
		{
			return;
		}

		//if (!defined("BX_NO_ACCELERATOR_RESET"))
		//	define("BX_NO_ACCELERATOR_RESET", true);
	}

	public static function getBasenameWithoutExtension($href)
	{
		$calendarItemPathInfo = pathinfo($href);
		return basename($href, '.' . $calendarItemPathInfo['extension']);
	}

	public static function DataSync($paramEntityType = null, $paramEntityId = 0)
	{
		if (DAV_CALDAV_DEBUG)
		{
			CDav::WriteToLog("Starting CalDAV sync", "SYNCC");
		}

		self::InitUserEntity();

		$maxNumber = 5;
		$index = 0;
		$bShouldClearCache = false;

		$paramEntityId = (int)$paramEntityId;
		$arConnectionsFilter = ['ACCOUNT_TYPE' => [
			'caldav',
			\Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_CALDAV
		]];
		if (!is_null($paramEntityType) && ($paramEntityId > 0))
		{
			$arConnectionsFilter["ENTITY_TYPE"] = $paramEntityType;
			$arConnectionsFilter["ENTITY_ID"] = $paramEntityId;
		}

		$syncInfo = [];
		$dbConnections = CDavConnection::GetList(
			["SYNCHRONIZED" => "ASC"],
			$arConnectionsFilter,
			false,
			false,
			[
				'ID',
				'ENTITY_TYPE',
				'ENTITY_ID',
				'ACCOUNT_TYPE',
				'SERVER_SCHEME',
				'SERVER_HOST',
				'SERVER_PORT',
				'SERVER_USERNAME',
				'SERVER_PASSWORD',
				'SERVER_PATH',
				'SYNCHRONIZED',
			]
		);
		while ($arConnection = $dbConnections->Fetch())
		{
			$index++;
			if ($index > $maxNumber)
			{
				break;
			}

			if (DAV_CALDAV_DEBUG)
			{
				CDav::WriteToLog("Connection [".$arConnection["ID"]."] ".$arConnection["ENTITY_TYPE"]."/".$arConnection["ENTITY_ID"], "SYNCC");
			}

			CDavConnection::SetLastResult($arConnection["ID"], "[0]");

			$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
			if (CDav::UseProxy())
			{
				$arProxy = CDav::GetProxySettings();
				$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
			}
			if ($arConnection['ACCOUNT_TYPE'] === \Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_CALDAV)
			{
				$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);
			}

			if (!$client->CheckWebdavServer($arConnection["SERVER_PATH"]))
			{
				$t = '';
				$arErrors = $client->GetErrors();
				foreach ($arErrors as $arError)
				{
					if ($t !== '')
					{
						$t .= ', ';
					}
					$t .= '['.$arError[0].'] '.$arError[1];
				}

				CDavConnection::SetLastResult($arConnection["ID"], (($t !== '') ? $t : "[404] Not Found"));
				$caldavHelper = ServiceLocator::getInstance()->get('calendar.service.caldav.helper');
				$connectionType = ($caldavHelper->isYandex($arConnection['SERVER_HOST'])
					? 'yandex' : 'caldav'
				);
				$connectionName = $connectionType.$arConnection['ID'];
				$connectionStatus = CCalendarSync::isConnectionSuccess($t);
				Util::addPullEvent('refresh_sync_status', $arConnection['ENTITY_ID'], [
					'syncInfo' => [
						$connectionName => [
							'syncTimestamp' => time() - CTimeZone::GetOffset((int)$arConnection['ENTITY_ID']),
							'status' => $connectionStatus,
							'type' => $connectionType,
							'connected' => true,
						],
					],
					'requestUid' => Util::getRequestUid(),
				]);

				if (DAV_CALDAV_DEBUG)
				{
					CDav::WriteToLog("ERROR: " . $t, "SYNCC");
				}

				continue;
			}

			$arCalendarsList = $client->GetCalendarList($arConnection["SERVER_PATH"]);

			if (!is_array($arCalendarsList) || empty($arCalendarsList))
			{
				CDavConnection::SetLastResult($arConnection["ID"], "[204] No Content");

				continue;
			}

			$arUserCalendars = [];
			foreach ($arCalendarsList as $value)
			{
				$arUserCalendars[] = [
					"XML_ID" => $value["href"] ?? null,
					"NAME" => $value["displayname"] ?? null,
					"DESCRIPTION" => $value["calendar-description"] ?? null,
					"COLOR" => $value["calendar-color"] ?? null,
					"MODIFICATION_LABEL" => $value["getctag"] ?? null,
				];
			}
			$tmpNumCals = count($arUserCalendars);
			$tmpNumItems = 0;

			$arUserCalendars = CCalendarSync::SyncCalendarSections(
				"caldav",
				$arUserCalendars,
				$arConnection["ENTITY_TYPE"],
				$arConnection["ENTITY_ID"],
				$arConnection["ID"]
			);

			foreach ($arUserCalendars as $userCalendar)
			{
				$arCalendarItemsList = $client->GetCalendarItemsList($userCalendar["XML_ID"]);

				if(!empty($arCalendarItemsList) && is_array($arCalendarItemsList))
				{
					$arUserCalendarItems = [];
					foreach ($arCalendarItemsList as $value)
					{
						if (
							isset($value["getetag"])
							&& mb_strpos(($value["getcontenttype"] ?? null), "text/calendar") !== false
						)
						{
							$xmlId = self::getBasenameWithoutExtension($value["href"]);
							$arUserCalendarItems[$xmlId] = $value["getetag"];
						}
					}

					$arModifiedUserCalendarItems = CCalendar::SyncCalendarItems(
						"caldav",
						$userCalendar["CALENDAR_ID"],
						$arUserCalendarItems
					);

					$arHrefs = [];
					$arIdMap = [];
					foreach ($arModifiedUserCalendarItems as $value)
					{
						$h = $client->GetRequestEventPath($userCalendar["XML_ID"], $value["XML_ID"]);
						$arHrefs[] = $h;
						$arIdMap[$h] = $value["ID"];
					}

					$arCalendarItemsList = $client->GetCalendarItemsList($userCalendar["XML_ID"], $arHrefs, true);
					if (is_array($arCalendarItemsList))
					{
						$tmpNumItems += count($arCalendarItemsList);

						foreach ($arCalendarItemsList as $value)
						{
							if (!array_key_exists($value["href"], $arIdMap))
							{
								continue;
							}

							$arModifyEventArray = [
								"ID" => $arIdMap[$value["href"]],
								"NAME" => $value["calendar-data"]["NAME"],
								"DETAIL_TEXT" => $value["calendar-data"]["DETAIL_TEXT"],
								"DETAIL_TEXT_TYPE" => $value["calendar-data"]["DETAIL_TEXT_TYPE"],
								"XML_ID" => self::getBasenameWithoutExtension($value["href"]),
								"PROPERTY_LOCATION" => $value["calendar-data"]["PROPERTY_LOCATION"],
								"DATE_FROM" => $value["calendar-data"]["DATE_FROM"],
								"DATE_TO" => $value["calendar-data"]["DATE_TO"],
								"TZ_FROM" => $value["calendar-data"]["TZ_FROM"],
								"TZ_TO" => $value["calendar-data"]["TZ_TO"],
								"DT_LENGTH" => $value["calendar-data"]["DT_LENGTH"] ?? null,
								"SKIP_TIME" => $value["calendar-data"]["SKIP_TIME"] ?? null,
								"PROPERTY_IMPORTANCE" => $value["calendar-data"]["PROPERTY_IMPORTANCE"] ?? null,
								"PROPERTY_ACCESSIBILITY" => $value["calendar-data"]["PROPERTY_ACCESSIBILITY"] ?? null,
								"PROPERTY_REMIND_SETTINGS" => $value["calendar-data"]["PROPERTY_REMIND_SETTINGS"] ?? null,
								"PROPERTY_PERIOD_TYPE" => "NONE",
								"PROPERTY_BXDAVCD_LABEL" => $value["getetag"] ?? null,
								"VERSION" => $value["calendar-data"]["VERSION"] ?? null,
								"ORGANIZER" => $value["calendar-data"]["ORGANIZER"] ?? null,
							];

							if (isset($value["calendar-data"]["PROPERTY_PERIOD_TYPE"]) && $value["calendar-data"]["PROPERTY_PERIOD_TYPE"] !== "NONE")
							{
								$arModifyEventArray["PROPERTY_PERIOD_TYPE"] = $value["calendar-data"]["PROPERTY_PERIOD_TYPE"] ?? null;
								$arModifyEventArray["PROPERTY_PERIOD_COUNT"] = $value["calendar-data"]["PROPERTY_PERIOD_COUNT"] ?? null;
								$arModifyEventArray["PROPERTY_PERIOD_ADDITIONAL"] = $value["calendar-data"]["PROPERTY_PERIOD_ADDITIONAL"] ?? null;
								$arModifyEventArray["PROPERTY_EVENT_LENGTH"] = $value["calendar-data"]["PROPERTY_EVENT_LENGTH"] ?? null;
								$arModifyEventArray["PROPERTY_PERIOD_UNTIL"] = $value["calendar-data"]["PROPERTY_PERIOD_UNTIL"] ?? null;
								$arModifyEventArray["EXDATE"] = $value["calendar-data"]["EXDATE"] ?? null;
								$arModifyEventArray["PROPERTY_RRULE_COUNT"] = $value["calendar-data"]["PROPERTY_RRULE_COUNT"] ?? null;
							}
							$k = CCalendarSync::ModifyEvent(
								$userCalendar["CALENDAR_ID"],
								$arModifyEventArray,
							);

							if (
								isset($value['calendar-data-ex'])
								&& is_array($value['calendar-data-ex'])
								&& !empty($value['calendar-data-ex'])
							)
							{
								CCalendarSync::ModifyReccurentInstances([
									'events' => $value['calendar-data-ex'],
									'parentId' => $k,
									'calendarId' => $userCalendar['CALENDAR_ID'],
								]);
							}
						}
					}
				}
			}

			if (DAV_CALDAV_DEBUG)
			{
				CDav::WriteToLog("Sync ".(int)$tmpNumCals." calendars, ".(int)$tmpNumItems." items", "SYNCC");
			}

			CDavConnection::SetLastResult($arConnection["ID"], "[200] OK");
			$caldavHelper = ServiceLocator::getInstance()->get('calendar.service.caldav.helper');
			$connectionType = ($caldavHelper->isYandex($arConnection['SERVER_HOST'])
				? 'yandex' : 'caldav'
			);
			$connectionName = $connectionType.$arConnection['ID'];
			Util::addPullEvent('refresh_sync_status', $arConnection['ENTITY_ID'], [
				'syncInfo' => [
					$connectionName => [
						'syncTimestamp' => time() - CTimeZone::GetOffset((int)$arConnection['ENTITY_ID']),
						'status' => true,
						'type' => $connectionType,
						'connected' => true,
					],
				],
				'requestUid' => Util::getRequestUid(),
			]);
		}

		if (DAV_CALDAV_DEBUG)
			CDav::WriteToLog("CalDAV sync finished", "SYNCC");

		return "CDavGroupdavClientCalendar::DataSync();";
	}

	public static function DoAddItem($connectionId, $calendarXmlId, $arFields)
	{
		if (DAV_CALDAV_DEBUG)
		{
			CDav::WriteToLog("CalDAV DoAddItem called for connection ".$connectionId, "MDFC");
		}

		$connectionId = (int)$connectionId;
		if ($connectionId <= 0)
		{
			return null;
		}

		$arConnection = CDavConnection::GetById($connectionId);
		if (!is_array($arConnection))
		{
			return null;
		}

		$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
		if (CDav::UseProxy())
		{
			$arProxy = CDav::GetProxySettings();
			$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
		}
		if ($arConnection['ACCOUNT_TYPE'] === \Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_CALDAV)
		{
			$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);
		}

		//$client->Debug();
		self::InitUserEntity();

		$result = $client->PutCalendarItem($calendarXmlId, SITE_ID, $arFields);

		if (!is_null($result))
			return $result;

		return $client->GetErrors();
	}

	public static function DoUpdateItem($connectionId, $calendarXmlId, $itemXmlId, $itemModificationLabel, $arFields)
	{
		if (DAV_CALDAV_DEBUG)
		{
			CDav::WriteToLog("CalDAV DoUpdateItem called for connection ".$connectionId, "MDFC");
		}

		$connectionId = (int)$connectionId;
		if ($connectionId <= 0)
		{
			return null;
		}


		$arConnection = CDavConnection::GetById($connectionId);
		if (!is_array($arConnection))
		{
			return null;
		}

		$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
		if (CDav::UseProxy())
		{
			$arProxy = CDav::GetProxySettings();
			$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
		}
		if ($arConnection['ACCOUNT_TYPE'] === \Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_CALDAV)
		{
			$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);
		}

		//$client->Debug();
		self::InitUserEntity();

		$arFields["XML_ID"] = $itemXmlId;
		$result = $client->PutCalendarItem($client->GetRequestEventPath($calendarXmlId, $itemXmlId), SITE_ID, $arFields);

		if (!is_null($result))
		{
			return $result;
		}

		return $client->GetErrors();
	}

	public static function DoDeleteItem($connectionId, $calendarXmlId, $itemXmlId)
	{
		if (DAV_CALDAV_DEBUG)
		{
			CDav::WriteToLog("CalDAV DoDeleteItem called for connection ".$connectionId, "MDFC");
		}

		$connectionId = (int)$connectionId;
		if ($connectionId <= 0)
		{
			return null;
		}

		$arConnection = CDavConnection::GetById($connectionId);
		if (!is_array($arConnection))
		{
			return null;
		}

		$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
		if (CDav::UseProxy())
		{
			$arProxy = CDav::GetProxySettings();
			$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
		}
		if ($arConnection['ACCOUNT_TYPE'] === \Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_CALDAV)
		{
			$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);
		}

		//$client->Debug();

		self::InitUserEntity();

		$result = $client->DeleteCalendarItem($client->GetRequestEventPath($calendarXmlId, $itemXmlId));
		if ($result === true)
		{
			return true;
		}

		return $client->GetErrors();
	}

	public static function DoAddCalendar($connectionId, $arFields)
	{
		return array(array(501, "Not Implemented"));
	}

	public static function DoUpdateCalendar($connectionId, $itemXmlId, $itemModificationLabel, $arFields)
	{
		return array(array(501, "Not Implemented"));
	}

	public static function DoDeleteCalendar($connectionId, $itemXmlId)
	{
		return array(array(501, "Not Implemented"));
	}

	public static function IsCalDAVEnabled()
	{
		$agentCalendar = COption::GetOptionString("dav", "agent_calendar_caldav", "N");
		return ($agentCalendar == "Y");
	}

	public static function DoCheckCalDAVServer($scheme, $host = null, $port = null, $username = null, $password = null, $path = null, $oauth = null)
	{
		if ($scheme."!" == intval($scheme)."!")
		{
			$scheme = intval($scheme);
			if ($scheme <= 0)
				return false;

			$arConnection = CDavConnection::GetById($scheme);
			if (!is_array($arConnection))
				return false;

			$scheme = $arConnection["SERVER_SCHEME"];
			$host = $arConnection["SERVER_HOST"];
			$port = $arConnection["SERVER_PORT"];
			$username = $arConnection["SERVER_USERNAME"];
			$password = $arConnection["SERVER_PASSWORD"];
			$path = $arConnection["SERVER_PATH"];

			if ($arConnection['ACCOUNT_TYPE'] === \Bitrix\Calendar\Sync\Google\Helper::GOOGLE_ACCOUNT_TYPE_CALDAV)
			{
				$oauth = [
					'type' => 'google',
					'id' => $arConnection['ENTITY_ID']
				];
			}
		}

		$client = new CDavGroupdavClientCalendar($scheme, $host, $port, $username, $password);
		$client->SetPrivateIp(false);
		if (CDav::UseProxy())
		{
			$arProxy = CDav::GetProxySettings();
			$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
		}
		if (!empty($oauth['type']) && $oauth['type'] == 'google')
		{
			$client->setGoogleCalendarOAuth($oauth['id']);
		}

		return $client->CheckWebdavServer($path);
	}

	public static function CheckCaldavServer($url, $host, $userName, $userPassword)
	{
		$options = [];
		if (CDav::UseProxy())
		{
			$arProxy = CDav::GetProxySettings();
			$options = [
				"proxyHost" => $arProxy["PROXY_SCHEME"],
				"proxyPort" => $arProxy["PROXY_PORT"],
				"proxyUser" => $arProxy["PROXY_USERNAME"],
				"proxyPassword" => $arProxy["PROXY_PASSWORD"],
			];
		}

		$client = new HttpClient($options);
		$client->setPrivateIp(true);
		$client->setHeader("User-Agent", "Bitrix CalDAV/CardDAV/GroupDAV client");
		$client->setHeader("Connection", "Keep-Alive");
		$client->setHeader("Host", $host);

		for ($i = 0; $i < 3; $i++)
		{
			$client->query(HttpClient::HTTP_OPTIONS, $url);

			if ($client->getStatus() == 401)
			{
				$client->setHeader('Authorization', 'Basic ' . base64_encode($userName.":".$userPassword));
				continue;
			}

			break;
		}

		$headers = $client->getHeaders();
		if (!empty($headers['dav']))
		{
			$davPart = explode(",", $headers['dav']);
			foreach ($davPart as $part)
			{
				if (trim($part)."!" == "1!")
				{
					return true;
				}
			}
		}

		return false;
	}

	public function GetRequestEventPath($calendarXmlId = '', $itemXmlId = '')
	{
		return rtrim($calendarXmlId, '/').'/'.$itemXmlId.".ics";
	}

	public function setGoogleCalendarOAuth($id)
	{
		CModule::includeModule('socialservices');

		$googleOAuthClient = new CSocServGoogleOAuth($id);
		$googleOAuthClient->getEntityOAuth()->addScope([
			'https://www.googleapis.com/auth/calendar',
			'https://www.googleapis.com/auth/calendar.readonly'
		]);
		if ($token = $googleOAuthClient->getStorageToken())
		{
			$this->setGoogleOAuth($token);
		}
	}
}
?>