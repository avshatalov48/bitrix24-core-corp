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

	/**
	 * @param array $data
	 * @return static
	 */
	public static function invokeFromArray(array $data): ?self
	{
		if (!array_key_exists('ID', $data))
		{
			return null;
		}

		$resource = new self((int)$data['ID']);

		if (array_key_exists('TITLE', $data))
		{
			$resource->title = (string)$data['TITLE'];
		}
		if (
			array_key_exists('DEADLINE', $data)
			&& $data['DEADLINE']
			&& (string)(int)$data['DEADLINE'] === (string)$data['DEADLINE']
		)
		{
			$resource->deadline = DateTime::createFromTimestampGmt($data['DEADLINE']);
		}
		if (array_key_exists('STATUS', $data))
		{
			$resource->status = (int)$data['STATUS'];
		}
		if (array_key_exists('GROUP_ID', $data))
		{
			$resource->groupId = $data['GROUP_ID'];
		}
		if (array_key_exists('MEMBERS', $data))
		{
			$resource->members = $data['MEMBERS'];
		}
		if (array_key_exists('IS_EXPIRED', $data))
		{
			$resource->isExpired = $data['IS_EXPIRED'];
		}

		return $resource;
	}

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
			$memberId = $member['USER_ID'];
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
			$res[$member['TYPE']][] = $member['USER_ID'];
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

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$members = [];
		foreach ($this->members as $member)
		{
			$members[] = [
				'TASK_ID' => $member['TASK_ID'],
				'USER_ID' => $member['USER_ID'],
				'TYPE' => $member['TYPE'],
			];
		}

		return [
			'ID' => $this->id,
			'TITLE' => $this->title,
			'DEADLINE' => $this->deadline ? $this->deadline->getTimestamp() : null,
			'STATUS' => $this->status,
			'GROUP_ID' => $this->groupId,
			'MEMBERS' => $members,
			'IS_EXPIRED' => $this->isExpired,
		];
	}
}