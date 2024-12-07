<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\AccessibleTask;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Flow;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use Bitrix\Tasks\Internals\Task\Status;

class TaskModel implements AccessibleTask
{
	private static $cache = [];

	private $id = 0;
	private $members;
	private $groupId;
	private $status;
	private $group;
	private $flowId = null;

	/**
	 * @param int $taskId
	 */
	public static function invalidateCache(int $taskId): void
	{
		unset(static::$cache[$taskId]);
		TaskRegistry::getInstance()->drop($taskId);
	}

	/**
	 * @param int $groupId
	 * @return static
	 */
	public static function createNew(int $groupId = 0): self
	{
		$model = new self();
		$model
			->setGroupId($groupId)
			->setStatus(Status::PENDING);
		return $model;
	}

	/**
	 * @param int $taskId
	 * @return AccessibleItem
	 */
	public static function createFromId(int $taskId): AccessibleItem
	{
		if (!isset(static::$cache[$taskId]))
		{
			$model = new self();
			$model->setId($taskId);
			static::$cache[$taskId] = $model;
		}

		return static::$cache[$taskId];
	}

	/**
	 * @param \Bitrix\Tasks\Item\Task $item
	 * @return TaskModel
	 */
	public static function createFromTaskItem(\Bitrix\Tasks\Item\Task $item): static
	{
		$item = $item->getRawValues();

		$model = new self();
		if (array_key_exists('ID', $item) && $item['ID'])
		{
			$model->setId((int) $item['ID']);
		}

		if (array_key_exists('GROUP_ID', $item) && $item['GROUP_ID'])
		{
			$model->setGroupId((int) $item['GROUP_ID']);
		}

		$members = [];
		if (array_key_exists('SE_MEMBER', $item))
		{
			foreach ($item['SE_MEMBER'] as $member)
			{
				$members[$member['TYPE']][] = (int) $member['USER_ID'];
			}

			$model->setMembers($members);
		}

		return $model;
	}

	/**
	 * @param array $request
	 * @return static
	 */
	public static function createFromRequest(array $request): self
	{
		$model = new self();

		// fill group
		$groupId = 0;
		if (array_key_exists('SE_PROJECT', $request) && isset($request['SE_PROJECT']['ID']))
		{
			$groupId = (int) $request['SE_PROJECT']['ID'];
		}
		$model->setGroupId($groupId);

		// fill members
		$members = [];
		if (array_key_exists('SE_RESPONSIBLE', $request) && is_array($request['SE_RESPONSIBLE']))
		{
			foreach ($request['SE_RESPONSIBLE'] as $responsible)
			{
				if (!is_array($responsible) || !isset($responsible['ID']))
				{
					continue;
				}
				$members[RoleDictionary::ROLE_RESPONSIBLE][] = (int) $responsible['ID'];
			}
		}
		if (array_key_exists('SE_ORIGINATOR', $request) && is_array($request['SE_ORIGINATOR']) && isset($request['SE_ORIGINATOR']['ID']))
		{
			$members[RoleDictionary::ROLE_DIRECTOR][] = (int) $request['SE_ORIGINATOR']['ID'];
		}
		if (array_key_exists('SE_ACCOMPLICE', $request) && is_array($request['SE_ACCOMPLICE']))
		{
			foreach ($request['SE_ACCOMPLICE'] as $member)
			{
				if (!is_array($member) || !isset($member['ID']))
				{
					continue;
				}
				$members[RoleDictionary::ROLE_ACCOMPLICE][] = (int) $member['ID'];
			}
		}
		if (array_key_exists('SE_AUDITOR', $request) && is_array($request['SE_AUDITOR']))
		{
			foreach ($request['SE_AUDITOR'] as $member)
			{
				if (!is_array($member) || !isset($member['ID']))
				{
					continue;
				}
				$members[RoleDictionary::ROLE_AUDITOR][] = (int) $member['ID'];
			}
		}
		$model->setMembers($members);

		if (!empty($request['FLOW_ID']) && (int)$request['FLOW_ID'] > 0)
		{
			$model->setFlowId((int)$request['FLOW_ID']);
		}

		return $model;
	}

	/**
	 * @param array $data
	 * @param array $default
	 * @return static
	 */
	public static function createFromArray(array $data, array $default = []): self
	{
		$model = new self();

		$id = 0;
		if (isset($data['ID']))
		{
			$id = (int) $data['ID'];
		}
		elseif (isset($default['ID']))
		{
			$id = (int) $default['ID'];
		}
		$model->setId($id);

		if (isset($data['STATUS']))
		{
			$model->setStatus((int) $data['STATUS']);
		}

		$groupId = 0;
		if (isset($data['GROUP_ID']))
		{
			$groupId = (int) $data['GROUP_ID'];
		}
		elseif (isset($default['GROUP_ID']))
		{
			$groupId = (int) $default['GROUP_ID'];
		}
		$model->setGroupId($groupId);

		$members = [];

		if (isset($data['CREATED_BY']))
		{
			$members[RoleDictionary::ROLE_DIRECTOR][] = (int) $data['CREATED_BY'];
		}
		elseif (isset($default['CREATED_BY']))
		{
			$members[RoleDictionary::ROLE_DIRECTOR][] = (int) $default['CREATED_BY'];
		}

		if (isset($data['RESPONSIBLE_ID']))
		{
			$members[RoleDictionary::ROLE_RESPONSIBLE][] = (int) $data['RESPONSIBLE_ID'];
		}
		elseif (isset($default['RESPONSIBLE_ID']))
		{
			$members[RoleDictionary::ROLE_RESPONSIBLE][] = (int) $default['RESPONSIBLE_ID'];
		}

		$accomplices = [];
		if (isset($data['ACCOMPLICES']))
		{
			if (is_scalar($data['ACCOMPLICES']))
			{
				$data['ACCOMPLICES'] = [$data['ACCOMPLICES']];
			}
			$accomplices = $data['ACCOMPLICES'];
		}
		elseif (isset($default['ACCOMPLICES']))
		{
			if (is_scalar($default['ACCOMPLICES']))
			{
				$default['ACCOMPLICES'] = [$default['ACCOMPLICES']];
			}
			$accomplices = $default['ACCOMPLICES'];
		}
		foreach ($accomplices as $member)
		{
			$members[RoleDictionary::ROLE_ACCOMPLICE][] = (int) $member;
		}

		$auditors = [];
		if (isset($data['AUDITORS']) && is_array($data['AUDITORS']))
		{
			$auditors = $data['AUDITORS'];
		}
		elseif (isset($default['AUDITORS']) && is_array($default['AUDITORS']))
		{
			$auditors = $default['AUDITORS'];
		}
		foreach ($auditors as $member)
		{
			$members[RoleDictionary::ROLE_AUDITOR][] = (int) $member;
		}

		$model->setMembers($members);

		if (!empty($data['FLOW_ID']) && (int)$data['FLOW_ID'] > 0)
		{
			$model->setFlowId((int)$data['FLOW_ID']);
		}

		return $model;
	}

	private function __construct()
	{
	}

	/**
	 * @param string|null $role
	 * @return array
	 */
	public function getMembers(string $role = null): array
	{
		if ($this->members === null)
		{
			$this->members = [];
			if (!$this->id)
			{
				return $this->members;
			}

			$task = $this->getTask(true);

			if (!$task)
			{
				return [];
			}

			foreach ($task['MEMBER_LIST'] as $member)
			{
				$this->members[$member['TYPE']][] = $member['USER_ID'];
			}
		}

		if (!$role)
		{
			return $this->members;
		}

		if (array_key_exists($role, $this->members))
		{
			return $this->members[$role];
		}

		return [];
	}

	/**
	 * @return int
	 */
	public function getGroupId(): int
	{
		if ($this->groupId === null)
		{
			$this->groupId = 0;

			$task = $this->getTask();
			if ($task)
			{
				$this->groupId = (int) $task['GROUP_ID'];
			}
		}
		return $this->groupId;
	}

	/**
	 * @return int
	 */
	public function getFlowId(): int
	{
		if ($this->flowId === null)
		{
			$this->flowId = 0;

			if ($this->id)
			{
				$this->flowId = $this->getTask(true)['FLOW_ID'] ?? 0;
			}
		}

		return $this->flowId;
	}

	/**
	 * @return array|null
	 */
	public function getGroup(): ?array
	{
		$groupId = $this->getGroupId();
		if (!$groupId)
		{
			return null;
		}

		if ($this->group)
		{
			return $this->group;
		}

		$task = $this->getTask();
		if ($task && (int) $task['GROUP_ID'] === $groupId)
		{
			$this->group = $task['GROUP_INFO'];
		}
		else
		{
			$this->group = GroupRegistry::getInstance()->get($groupId);
		}

		return $this->group;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @param array $members
	 * @return $this
	 */
	public function setMembers(array $members): self
	{
		$this->members = $members;
		return $this;
	}

	/**
	 * @param int $groupId
	 * @return $this
	 */
	public function setGroupId(int $groupId): self
	{
		$this->groupId = $groupId;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 */
	public function setStatus(int $value): self
	{
		$this->status = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 */
	public function setFlowId(int $value): self
	{
		$this->flowId = $value;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isClosed(): bool
	{
		$status = $this->getStatus();
		return $status === Status::COMPLETED;
	}

	/**
	 * @return bool
	 */
	public function isDeleted(): bool
	{
		$task = $this->getTask();
		if (!$task)
		{
			return false;
		}
		return $task['ZOMBIE'] === 'Y';
	}

	/**
	 * @return int|null
	 */
	public function getStatus(): ?int
	{
		if (!is_null($this->status))
		{
			return $this->status;
		}

		$task = $this->getTask();
		if (!$task)
		{
			return 0;
		}
		return (int) $task['STATUS'];
	}

	/**
	 * @return bool
	 */
	public function isAllowedChangeDeadline(): bool
	{
		$task = $this->getTask();
		if (!$task)
		{
			return false;
		}
		return $task['ALLOW_CHANGE_DEADLINE'] === 'Y';
	}

	/**
	 * @return bool
	 */
	public function isAllowedTimeTracking(): bool
	{
		$task = $this->getTask();
		if (!$task)
		{
			return false;
		}
		return $task['ALLOW_TIME_TRACKING'] === 'Y';
	}

	/**
	 * @param int $userId
	 * @param string|null $role
	 * @return bool
	 */
	public function isMember(int $userId, string $role = null): bool
	{
		$roles = $this->getUserRoles($userId);
		if (!$role)
		{
			return !empty($roles);
		}
		return in_array($role, $roles);
	}

	/**
	 * @param int $userId
	 * @return array
	 */
	public function getUserRoles(int $userId): array
	{
		$roles = [];
		if (!$userId)
		{
			return $roles;
		}
		foreach ($this->getMembers() as $role => $members)
		{
			if (in_array($userId, $members))
			{
				$roles[] = $role;
			}
		}

		return $roles;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getChecklist(): array
	{
		if (!$this->id)
		{
			return [];
		}
		return TaskCheckListFacade::getByEntityId($this->id);
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function isFavorite(int $userId): bool
	{
		$task = $this->getTask(true);
		if (!$task)
		{
			return false;
		}
		return in_array($userId, $task['IN_FAVORITES']);
	}

	/**
	 * @param int $userId
	 * @param bool $recursive
	 * @param array $roles
	 * @return bool
	 */
	public function isInDepartment(int $userId, bool $recursive = false, array $roles = []): bool
	{
		$userDepartments = \CIntranetUtils::GetUserDepartments($userId);
		if (!is_array($userDepartments))
		{
			return false;
		}
		return !empty(array_intersect($userDepartments, $this->getDepartments($roles)));
	}

	/**
	 * @param bool $withRelations
	 * @return array|null
	 */
	private function getTask(bool $withRelations = false): ?array
	{
		if (!$this->id)
		{
			return null;
		}
		return TaskRegistry::getInstance()->get($this->id, $withRelations);
	}

	/**
	 * @param array $roles
	 * @return array
	 */
	private function getDepartments(array $roles = []): array
	{
		$task = $this->getTask(true);
		if (!$task)
		{
			return [];
		}

		$res = [];
		foreach ($task['DEPARTMENTS'] as $role => $deps)
		{
			if (!in_array($role, $roles))
			{
				continue;
			}
			$res = array_merge($res, $deps);
		}

		return array_unique($res);
	}
}