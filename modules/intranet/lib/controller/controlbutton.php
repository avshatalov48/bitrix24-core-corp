<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Application;

class ControlButton extends \Bitrix\Main\Engine\Controller
{
	public function getAvailableItemsAction()
	{
		$items = [];

		if (ModuleManager::isModuleInstalled('im'))
		{
			$items[] = 'chat';
			$items[] = 'videocall';
		}

		if (ModuleManager::isModuleInstalled('socialnetwork'))
		{
			$items[] = 'blog_post';
		}

		if (ModuleManager::isModuleInstalled('tasks'))
		{
			$items[] = 'task';
		}

		if (ModuleManager::isModuleInstalled('calendar'))
		{
			$items[] = 'calendar_event';
		}

		return $items;
	}

	private function udpateChatUsers($chatId, $newUserIds)
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		$userIdsToAdd = [];
		$currentUsers = \Bitrix\Im\Chat::getUsers($chatId);
		$currentUserIds = [];
		$chat = new \CIMChat(0);

		foreach ($currentUsers as $key => $user)
		{
			$currentUserIds[] = $user['id'];

			if (!in_array($user['id'], $newUserIds))
			{
				$res = $chat->DeleteUser($chatId, $user['id'], false);
			}
		}

		foreach ($newUserIds as $userId)
		{
			if (!in_array($userId, $currentUserIds))
			{
				$userIdsToAdd[] = $userId;
			}
		}

		if (!empty($userIdsToAdd))
		{
			$chat->AddUser($chatId, $userIdsToAdd);
		}
	}

	private function checkUsers(&$userIds)
	{
		$newUserIds = [];
		$externalUserTypes = \Bitrix\Main\UserTable::getExternalUserTypes();

		$result = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID'],
			'filter' => ["=ID" => $userIds],
		]);
		while ($user = $result->fetch())
		{
			if (!in_array($user['EXTERNAL_AUTH_ID'], $externalUserTypes))
			{
				$newUserIds[] = $user['ID'];
			}
		}

		$userIds = $newUserIds;
	}

	private function getTaskData($id)
	{
		global $USER;

		if (!Loader::includeModule('tasks'))
		{
			return;
		}

		$query = new Query(\Bitrix\Tasks\Internals\TaskTable::getEntity());
		$query->setSelect([
			'ID',
			'TITLE',
			'DESCRIPTION',
			'DEADLINE',
			'STATUS',
			'CREATED_BY',
			'GROUP_ID',
			'TM_USER_ID' => 'TM.USER_ID',
			//'TM_TYPE' => 'TM.TYPE',
		]);
		$query->setFilter([
			'=ID' => $id,
		]);

		$query->registerRuntimeField('', new ReferenceField(
			'TM',
			\Bitrix\Tasks\Internals\Task\MemberTable::getEntity(),
			['=ref.TASK_ID' => 'this.ID']
		));

		$res = $query->exec();

		$task = [
			'USER_IDS' => [],
		];

		while ($item = $res->fetch())
		{
			$task['ID'] = $item['ID'];
			$task['TITLE'] = $item['TITLE'];
			$task['DESCRIPTION'] = $item['DESCRIPTION'];
			$task['CREATED_BY'] = $item['CREATED_BY'];
			$task['GROUP_ID'] = $item['GROUP_ID'];
			$task['LINK'] = SITE_DIR . 'company/personal/user/' . $item['CREATED_BY'] . '/tasks/task/view/' . $item['ID'] . '/';

			$userId = $item['TM_USER_ID'];
			$userType = $item['TM_TYPE'];

			unset($item['TM_USER_ID'], $item['TM_TYPE']);

			$task['SE_MEMBER'][$userId] = ['USER_ID' => $userId, 'TYPE' => $userType];
			if (!in_array($userId, $task['USER_IDS']))
			{
				$task['USER_IDS'][] = $userId;
			}

			/*$roleMap = ['O' => 'CREATED_BY', 'R' => 'RESPONSIBLE_ID'];
			if (array_key_exists($userType, $roleMap))
			{
				$tasks[$taskId][$roleMap[$userType]] = $userId;
			}*/
		}

		$this->checkUsers($task['USER_IDS']);

		return $task;
	}

	public function getCalendarData($entityId, $entityData = [])
	{
		global $USER;

		if (!Loader::includeModule('calendar'))
		{
			return;
		}

		$entry = \CCalendarEvent::getEventForViewInterface($entityId);

		$pathToCalendar = \CCalendar::GetPathForCalendarEx($USER->GetID());
		$pathToEvent = \CHTTP::urlAddParams($pathToCalendar, ['EVENT_ID' => $entry['ID']]);

		$res = [
			'ID' => $entry['ID'],
			'TITLE' => $entry['NAME'],
			'DESCRIPTION' => $entry['DESCRIPTION'],
			'CREATED_BY' => $entry['CREATED_BY'],
			'MEETING' => $entry['MEETING'],
			'DATE_FROM' => $entry['DATE_FROM'],
			'DT_SKIP_TIME' => $entry['DT_SKIP_TIME'],
			'USER_IDS' => [],
			'LINK' => $pathToEvent,
		];

		foreach($entry['ATTENDEE_LIST'] as $user)
		{
			if (intval($user['id']) > 0)
			{
				$res['USER_IDS'][] = $user['id'];
			}
		}

		if (empty($res['USER_IDS']))
		{
			$res['USER_IDS'][] = $entry['CREATED_BY'];
		}

		$this->checkUsers($res['USER_IDS']);

		return $res;
	}

	private function getTaskChat($entityId)
	{
		global $USER;

		if (!Loader::includeModule('tasks') && !Loader::includeModule('im'))
		{
			return;
		}

		$chatId = '';
		$userId = $USER->GetID();
		$taskData = $this->getTaskData($entityId);

		$res = \Bitrix\Im\Model\ChatTable::getList(array(
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE' => 'TASKS',
				'=ENTITY_ID' => $entityId,
			],
			'limit' => 1
		));

		if ($chat = $res->fetch())
		{
			$chatId = $chat['ID'];
			$this->udpateChatUsers($chatId, $taskData['USER_IDS']);
		}
		else
		{
			$lockName = "chat_create_task_{$entityId}";
			if (!Application::getConnection()->lock($lockName))
			{
				$this->addError(new Error(
						Loc::getMessage('INTRANET_CONTROL_BUTTON_CREATE_CHAT_LOCK_ERROR'), 'lock_error')
				);
				return null;
			}

			$chat = new \CIMChat(0);
			$chatFields = [
				'TITLE' => Loc::getMessage(
					'INTRANET_CONTROL_BUTTON_TASK_CHAT_TITLE',
					['#TASK_TITLE#' => $taskData['TITLE']]
				),
				'TYPE' => IM_MESSAGE_CHAT,
				'ENTITY_TYPE' => 'TASKS',
				'ENTITY_ID' => $taskData['ID'],
				'SKIP_ADD_MESSAGE' => 'Y',
				'AUTHOR_ID' => $userId,
				'USERS' => $taskData['USER_IDS']
			];

			$chatId = $chat->add($chatFields);

			if ($chatId)
			{
				$pathToTask = SITE_DIR . 'company/personal/user/' . $taskData['CREATED_BY'] . '/tasks/task/view/' . $taskData['ID'] . '/';
				$entryLinkTitle = '[url=' . $pathToTask . ']' . $taskData['TITLE'] . '[/url]';
				$chatMessageFields = [
					'FROM_USER_ID' => $userId,
					'MESSAGE' => Loc::getMessage(
						'INTRANET_CONTROL_BUTTON_TASK_CHAT_FIRST_MESSAGE',
						[
							'#TASK_TITLE#' => $entryLinkTitle,
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
			}

			Application::getConnection()->unlock($lockName);
		}

		return $chatId;
	}

	private function getCalendarChat($entityId, $entityData = [])
	{
		global $USER;

		if (!Loader::includeModule('calendar') || !Loader::includeModule('im'))
		{
			return;
		}

		$chatId = '';
		$userId = $USER->GetId();
		$calendarData = $this->getCalendarData($entityId, $entityData);

		if ($calendarData['MEETING']['CHAT_ID'] > 0)
		{
			$chatId = $calendarData['MEETING']['CHAT_ID'];
		}
		else
		{
			$lockName = "chat_create_calendar_event_{$entityId}";
			if (!Application::getConnection()->lock($lockName))
			{
				$this->addError(new Error(
					Loc::getMessage('INTRANET_CONTROL_BUTTON_CREATE_CHAT_LOCK_ERROR'), 'lock_error')
				);
				return null;
			}

			$chat = new \CIMChat(0);
			$chatFields = [
				'TITLE' => Loc::getMessage(
					'INTRANET_CONTROL_BUTTON_CALENDAR_CHAT_TITLE',
					['#EVENT_TITLE#' => $calendarData['TITLE']]
				),
				'TYPE' => IM_MESSAGE_CHAT,
				'ENTITY_TYPE' => \CCalendar::CALENDAR_CHAT_ENTITY_TYPE,
				'ENTITY_ID' => $calendarData['ID'],
				'SKIP_ADD_MESSAGE' => 'Y',
				'AUTHOR_ID' => $userId,
				'USERS' => $calendarData['USER_IDS']
			];

			$chatId = $chat->add($chatFields);

			if ($chatId)
			{
				$pathToCalendar = \CCalendar::GetPathForCalendarEx($userId);
				$pathToEvent = \CHTTP::urlAddParams($pathToCalendar, ['EVENT_ID' => $calendarData['ID']]);
				$entryLinkTitle = '[url=' . $pathToEvent . ']' . $calendarData['TITLE'] . '[/url]';
				$chatMessageFields = [
					'FROM_USER_ID' => $userId,
					'MESSAGE' => Loc::getMessage(
						'INTRANET_CONTROL_BUTTON_CALENDAR_CHAT_FIRST_MESSAGE',
						[
							'#EVENT_TITLE#' => $entryLinkTitle,
							'#DATETIME_FROM#' => \CCalendar::Date(
								\CCalendar::Timestamp($calendarData['DATE_FROM']),
								$calendarData['DT_SKIP_TIME'] === 'N',
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

				$calendarData['MEETING']['CHAT_ID'] = $chatId;
				$response['id'] = \CCalendar::SaveEvent([
					'arFields' => [
						'ID' => $calendarData['ID'],
						'MEETING' => $calendarData['MEETING']
					],
					'checkPermission' => false,
					'userId' => $calendarData['CREATED_BY']
				]);

				\CCalendar::ClearCache('event_list');
			}

			Application::getConnection()->unlock($lockName);
		}

		return $chatId;
	}

	public function getChatAction($entityType, $entityId, $entityData = [])
	{
		if (!$entityType || !$entityId)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_ENTITY_ERROR')));
			return;
		}

		if (!Loader::includeModule('im'))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_IM_ERROR'), 'create_chat_error'));
			return;
		}

		$chatId = '';

		if ($entityType === 'task')
		{
			$chatId = $this->getTaskChat($entityId);
		}
		elseif ($entityType === 'calendar_event')
		{
			$chatId = $this->getCalendarChat($entityId, $entityData);
		}

		return $chatId;
	}

	public function getVideoCallChatAction($entityType, $entityId, $entityData = [])
	{
		if (!$entityType || !$entityId)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_ENTITY_ERROR')));
			return;
		}

		if (!Loader::includeModule('im'))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_IM_ERROR'), 'create_chat_error'));
			return;
		}

		$userCount = 0;

		if ($entityType === 'task')
		{
			$taskData = $this->getTaskData($entityId);
			$userCount = count($taskData['USER_IDS']);
		}
		elseif ($entityType === 'calendar_event')
		{
			$calendarData = $this->getCalendarData($entityId, $entityData);
			$userCount = count($calendarData['USER_IDS']);
		}

		if ($userCount > \Bitrix\Im\Call\Call::getMaxParticipants())
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_VIDEOCALL_LIMIT')));
			return;
		}

		return self::getChatAction($entityType, $entityId);
	}

	public function getTaskLinkAction($entityType, $entityId, $entityData = [])
	{
		global $USER;

		$result = [
			'link' => '',
			'title' => '',
			'description' => '',
			'auditors' => '',
		];

		if (!$entityType || !$entityId)
		{
			return $result;
		}

		$data = [];

		if ($entityType === 'calendar_event')
		{
			$data = $this->getCalendarData($entityId, $entityData);
		}

		if (!empty($data))
		{
			$result['link'] = SITE_DIR . 'company/personal/user/' . $USER->GetID() . '/tasks/task/edit/0/';
			$result['TITLE'] = $data['TITLE'];
			$result['DESCRIPTION'] = $data['DESCRIPTION'];

			if (isset($data['USER_IDS']) && !empty($data['USER_IDS']))
			{
				$result['AUDITORS'] = implode(',', $data['USER_IDS']);
			}

			if ($entityType === 'calendar_event')
			{
				$result['CALENDAR_EVENT_ID'] = $data['ID'];
				$result['CALENDAR_EVENT_DATA'] = $entityData;
			}
		}

		return $result;
	}

	public function getCalendarLinkAction($entityType, $entityId)
	{
		if (!$entityType || !$entityId)
		{
			return;
		}

		$res = [];

		if ($entityType === 'task')
		{
			$data = $this->getTaskData($entityId);

			$res = [
				'name' => $data['TITLE'],
				'desc' => Loc::getMessage('INTRANET_CONTROL_BUTTON_POST_MESSAGE_TASK', [
					'#LINK#' => $data['LINK'],
				]),
				'userIds' => $data['USER_IDS'],
			];
		}

		return $res;
	}

	public function getPostLinkAction($entityType, $entityId, $entityData = [])
	{
		global $USER;

		$result = [
			'link' => '',
			'destTo' => [],
		];

		if (!$entityType || !$entityId)
		{
			return $result;
		}

		$result['link'] = SITE_DIR . 'company/personal/user/' . $USER->GetID() . '/blog/edit/post/0/';

		if ($entityType === 'calendar_event')
		{
			$data = $this->getCalendarData($entityId, $entityData);
			$result['message'] = Loc::getMessage('INTRANET_CONTROL_BUTTON_POST_MESSAGE_CALENDAR', [
				'#LINK#' => $data['LINK'],
			]);
		}
		elseif ($entityType === 'task')
		{
			$data = $this->getTaskData($entityId);
			$result['message'] = Loc::getMessage('INTRANET_CONTROL_BUTTON_POST_MESSAGE_TASK', [
				'#LINK#' => $data['LINK'],
			]);
		}

		foreach ($data['USER_IDS'] as $key => $userId)
		{
			$result['destTo'][] = 'U' . (int)$userId;
		}

		$result['title'] = $data['TITLE'];

		if (isset($data['GROUP_ID']) && (int)$data['GROUP_ID'] > 0)
		{
			$result['destTo'][] = 'SG' . (int)$data['GROUP_ID'];
		}

		return $result;
	}
}
