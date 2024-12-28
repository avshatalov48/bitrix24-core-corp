<?php
namespace Bitrix\Intranet\Integration;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\Secretary;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

final class Tasks
{
	public static function createDemoTemplates(): void
	{
		if (
			!Loader::includeModule('tasks')
			|| !($adminId = User::getAdminId())
		)
		{
			return;
		}

		$map = [
			'SONET_INITIAL_TASK' => [
				'TITLE' => Loc::getMessage('SONET_TASK_TITLE'),
				'DESCRIPTION' => Loc::getMessage('SONET_TASK_DESCRIPTION'),
			],
			'SONET_INVITE_TASK' => [
				"TITLE" => Loc::getMessage('SONET_INVITE_TASK_TITLE'),
				"DESCRIPTION" => Loc::getMessage('SONET_INVITE_TASK_DESCRIPTION_V2'),
			],
			'SONTE_INSTALL_APP_TASK' => [
				'TITLE' => Loc::getMessage('SONET_INSTALL_APP_TASK_TITLE'),
				'DESCRIPTION' => Loc::getMessage('SONET_INSTALL_APP_TASK_DESCRIPTION'),
			],
		];
		foreach ($map as $xmlId => $data)
		{
			$order = $navParams = $params = false;
			$filter = [
				'XML_ID' => $xmlId,
				'CREATED_BY' => $adminId,
			];
			$select = ['ID'];

			$templateResult = \CTaskTemplates::GetList($order, $filter, $navParams, $params, $select);
			if (!$templateResult->Fetch())
			{
				(new \CTaskTemplates())->Add([
					'CREATED_BY' => $adminId,
					'TPARAM_TYPE' => \CTaskTemplates::TYPE_FOR_NEW_USER,
					'PRIORITY' => \CTasks::PRIORITY_AVERAGE,
					'STATUS' => \CTasks::STATE_PENDING,
					'TITLE' => $data['TITLE'],
					'DESCRIPTION' => $data['DESCRIPTION'],
					'DESCRIPTION_IN_BBCODE' => 'Y',
					'SITE_ID' => \CTaskTemplates::CURRENT_SITE_ID,
					'XML_ID' => $xmlId,
					'ALLOW_CHANGE_DEADLINE' => 'Y',
				]);
			}
		}
	}

	public static function createDemoTasksForUser(int $userId): void
	{
		if (!Loader::includeModule('tasks'))
		{
			return;
		}

		if (self::isCollaber($userId))
		{
			return;
		}

		$templateResult = \CTaskTemplates::GetList(
			false,
			[
				'TPARAM_TYPE' => \CTaskTemplates::TYPE_FOR_NEW_USER,
				'BASE_TEMPLATE_ID' => false,
				'!XML_ID' => [
					'SONET_INITIAL_TASK',
					'SONET_INVITE_TASK',
					'SONTE_INSTALL_APP_TASK',
				],
			],
			false,
			false,
			['ID', 'CREATED_BY']
		);
		while ($item = $templateResult->Fetch())
		{
			\CTaskItem::addByTemplate(
				$item['ID'],
				$item['CREATED_BY'],
				false,
				['BEFORE_ADD_CALLBACK' => self::getBeforeAddCallback($userId)]
			);
		}
	}

	private static function getBeforeAddCallback(int $userId): \Closure
	{
		return static function (&$fields) use ($userId)
		{
			if (!(int)$fields['RESPONSIBLE_ID'])
			{
				$fields['RESPONSIBLE_ID'] = $userId;
			}
			$fields['XML_ID'] = md5($fields['TITLE'] . $fields['DESCRIPTION'] . SITE_ID);
			$fields['STATUS'] = \CTasks::STATE_PENDING;
			$fields['SITE_ID'] = SITE_ID;

			return true;
		};
	}

	private static function prepareUserList($taskFields): array
	{
		$userList = [];

		if (isset($taskFields['CREATED_BY']))
		{
			$userList[] =  (int) $taskFields['CREATED_BY'];

			if (
				isset($taskFields['RESPONSIBLE_ID'])
				&& $taskFields['RESPONSIBLE_ID'] !== $taskFields['CREATED_BY']
			)
			{
				$userList[] = (int) $taskFields['RESPONSIBLE_ID'];
			}
		}

		if (isset($taskFields['AUDITORS']))
		{
			foreach ($taskFields['AUDITORS'] as $userId)
			{
				$userId = (int)$userId;

				if (!in_array($userId, $userList))
				{
					$userList[] = $userId;
				}
			}
		}

		if (isset($taskFields['ACCOMPLICES']))
		{
			foreach ($taskFields['ACCOMPLICES'] as $userId)
			{
				$userId = (int)$userId;

				if (!in_array($userId, $userList))
				{
					$userList[] = $userId;
				}
			}
		}

		return $userList;
	}

	private static function prepareCurrentUserList($taskFields): array
	{
		$userList = [];

		if (
			!isset($taskFields['CREATED_BY'])
			|| !isset($taskFields['RESPONSIBLE_ID'])
			|| !isset($taskFields['AUDITORS'])
			|| !isset($taskFields['ACCOMPLICES'])
		)
		{
			$query = new Query(\Bitrix\Tasks\Internals\TaskTable::getEntity());
			$query->setSelect([
				'ID',
				'CREATED_BY',
				'TM_USER_ID' => 'TM.USER_ID',
			]);
			$query->setFilter([
				'=ID' => $taskFields['ID'],
			]);

			$query->registerRuntimeField('', new ReferenceField(
				'TM',
				\Bitrix\Tasks\Internals\Task\MemberTable::getEntity(),
				['=ref.TASK_ID' => 'this.ID']
			));

			$res = $query->exec();

			while ($item = $res->fetch())
			{
				$userId = $item['TM_USER_ID'];

				if (!in_array($userId, $userList))
				{
					$userList[] = $userId;
				}
			}
		}
		else
		{
			$userList = self::prepareUserList($taskFields);
		}

		return $userList;
	}

	public static function onTaskUpdate($taskId, &$currentTaskFields, &$previousTaskFields): void
	{
		if (!Loader::includeModule('tasks') || !Loader::includeModule('im'))
		{
			return;
		}

		$res = \Bitrix\Im\Model\ChatTable::getList(array(
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE' => 'TASKS',
				'=ENTITY_ID' => $taskId,
			],
			'limit' => 1
		));

		if ($chat = $res->fetch())
		{
			$chatId = $chat['ID'];
		}
		else
		{
			return;
		}

		$userIdNewList = self::prepareCurrentUserList($currentTaskFields);
		$userIdOldList = self::prepareUserList($previousTaskFields);

		if (empty($userIdNewList) || empty($userIdOldList))
		{
			return;
		}

		$addedUsers = array_diff($userIdNewList, $userIdOldList);
		$deletedUsers = array_diff($userIdOldList, $userIdNewList);

		if (empty($addedUsers) && empty($deletedUsers))
		{
			return;
		}

		Secretary::updateChatUsers($chatId, $addedUsers, $deletedUsers);
	}

	private static function isCollaber(int $userId): bool
	{
		if (!Loader::includeModule('extranet'))
		{
			return false;
		}

		return ServiceContainer::getInstance()->getCollaberService()->isCollaberById($userId);
	}
}