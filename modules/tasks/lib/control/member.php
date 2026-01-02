<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;

class Member
{
	private int $userId;
	private int $taskId;
	private ?TaskObject $task = null;

	public function __construct(int $userId, int $taskId)
	{
		$this->userId = $userId;
		$this->taskId = $taskId;
	}

	private const FIELD_CREATED_BY = 'CREATED_BY';
	private const FIELD_RESPONSIBLE_ID = 'RESPONSIBLE_ID';
	private const FIELD_ACCOMPLICES = 'ACCOMPLICES';
	private const FIELD_AUDITORS = 'AUDITORS';

	public function add(array $fields): void
	{
		$this->saveMembers($fields);
	}

	public function set(array $data, array $changes = []): void
	{
		$this->loadTaskMembers();
		$members = $this->formatCurrentMembers();
		$this->deleteByTask();

		$this->saveMembers($data, $members);

		$this->unsubscribeExcludedUsers($changes, $members);
	}

	private function loadTaskMembers(): void
	{
		if ($this->task)
		{
			return;
		}

		$select = ['CREATED_BY', 'RESPONSIBLE_ID'];
		$this->task = TaskTable::getByPrimary($this->taskId, ['select' => $select])->fetchObject();
		if (!$this->task)
		{
			throw new TaskNotFoundException();
		}

		$this->task->fillMemberList();
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function deleteByTask(): void
	{
		MemberTable::deleteList([
			'TASK_ID' => $this->taskId,
		]);
	}

	private function unsubscribeExcludedUsers(array $changes, array $members): void
	{
		if (empty($changes))
		{
			return;
		}

		foreach ($changes as $key => $value)
		{
			if (in_array($key, [self::FIELD_ACCOMPLICES, self::FIELD_AUDITORS, self::FIELD_RESPONSIBLE_ID], true))
			{
				$fromUsers = explode(',', $value['FROM_VALUE']);
				$toUsers = explode(',', $value['TO_VALUE']);
				$this->unsubscribe($this->getUniqueExcludedUsers($fromUsers, $toUsers, $members));
			}
		}
	}

	private function unsubscribe(array $excludedUsers): void
	{
		if (empty($excludedUsers))
		{
			return;
		}

		$tags = [
			'TASK_VIEW_' . $this->taskId,
			'UNICOMMENTSTASK_' . $this->taskId,
			'UNICOMMENTSEXTENDEDTASK_' . $this->taskId,
			'CONTENTVIEWTASK-' . $this->taskId,
		];

		foreach ($excludedUsers as $userId)
		{
			$params = [
				'module_id' => 'tasks',
				'command' => PushCommand::TASK_PULL_UNSUBSCRIBE,
				'params' => [
					'userId' => $userId,
				],
			];

			foreach ($tags as $tag)
			{
				PushService::addEventByTag($tag, $params);
			}
		}
	}

	private function formatCurrentMembers(): array
	{
		$members = [];
		$memberList = $this->task->getMemberList();
		foreach ($memberList as $member)
		{
			$memberType = $member->getType();
			$members[$memberType][] = [
				'USER_ID' => $member->getUserId(),
				'TYPE' => $memberType,
			];
		}

		if (!array_key_exists(MemberTable::MEMBER_TYPE_RESPONSIBLE, $members))
		{
			$members[MemberTable::MEMBER_TYPE_RESPONSIBLE][] = [
				'USER_ID' => $this->task->getResponsibleId(),
				'TYPE' => MemberTable::MEMBER_TYPE_RESPONSIBLE,
			];
		}

		if (!array_key_exists(MemberTable::MEMBER_TYPE_ORIGINATOR, $members))
		{
			$members[MemberTable::MEMBER_TYPE_ORIGINATOR][] = [
				'USER_ID' => $this->task->getCreatedBy(),
				'TYPE' => MemberTable::MEMBER_TYPE_ORIGINATOR,
			];
		}

		return $members;
	}

	private function getUniqueExcludedUsers(array $from, array $to, array $members): array
	{
		$excludedUsers = [];

		$users = array_unique(array_diff($from, $to));

		foreach ($users as $user)
		{
			$userId = (int)$user;
			if ($this->isUserInCurrentMemberList($userId, $members))
			{
				// skip
				continue;
			}

			$excludedUsers[] = $userId;
		}

		return $excludedUsers;
	}

	private function isUserInCurrentMemberList(int $userId, array $members): bool
	{
		foreach ($members as $type => $list)
		{
			foreach ($list as $element)
			{
				$memberUserId = (int)($element['USER_ID'] ?? 0);
				if ($memberUserId === $userId)
				{
					return true;
				}
			}
		}

		return false;
	}

	private function saveMembers(array $data, array $members = []): void
	{
		if (
			array_key_exists(self::FIELD_RESPONSIBLE_ID, $data)
			&& (int)$data[self::FIELD_RESPONSIBLE_ID] > 0
		)
		{
			$members[MemberTable::MEMBER_TYPE_RESPONSIBLE] = [];
			$members[MemberTable::MEMBER_TYPE_RESPONSIBLE][] = [
				'USER_ID' => (int)$data[self::FIELD_RESPONSIBLE_ID],
				'TYPE' => MemberTable::MEMBER_TYPE_RESPONSIBLE,
			];
		}

		if (
			array_key_exists(self::FIELD_CREATED_BY, $data)
			&& (int)$data[self::FIELD_CREATED_BY] > 0
		)
		{
			$members[MemberTable::MEMBER_TYPE_ORIGINATOR] = [];
			$members[MemberTable::MEMBER_TYPE_ORIGINATOR][] = [
				'USER_ID' => (int)$data[self::FIELD_CREATED_BY],
				'TYPE' => MemberTable::MEMBER_TYPE_ORIGINATOR,
			];
		}

		if (array_key_exists(self::FIELD_ACCOMPLICES, $data))
		{
			$members[MemberTable::MEMBER_TYPE_ACCOMPLICE] = [];
			foreach ($data[self::FIELD_ACCOMPLICES] as $userId)
			{
				$userId = (int)$userId;
				if ($userId < 1)
				{
					continue;
				}
				$members[MemberTable::MEMBER_TYPE_ACCOMPLICE][] = [
					'USER_ID' => $userId,
					'TYPE' => MemberTable::MEMBER_TYPE_ACCOMPLICE,
				];
			}
		}

		if (array_key_exists(self::FIELD_AUDITORS, $data))
		{
			$members[MemberTable::MEMBER_TYPE_AUDITOR] = [];
			foreach ($data[self::FIELD_AUDITORS] as $userId)
			{
				$userId = (int)$userId;
				if ($userId < 1)
				{
					continue;
				}
				$members[MemberTable::MEMBER_TYPE_AUDITOR][] = [
					'USER_ID' => $userId,
					'TYPE' => MemberTable::MEMBER_TYPE_AUDITOR,
				];
			}
		}

		if (empty($members))
		{
			return;
		}

		$insertRows = [];
		foreach ($members as $type => $list)
		{
			foreach ($list as $member)
			{
				$insertRows[] = [
					'USER_ID' => (int)$member['USER_ID'],
					'TYPE' => $type,
					'TASK_ID' => $this->taskId,
				];
			}
		}

		$result = MemberTable::addInsertIgnoreMulti($insertRows);
		if (!$result->isSuccess())
		{
			throw new SqlQueryException($result->getError()?->getMessage());
		}
	}
}