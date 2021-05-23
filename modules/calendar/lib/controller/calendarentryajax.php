<?
namespace Bitrix\Calendar\Controller;

use Bitrix\Calendar\Util;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Error;
use Bitrix\Calendar\Internals;
use \Bitrix\Main\Engine\Response;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Calendar\UserSettings;
use \Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use \Bitrix\Calendar\Integration\Bitrix24\Limitation;

Loc::loadMessages(__FILE__);

/**
 * Class CalendarEntryAjax
 */
class CalendarEntryAjax extends \Bitrix\Main\Engine\Controller
{
	public function loadEntriesAction()
	{
		$request = $this->getRequest();

		$finish = false;
		$monthFrom = intval($request->getPost('month_from'));
		$yearFrom = intval($request->getPost('year_from'));
		$monthTo = intval($request->getPost('month_to'));
		$yearTo = intval($request->getPost('year_to'));
		$loadLimit = intval($request->getPost('loadLimit'));
		$loadNext = $request->getPost('loadNext') === 'Y';
		$loadPrevious = $request->getPost('loadPrevious') === 'Y';
		$parseRecursion = true;
		$activeSectionIds = $request->getPost('active_sect');
		$additionalSectionIds = $request->getPost('sup_sect');
		$calendarType = $request->getPost('type');
		$ownerId = (int)$request->getPost('ownerId');

		$params = [
			'type' => $calendarType,
			'section' => [],
			'fromLimit' => $monthFrom ? \CCalendar::Date(mktime(0, 0, 0, $monthFrom, 1, $yearFrom), false) : false,
			'toLimit' => $monthTo ? \CCalendar::Date(mktime(0, 0, 0, $monthTo, 1, $yearTo), false) : false,
		];

		$connections = false;
		if ($loadNext|| $loadPrevious)
		{
			$params['limit'] = $loadLimit;
			$parseRecursion = false;
		}

		if ($request->getPost('cal_dav_data_sync') === 'Y' && \CCalendar::IsCalDAVEnabled())
		{
			$isGoogleApiEnabled = \CCalendar::isGoogleApiEnabled();
			$config = [];
			\CCalendar::InitExternalCalendarsSyncParams($config);
			\CDavGroupdavClientCalendar::DataSync("user", $ownerId);

			if ($isGoogleApiEnabled)
			{
				$dbConnections = CDavConnection::GetList(
					[
						"SYNCHRONIZED" => "ASC"
					],
					[
						'ACCOUNT_TYPE' => 'google_api_oauth',
						'ENTITY_TYPE' => 'user',
						'ENTITY_ID' => $ownerId
					],
					false,
					false,
					['ID', 'ENTITY_TYPE', 'ENTITY_ID', 'ACCOUNT_TYPE', 'SERVER_SCHEME', 'SERVER_HOST', 'SERVER_PORT', 'SERVER_USERNAME', 'SERVER_PASSWORD', 'SERVER_PATH', 'SYNCHRONIZED', 'SYNC_TOKEN']
				);

				if ($connection = $dbConnections->Fetch())
				{
					$connection['forceSync'] = true;
					\CCalendarSync::dataSync($connection);
				}

				\CCalendar::InitExternalCalendarsSyncParams($config);
			}
			if ($config['connections'])
			{
				$connections = $config['connections'];
			}
		}

		$fetchTasks = false;
		if (is_array($activeSectionIds))
		{
			foreach($activeSectionIds as $sectId)
			{
				if ($sectId == 'tasks')
					$fetchTasks = true;
				elseif (intval($sectId) > 0)
					$params['section'][] = intval($sectId);
			}
		}

		$entries = [];
		$activeSections = [];
		if (count($params['section']) > 0)
		{
			$sect = \CCalendarSect::GetList([
				'arFilter' => [
					'ID'=> $params['section'],
					'ACTIVE' => 'Y'
				]]
			);
			foreach($sect as $section)
			{
				$activeSections[] = $section['ID'];
			}
			$params['section'] = $activeSections;
		}

		if (count($params['section']) > 0)
		{
			$arFilter = [
				'OWNER_ID' => $ownerId,
				'SECTION' => $params['section']
			];

			if (isset($params['fromLimit']))
			{
				$arFilter["FROM_LIMIT"] = $params['fromLimit'];
			}
			if (isset($params['toLimit']))
			{
				$arFilter["TO_LIMIT"] = $params['toLimit'];
			}

			if ($params['type'] == 'user')
			{
				$fetchMeetings = in_array(\CCalendar::GetMeetingSection($arFilter['OWNER_ID']), $params['section']);
			}
			else
			{
				$fetchMeetings = in_array(\CCalendar::GetCurUserMeetingSection(), $params['section']);
				if ($params['type'])
				{
					$arFilter['CAL_TYPE'] = $params['type'];
				}
			}

			$res = \CCalendarEvent::GetList(
				[
					'arFilter' => $arFilter,
					'parseRecursion' => $parseRecursion,
					'fetchAttendees' => true,
					'userId' => \CCalendar::GetCurUserId(),
					'fetchMeetings' => $fetchMeetings,
					'setDefaultLimit' => false,
					'limit' => $params['limit']
				]
			);

			$finish = $params['limit'] && count($res) < $params['limit'];
			$entries = [];
			$lastDateTimestamp = 0;
			$firstDateTimestamp = INF;
			foreach($res as $entry)
			{
				if(in_array($entry['SECT_ID'], $params['section']))
				{
					$entries[] = $entry;

					if ($loadNext && !\CCalendarEvent::CheckRecurcion($entry) && $entry['DATE_TO_TS_UTC'] > $lastDateTimestamp)
					{
						$lastDateTimestamp = $entry['DATE_TO_TS_UTC'];
					}
					elseif($loadPrevious && !\CCalendarEvent::CheckRecurcion($entry) && $entry['DATE_FROM_TS_UTC'] < $lastDateTimestamp)
					{
						$firstDateTimestamp = $entry['DATE_FROM_TS_UTC'];
					}
				}
			}

			if ($loadNext)
			{
				$params['toLimit'] = \CCalendar::Date($lastDateTimestamp);
			}
			if ($loadPrevious)
			{
				$params['fromLimit'] = \CCalendar::Date($firstDateTimestamp);
			}

			if(!$parseRecursion)
			{
				foreach($entries as $entry)
				{
					if (in_array($entry['SECT_ID'], $params['section']))
					{

						if (\CCalendarEvent::CheckRecurcion($entry))
						{
							\CCalendarEvent::ParseRecursion($entries, $entry, [
								'fromLimit' => $params['fromLimit'],
								'toLimit' => $params['toLimit'],
								'instanceCount' => false,
								'preciseLimits' => true
							]);
						}
					}
				}
			}
		}

		if (is_array($additionalSectionIds) && !empty($additionalSectionIds))
		{
			array_walk($additionalSectionIds, 'intval');
			$superposedEvents = \CCalendarEvent::GetList(
				[
					'arFilter' => [
						"FROM_LIMIT" => $params['fromLimit'],
						"TO_LIMIT" => $params['toLimit'],
						"SECTION" => $additionalSectionIds
					],
					'parseRecursion' => true,
					'fetchAttendees' => true,
					'userId' => \CCalendar::GetUserId()
				]
			);
			$entries = array_merge($entries, $superposedEvents);
		}

		//  **** GET TASKS ****
		if ($fetchTasks)
		{
			$tasksEntries = \CCalendar::getTaskList(
				[
					'type' => $calendarType,
					'ownerId' => $ownerId
				]
			);

			if(count($tasksEntries) > 0)
			{
				$entries = array_merge($entries, $tasksEntries);
			}
		}

		$response = [
			'entries' => $entries,
			'userIndex' => \CCalendarEvent::getUserIndex(),
		];
		if (is_array($connections))
		{
			$response['connections'] = $connections;
		}
		if ($params['limit'])
		{
			$response['finish'] = $finish;
		}

		return $response;
	}

	public function moveEventAction()
	{
		$request = $this->getRequest();
		$userId = \CCalendar::getCurUserId();
		$id = intval($request->getPost('id'));

		$sectionId = intval($request->getPost('section'));

		if (!$id && !\CCalendarSect::CanDo('calendar_add', $sectionId, $userId)
			||
			$id && !\CCalendarSect::CanDo('calendar_edit', $sectionId, $userId))
		{
			$this->addError(new Error(Loc::getMessage('EC_ACCESS_DENIED'), 'move_entry_access_denied'));
		}

		$reload = $request->getPost('recursive') === 'Y';
		$sendInvitesToDeclined = $request->getPost('sendInvitesAgain') === 'Y';

		$skipTime = $request->getPost('skip_time') === 'Y';
		$dateFrom = $request->getPost('date_from');
		$dateTo = $request->getPost('date_to');
		$timezone = $request->getPost('timezone');
		$attendees = $request->getPost('attendees');
		$location = trim((string) $request->getPost('location'));

		$locationBusyWarning = false;
		$busyWarning = false;

		if(empty($this->getErrors()))
		{

			$arFields = [
				"ID" => $id,
				"DATE_FROM" => \CCalendar::Date(\CCalendar::Timestamp($dateFrom), !$skipTime),
				"SKIP_TIME" => $skipTime
			];

			if (!empty($dateTo))
			{
				$arFields["DATE_TO"] = \CCalendar::Date(\CCalendar::Timestamp($dateTo), !$skipTime);
			}

			if (!$skipTime && $request->getPost('set_timezone') === 'Y' && $timezone)
			{
				$arFields["TZ_FROM"] = $timezone;
				$arFields["TZ_TO"] = $timezone;
			}

			if (!empty($location) && !\CCalendarLocation::checkAccessibility($location, ['fields' => $arFields]))
			{
				$locationBusyWarning = true;
				$reload = true;
			}

			if ($request->getPost('is_meeting') === 'Y' && is_array($attendees))
			{
				$usersToCheck = [];
				foreach ($attendees as $attId)
				{
					if ($attId !== \CCalendar::GetUserId())
					{
						$userSettings = \Bitrix\Calendar\UserSettings::get(intval($attId));
						if ($userSettings && $userSettings['denyBusyInvitation'])
						{
							$usersToCheck[] = intval($attId);
						}
					}
				}

				if (count($usersToCheck) > 0)
				{
					$fromTs = \CCalendar::Timestamp($arFields["DATE_FROM"]);
					$toTs = \CCalendar::Timestamp($arFields["DATE_TO"]);
					$fromTs = $fromTs - \CCalendar::GetTimezoneOffset($timezone, $fromTs);
					$toTs = $toTs - \CCalendar::GetTimezoneOffset($timezone, $toTs);
					$dateFromUtc = \CCalendar::Date($fromTs);
					$dateToUtc = \CCalendar::Date($toTs);

					$accessibility = \CCalendar::GetAccessibilityForUsers(
						[
							'users' => $usersToCheck,
							'from' => $dateFromUtc, // date or datetime in UTC
							'to' => $dateToUtc, // date or datetime in UTC
							'curEventId' => $id,
							'getFromHR' => true,
							'checkPermissions' => false
						]
					);

					foreach ($accessibility as $userId => $entries)
					{
						foreach ($entries as $entry)
						{
							$entFromTs = \CCalendar::Timestamp($entry["DATE_FROM"]);
							$entToTs = \CCalendar::Timestamp($entry["DATE_TO"]);
							$entFromTs -= \CCalendar::GetTimezoneOffset($entry['TZ_FROM'], $entFromTs);
							$entToTs -= \CCalendar::GetTimezoneOffset($entry['TZ_TO'], $entToTs);

							if ($entFromTs < $toTs && $entToTs > $fromTs)
							{
								$busyWarning = true;
								$reload = true;
								break;
							}
						}

						if ($busyWarning)
						{
							break;
						}
					}
				}
			}

			if (!$busyWarning && !$locationBusyWarning)
			{
				if ($request->getPost('recursive') === 'Y')
				{
					\CCalendar::SaveEventEx(
						[
							'arFields' => $arFields,
							'silentErrorMode' => false,
							'recursionEditMode' => 'this',
							'currentEventDateFrom' => \CCalendar::Date(
								\CCalendar::Timestamp($request->getPost('current_date_from')),
								false
							),
							'sendInvitesToDeclined' => $sendInvitesToDeclined
						]
					);
				}
				else
				{
					$id = \CCalendar::SaveEvent(
						[
							'arFields' => $arFields,
							'silentErrorMode' => false,
							'sendInvitesToDeclined' => $sendInvitesToDeclined
						]
					);
				}
			}
		}

		return [
			'id' => $id,
			'reload' => $reload,
			'busy_warning' => $busyWarning,
			'location_busy_warning' => $locationBusyWarning
		];
	}
}
