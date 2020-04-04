<?
namespace Bitrix\Calendar\Sync;

use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Web\Uri;
use Bitrix\Main\Application;
use \Bitrix\Main\Web;
/**
 * Class GoogleApiSync
 *
 * @package Bitrix\Calendar\Sync
 */
final class GoogleApiSync
{
	const MAXIMUM_CONNECTIONS_TO_SYNC = 3;
	const ONE_DAY = 86400; //60*60*24;
	const ENABLE_ATTENDEE_DESC = true;
	/**
	 * @var GoogleApiTransport
	 */
	private $syncTransport;
	private $eventsSyncToken = '',
			$defaultTimezone = 'UTC',
			$userId = 0,
			$calendarList = array(),
			$defaultReminderData = array(),
			$calendarColors = false,
			$eventColors = false,
			$eventMapping = array(
				'DAV_XML_ID'	=>	'iCalUID',
				'NAME'			=>	'summary',
				'DESCRIPTION'	=>	'description',
				'CAL_DAV_LABEL'	=>	'etag'
			);

	/**
	 * Closes watch channel and asking google to stop pushes
	 *
	 * @param $channelId
	 * @param $resourceId
	 */
	public function stopChannel($channelId, $resourceId)
	{
		$this->syncTransport->stopChannel($channelId, $resourceId);
	}

	/**
	 * Creates watch channel for connection
	 * @param $name
	 * @return array
	 */
	public function startWatchCalendarList($name)
	{
		$channel = $this->syncTransport->openCalendarListChannel($this->makeChannelParams($name, 'BX_CALENDAR_CON_'));
		if (!$this->syncTransport->getErrors())
		{
			$channel['expiration'] = Type\DateTime::createFromTimestamp($channel['expiration']/1000);
			return $channel;
		}
		return array();
	}

	private function makeChannelParams($inputSecretWord, $type)
	{
		if (defined('BX24_HOST_NAME') && BX24_HOST_NAME)
		{
			$externalUrl = 'https://www.bitrix24.com/controller/google_calendar_push.php?target_host=' . BX24_HOST_NAME;
		}
		else
		{
			$server = \Bitrix\Main\Application::getInstance()->getContext()->getServer();
			$domain = $server['HTTP_HOST'];
			$externalUrl = 'https://' . $domain . '/bitrix/tools/calendar/push.php';
		}

		$channelId = $type . md5($inputSecretWord . strtotime('now'));

		$requestParams = array(
			'id' => $channelId,
			'type' => 'web_hook',
			'address' => $externalUrl,
		);
		return $requestParams;
	}

	/**
	 * Creates watch channel for new events
	 *
	 * @param string $calendarId
	 * @return array|bool
	 */
	public function startWatchEventsChannel($calendarId = 'primary')
	{
		$channel = $this->syncTransport->openEventsWatchChannel($calendarId, $this->makeChannelParams($calendarId, 'BX_CALENDAR_SECT_'));
		if (!$this->syncTransport->getErrors())
		{
			$channel['expiration'] = Type\DateTime::createFromTimestamp($channel['expiration']/1000);
			return $channel;
		}
		return false;
	}

	/**
	 * GoogleApiSync constructor.
	 *
	 * @param int $userId
	 */
	public function __construct($userId = 0)
	{
		if (!$userId)
		{
			$userId = \CCalendar::GetUserId();
		}
		$this->userId = $userId;
		$this->syncTransport = new GoogleApiTransport($userId);

	}

	/**
	 * Test ability to establish google api connection
	 * @return bool
	 */
	public function testConnection()
	{
		$this->setColors();
		if ($this->getTransportErrors())
		{
			return false;
		}
		return true;
	}

	/**
	 * Check if errors from transport exists
	 * @return array
	 */
	public function getTransportErrors()
	{
		return $this->syncTransport->getErrors();
	}

	private function setColors()
	{
		if ($this->calendarColors === false || $this->eventColors === false)
		{
			$cacheTime = 86400 * 7;
			$colorData = null;

			if ($cacheTime)
			{
				$cache = \Bitrix\Main\Data\Cache::createInstance();
				$cacheId = "google_colors";
				$cachePath = 'googlecolors';

				if ($cache->initCache($cacheTime, $cacheId, $cachePath))
				{
					$res = $cache->getVars();
					$colorData = $res["colorData"];
				}
			}

			if (!$cacheTime || empty($colorData))
			{
				$colorData = $this->syncTransport->getColors();
				if ($cacheTime && isset($cache, $cacheId, $cachePath))
				{
					$cache->startDataCache($cacheTime, $cacheId, $cachePath);
					$cache->endDataCache(array(
						"colorData" => $colorData
					));
				}
			}

			$this->calendarColors = array();
			$this->eventColors = array();
			if (is_array($colorData) && !empty($colorData['calendar']) && !empty($colorData['event']))
			{
				foreach ($colorData['calendar'] as $key => $color)
				{
					$this->calendarColors[$key] = $color;
				}

				foreach ($colorData['event'] as $key => $color)
				{
					$this->eventColors[$key] = $color;
				}
			}
		}
	}

	/**
	 * @param int $colorId
	 * @param bool $background
	 * @return string
	 */
	private function getCalendarColor($colorId, $background = true)
	{
		$calendarColors = is_array($this->calendarColors) ? $this->calendarColors : array();
		return $calendarColors[$colorId][($background ? 'background' : 'foreground')];
	}

	/**
	 * @param int $colorId
	 * @param bool $background
	 * @return string
	 */
	private function getEventColor($colorId, $background = true)
	{
		$eventColors = is_array($this->eventColors) ? $this->eventColors : array();
		return $eventColors[$colorId][($background ? 'background' : 'foreground')];
	}

	/**
	 * Returns connection error code in message;
	 *
	 * @return array
	 */
	public function getTransportConnectionError()
	{
		$connectionError = $this->syncTransport->getErrorByCode('CONNECTION');
		if ($connectionError)
		{
			return $connectionError['message'];
		}
		return array();
	}

	/**
	 * get calendar list from google
	 *
	 * @param string $syncToken
	 * @return array
	 */
	public function getCalendarItems()
	{
		$this->setColors();
		$calendarList = array();
		if (empty($this->calendarList))
		{
			$calendarList = $this->syncTransport->getCalendarList();
		}

		if (!empty($calendarList['items']))
		{
			foreach($calendarList['items'] as $calendarItem)
			{
				$calendarItem['backgroundColor'] = $this->getCalendarColor($calendarItem['colorId']);
				$calendarItem['textColor'] = $this->getCalendarColor($calendarItem['colorId'], true);
				$this->calendarList[] = $calendarItem;
			}
		}

		return $this->calendarList;
	}

	/**
	 * @return string
	 */
	public function getEventsSyncToken()
	{
		return $this->eventsSyncToken;
	}

	/**
	 * get google calendar events list.
	 * By default selecting primary calendar
	 * @param array $calendarData
	 * @param bool $extIdAsKey
	 * @param bool $firstTry
	 * @return array
	 */
	public function getEvents($calendarData = array(), $extIdAsKey = true, $firstTry = true)
	{
		$this->setColors();
		$eventsList = array();
		// in a case for events count > 250 (google default page limit)
		$getEventsRequestParams = array(
			'pageToken' => '',
			'syncToken' => $calendarData['SYNC_TOKEN'],
			'showDeleted' => 'true'
		);
		$syncToken = $calendarData['SYNC_TOKEN'];

		do
		{
			$results = $this->syncTransport->getEvents($calendarData['GAPI_CALENDAR_ID'], $getEventsRequestParams);
			// If error (410) occured just remove SYNC_TOKEN and try again ONCE
			if (!$results && $firstTry)
			{
				unset($calendarData['SYNC_TOKEN']);
				return $this->getEvents($calendarData, $extIdAsKey, false);
			}

			$this->defaultReminderData = (!empty($results['defaultReminders'])) ? $results['defaultReminders'] : $this->defaultReminderData;

			if (!empty($results['timeZone']))
			{
				try
				{
					new \DateTimeZone($results['timeZone']);
					$this->defaultTimezone = $results['timeZone'];
				}
				catch(Exception $e){}
			}

			$syncToken = (!empty($results['nextSyncToken'])) ? $results['nextSyncToken'] : $syncToken;

			if (empty($results['items']) && empty($results['nextPageToken']))
			{
				break;
			}

			foreach ($results['items'] as $result)
			{
				$eventsList[] = $this->prepareEvent($result);
			}
		}
		while ((!empty($results['nextPageToken']) && $getEventsRequestParams['pageToken'] = $results['nextPageToken']));

		$this->eventsSyncToken = $syncToken;

		if ($extIdAsKey)
		{
			foreach($eventsList as $key => $eventData)
			{
				$eventsList[$eventData['G_EVENT_ID']] = $eventData;
				unset($eventsList[$key]);
			}
		}

		return $eventsList;
	}

	/**
	 * get id of Primary (main) calendar
	 *
	 * @return string
	 */
	public function getPrimaryId()
	{
		$calendar = $this->getCalendarById('primary');
		return !empty($calendar) ? $calendar['id'] : '';
	}

	/**
	 * Get calendar data by provided ID
	 *
	 * @param $calendarId
	 * @return array
	 */
	private function getCalendarById($calendarId)
	{
		$this->getCalendarItems();

		foreach ($this->calendarList as $calendar)
		{
			if (($calendar['id'] == $calendarId) || (isset($calendar['primary']) && $calendarId == 'primary'))
			{
				return $calendar;
			}
		}
		return array();
	}

	/**
	 * Delete event from specified google calendar
	 *
	 * @param $eventId
	 * @param $calendarId
	 * @return array|mixed
	 */
	public function deleteEvent($eventId, $calendarId)
	{
		return $this->syncTransport->deleteEvent(preg_replace('/(@google.com)/', '', $eventId), urlencode($calendarId));
	}

	/**
	 * publishes event to google calendar, returns iCalUid field or false
	 *
	 * @param $eventData
	 * @param $calendarId
	 * @return array
	 */
	public function saveEvent($eventData, $calendarId, $parameters = [])
	{
		$params['editInstance'] = !empty($parameters['editInstance']) ? $parameters['editInstance'] : false;
		$params['originalDavXmlId'] = !empty($parameters['originalDavXmlId']) ? $parameters['originalDavXmlId'] : null;
		$params['editParentEvents'] = !empty($parameters['editParentEvents']) ? $parameters['editParentEvents'] : false;
		$params['editNextEvents'] = !empty($parameters['editNextEvents']) ? $parameters['editNextEvents'] : false;
		$params['calendarId'] = $calendarId;
		$params['instanceTz'] = !empty($parameters['instanceTz']) ? $parameters['instanceTz'] : null;
		$params['originalDateFrom'] = !empty($eventData['ORIGINAL_DATE_FROM']) ? $eventData['ORIGINAL_DATE_FROM'] : null;
		$params['gEventId'] = $eventData['G_EVENT_ID'] ?: str_replace('@google.com', '',  $eventData['DAV_XML_ID']);
		$params['syncCaldav'] = !empty($parameters['syncCaldav']) ? $parameters['syncCaldav'] : false;

		$newEvent = $this->prepareToSaveEvent($eventData, $params);

		$externalEvent = $this->sendToSaveEvent($newEvent, $params);

		if ($externalEvent)
		{
			return array(
				'DAV_XML_ID' => $externalEvent['iCalUID'],
				'CAL_DAV_LABEL' => $externalEvent['etag'],
				'ORIGINAL_DATE_FROM' => $externalEvent['originalStartTime'] ? $eventData['ORIGINAL_DATE_FROM'] : null,
				'G_EVENT_ID' => $externalEvent['id'],
			);
		}
		else
		{
			return null;
		}
	}

	public function saveBatchEvents($events, $sectionId, $params)
	{
		$prepareEvents = [];

		if (is_array($events))
		{
			foreach ($events as $event)
			{
				$localEvent['gEventId'] = $event['gEventId'];
				$partBody = $this->prepareToSaveEvent($event);
				$localEvent['partBody'] = Web\Json::encode($partBody, JSON_UNESCAPED_SLASHES);
				$prepareEvents[$event['ID']] = $localEvent;
			}
		}

		$externalEvents = $this->syncTransport->sendBatchEvents($prepareEvents, $sectionId, $params);

		if ($externalEvents)
		{
			foreach ($externalEvents as $key => $externalEvent)
			{
				$responseFields[$key]['DAV_XML_ID'] = $externalEvent['iCalUID'];
				$responseFields[$key]['CAL_DAV_LABEL'] = $externalEvent['etag'];
				$responseFields[$key]['G_EVENT_ID'] = $externalEvent['id'];
				$responseFields[$key]['ORIGINAL_DATE_FROM'] = $externalEvent['originalStartTime'] ? $events[$key]['ORIGINAL_DATE_FROM'] : null;
			}
		}

		return $responseFields;
	}

	/**
	 * Prepearing event for future use
	 *
	 * @param $event
	 * @return array
	 */
	private function prepareEvent($event)
	{
		$returnData = [
			'TZ_FROM' => $this->defaultTimezone,
			'TZ_TO' => $this->defaultTimezone
		];
		foreach ($this->eventMapping as $internalKey => $externalKey)
		{
			$returnData[$internalKey] = (isset($event[$externalKey]) ? $event[$externalKey] : '');
		}
		$returnData['iCalUID'] = $event['iCalUID'];
		$returnData['DAV_XML_ID'] = $event['iCalUID'];
		$returnData['G_EVENT_ID'] = $event['id'];

		if (!empty($event['description']))
		{
			$parts = explode('~-~-~-~-~', $event['description'], 2);

			if (!empty($parts[1]))
			{
				$returnData["DESCRIPTION"] = trim($parts[1]);
			}
			else
			{
				$returnData["DESCRIPTION"] = $event['description'];
			}
		}

		if (empty($event['summary']))
		{
			$returnData['NAME'] = GetMessage('EC_T_NEW_EVENT');
		}

		if (!empty($event['transparency']) && $event['transparency'] == 'transparent')
		{
			$returnData['ACCESSIBILITY'] = 'free';
		}
		else
		{
			$returnData['ACCESSIBILITY'] = 'busy';
		}

		if (!empty($event['visibility']) && $event['visibility'] === 'private')
		{
			$returnData['PRIVATE_EVENT'] = true;
		}
		else
		{
			$returnData['PRIVATE_EVENT'] = false;
		}

		$returnData['OWNER_ID'] = $this->userId;
		$returnData['CREATED_BY'] = $this->userId;
		$returnData['CAL_TYPE'] = 'user';

		if (!empty($event['start']['dateTime']) && !empty($event['end']['dateTime']))
		{
			try
			{
				$returnData['TZ_FROM'] = empty($event['start']['timeZone']) ? $this->defaultTimezone : $event['start']['timeZone'];
				new \DateTimeZone($returnData['TZ_FROM']);
			}
			catch(Exception $e)
			{
				$returnData['TZ_FROM'] = $this->defaultTimezone;
			}

			try
			{
				$returnData['TZ_TO'] = empty($event['end']['timeZone']) ? $this->defaultTimezone : $event['end']['timeZone'];
				new \DateTimeZone($returnData['TZ_TO']);
			}
			catch(Exception $e)
			{
				$returnData['TZ_TO'] = $this->defaultTimezone;
			}

			$eventStartDateTime = new Type\DateTime($event['start']['dateTime'], \DateTime::RFC3339, new \DateTimeZone($this->defaultTimezone));
			$returnData['DATE_FROM'] = \CCalendar::Date(\CCalendar::Timestamp($eventStartDateTime->setTimeZone(new \DateTimeZone($returnData['TZ_FROM']))->format(Type\Date::convertFormatToPhp(FORMAT_DATETIME))));

			$eventStartDateTime = new Type\DateTime($event['end']['dateTime'], \DateTime::RFC3339, new \DateTimeZone($this->defaultTimezone));
			$returnData['DATE_TO'] = \CCalendar::Date(\CCalendar::Timestamp($eventStartDateTime->setTimeZone(new \DateTimeZone($returnData['TZ_TO']))->format(Type\Date::convertFormatToPhp(FORMAT_DATETIME))));
		}

		if (!empty($event['start']['date']))
		{
			$returnData['DATE_FROM'] = \CCalendar::Date(strtotime($event['start']['date']), false);
		}

		if (!empty($event['end']['date']))
		{
			if ($event['end']['date'] == $event['start']['date'])
			{
				$dateStr = strtotime($event['end']['date']);
			}
			else
			{
				$dateStr = strtotime($event['end']['date']) - self::ONE_DAY;
			}

			$returnData['DATE_TO'] = \CCalendar::Date($dateStr, false);
			$returnData['DT_SKIP_TIME'] = 'Y';
		}
		else
		{
			$returnData['DT_SKIP_TIME'] = 'N';
		}

		$returnData['DATE_CREATE'] = \CCalendar::Date(strtotime($event['created']));

		if (!empty($event['colorId']))
		{
			$returnData['COLOR'] = $this->getEventColor($event['colorId']);
			$returnData['TEXT_COLOR'] = $this->getEventColor($event['colorId'], false);
		}

		$returnData['DATE_CREATE'] = \CCalendar::Date(time());
		$returnData['status'] = $event['status'];
		$returnData['hasMoved'] = "N";
		$returnData['isRecurring'] = "N";

		$exDates = array();

		if ($event['recurrence'])
		{
			foreach ($event['recurrence'] as $recurrence)
			{
				if (preg_match('/(RRULE)/', $recurrence))
				{
					$rRuleData = preg_replace('/(RRULE\:)/', '', $recurrence);
					$rRuleList = explode(';', $rRuleData);
					$rRuleSet = array();
					foreach ($rRuleList as $rRuleElement)
					{
						list($rRuleProp, $rRuleVal) = explode('=', $rRuleElement);
						switch ($rRuleProp)
						{
							case 'FREQ':
								if (in_array($rRuleVal, array('HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY')))
								{
									$rRuleSet['FREQ'] = $rRuleVal;
								}
								break;
							case 'COUNT':
								$rRuleSet['COUNT'] = $rRuleVal;
								break;
							case 'INTERVAL':
								$rRuleSet['INTERVAL'] = $rRuleVal;
								break;
							case 'BYDAY':
								$rRuleByDay = array();
								foreach(explode(',', $rRuleVal) as $day)
								{
									$matches = array();
									if (preg_match('/((\-|\+)?\d+)?(MO|TU|WE|TH|FR|SA|SU)/', $day, $matches))
										$rRuleByDay[$matches[1] == '' ? $matches[3] : $matches[1]] = $matches[1] == '' ? $matches[3] : $matches[1];
								}
								if (!empty($rRuleByDay))
								{
									$rRuleSet['BYDAY'] = $rRuleByDay;
								}
								break;
							case 'UNTIL':
								try
								{
									$rRuleValDateTime = new Type\DateTime($rRuleVal, 'Ymd\THis\Z', new \DateTimeZone('UTC'));
									$rRuleValDateTime->setTimeZone(new \DateTimeZone($returnData['TZ_TO']));
									$untilDateTime = explode("T", $rRuleVal);

									if ($untilDateTime[1] == "000000Z")
									{
										$rRuleValDateTime = $rRuleValDateTime->add("-1 day");
									}

									$rRuleSet['UNTIL'] = \CCalendar::Date(\CCalendar::Timestamp($rRuleValDateTime->format(Type\Date::convertFormatToPhp(FORMAT_DATETIME))) - 60, false, false);
								}
								catch(Exception $e)
								{
									$rRuleSet['UNTIL'] = \CCalendar::Date(strtotime($rRuleVal), false, false);
								}

								break;
						}
					}
					$returnData["RRULE"] = \CCalendarEvent::CheckRRULE($rRuleSet);
				}
				elseif (preg_match('/(\d{4}-?\d{2}-?\d{2})(Z)?/', $recurrence, $date))
				{
					if (!empty($date[1]))
					{
						$exDates[] = \CCalendar::Date(strtotime($date[1]), false);
					}
				}
			}
		}

		if (!empty($event['recurringEventId']))
		{
			$returnData['isRecurring'] = "Y";
			if ($event['status'] == 'cancelled')
			{
				$exDates[] = date('d.m.Y', strtotime(!empty($event['originalStartTime']['dateTime']) ? $event['originalStartTime']['dateTime'] : $event['originalStartTime']['date']));
			}
			elseif ($event['status'] == 'confirmed' && !empty($event['originalStartTime']))
			{
				$returnData['hasMoved'] = "Y";
				$exDates[] = date('d.m.Y', strtotime(!empty($event['originalStartTime']['dateTime']) ? $event['originalStartTime']['dateTime'] : $event['originalStartTime']['date']));

				if (!empty($event['originalStartTime']['dateTime']))
				{
					$eventOriginalDateFrom = new Type\DateTime($event['originalStartTime']['dateTime'], \DateTime::RFC3339, new \DateTimeZone($this->defaultTimezone));
					$returnData['ORIGINAL_DATE_FROM'] = \CCalendar::Date(\CCalendar::Timestamp($eventOriginalDateFrom->setTimeZone(new \DateTimeZone($event['originalStartTime']['timeZone']))->format(Type\Date::convertFormatToPhp(FORMAT_DATETIME))));
				}

				if (!empty($event['originalStartTime']['date']))
				{
					$returnData['ORIGINAL_DATE_FROM'] = \CCalendar::Date(strtotime($event['originalStartTime']['date']), false);
				}
			}

			$returnData['recurringEventId'] = $event['recurringEventId'] . '@google.com';
		}
		if (!empty($exDates))
		{
			$returnData['EXDATE'] = implode(';', $exDates);
		}

		$returnData['REMIND'] = array();
		if (!empty($event['reminders']['overrides']))
		{
			foreach ($event['reminders']['overrides'] as $remindData)
			{
				$remindTimeout = $remindData['minutes'];
				$returnData['REMIND'][] = array(
					'type' => 'min',
					'count' => $remindTimeout
				);
			}
		}
		if (!empty($event['reminders']['useDefault']) && !empty($this->defaultReminderData) && $event['reminders']['useDefault'] == 1)
		{
			foreach ($this->defaultReminderData as $remindData)
			{
				$remindTimeout = $remindData['minutes'];
				$returnData['REMIND'][] = array(
					'type' => 'min',
					'count' => $remindTimeout
				);
			}
		}
		if (!empty($event['location']))
		{
			$returnData['LOCATION'] = \CCalendar::UnParseTextLocation($event['location']);
		}

		return $returnData;
	}

	private function prepareTimezone ($timeZone)
	{
		return !empty($timeZone) && $timeZone != 'false' ? new \DateTimeZone($timeZone) : new \DateTimeZone("UTC");
	}

	private function prepareToSaveEvent($eventData, $params = null)
	{
		$newEvent = array();
		$newEvent['summary'] = $eventData['NAME'];
		if (isset($eventData['ATTENDEES_CODES'])  && self::ENABLE_ATTENDEE_DESC)
		{
			$users = $this->getAttendees($eventData['ATTENDEES_CODES']);
			$newEvent['description'] = Loc::getMessage('ATTENDEES_EVENT').': '.$users.' ~-~-~-~-~ '.$eventData["DESCRIPTION"];
		}
		else
		{
			$newEvent['description'] = $eventData['DESCRIPTION'];
		}

		if (!empty($eventData['ACCESSIBILITY']) && $eventData['ACCESSIBILITY'] == 'busy')
		{
			$newEvent['transparency'] = 'opaque';
		}
		else
		{
			$newEvent['transparency'] = 'transparent';
		}

		if (!empty($eventData['LOCATION']['NEW']))
		{
			$newEvent['location'] = \CCalendar::GetTextLocation($eventData['LOCATION']['NEW']);
		}

		if (!empty($eventData['REMIND']))
		{
			$newEvent['reminders'] = array();
			$newEvent['reminders']['useDefault'] = false;
			$newEvent['reminders']['overrides'] = array();
			foreach($eventData['REMIND'] as $remindRule)
			{
				$minutes = $remindRule['count'];
				if ($remindRule['type'] == 'min')
				{

				}
				elseif ($remindRule['type'] == 'hour')
				{
					$minutes = 60 * $remindRule['count'];
				}
				elseif ($remindRule['type'] == 'day')
				{
					$minutes = 24 * 60 * $remindRule['count'];
				}

				$newEvent['reminders']['overrides'][] = array(
					'minutes' => $minutes,
					'method' => 'popup', //todo - should able to be changed in settings
				);
			}
		}

		if ($eventData['DT_SKIP_TIME'] == "Y")
		{
			$startDate = new Type\Date($eventData['DATE_FROM']);
			$newEvent['start']['date'] = $startDate->format('Y-m-d');
			$endDate = new Type\Date($eventData['DATE_TO']);
			$newEvent['end']['date'] =  $endDate->add('+1 day')->format('Y-m-d');

			if (!empty($eventData['DAV_XML_ID']) && stripos($eventData['DAV_XML_ID'], '@google.com') !== false)
			{
				$newEvent['start']['dateTime'] = null;
				$newEvent['end']['dateTime'] = null;
				$newEvent['start']['timeZone'] = null;
				$newEvent['end']['timeZone'] = null;
			}
		}
		else
		{
			$dateTimeZoneFrom = $this->prepareTimezone($eventData['TZ_FROM']);
			$eventStartDateTime = new Type\DateTime($eventData['DATE_FROM'], Type\Date::convertFormatToPhp(FORMAT_DATETIME), $dateTimeZoneFrom);
			$newEvent['start']['dateTime'] = $eventStartDateTime->format(\DateTime::RFC3339);
			$newEvent['start']['timeZone'] = $dateTimeZoneFrom->getName();

			$dateTimeZoneTo = $this->prepareTimezone($eventData['TZ_TO']);
			$eventEndDateTime = new Type\DateTime($eventData['DATE_TO'], Type\Date::convertFormatToPhp(FORMAT_DATETIME), $dateTimeZoneTo);
			$newEvent['end']['dateTime'] = $eventEndDateTime->format(\DateTime::RFC3339);
			$newEvent['end']['timeZone'] = $dateTimeZoneTo->getName();

			if (!empty($eventData['DAV_XML_ID']) && stripos($eventData['DAV_XML_ID'], '@google.com') !== false)
			{
				$newEvent['start']['date'] = null;
				$newEvent['end']['date'] = null;
			}
		}

		if (!empty($eventData['RRULE']) && $eventData['RRULE']['FREQ'] != 'NONE')
		{
			$rRuleData = \CCalendarEvent::ParseRRULE($eventData['RRULE']);
			$rRule = 'RRULE:';
			$rRule .= 'FREQ=' .$rRuleData['FREQ'];
			$rRule .= !empty($rRuleData['INTERVAL']) ? ';INTERVAL=' . $rRuleData['INTERVAL'] : '';
			$rRule .= !empty($rRuleData['BYDAY']) ? ';BYDAY=' . $rRuleData['BYDAY'] : '';
			if (!empty($rRuleData['COUNT']))
			{
				$rRule .=  ';COUNT=' . $rRuleData['COUNT'];
			}
			elseif (!empty($rRuleData['UNTIL']))
			{
				$tsTo = new Type\Date($rRuleData['UNTIL']);
				if ($eventData['DT_SKIP_TIME'] == "N")
				{
					$tsTo->add('+1 day');
				}
				$rRule .= ';UNTIL='.$tsTo->format('Ymd\THis\Z');
			}
			$newEvent['recurrence'][] = $rRule;

			if (!empty($eventData['EXDATE']) && $params['editNextEvents'] !== true)
			{
				$exDates = explode(';', $eventData['EXDATE']);
				foreach ($exDates as $exDate)
				{

					if ($eventData['DT_SKIP_TIME'] == "Y")
					{
						$newEvent['recurrence'][] = 'EXDATE;VALUE=DATE:' . date('Ymd', strtotime($exDate));
					}
					else
					{
//						$exDateStr = 'EXDATE;TZID=UTC:' . date("Ymd", strtotime($exDate)) . Type\DateTime::createFromUserTime($eventData['DATE_FROM'])->setTimeZone(new \DateTimeZone('UTC'))->format('\\THis\\Z');
						$exDateStr = 'EXDATE;TZID=UTC:' . date("Ymd", strtotime($exDate)) . (new Type\DateTime($eventData['DATE_FROM'], Type\Date::convertFormatToPhp(FORMAT_DATETIME), $dateTimeZoneFrom))->setTimeZone(new \DateTimeZone('UTC'))->format('\\THis\\Z');
						$newEvent['recurrence'][] = $exDateStr;
					}
				}
			}
		}

		if (isset($eventData['ORIGINAL_DATE_FROM']))
		{
			$instanceOriginalTimeZone = $this->prepareTimezone($eventData['TZ_FROM']);
			$eventOriginalStart = new Type\DateTime($eventData['ORIGINAL_DATE_FROM'], Type\Date::convertFormatToPhp(FORMAT_DATETIME), $instanceOriginalTimeZone);
			$newEvent['originalStartTime'] = $eventOriginalStart->format(\DateTime::RFC3339);
		}

		if (isset($eventData['DAV_XML_ID']) && isset($eventData['RECURRENCE_ID']))
		{
			$newEvent['recurringEventId'] = $eventData['DAV_XML_ID'];
		}

		if ($params['syncCaldav'])
		{
			$newEvent['iCalUID'] = $eventData['DAV_XML_ID'];
		}

		if (!empty($eventData['PRIVATE_EVENT']))
		{
			$newEvent['visibility'] = "private";
		}
		else
		{
			$newEvent['visibility'] = "public";
		}

		return $newEvent;
	}

	private function sendToSaveEvent($newEvent, $params)
	{
		if ($params['editInstance'] === true)
		{
			$instanceOriginalTimeZone = $this->prepareTimezone($params['instanceTz']);
			$eventOriginalStart = new Type\DateTime($params['originalDateFrom'], Type\Date::convertFormatToPhp(FORMAT_DATETIME), $instanceOriginalTimeZone);
			$originalStart = $eventOriginalStart->format(\DateTime::RFC3339);
			$externalId = $params['gEventId'] ?: $params['originalDavXmlId'];
			$instance = $this->syncTransport->getInstanceRecurringEvent($params['calendarId'], $externalId, $originalStart);

			$newEvent['originalStartTime'] = $originalStart;
			$newEvent['recurringEventId'] = $params['originalDavXmlId'];

			if (is_array($instance['items']))
			{
				$externalEvent = $this->syncTransport->updateEvent($newEvent, urlencode($params['calendarId']), $instance['items'][0]['id']);
			}
		}
		elseif ($params['editParentEvents'] === true)
		{
			$externalEvent = $this->syncTransport->updateEvent($newEvent, urldecode($params['calendarId']), $params['gEventId']);
		}
		elseif ($params['syncCaldav'])
		{
			$externalEvent = $this->syncTransport->importEvent($newEvent, urlencode($params['calendarId']));
		}
		else
		{
			if (!empty($params['gEventId']))
			{
				$externalEvent = $this->syncTransport->patchEvent($newEvent, urlencode($params['calendarId']), $params['gEventId']);
			}
			else
			{
				$externalEvent = $this->syncTransport->insertEvent($newEvent, urlencode($params['calendarId']));
			}
		}

		return $externalEvent;
	}

	public function createCalendar($calendar)
	{
		$data = $this->prepareCalendar($calendar);
		$externalData = $this->syncTransport->insertCalendar($data);

		if ($externalData)
		{
			return array(
				'GAPI_CALENDAR_ID' => $externalData['id'],
			);
		}
		else
		{
			return null;
		}
	}

	private function prepareCalendar($calendar)
	{
		$returnData['summary'] = $calendar['NAME'];

		return $returnData;
	}

	private function getAttendees($codeAttendees)
	{
		$userIdList = [];
		$userList = [];

		foreach ($codeAttendees as $codeAttend)
		{
			if (substr($codeAttend, 0, 1) === 'U')
			{
				$userId = (int)(substr($codeAttend, 1));
				$userIdList[] = $userId;
			}
		}

		if (!empty($userIdList))
		{
			$res = \Bitrix\Main\UserTable::getList(array(
				'filter' => array(
					'=ID' => $userIdList,
				),
				'select' => array('NAME', 'LAST_NAME'),
			));

			while ($user = $res->fetch())
			{
				$userList[] = $user['NAME'].' '.$user['LAST_NAME'];
			}
		}

		return implode(', ', $userList);
	}
}
