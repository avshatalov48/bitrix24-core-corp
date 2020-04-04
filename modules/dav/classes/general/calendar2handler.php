<?
IncludeModuleLangFile(__FILE__);

if (CModule::IncludeModule("calendar") && class_exists("CCalendar") && !class_exists("CDavCalendarHandler"))
{
	class CDavCalendarHandler
		extends CDavGroupdavHandler
	{
		protected function GetMethodMinimumPrivilege($method)
		{
			static $arMethodMinimumPrivilegeMap = array(
				'GET' => 'urn:ietf:params:xml:ns:caldav:read-free-busy',
				'PUT' => 'DAV:write',
				'DELETE' => 'DAV:write',
			);
			return $arMethodMinimumPrivilegeMap[$method];
		}

		public function __construct($groupdav, $app)
		{
			parent::__construct($groupdav, $app);
		}

		public function GetCollectionProperties(CDavResource $resource, $siteId, $account = null, $currentApplication = null, $arPath = null, $options = 0)
		{
			$request = $this->groupdav->GetRequest();
			$currentPrincipal = $request->GetPrincipal();

			$homeUrl = $this->GetHomeCollectionUrl($siteId, $account, $arPath);
			$resource->AddProperty('calendar-home-set', array('href', $request->GetBaseUri().$homeUrl), CDavGroupDav::CALDAV);
			$resource->AddProperty('schedule-outbox-URL', array('href', $request->GetBaseUri().$homeUrl, CDavGroupDav::DAV), CDavGroupDav::CALDAV);

			if ($currentApplication == "calendar")
			{
				$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
				if (isset($calendarId[0]))
				{
					$this->GetCalendarProperties($resource, $siteId, $account, $arPath, $options);

					$notify = new \Bitrix\Main\Event(
						'dav', 'OnDavCalendarProperties',
						array(
							'id'    => $calendarId,
							'agent' => $request->getParameter('HTTP_USER_AGENT')
						)
					);
					$notify->send();
				}
				else
				{
					$resource->AddProperty('resourcetype', array(array('collection', '')));
				}
			}
		}

		public function GetCalendarProperties(CDavResource $resource, $siteId, $account = null, $arPath = null, $options = 0)
		{
			$resource->AddProperty('resourcetype',
				array(
					array('collection', ''),
					//array('vevent-collection', '', CDavGroupDav::GROUPDAV),
					array('calendar', '', CDavGroupDav::CALDAV),
				)
			);
			$resource->AddProperty('component-set', 'VEVENT', CDavGroupDav::GROUPDAV);
			$resource->AddProperty('supported-calendar-component-set',
				array(
					array('comp', array('name' => 'VCALENDAR'), CDavGroupDav::CALDAV),
					array('comp', array('name' => 'VTIMEZONE'), CDavGroupDav::CALDAV),
					array('comp', array('name' => 'VEVENT'), CDavGroupDav::CALDAV)
				),
				CDavGroupDav::CALDAV
			);
			$resource->AddProperty('supported-report-set',
				array('supported-report',
					array(CDavResource::MakeProp('report', array(CDavResource::MakeProp('calendar-multiget', '', CDavGroupDav::CALDAV))))
				)
			);
			$resource->AddProperty('supported-calendar-data',
				array(
					array('calendar-data', array('content-type' => 'text/calendar', 'version'=> '2.0'), CDavGroupDav::CALDAV),
					array('calendar-data', array('content-type' => 'text/x-calendar', 'version'=> '1.0'), CDavGroupDav::CALDAV)
				),
				CDavGroupDav::CALDAV
			);

			$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
			if ($calendarId[0] > 0)
			{
				$arCalendarList = CCalendar::GetCalendarList($calendarId, array('skipExchange' => true, 'active' => true));
				if (count($arCalendarList) > 0)
				{
					$name = isset($arCalendarList[0]["~NAME"]) ? $arCalendarList[0]["~NAME"] : $arCalendarList[0]["NAME"];
					$resource->AddProperty('displayname', $name, CDavGroupDav::DAV);
					$resource->AddProperty('calendar-description', $arCalendarList[0]["DESCRIPTION"], CDavGroupDav::CALDAV);
					$resource->AddProperty('calendar-color', $arCalendarList[0]["COLOR"], CDavGroupDav::ICAL);
				}
			}

			$request = $this->groupdav->GetRequest();
			$resource->AddProperty('getctag', $this->GetCTag($siteId, $account, $arPath), CDavGroupDav::CALENDARSERVER);


			$arAccount = null;
			if ($account != null)
			{
				$arAccount = CDavAccount::GetAccountById($account);

				$resource->AddProperty('calendar-user-address-set',
					array(
						array('href', 'MAILTO:'.$arAccount['EMAIL']),
						array('href', $request->GetBaseUri().'/principals/'.$arAccount["TYPE"].'/'.$arAccount["CODE"].'/'),
						array('href', 'urn:uuid:'.$arAccount["ID"])
					),
					CDavGroupDav::CALDAV
				);
			}
		}

		public function CheckPrivilegesByPath($testPrivileges, $principal, $siteId, $account, $arPath)
		{
			$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
			if ($calendarId == null)
				return false;

			return $this->CheckPrivileges($testPrivileges, $principal, $calendarId);
		}

		public function CheckPrivileges($testPrivileges, $principal, $calendarId)
		{
			if (is_object($principal) && ($principal instanceof CDavPrincipal))
				$principal = $principal->Id();

			if (!is_numeric($principal))
				return false;

			$principal = IntVal($principal);
			$calendarIdNorm = implode("-", $calendarId);

			static $arCalendarPrivilegesCache = array();


			if (!isset($arCalendarPrivilegesCache[$calendarIdNorm][$principal]))
			{
				$arPriv = array();
				// $arPrivOrig = array('bAccess' => true/false, 'bReadOnly' => true/false, 'privateStatus' => 'time'/'title');
				$arPrivOrig = CCalendar::GetUserPermissionsForCalendar($calendarId, $principal);
				if ($arPrivOrig['bAccess'])
				{
					$arPriv[] = 'urn:ietf:params:xml:ns:caldav:read-free-busy';
					if (!isset($arPrivOrig['privateStatus']) || $arPrivOrig['privateStatus'] != 'time')
						$arPriv[] = 'DAV::read';

					if (!$arPrivOrig['bReadOnly'])
					{
						$arPriv[] = 'DAV:write';
						$arPriv[] = 'DAV:bind';
						$arPriv[] = 'DAV:unbind';
						$arPriv[] = 'DAV:write-properties';
						$arPriv[] = 'DAV:write-content';
					}
				}

				$arCalendarPrivilegesCache[$calendarIdNorm][$principal] = CDav::PackPrivileges($arPriv);
			}

			$testPrivilegesBits = CDav::PackPrivileges($testPrivileges);

			return ($arCalendarPrivilegesCache[$calendarIdNorm][$principal] & $testPrivilegesBits) > 0;
		}

		public function GetCalendarId($siteId, $account, $arPath)
		{
			if (is_null($arPath))
				return null;

			if (is_null($account))
			{
				if (count($arPath) == 0)
					return null;
				return array(null, null, null);
			}

			$arAccount = CDavAccount::GetAccountById($account);
			if ($arAccount["ID"] <= 0)
				return null;

			return array(isset($arPath[0]) ? $arPath[0] : null, $arAccount["TYPE"], $arAccount["ID"]);
		}

		public function GetHomeCollectionUrl($siteId, $account, $arPath)
		{
			if (is_null($siteId))
				return "";

			$url = "/".$siteId;

			if (is_null($account))		//	/calendar/12/
			{
				if (is_null($arPath) || count($arPath) == 0)
					return "";

				return $url."/calendar/".$arPath[0]."/";
			}

			$arAccount = CDavAccount::GetAccountById($account);
			if (is_null($arAccount))
				return "";

			return $url."/".$arAccount["CODE"]."/calendar/";
		}

		public function GetCTag($siteId, $account, $arPath)
		{
			$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
			if ($calendarId == null)
				return null;

			$label = CCalendarSect::GetModificationLabel($calendarId);
			$label = MakeTimeStamp($label);
			return 'BX:'.$label;
		}

		// Handle propfind in the calendar folder
		public function Propfind(&$arResources, $siteId, $account, $arPath, $id = null)
		{
			$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
			if ($calendarId == null)
				return '404 Not Found';

			$request = $this->groupdav->GetRequest();
			$currentPrincipal = $request->GetPrincipal();

			if (!$this->CheckPrivileges('urn:ietf:params:xml:ns:caldav:read-free-busy', $currentPrincipal, $calendarId))
				return '403 Forbidden';

			$requestDocument = $request->GetXmlDocument();

			$path = CDav::CheckIfRightSlashAdded($request->GetPath());

			if (!isset($calendarId[0], $calendarId[1]))
			{
				$arCalendarList = CCalendar::GetCalendarList($calendarId, array('skipExchange' => true, 'active' => true));
				foreach ($arCalendarList as $calendar)
				{
					$resource = new CDavResource($path.$calendar["ID"]."/");
					$this->GetCalendarProperties($resource, $siteId, $account, array($calendar["ID"]), 0);
					$arResources[] = $resource;
				}
				return true;
			}

			$bCalendarData = (count($requestDocument->GetPath('/*/DAV::allprop')) > 0);
			if (!$bCalendarData || $requestDocument->GetRoot()->GetXmlns() != CDavGroupDav::CALDAV)
			{
				$arProp = $requestDocument->GetPath('/*/DAV::prop/*');
				foreach ($arProp as $prop)
				{
					if ($prop->GetTag() == 'calendar-data')
					{
						$bCalendarData = true;
						break;
					}
				}
			}

			$calendarSettings = \CCalendar::GetUserSettings($account[1]);
			$pastMonth = (isset($calendarSettings['syncPeriodPast']) && $calendarSettings['syncPeriodPast'] > 0) ? $calendarSettings['syncPeriodPast']: 3; // default tree monthes back

			$futureMonth = (isset($calendarSettings['syncPeriodFuture']) && $calendarSettings['syncPeriodFuture'] > 0) ?
				$calendarSettings['syncPeriodFuture']: 12; // default one year into the future

			$arFilter = array(
				'DATE_START' => ConvertTimeStamp(time() - $pastMonth * 31 * 24 * 3600, "FULL"),
				'DATE_END' => ConvertTimeStamp(time() + $futureMonth * 31 * 24 * 3600, "FULL")
			);

			if (($id || $requestDocument->GetRoot() && $requestDocument->GetRoot()->GetTag() != 'propfind') && !$this->PrepareFilters($arFilter, $requestDocument, $id))
				return false;

			if ($calendarId[0] > 0)
			{
				$arEvents = \CCalendar::GetDavCalendarEventsList($calendarId, $arFilter);

				foreach ($arEvents as $event)
				{
					if (!$this->CheckPrivileges('DAV::read', $currentPrincipal, $calendarId))
						$this->ClearPrivateData($event);

					$resource = new CDavResource($path.$this->GetPath($event));
					$resource->AddProperty('getetag', $this->GetETag($calendarId, $event));
					$resource->AddProperty('getcontenttype', $request->GetAgent() != 'kde' ? 'text/calendar; charset=utf-8; component=VEVENT' : 'text/calendar');
					$resource->AddProperty('getlastmodified', MakeTimeStamp($event['TIMESTAMP_X']));
					$resource->AddProperty('resourcetype', '');

					if ($bCalendarData)
					{
						$content = $this->GetICalContent($event, $siteId);
						$resource->AddProperty('getcontentlength', strlen($content));
						$resource->AddProperty('calendar-data', $content, CDavGroupDav::CALDAV);
					}
					else
					{
						$resource->AddProperty('getcontentlength', "");
					}

					$arResources[] = $resource;
				}
			}

			return true;
		}

		private function PrepareFilters(&$arFilter, $requestDocument, $id)
		{
			// We have options in bitrix calendar to set time range
//			$arNodes = $requestDocument->GetPath('/*/filter');
//			if (count($arNodes) > 0)
//			{
//				$dateStartOld = $arFilter['DATE_START'];
//				$dateEndOld = $arFilter['DATE_END'];
//
//				unset($arFilter['DATE_START']);
//				unset($arFilter['DATE_END']);
//
//				$numberOfItems = count($arFilter);
//
//				$arNodes = $requestDocument->GetPath('/*/filter/*/*/time-range');
//				if (count($arNodes) > 0)
//				{
//					if ($s = $arNodes[0]->GetAttribute('start'))
//						$arFilter['DATE_START'] = CDavICalendarTimeZone::GetFormattedServerDateTime($s);
//					if ($s = $arNodes[0]->GetAttribute('end'))
//						$arFilter['DATE_END'] = CDavICalendarTimeZone::GetFormattedServerDateTime($s);
//				}
//
//				if (count($arFilter) == $numberOfItems)			// no filters set - restore default start and end time
//				{
//					$arFilter['DATE_START'] = $dateStartOld;
//					$arFilter['DATE_END'] = $dateEndOld;
//				}
//			}

			if ($id)
			{
				if (false && is_numeric($id))
					$arFilter["ID"] = intval($id);
				else
					$arFilter['DAV_XML_ID'] = basename(urldecode($id), '.ics');
			}
			elseif ($requestDocument->GetRoot()->GetTag() == 'calendar-multiget')
			{
				$arIds = array();
				$arXmlIds = array();

				$arProp = $requestDocument->GetPath('/calendar-multiget/DAV::href');
				foreach ($arProp as $prop)
				{
					$parts = explode('/', $prop->GetContent());
					if (!($idTmp = urldecode(basename(array_pop($parts), '.ics'))))
						continue;

					if (false && is_numeric($idTmp))
						$arIds[] = $idTmp;
					else
						$arXmlIds[] = $idTmp;
				}

				if ($arIds)
					$arFilter["ID"] = (count($arIds) > 1 ? $arIds : $arIds[0]);
				if ($arXmlIds)
					$arFilter["DAV_XML_ID"] = (count($arXmlIds) > 1 ? $arXmlIds : $arXmlIds[0]);
			}

			return true;
		}

		private function ClearPrivateData(array &$event)
		{
			$event = array(
				'ID' => $event['ID'],
				'DAV_XML_ID' => $event['DAV_XML_ID'],
				'DT_FROM' => $event['DT_FROM'],
				'DT_TO' => $event['DT_TO'],
				'NAME' => GetMessage("DAV_PRIVATE"),
				'TIMESTAMP_X' => $event['TIMESTAMP_X'],
			);
		}

		private function GetICalContent(array $event, $siteId = null)
		{
			if ($event["DT_SKIP_TIME"] == "Y")
			{
				$dtStart = array(
					"VALUE" => date("Ymd", MakeTimeStamp($event["DATE_FROM"])),
					"PARAMETERS" => array("VALUE" => "DATE")
				);
				$dtEnd = array(
					"VALUE" => date("Ymd", MakeTimeStamp($event["DATE_TO"]) + 86400 /* + one day*/),
					"PARAMETERS" => array("VALUE" => "DATE")
				);
			}
			else
			{
				$dtStart = array(
					"VALUE" => date("Ymd\\THis", MakeTimeStamp($event["DATE_FROM"])),
					"PARAMETERS" => array("TZID" => $event["TZ_FROM"])
				);
				$dtEnd = array(
					"VALUE" => date("Ymd\\THis", MakeTimeStamp($event["DATE_TO"])),
					"PARAMETERS" => array("TZID" => $event["TZ_TO"])
				);
			}

			$iCalEvent = array(
				"TYPE" => "VEVENT",
				"CREATED" => date("Ymd\\THis\\Z", MakeTimeStamp($event["DATE_CREATE"])),
				"LAST-MODIFIED" => date("Ymd\\THis\\Z", MakeTimeStamp($event["TIMESTAMP_X"])),
				"DTSTAMP" => date("Ymd\\THis\\Z", MakeTimeStamp($event["TIMESTAMP_X"])),
				"UID" => $event["DAV_XML_ID"],
				"SUMMARY" => $event["NAME"],
				"DTSTART" => $dtStart,
				"DTEND" => $dtEnd
			);

			if (isset($event["ACCESSIBILITY"]) && ($event["ACCESSIBILITY"] == 'free' || $event["ACCESSIBILITY"] == 'quest'))
				$iCalEvent["TRANSP"] = 'TRANSPARENT';
			else
				$iCalEvent["TRANSP"] = 'OPAQUE';

			if (isset($event["LOCATION"]))
			{
				if(is_array($event["LOCATION"]) && isset($event["LOCATION"]["NEW"]))
					$iCalEvent["LOCATION"] = $event["LOCATION"]["NEW"];
				elseif($event["LOCATION"] != '')
					$iCalEvent["LOCATION"] = $event["LOCATION"];
			}

			if (isset($event["IMPORTANCE"]))
			{
				if ($event["IMPORTANCE"] == "low")
					$iCalEvent["PRIORITY"] = 9;
				elseif ($event["IMPORTANCE"] == "high")
					$iCalEvent["PRIORITY"] = 1;
				else
					$iCalEvent["PRIORITY"] = 5;
			}

			if (isset($event["DESCRIPTION"]) && strlen($event["DESCRIPTION"]) > 0)
				$iCalEvent["DESCRIPTION"] = $event["DESCRIPTION"];

			if (isset($event["REMIND"]) && is_array($event["REMIND"]) && count($event["REMIND"]) > 0)
			{
				$arPeriodMapTmp = array("min" => "M", "hour" => "H", "day" => "D");
				foreach($event["REMIND"] as $key => $remind)
				{
					$type = $arPeriodMapTmp[$remind['type']];
					$iCalEvent["@VALARM".$key] = array(
						"TYPE" => "VALARM",
						"ACTION" => "DISPLAY",
						"TRIGGER" => array(
							"VALUE" => "-P".($type == 'D' ? '' : 'T').$remind['count'].$type
						)
					);
				}
			}

			if (is_array($event["RRULE"]) && in_array($event["RRULE"]["FREQ"], array("DAILY", "WEEKLY", "MONTHLY", "YEARLY")))
			{
				$val = "FREQ=".$event["RRULE"]["FREQ"];
				$val .= ";INTERVAL=".$event["RRULE"]["INTERVAL"];

				if ($event["RRULE"]["FREQ"] == "WEEKLY" && count($event["RRULE"]["BYDAY"]) > 0)
					$val .= ";BYDAY=".implode(",", $event["RRULE"]["BYDAY"]);

				if (isset($event["RRULE"]["COUNT"]) && $event["RRULE"]["COUNT"])
				{
					$val .= ";COUNT=".(intval($event["RRULE"]["COUNT"]));
				}
				elseif ($event["RRULE"]["UNTIL"])
				{
					$untilTs = MakeTimeStamp($event["RRULE"]["UNTIL"]);
					$untilTs += 86400 - 1;
					$val .= ";UNTIL=".date("Ymd\\THis\\Z", $untilTs);
				}
				else
				{
					$val .= ";UNTIL=".date("Ymd\\THis\\Z", $event["DATE_TO_TS_UTC"]);
				}

				$iCalEvent["RRULE"] = $val;
			}

			if($event["EXDATE"] && $event["RRULE"])
			{
				$event["EXDATE"] = explode(';', $event["EXDATE"]);

				$exdate = array();
				foreach ($event["EXDATE"] as $date)
				{
					if ($event["DT_SKIP_TIME"] == 'Y')
					{
						$exdate[] = date("Ymd", MakeTimeStamp($date));
					}
					else
					{
						$exdate[] = date("Ymd", MakeTimeStamp($date)).'T'.date("His", MakeTimeStamp($event["DATE_FROM"]));
					}
				}

				if (!empty($exdate))
				{
					if ($event["DT_SKIP_TIME"] == 'Y')
					{
						$iCalEvent["EXDATE"] = array(
							"VALUE" => implode(',', $exdate),
							"PARAMETERS" => array(
								"VALUE" => "DATE"
							)
						);
					}
					else
					{
						$iCalEvent["EXDATE"] = array(
							"VALUE" => implode(',', $exdate),
							"PARAMETERS" => array(
								"TZID" => $event["TZ_FROM"],
								"VALUE" => "DATE-TIME"
							)
						);
					}
				}
			}

			$cal = new CDavICalendar($iCalEvent, $siteId);

			return $cal->Render();
		}

		private function GetPath($event)
		{
			$id = (is_string($event) ? $event : $event["DAV_XML_ID"]);
			return $id.'.ics'; // rawurlencode
		}

		protected function GetETag($calendarId, $event)
		{
			if (!is_array($event))
			{
				$request = $this->groupdav->GetRequest();
				if (!$this->CheckPrivileges('urn:ietf:params:xml:ns:caldav:read-free-busy', $request->GetPrincipal(), $calendarId))
					return false;

				$event = $this->Read($calendarId, $event);
			}

			return 'BX:'.$event['ID'].':'.MakeTimeStamp($event['TIMESTAMP_X']);
		}

		// return array/boolean array with entry, false if no read rights, null if $id does not exist
		public function Read($calendarId, $id)
		{
			$arEvents = CCalendar::GetDavCalendarEventsList($calendarId, array("DAV_XML_ID" => str_replace('%25', '%', $id)));
			if (count($arEvents) <= 0)
				return null;

			$request = $this->groupdav->GetRequest();
			if (!$this->CheckPrivileges('urn:ietf:params:xml:ns:caldav:read-free-busy', $request->GetPrincipal(), $calendarId))
				return false;

			$event = $arEvents[0];

			if (!$this->CheckPrivileges('DAV::read', $request->GetPrincipal(), $calendarId))
				$this->ClearPrivateData($event);

			return $event;
		}

		private function ConvertICalToArray($event, $calendar)
		{
			static $arWeekDayMap = array("SU" => 6, "MO" => 0, "TU" => 1, "WE" => 2, "TH" => 3, "FR" => 4, "SA" => 5);

			$request = $this->groupdav->GetRequest();

			$tzFrom = $event->GetPropertyParameter("DTSTART", "TZID");
			$tzTo = $event->GetPropertyParameter("DTEND", "TZID");

			$skipTime = !is_null($event->GetPropertyParameter("DTSTART", "VALUE")) &&
				!is_null($event->GetPropertyParameter("DTEND", "VALUE")) &&
				is_null($tzFrom) && is_null($tzTo);

			$dateFrom = CDavICalendarTimeZone::GetFormattedServerDateTime($event->GetPropertyValue("DTSTART"));
			$dateTo = CDavICalendarTimeZone::GetFormattedServerDateTime($event->GetPropertyValue("DTEND"));

			$arFields = array(
				"NAME" => $event->GetPropertyValue("SUMMARY"),
				"PROPERTY_LOCATION" => $event->GetPropertyValue("LOCATION"),
				"DETAIL_TEXT" => $event->GetPropertyValue("DESCRIPTION"),
				"DETAIL_TEXT_TYPE" => 'html',
				"DATE_FROM" => $dateFrom,
				"DATE_TO" => $dateTo,
				"TZ_FROM" => $tzFrom,
				"TZ_TO" => $tzTo,
				"SKIP_TIME" => $skipTime,
				"MODIFIED_BY" => $request->GetPrincipal()->Id(),
				"DAV_XML_ID" => $event->GetPropertyValue("UID"),
				"DATE_CREATE" => CDavICalendarTimeZone::GetFormattedServerDateTime($event->GetPropertyValue("CREATED")),
				"PROPERTY_CATEGORY" => $event->GetPropertyValue("CATEGORIES"),
			);

			if ($priority = $event->GetPropertyValue("PRIORITY"))
			{
				if ($priority <= 3)
					$arFields["PROPERTY_IMPORTANCE"] = "high";
				elseif ($priority > 3 && $priority <= 6)
					$arFields["PROPERTY_IMPORTANCE"] = "normal";
				else
					$arFields["PROPERTY_IMPORTANCE"] = "low";
			}
			else
			{
				$arFields["PROPERTY_IMPORTANCE"] = "normal";
			}

			if ($transp = $event->GetPropertyValue("TRANSP"))
			{
				if ($transp == 'TRANSPARENT')
					$arFields["PROPERTY_ACCESSIBILITY"] = "free";
				else
					$arFields["PROPERTY_ACCESSIBILITY"] = "busy";
			}
			else
			{
				$arFields["PROPERTY_ACCESSIBILITY"] = "busy";
			}

			$arVAlarm = $event->GetComponents("VALARM");
			if (count($arVAlarm) > 0 && $event->GetPropertyValue("X-MOZ-LASTACK") == null)
			{
				$arPeriodMapTmp = array("M" => "min", "H" => "hour", "D" => "day");
				foreach ($arVAlarm as $valarm)
				{
					$trigger = $valarm->GetPropertyValue("TRIGGER");

					if (preg_match('/^-P(T?)([0-9]+)([HMD])$/i', $trigger, $arMatches))
					{
						$arFields["PROPERTY_REMIND_SETTINGS"][] = $arMatches[2]."_".$arPeriodMapTmp[$arMatches[3]];
					}
				}
			}

			if ($rrule = $event->GetPropertyValueParsed("RRULE"))
			{
				// RRULE:FREQ=WEEKLY;COUNT=5;INTERVAL=2;BYDAY=TU,SA
				$arFields["PROPERTY_PERIOD_TYPE"] = $rrule["FREQ"];
				$arFields["PROPERTY_PERIOD_COUNT"] = isset($rrule["INTERVAL"]) ? $rrule["INTERVAL"] : 1;

				if ($arFields["PROPERTY_PERIOD_TYPE"] == "WEEKLY")
				{
					if (isset($rrule["BYDAY"]))
					{
						$ar = explode(",", $rrule["BYDAY"]);
						$ar1 = array();
						foreach ($ar as $v)
							$ar1[] = $arWeekDayMap[strtoupper($v)];
						$arFields["PROPERTY_PERIOD_ADDITIONAL"] = implode(",", $ar1);
					}
					else
					{
						$arFields["PROPERTY_PERIOD_ADDITIONAL"] = date("w", MakeTimeStamp($arFields["DATE_FROM"])) - 1;
						if ($arFields["PROPERTY_PERIOD_ADDITIONAL"] < 0)
							$arFields["PROPERTY_PERIOD_ADDITIONAL"] = 6;
					}
				}

				$arFields["PROPERTY_EVENT_LENGTH"] = MakeTimeStamp($arFields["DATE_TO"]) - MakeTimeStamp($arFields["DATE_FROM"]);

				if (isset($rrule["COUNT"]))
				{
					$arFields["PROPERTY_RRULE_COUNT"] = $rrule["COUNT"];
				}
				elseif (isset($rrule["UNTIL"]))
				{
					$arFields["PROPERTY_PERIOD_UNTIL"] = CCalendar::Date(MakeTimeStamp(CDavICalendarTimeZone::GetFormattedServerDate($rrule["UNTIL"])) - 86400 /* - one day*/);
				}
				else
				{
					$arFields["PROPERTY_PERIOD_UNTIL"] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), 2145938400);
				}
			}

			$recurrenceId = $event->GetPropertyValue("RECURRENCE-ID");
			if ($recurrenceId)
			{
				$arFields["RECURRENCE_ID_DATE"] = CDavICalendarTimeZone::GetFormattedServerDateTime(
					$event->GetPropertyValue("RECURRENCE-ID"),
					false,
					$calendar
				);
			}

			$exDatesVal = $event->GetProperties("EXDATE");
			if (count($exDatesVal) > 0)
			{
				$arFields["EXDATE"] = array();
				foreach ($exDatesVal as $val)
				{
					$arFields["EXDATE"][] = CDavICalendarTimeZone::GetFormattedServerDate($val->Value());
				}
			}

			return $arFields;
		}

		private function GetPeriodicEventTime($eventDate, $arParams, $number)
		{
			$number = intval($number);
			if ($number < 1)
				$number = 1;

			if (!isset($arParams["interval"]))
				$arParams["interval"] = 1;
			$arParams["interval"] = intval($arParams["interval"]);

			if (!isset($arParams["freq"]) || !in_array(strtoupper($arParams["freq"]), array('DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY')))
				$arParams["freq"] = "DAILY";
			$arParams["freq"] = strtoupper($arParams["freq"]);

			if ($arParams["freq"] == 'WEEKLY')
			{
				if (isset($arParams["byday"]))
				{
					$arOld = explode(",", $arParams["byday"]);
					$arNew = array();
					foreach ($arOld as $v)
					{
						$v = trim($v);
						if (is_numeric($v))
						{
							$v = intval($v);
							if ($v >= 0 && $v < 7)
								$arNew[] = intval($v);
						}
					}
					if (count($arNew) > 0)
					{
						sort($arNew, SORT_NUMERIC);
						$arParams["byday"] = implode(",", $arNew);
					}
					else
					{
						unset($arParams["byday"]);
					}
				}

				if (!isset($arParams["byday"]))
				{
					$arParams["byday"] = date("w", $eventDate) - 1;
					if ($arParams["byday"] < 0)
						$arParams["byday"] = 6;
				}
			}

			$newEventDate = $eventDate;

			switch ($arParams["freq"])
			{
				case 'DAILY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + $arParams["interval"] * ($number - 1), date("Y", $newEventDate));
					break;
				case 'WEEKLY':
					$newEventDateDay = date("w", $newEventDate) - 1;
					if ($newEventDateDay < 0)
						$newEventDateDay = 6;

					$bStartFound = false;
					$arDays = explode(",", $arParams["byday"]);
					foreach ($arDays as $day)
					{
						if ($day >= $newEventDateDay)
						{
							$bStartFound = true;
							if ($day > $newEventDateDay)
							{
								$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + ($day - $newEventDateDay), date("Y", $newEventDate));
								$newEventDateDay = $day;
							}
							break;
						}
					}
					if (!$bStartFound)
					{
						$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + (($arParams["interval"] - 1) * 7 + (6 - $newEventDateDay) + $arDays[0] + 1), date("Y", $newEventDate));
						$newEventDateDay = $arDays[0];
					}

					$d = $i = 0;
					$priorDay = $newEventDateDay;
					foreach ($arDays as $day)
					{
						if ($newEventDateDay >= $day)
							continue;

						$d += $day - $priorDay;
						$priorDay = $day;

						$i++;
						if ($i >= $number - 1)
							break;
					}

					while ($i < $number - 1)
					{
						$bFirst = true;
						foreach ($arDays as $day)
						{
							if ($bFirst)
								$d += ($arParams["interval"] - 1) * 7 + (6 - $priorDay) + $day + 1;
							else
								$d += $day - $priorDay;
							$bFirst = false;

							$priorDay = $day;

							$i++;
							if ($i >= $number - 1)
								break;
						}
					}
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + $d, date("Y", $newEventDate));
					break;
				case 'MONTHLY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate) + $arParams["interval"] * ($number - 1), date("d", $newEventDate), date("Y", $newEventDate));
					break;
				case 'YEARLY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate), date("Y", $newEventDate) + $arParams["interval"] * ($number - 1));
					break;
			}

			return $newEventDate;
		}

		public function Get(&$arResult, $id, $siteId, $account, $arPath)
		{
			$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
			if ($calendarId == null)
				return '404 Not Found';

			$request = $this->groupdav->GetRequest();

			$oldEvent = $this->GetEntry('GET', $id, $calendarId);
			if (is_null($oldEvent) || !is_array($oldEvent))
				return $oldEvent;

			$arResult['data'] = $this->groupdav->GetResponse()->Encode($this->GetICalContent($oldEvent, $siteId));
			$arResult['mimetype'] = 'text/calendar; charset=utf-8';
			$arResult['headers'] = array('Content-Encoding: identity', 'ETag: '.$this->GetETag($calendarId, $oldEvent));

			return true;
		}

		public function Put($id, $siteId, $account, $arPath)
		{
			$params = [];
			$params['caldav'] = true;
			$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
			if ($calendarId == null)
				return '404 Not Found';

			CDav::Report("CDavCalendarHandler::Put", "calendarId", $calendarId);

			$request = $this->groupdav->GetRequest();

			$oldEvent = $this->GetEntry('PUT', $id, $calendarId);
			if (!is_null($oldEvent) && !is_array($oldEvent))
				return $oldEvent;

			$charset = "utf-8";
			$arContentParameters = $request->GetContentParameters();

			//CDav::Report("CDavCalendarHandler::Put", "arContentParameters", $arContentParameters);

			if (!empty($arContentParameters['CONTENT_TYPE']))
			{
				$arContentType = explode(';', $arContentParameters['CONTENT_TYPE']);
				if (count($arContentType) > 1)
				{
					array_shift($arContentType);
					foreach ($arContentType as $attribute)
					{
						$attribute = trim($attribute);
						list($key, $value) = explode('=', $attribute);
						if (strtolower($key) == 'charset')
							$charset = strtolower($value);
					}
				}
			}

			$content = $request->GetRequestBody();
			$content = htmlspecialcharsback($content);

			//CDav::Report("CDavCalendarHandler::Put", "content", $content);
			if (is_array($oldEvent))
			{
				$eventId = $oldEvent['ID'];
			}
			else
			{
				// Search the same event without ID
				$eventId = 0;
			}

			$cs = CDav::GetCharset($siteId);
			if (is_null($cs) || empty($cs))
				$cs = "utf-8";

			$content = $GLOBALS["APPLICATION"]->ConvertCharset($content, $charset, $cs);

			CDav::Report("CDavCalendarHandler::Put", "content (converted ".$charset." -> ".$cs.")", $content);

			$cal = new CDavICalendar($content, $siteId);

			$arEvents = $cal->GetComponents('VTIMEZONE', false);
			if (count($arEvents) <= 0)
				return '404 Not Found';

			$arFields = $this->ConvertICalToArray($arEvents[0], $cal);

			if ($eventId > 0)
				$arFields['ID'] = $eventId;
			else
				$arFields['CREATED_BY'] = $arFields['MODIFIED_BY'];

			if (isset($arFields['DAV_XML_ID']))
				$arFields['XML_ID'] = $arFields['DAV_XML_ID'];

			CDav::Report("CDavCalendarHandler::Put", "arFields", $arFields);
			$eventId = CCalendarSync::ModifyEvent($calendarId, $arFields, $params);

			if (count($arEvents) > 1)
			{
				$recEvents = array();
				for($i = 1; $i <= count($arEvents) - 1; $i++)
				{
					$recEvents[] = $this->ConvertICalToArray($arEvents[$i], $cal);
				}

				CCalendarSync::ModifyReccurentInstances(array(
					'events' => $recEvents,
					'parentId' => $eventId,
					'calendarId' => $calendarId
				));
			}


			if (is_bool($eventId))
				return $eventId;

			if (!is_numeric($eventId))
				return false;

			//header('ETag: '.$this->GetETag($calendarId, $xmlId));
			//$path = preg_replace('|(.*)/[^/]*|', '\1/', $request->GetPath());
			//header('Location: '.$request->GetBaseUri().$path.$this->GetPath($xmlId));

			return "201 Created";
		}

		public function Delete($id, $siteId, $account, $arPath)
		{
			$calendarId = $this->GetCalendarId($siteId, $account, $arPath);
			if ($calendarId == null)
				return '404 Not Found';

			$request = $this->groupdav->GetRequest();

			$oldEvent = $this->GetEntry('DELETE', $id, $calendarId);
			if (!is_array($oldEvent))
				return $oldEvent;

			CDav::Report("CDavCalendarHandler::Delete", "id", $id);

			return CCalendar::DeleteCalendarEvent($calendarId, $oldEvent["ID"], $request->GetPrincipal()->Id());
		}
	}
}
?>