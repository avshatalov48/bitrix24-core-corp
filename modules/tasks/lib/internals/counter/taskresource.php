<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Util\Type\DateTime;

/**
 * Class TaskResource
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class TaskResource
{
	private
		$id,
		$title,
		$deadline,
		$status 			= \CTasks::STATE_PENDING,
		$groupId 			= 0,
		$members 			= [],
		$isExpired 			= false;

	public function __construct(int $taskId)
	{
		$this->id = $taskId;
	}

	public function fill(): self
	{
		$task = TaskRegistry::getInstance()->getObject($this->id, true);
		if (!$task)
		{
			return $this;
		}

		$this->status 		= (int) $task->getStatus();
		$this->groupId 		= (int) $task->getGroupId();
		$this->title		= $task->getTitle();
		$this->deadline		= $task->getDeadline();
		$this->isExpired 	= $task->isExpired();
		$this->members		= $task->getMemberList();

		return $this;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getStatus(): int
	{
		return $this->status;
	}

	public function getGroupId(): int
	{
		return $this->groupId;
	}

	public function getMembers(): array
	{
		return $this->members;
	}

	public function getMemberIds(): array
	{
		$ids = [];
		foreach ($this->members as $member)
		{
			$memberId = $member->getUserId();
			$ids[$memberId] = $memberId;
		}
		return $ids;
	}

	public function getMembersAsArray(): array
	{
		$res = [
			MemberTable::MEMBER_TYPE_ACCOMPLICE => [],
			MemberTable::MEMBER_TYPE_ORIGINATOR => [],
			MemberTable::MEMBER_TYPE_RESPONSIBLE => [],
			MemberTable::MEMBER_TYPE_AUDITOR => [],
		];
		foreach ($this->members as $member)
		{
			$res[$member->getType()][] = $member->getUserId();
		}
		return $res;
	}

	public function isExpired(): bool
	{
		return $this->isExpired;
	}

	public function getDeadline(): ?DateTime
	{
		return $this->deadline;
	}
}