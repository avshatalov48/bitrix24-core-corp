<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Disk\File;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Application;
use Bitrix\Intranet;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\Json;

class ControlButton extends \Bitrix\Main\Engine\Controller
{
	protected const SIGNATURE_SALT = 'control_button_salt';

	public function getAvailableItemsAction(): array
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

	private function checkUsers(&$userIds): void
	{
		$newUserIds = [];
		$externalUserTypes = \Bitrix\Main\UserTable::getExternalUserTypes();

		$result = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID'],
			'filter' => ["=ID" => $userIds],
		]);
		while ($user = $result->fetch())
		{
			if (!in_array($user['EXTERNAL_AUTH_ID'], $externalUserTypes, true))
			{
				$newUserIds[] = $user['ID'];
			}
		}

		$userIds = $newUserIds;
	}

	private function getTaskData($entityId): array
	{
		global $USER;

		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		if (
			!\Bitrix\Tasks\Access\TaskAccessController::can(
				$USER->GetID(),
				\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_READ,
				$entityId
			)
		)
		{
			return [];
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
			'=ID' => $entityId,
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

			$userId = (int)$item['TM_USER_ID'];
			$userType = $item['TM_TYPE'];

			unset($item['TM_USER_ID'], $item['TM_TYPE']);

			$task['SE_MEMBER'][$userId] = ['USER_ID' => $userId, 'TYPE' => $userType];
			if (!in_array($userId, $task['USER_IDS'], true))
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

	public function getCalendarData($entityId, $entityData = []): array
	{
		global $USER;

		$res = [];

		if (!Loader::includeModule('calendar'))
		{
			return $res;
		}

		$entry = \CCalendarEvent::getEventForViewInterface($entityId);

		if (!$entry)
		{
			return $res;
		}

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
			'RECURRENCE_ID' => $entry['RECURRENCE_ID'],
			'USER_IDS' => is_array($entry['ATTENDEE_LIST']) ? array_column($entry['ATTENDEE_LIST'], 'id') : [$entry['CREATED_BY']],
			'LINK' => $pathToEvent,
			'URL' => $pathToEvent,
		];

		$this->checkUsers($res['USER_IDS']);

		return $res;
	}

	private function getTaskChat($entityId)
	{
		$chatId = '';

		global $USER;
		$userId = $USER->GetID();

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('im')
			|| !\Bitrix\Tasks\Access\TaskAccessController::can(
				$userId,
				\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_READ,
				$entityId
			)
		)
		{
			return $chatId;
		}

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
			Intranet\ControlButton::addUserToChat($chatId, $userId);
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

			$chatId = Intranet\ControlButton::createTaskChat($taskData, $userId);

			Application::getConnection()->unlock($lockName);
		}

		return $chatId;
	}

	private function getCalendarChat($entityId, $entityData = [])
	{
		global $USER;

		$chatId = '';

		if (!Loader::includeModule('calendar') || !Loader::includeModule('im'))
		{
			return $chatId;
		}

		$userId = $USER->GetId();
		$calendarData = $this->getCalendarData($entityId, $entityData);

		if (!in_array($userId, $calendarData['USER_IDS']))
		{
			return $chatId;
		}

		if ($calendarData['MEETING']['CHAT_ID'] > 0)
		{
			$chatId = $calendarData['MEETING']['CHAT_ID'];
			Intranet\ControlButton::addUserToChat($chatId, $userId);
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
			
			$parentCalendarData = [];
			if ($calendarData['RECURRENCE_ID'])
			{
				$parentCalendarData = $this->getCalendarData($calendarData['RECURRENCE_ID']);
			}

			$chatId = Intranet\ControlButton::createCalendarChat($calendarData, $userId, $parentCalendarData);

			Application::getConnection()->unlock($lockName);
		}

		return $chatId;
	}

	public function getChatAction($entityType, $entityId, $entityData = [])
	{
		if (!$entityType || !$entityId)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_ENTITY_ERROR')));
			return null;
		}

		if (!Loader::includeModule('im'))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_IM_ERROR'), 'create_chat_error'));
			return null;
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
			return null;
		}

		if (!Loader::includeModule('im'))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_IM_ERROR'), 'create_chat_error'));
			return null;
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
			return null;
		}

		if ($userCount === 1)
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROL_BUTTON_VIDEOCALL_SELF_ERROR')));
			return null;
		}
		return $this->getChatAction($entityType, $entityId);
	}

	public function getTaskLinkAction($entityType, $entityId, $postEntityType = '', $entityData = []): array
	{
		global $USER;

		$result = [
			'link' => '',
			'TITLE' => '',
			'DESCRIPTION' => '',
			'AUDITORS' => '',
		];

		if (!$entityType || !$entityId)
		{
			return $result;
		}

		switch (strtolower($entityType))
		{
			case 'calendar_event':
				$data = $this->getCalendarData($entityId, $entityData);
				break;
			default:

				if (
					!Loader::includeModule('socialnetwork')
					|| !Loader::includeModule('tasks')
				)
				{
					return $result;
				}

				$data = [];

				if ($provider = \Bitrix\Socialnetwork\Livefeed\Provider::init([
					'ENTITY_TYPE' => $entityType,
					'ENTITY_ID' => $entityId,
					'CLONE_DISK_OBJECTS' => true,
				]))
				{
					$data = [
						'TITLE' => $provider->getSourceTitle(),
						'DESCRIPTION' => $provider->getSourceDescription(),
						'SUFFIX' => $provider->getSuffix(),
						'URL' => $provider->getLiveFeedUrl(),
						'DISK_FILES' => array_values($provider->getAttachedDiskObjectsCloned()),
						'SONET_GROUP_ID' => $provider->getSonetGroupsAvailable(),
					];
				}
		}

		if (!empty($data))
		{
			$result['link'] = str_replace(
				[ '#USER_ID#', '#ID#' ],
				$USER->getId(),
				Option::get('intranet', 'search_user_url', SITE_DIR . 'company/personal/user/#USER_ID#/')
			) . 'tasks/task/edit/0/';
			$result['TITLE'] = $data['TITLE'];
			$result['DESCRIPTION'] = $data['DESCRIPTION'];
			$result['URL'] = $data['URL'];

			if (isset($data['USER_IDS']) && !empty($data['USER_IDS']))
			{
				$result['AUDITORS'] = implode(',', $data['USER_IDS']);
			}

			if ($entityType === 'calendar_event')
			{
				$result['CALENDAR_EVENT_ID'] = $data['ID'];
				$result['CALENDAR_EVENT_DATA'] = $entityData;
			}
			else
			{
				$result['SOURCE_POST_ENTITY_TYPE'] = $postEntityType;
				$result['SOURCE_ENTITY_TYPE'] = $entityType;
				$result['SOURCE_ENTITY_ID'] = (int)$entityId;
				$result['SUFFIX'] = $data['SUFFIX'];

				if (!empty($data['DISK_FILES']))
				{
					$diskFileUFCode = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();
					$result[$diskFileUFCode] = $data['DISK_FILES'];
					$signer = new Signer;
					$result[$diskFileUFCode . '_SIGN'] = $signer->sign(Json::encode($data['DISK_FILES']), static::SIGNATURE_SALT);
				}

				if (
					!empty($data['SONET_GROUP_ID'])
					&& count($data['SONET_GROUP_ID']) === 1
				)
				{
					$result['GROUP_ID'] = (int)array_pop($data['SONET_GROUP_ID']);
				}
			}
		}

		return $result;
	}

	public function clearNewTaskFilesAction(string $signedFiles = '')
	{
		if ($signedFiles === '')
		{
			return;
		}

		$signer = new Signer;

		try
		{
			$unsigned = $signer->unsign($signedFiles, static::SIGNATURE_SALT);
			$diskFiles = Json::decode($unsigned);
		}
		catch (\Exception $e)
		{
			$diskFiles = [];
		}

		if (
			!is_array($diskFiles)
			|| empty($diskFiles)
		)
		{
			return;
		}

		if (!Loader::includeModule('disk'))
		{
			$this->addError(new Error(
					Loc::getMessage('INTRANET_CONTROL_BUTTON_DISK_ERROR'), 'delete_new_task_files_no_module_error')
			);
			return null;
		}

		$fileIdList = array_map(static function($value) {
			return (
			preg_match('/^' . FileUserType::NEW_FILE_PREFIX . '(\d+)$/i', $value, $matches)
				? (int)$matches[1]
				: 0
			);
		}, $diskFiles);
		$fileIdList = array_filter($fileIdList, static function($value) {
			return ($value > 0);
		});
		$fileIdList = array_unique($fileIdList);

		foreach( File::getModelList([ 'filter' => [ 'ID' => $fileIdList ] ] ) as $file)
		{
			if (
				($storage = $file->getStorage())
				&& $file->canDelete($storage->getCurrentUserSecurityContext())
			)
			{
				if (!$file->delete($this->getCurrentUser()->getId()))
				{
					$this->addError(new Error(
							Loc::getMessage('INTRANET_CONTROL_BUTTON_DELETE_TASK_FILE_ERROR'), 'delete_new_task_file_error')
					);
					return null;
				}
			}
		}
	}

	public function getCalendarLinkAction($entityType, $entityId): array
	{
		if (!$entityType || !$entityId)
		{
			return [];
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

	public function getPostLinkAction($entityType, $entityId, $entityData = []): array
	{
		global $USER;

		$result = [
			'link' => '',
			'destTo' => [],
			'title' => '',
		];

		if (!$entityType || !$entityId)
		{
			return $result;
		}

		$result['link'] = SITE_DIR . 'company/personal/user/' . $USER->GetID() . '/blog/edit/post/0/';

		$data = false;

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

		if (is_array($data))
		{
			foreach ($data['USER_IDS'] as $userId)
			{
				$result['destTo'][] = 'U' . (int)$userId;
			}

			$result['title'] = $data['TITLE'];

			if (isset($data['GROUP_ID']) && (int)$data['GROUP_ID'] > 0)
			{
				$result['destTo'][] = 'SG' . (int)$data['GROUP_ID'];
			}
		}

		return $result;
	}
}
