<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Internals\Task\FavoriteTable;

class TaskModel
	implements \Bitrix\Tasks\Access\AccessibleTask
{
	use DepartmentTrait;

	private const
		CACHE_MODEL_KEY = 'model',
		CACHE_TASK_KEY = 'task';

	private static $cache = [];

	private
		$id = 0,
		$members,
		$groupId,
		$status,
		$group;

	private $task;

	public static function invalidateCache(int $taskId)
	{
		unset(static::$cache[$taskId]);
	}

	/**
	 * @param array $ids
	 * @param int $userId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function preloadModels(array $ids, int $userId = 0)
	{
		$res = \Bitrix\Tasks\Internals\TaskTable::query()
			->addSelect('ID')
			->addSelect('GROUP_ID')
			->addSelect('STATUS')
			->addSelect('ALLOW_CHANGE_DEADLINE')
			->addSelect('ALLOW_TIME_TRACKING')
			->addSelect('ZOMBIE')
			->whereIn('ID', $ids)
			->exec();

		while ($row = $res->fetch())
		{
			static::$cache[$row['ID']][self::CACHE_TASK_KEY] = $row;
		}

		if (!$userId)
		{
			return;
		}

		$res = FavoriteTable::getList([
			'select' => ['TASK_ID'],
			'filter' => [
				'=USER_ID' => $userId,
				'@TASK_ID' => $ids
			]
		]);
		$favorites = array_column($res->fetchAll(), 'TASK_ID');

		foreach (static::$cache as $taskId => $data)
		{
			static::$cache[$taskId][self::CACHE_TASK_KEY]['FAVORITES'][$userId] = false;
			if (in_array($taskId, $favorites))
			{
				static::$cache[$taskId][self::CACHE_TASK_KEY]['FAVORITES'][$userId] = true;
			}
		}
	}

	public static function createNew(int $groupId = 0): self
	{
		$model = new self();
		$model
			->setGroupId($groupId)
			->setStatus(\CTasks::STATE_PENDING);
		return $model;
	}

	public static function createFromId(int $taskId): AccessibleItem
	{
		if (!isset(static::$cache[$taskId][self::CACHE_MODEL_KEY]))
		{
			$model = new self();
			$model->setId($taskId);
			static::$cache[$taskId][self::CACHE_MODEL_KEY] = $model;
		}

		return static::$cache[$taskId][self::CACHE_MODEL_KEY];
	}

	public static function createFromTaskItem(\Bitrix\Tasks\Item\Task $item)
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


		return $model;
	}

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
			if (is_scalar($data['AUDITORS']))
			{
				$data['AUDITORS'] = [$data['AUDITORS']];
			}
			$auditors = $data['AUDITORS'];
		}
		elseif (isset($default['AUDITORS']) && is_array($default['AUDITORS']))
		{
			if (is_scalar($default['AUDITORS']))
			{
				$default['AUDITORS'] = [$default['AUDITORS']];
			}
			$auditors = $default['AUDITORS'];
		}
		foreach ($auditors as $member)
		{
			$members[RoleDictionary::ROLE_AUDITOR][] = (int) $member;
		}

		$model->setMembers($members);

		return $model;
	}

	private function __construct()
	{
	}

	public function getMembers(string $role = null): array
	{
		if ($this->members === null)
		{
			$this->members = [];
			if (!$this->id)
			{
				return $this->members;
			}

			$members = \Bitrix\Tasks\Internals\Task\MemberTable::query()
				->addSelect('USER_ID')
				->addSelect('TYPE')
				->where('TASK_ID', $this->id)
				->exec()
				->fetchAll();

			foreach ($members as $member)
			{
				$this->members[$member['TYPE']][] = (int) $member['USER_ID'];
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

	public function getGroupId(): int
	{
		if ($this->groupId === null)
		{
			$this->groupId = 0;

			$res = $this->loadTask();
			if (!empty($res))
			{
				$this->groupId = (int) $res['GROUP_ID'];
			}
		}
		return $this->groupId;
	}

	public function getGroup(): ?GroupModel
	{
		$groupId = $this->getGroupId();
		if (!$groupId)
		{
			return null;
		}

		if (!$this->group)
		{
			$this->group = GroupModel::createFromId($groupId);
		}
		return $this->group;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	public function setMembers(array $members): self
	{
		$this->members = $members;
		return $this;
	}

	public function setGroupId(int $groupId): self
	{
		$this->groupId = $groupId;
		return $this;
	}

	public function setStatus(int $value): self
	{
		$this->status = $value;
		return $this;
	}

	public function isClosed(): bool
	{
		$status = $this->getStatus();
		return $status === \CTasks::STATE_COMPLETED;
	}

	public function isDeleted(): bool
	{
		$task = $this->loadTask();
		return $task['ZOMBIE'] === 'Y';
	}

	public function getStatus(): ?int
	{
		$task = $this->loadTask();
		return (int) $task['STATUS'];
	}

	public function isAllowedChangeDeadline()
	{
		$task = $this->loadTask();
		return $task['ALLOW_CHANGE_DEADLINE'] === 'Y';
	}

	public function isAllowedTimeTracking()
	{
		$task = $this->loadTask();
		return $task['ALLOW_TIME_TRACKING'] === 'Y';
	}

	public function isMember(int $userId, string $role = null): bool
	{
		$roles = $this->getUserRoles($userId);
		if (!$role)
		{
			return !empty($roles);
		}
		return in_array($role, $roles);
	}

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

	public function getChecklist()
	{
		if (!$this->id)
		{
			return [];
		}
		return TaskCheckListFacade::getByEntityId($this->id);
	}

	public function isFavorite(int $userId): bool
	{
		if (
			!isset(static::$cache[$this->id][self::CACHE_TASK_KEY]['FAVORITES'])
			|| !array_key_exists($userId, static::$cache[$this->id][self::CACHE_TASK_KEY]['FAVORITES'])
		)
		{
			static::$cache[$this->id][self::CACHE_TASK_KEY]['FAVORITES'][$userId] = (bool) FavoriteTable::check([
				'TASK_ID' => $this->id,
				'USER_ID' => $userId
			]);
		}

		return static::$cache[$this->id][self::CACHE_TASK_KEY]['FAVORITES'][$userId];
	}

	private function loadTask(): ?array
	{
		if (!$this->id)
		{
			return null;
		}
		if (!isset(static::$cache[$this->id][self::CACHE_TASK_KEY]))
		{
			$res = \Bitrix\Tasks\Internals\TaskTable::query()
				->addSelect('ID')
				->addSelect('GROUP_ID')
				->addSelect('STATUS')
				->addSelect('ALLOW_CHANGE_DEADLINE')
				->addSelect('ALLOW_TIME_TRACKING')
				->addSelect('ZOMBIE')
				->where('ID', $this->id)
				->exec()
				->fetch();

			if ($res)
            {
				static::$cache[$this->id][self::CACHE_TASK_KEY] = $res;
            }
		}
		return static::$cache[$this->id][self::CACHE_TASK_KEY];
	}
}