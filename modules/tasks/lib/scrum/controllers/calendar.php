<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Util\User;

class Calendar extends Controller
{
	const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_CC_01';
	const ERROR_ACCESS_DENIED = 'TASKS_CC_02';

	protected function processBeforeAction(Action $action)
	{
		if (
			!Loader::includeModule('socialnetwork')
			|| !Loader::includeModule('calendar')
		)
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_CC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return false;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
		$userId = User::getId();

		if (!Group::canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_CC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		return parent::processBeforeAction($action);
	}

	/**
	 * Returns the data needed to display Scrum events.
	 *
	 * @param int $groupId Group id.
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getMeetingsAction(int $groupId): ?array
	{
		if (!Loader::includeModule('im'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_CC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return null;
		}

		$userId = User::getId();

		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		$info = $backlog->getInfo();

		$mapCreatedEvents = $this->getMapCreatedEventsByTemplate($info->getEvents());

		$listEvents = $this->getUpcomingEventsForThisProject($groupId);

		$chats = $this->getEventChats($userId, $listEvents);

		[$listEvents, $todayEvent] = $this->getEventForToday($listEvents);

		$defaultSprintDuration = $this->getDefaultSprintDuration($groupId);

		$culture = Context::getCurrent()->getCulture();

		return [
			'mapCreatedEvents' => $mapCreatedEvents,
			'todayEvent' => (empty($todayEvent) ? null : $todayEvent),
			'listEvents' => array_values($listEvents),
			'isTemplatesClosed' => $info->isTemplatesClosed(),
			'chats' => $chats,
			'defaultSprintDuration' => $defaultSprintDuration,
			'calendarSettings' => $this->getCalendarSettings($defaultSprintDuration),
			'culture'=> [
				'dayMonthFormat' => $culture->getDayMonthFormat(),
				'longDateFormat' => $culture->getLongDateFormat(),
				'shortTimeFormat' => $culture->getShortTimeFormat(),
			],
		];
	}

	/**
	 * Checks if the chat exists. Whether the user can access it. Adds it to the chat if needed.
	 *
	 * @param int $chatId Chat id.
	 * @return int|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getChatAction(int $chatId): ?int
	{
		if (!Loader::includeModule('im'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_CC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return null;
		}

		$userId = User::getId();

		if ($chatId > 0)
		{
			$chatData = \CIMChat::getChatData(['ID' => $chatId]);
			if ($chatData)
			{
				$userIds = $chatData['userInChat'][$chatId];

				if (!in_array($userId, $userIds))
				{
					$chat = new \CIMChat(0);
					$chat->addUser($chatId, $userId);
				}
			}
		}

		return $chatId;
	}

	/**
	 * Saves an association between an event and a event template type.
	 *
	 * @param int $groupId Group id.
	 * @param string $templateId Event template id.
	 * @param int $eventId Event id.
	 * @return int[]|null
	 */
	public function saveEventInfoAction(int $groupId, string $templateId, int $eventId): ?bool
	{
		$userId = User::getId();

		$eventData = $this->getEventData($userId, $eventId);
		if (empty($eventData['ID']))
		{
			return null;
		}

		$this->createChat($userId, $eventId, $eventData);

		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		$backlog->getInfo()->setEvents([$templateId => $eventId]);

		$backlogService->changeBacklog($backlog);
		if (!empty($backlogService->getErrors()))
		{
			$this->errorCollection->add([new Error('System error')]);

			return null;
		}

		return true;
	}

	/**
	 * Handles the logic for closing templates.
	 *
	 * @param int $groupId Group id.
	 * @return string
	 */
	public function closeTemplatesAction(int $groupId)
	{
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		$backlog->getInfo()->setTemplatesClosed('Y');

		$backlogService->changeBacklog($backlog);

		return '';
	}

	private function getMapCreatedEventsByTemplate(array $events): array
	{
		$map = [];

		foreach ($events as $templateId => $eventId)
		{
			if (\CCalendarEvent::getById($eventId))
			{
				$map[$templateId] = $eventId;
			}
		}

		return $map;
	}

	private function getUpcomingEventsForThisProject(int $groupId): array
	{
		$listEvents = [];

		$defaultSprintDuration = $this->getDefaultSprintDuration($groupId) + 86400;

		$sections = \CCalendarSect::getList([
			'arFilter' => [
				'OWNER_ID'=> $groupId,
				'ACTIVE' => 'Y',
				'CAL_TYPE' => 'group',
			],
			'checkPermissions' => true
		]);
		foreach ($sections as $section)
		{
			$events = \CCalendarEvent::getList(
				[
					'arFilter' => [
						'SECTION_ID' => $section['ID'],
						'OWNER_ID' => $groupId,
						'CAL_TYPE' => 'group',
						'DELETED' => 'N',
						'FROM_LIMIT' => \CCalendar::date(time(), false),
						'TO_LIMIT' => \CCalendar::date(time() + $defaultSprintDuration, false),
					],
					'parseRecursion' => true,
					'preciseLimits' => true,
					'fetchAttendees' => true,
					'checkPermissions' => true,
					'setDefaultLimit' => false
				]
			);
			foreach ($events as $event)
			{
				$fromTs = \CCalendar::timestamp($event['DATE_FROM']);
				$toTs = \CCalendar::timestamp($event['DATE_TO']);

				//$fromTs += \CCalendar::getTimezoneOffset($event['TZ_FROM'], $fromTs);
				//$toTs += \CCalendar::getTimezoneOffset($event['TZ_TO'], $toTs);

				$fromTs = $fromTs + date('Z', $fromTs);
				$toTs = $toTs + date('Z', $toTs);

				$currentTs = time();

				if ($fromTs > $currentTs)
				{
					if (!isset($listEvents[$event['ID']]))
					{
						$listEvents[$event['ID']] = [
							'id' => $event['ID'],
							'name' => $event['NAME'],
							'from' => $fromTs,
							'to' => $toTs,
							'color' => $event['COLOR'],
							'repeatable' => $event['RRULE'] !== '',
						];
					}
				}
			}
		}

		return array_values($listEvents);
	}

	private function getEventForToday(array $listEvents): array
	{
		$todayEvent = [];

		foreach ($listEvents as $key => $event)
		{
			$endCurrentDayTs = (strtotime('tomorrow', time()) - 1);
			if ($event['from'] < $endCurrentDayTs)
			{
				$todayEvent = $event;
				unset($listEvents[$key]);
				break;
			}
		}

		return [$listEvents, $todayEvent];
	}

	private function getEventData(int $userId, int $entityId): array
	{
		$data = [];

		$event = \CCalendarEvent::getEventForViewInterface($entityId);
		if (!$event)
		{
			return $data;
		}

		$pathToCalendar = \CCalendar::getPathForCalendarEx($userId);
		$pathToEvent = \CHTTP::urlAddParams($pathToCalendar, ['EVENT_ID' => $event['ID']]);

		$data = [
			'ID' => $event['ID'],
			'TITLE' => $event['NAME'],
			'DESCRIPTION' => $event['DESCRIPTION'],
			'CREATED_BY' => $event['CREATED_BY'],
			'MEETING' => $event['MEETING'],
			'DATE_FROM' => $event['DATE_FROM'],
			'DT_SKIP_TIME' => $event['DT_SKIP_TIME'],
			'LINK' => $pathToEvent,
			'URL' => $pathToEvent,
			'USER_IDS' => [],
		];

		foreach($event['ATTENDEE_LIST'] as $user)
		{
			if ((int)$user['id'] > 0)
			{
				$data['USER_IDS'][] = $user['id'];
			}
		}

		if (empty($data['USER_IDS']))
		{
			$data['USER_IDS'][] = $event['CREATED_BY'];
		}

		$data['USER_IDS'] = $this->checkUsers($data['USER_IDS']);

		return $data;
	}

	private function checkUsers($userIds): array
	{
		$newUserIds = [];

		$externalUserTypes = \Bitrix\Main\UserTable::getExternalUserTypes();

		$result = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID'],
			'filter' => ['=ID' => $userIds],
		]);
		while ($user = $result->fetch())
		{
			if (!in_array($user['EXTERNAL_AUTH_ID'], $externalUserTypes, true))
			{
				$newUserIds[] = $user['ID'];
			}
		}

		return $newUserIds;
	}

	private function createChat(int $userId, int $eventId, array $eventData): int
	{
		$lockName = 'chat_create_calendar_event_' . $eventId;

		if (!Application::getConnection()->lock($lockName))
		{
			return 0;
		}

		$chatId = $this->createCalendarChat($eventData, $userId);

		Application::getConnection()->unlock($lockName);

		return $chatId;
	}

	private function createCalendarChat(array $eventData, int $userId): int
	{
		if (!Loader::includeModule('im'))
		{
			return 0;
		}

		$chat = new \CIMChat(0);

		$chatFields = [
			'TITLE' => $eventData['TITLE'],
			'TYPE' => IM_MESSAGE_CHAT,
			'ENTITY_TYPE' => \CCalendar::CALENDAR_CHAT_ENTITY_TYPE,
			'ENTITY_ID' => $eventData['ID'],
			'SKIP_ADD_MESSAGE' => 'Y',
			'AUTHOR_ID' => $userId,
			'USERS' => $eventData['USER_IDS']
		];

		$chatId = $chat->add($chatFields);

		if ($chatId)
		{
			$pathToCalendar = \CCalendar::getPathForCalendarEx($userId);

			$pathToEvent = \CHTTP::urlAddParams($pathToCalendar, ['EVENT_ID' => $eventData['ID']]);

			$chatMessageFields = [
				'FROM_USER_ID' => $userId,
				'MESSAGE' => Loc::getMessage(
					'TSC_CHAT_MESSAGE',
					[
						'#EVENT_TITLE#' => '[url=' . $pathToEvent . ']' . $eventData['TITLE'] . '[/url]',
						'#DATETIME_FROM#' => \CCalendar::Date(
							\CCalendar::Timestamp($eventData['DATE_FROM']),
							$eventData['DT_SKIP_TIME'] === 'N',
							true, true
						)
					]
				),
				'SYSTEM' => 'Y',
				'INCREMENT_COUNTER' => 'N',
				'PUSH' => 'Y',
				'TO_CHAT_ID' => $chatId,
				'SKIP_USER_CHECK' => 'Y',
				'SKIP_COMMAND' => 'Y'
			];

			\CIMChat::addMessage($chatMessageFields);

			$eventData['MEETING']['CHAT_ID'] = $chatId;

			\CCalendar::SaveEvent([
				'arFields' => [
					'ID' => $eventData['ID'],
					'MEETING' => $eventData['MEETING']
				],
				'checkPermission' => false,
				'userId' => $eventData['CREATED_BY']
			]);

			\CCalendar::clearCache('event_list');
		}

		return $chatId;
	}

	private function getEventChats(int $userId, array $listEvents): array
	{
		$userService = new UserService();

		$chats = [];

		foreach ($listEvents as $event)
		{
			$chatId = \CIMChat::getEntityChat('CALENDAR', $event['id']);
			if ($chatId === false)
			{
				continue;
			}

			$chatData = \CIMChat::getChatData(['ID' => $chatId, 'PHOTO_SIZE' => 32]);

			if (($chatData['chat'][$chatId]['avatar'] ?? null) === '/bitrix/js/im/images/blank.gif')
			{
				$chatData['chat'][$chatId]['avatar'] = '';
			}

			$userIds = $chatData['userInChat'][$chatId] ?? null;
			if (is_array($userIds))
			{
				$usersInfo = $userService->getInfoAboutUsers($userIds);
				$users = (count($userIds) > 1 ? array_values($usersInfo) : [$usersInfo]);

				$chats[] = [
					'id' => $chatData['chat'][$chatId]['id'],
					'type' => $chatData['chat'][$chatId]['type'],
					'icon' => $chatData['chat'][$chatId]['avatar'],
					'name' => $chatData['chat'][$chatId]['name'],
					'users' => $users,
				];
			}
		}

		return $chats;
	}

	private function getDefaultSprintDuration(int $groupId): int
	{
		$group = Workgroup::getById($groupId);

		if ($group)
		{
			return $group->getDefaultSprintDuration();
		}
		else
		{
			return \DateInterval::createFromDateString('2 weeks')->format('%d') * 86400;
		}
	}

	private function getCalendarSettings(int $defaultSprintDuration): array
	{
		$settings = [];

		$settings['workTimeStart'] = (int) \COption::getOptionString('calendar', 'work_time_start', 9);

		$settings['weekDays'] = [];

		$holidays = explode('|', \COption::getOptionString('calendar', 'week_holidays', 'SA|SU'));
		foreach (\CCalendarSceleton::getWeekDays() as $day)
		{
			if (!in_array($day[2], $holidays, true))
			{
				$settings['weekDays'][$day[2]] = $day[2];
			}
		}

		$weekStart = current($settings['weekDays']);
		$settings['weekStart'][$weekStart] = $weekStart;

		$oneWeek = \DateInterval::createFromDateString('1 week')->format('%d') * 86400;
		$twoWeek = \DateInterval::createFromDateString('2 weeks')->format('%d') * 86400;
		$threeWeek = \DateInterval::createFromDateString('3 weeks')->format('%d') * 86400;
		$fourWeek = \DateInterval::createFromDateString('4 weeks')->format('%d') * 86400;
		$interval = 1;
		if ($defaultSprintDuration === $oneWeek)
		{
			$interval= 1;
		}
		elseif ($defaultSprintDuration === $twoWeek)
		{
			$interval= 2;
		}
		elseif ($defaultSprintDuration === $threeWeek)
		{
			$interval= 3;
		}
		elseif ($defaultSprintDuration === $fourWeek)
		{
			$interval= 4;
		}
		$settings['interval'] = $interval;

		return $settings;
	}
}