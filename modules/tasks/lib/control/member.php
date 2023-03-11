<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskTable;

class Member
{
	use BaseControlTrait;

	private const FIELD_CREATED_BY = 'CREATED_BY';
	private const FIELD_RESPONSIBLE_ID = 'RESPONSIBLE_ID';
	private const FIELD_ACCOMPLICES = 'ACCOMPLICES';
	private const FIELD_AUDITORS = 'AUDITORS';


	private function setToOwner(int $fromUser, Tag $userTagRepository)
	{
		$userTagRepository->changeTagsOwner($this->taskId, $fromUser);
	}
	private function transferTags(array $users)
	{
		$userTagRepository = new Tag($this->userId);
		foreach ($users as $fromUser)
		{
			$this->setToOwner((int)$fromUser, $userTagRepository);
		}
	}

	private function getMembersIds(array $curMembers)
	{
		$members = [];

		foreach ($curMembers  as $role => $roleMembers)
		{
			foreach ($roleMembers as $roleMember)
			{
				$members[] = $roleMember['USER_ID'];
			}
		}
		return array_unique($members);
	}

	private function getDataMembersIds(array $data)
	{
		$members = [];

		if (!empty($data[self::FIELD_CREATED_BY]))
		{
			$members[] = $data[self::FIELD_CREATED_BY];
		}
		else
		{
			$members[] = (int)TaskTable::getById($this->taskId)->fetch()['CREATED_BY'];
		}
		if (!empty($data[self::FIELD_RESPONSIBLE_ID]))
		{
			$members[] = $data[self::FIELD_RESPONSIBLE_ID];
		}
		if (!empty($data[self::FIELD_ACCOMPLICES]))
		{
			foreach ($data[self::FIELD_ACCOMPLICES] as $key => $id)
			{
				$members[] = $id;
			}
		}

		if (!empty($data[self::FIELD_AUDITORS]))
		{
			foreach ($data[self::FIELD_AUDITORS] as $key => $id)
			{
				$members[] = $id;
			}
		}
		return array_unique($members);
	}
	/**
	 * @param array $data
	 * @return void
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function set(array $data)
	{
		$this->loadTask();
		$members = $this->getCurrentMembers();

		$curMembersIds = $this->getMembersIds($members);

		// $membersToRemove = array_diff($curMembersIds, $newMembersIds);


		// $this->transferTags($membersToRemove);

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
		$newMembersIds = $this->getMembersIds($members);
		$membersToRemove = array_diff($curMembersIds, $newMembersIds);

		// $this->transferTags($membersToRemove);


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
			(`USER_ID`, `TASK_ID`, `TYPE`)
			VALUES
			(" . implode("),(", $insertRows) . ")
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteByTask()
	{
		MemberTable::deleteList([
			'TASK_ID' => $this->taskId,
		]);
	}

	/**
	 * @return array
	 */
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