<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Util\Type\DateTime;

/**
 * Class TaskObject
 *
 * @package Bitrix\Tasks\Internals
 */
class TaskObject extends EO_Task
{
	/**
	 * @param int $taskId
	 * @return TaskObject|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function loadById(int $taskId): ?TaskObject
	{
		return TaskTable::getByPrimary($taskId)->fetchObject();
	}

	/**
	 * @param array $roles
	 * @return array
	 */
	public function getMembersByRoles(
		array $roles = [
			MemberTable::MEMBER_TYPE_ORIGINATOR,
			MemberTable::MEMBER_TYPE_RESPONSIBLE,
			MemberTable::MEMBER_TYPE_ACCOMPLICE,
			MemberTable::MEMBER_TYPE_AUDITOR,
		]
	): array
	{
		$this->fillMemberList();

		$res = [];
		foreach ($this->getMemberList() as $member)
		{
			if (in_array($member->getType(), $roles, true))
			{
				$res[] = $member;
			}
		}

		return $res;
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function isMuted(int $userId): bool
	{
		return UserOption::isOptionSet($this->getId(), $userId, UserOption\Option::MUTED);
	}

	/**
	 * @return bool
	 */
	public function isExpired(): bool
	{
		$status = (int)$this->getStatus();
		$completedStates = [\CTasks::STATE_SUPPOSEDLY_COMPLETED, \CTasks::STATE_COMPLETED, \CTasks::STATE_DEFERRED];

		if (!$this->getDeadline() || in_array($status, $completedStates, true))
		{
			return false;
		}

		return (DateTime::createFrom($this->getDeadline()))->checkLT(new DateTime());
	}
}