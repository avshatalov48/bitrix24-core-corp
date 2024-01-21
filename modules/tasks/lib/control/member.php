<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Internals\Task\MemberTable;

class Member
{
	use BaseControlTrait;

	private const FIELD_CREATED_BY = 'CREATED_BY';
	private const FIELD_RESPONSIBLE_ID = 'RESPONSIBLE_ID';
	private const FIELD_ACCOMPLICES = 'ACCOMPLICES';
	private const FIELD_AUDITORS = 'AUDITORS';

	/**
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function set(array $data): void
	{
		$this->loadTask();
		$members = $this->getCurrentMembers();
		$this->deleteByTask();

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

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$insertRows = [];
		foreach ($members as $type => $list)
		{
			$insertRows = array_merge(
				$insertRows,
				array_map(function ($el) use ($sqlHelper) {
					$implode = (int)$el['USER_ID'];
					$implode .= ',' . $this->taskId;
					$implode .= ',\'' . $sqlHelper->forSql($el['TYPE']) . '\'';
					return $implode;
				}, $list)
			);
		}

		$sql = "
			INSERT INTO " . MemberTable::getTableName() . "
			(USER_ID, TASK_ID, TYPE)
			VALUES
			(" . implode("),(", $insertRows) . ")
		";

		Application::getConnection()->query($sql);
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

	private function getCurrentMembers(): array
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
}