<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model;


use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\AccessibleTask;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Internals\Task\TemplateTable;

class TemplateModel
	implements AccessibleTask
{
	private static $cache = [];

	public const ROLE_OWNER = 'OWNER';

	private $id;
	private $members;

	private $permissions;

	private $template;


	/**
	 * @param int $templateId
	 */
	public static function invalidateCache(int $templateId)
	{
		unset(static::$cache[$templateId]);
	}

	/**
	 * @return static
	 */
	public static function createNew(): self
	{
		$model = new self();
		return $model;
	}

	/**
	 * @param int $id
	 * @return AccessibleItem
	 */
	public static function createFromId(int $id): AccessibleItem
	{
		if (!array_key_exists($id, static::$cache))
		{
			$model = new self();
			$model->setId($id);
			static::$cache[$id] = $model;
		}

		return static::$cache[$id];
	}

	private function __construct()
	{
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
	 * @param string|null $role
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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

			$members = TemplateTable::query()
				->addSelect('CREATED_BY')
				->addSelect('RESPONSIBLE_ID')
				->addSelect('RESPONSIBLES')
				->addSelect('ACCOMPLICES')
				->addSelect('AUDITORS')
				->where('ID', $this->id)
				->exec()
				->fetch();

			if (!$members)
            {
                return $this->members;
            }

			$responsibles = unserialize($members['RESPONSIBLES'], ['allowed_classes' => false]);

			$this->members[RoleDictionary::ROLE_DIRECTOR] 		= [$members['CREATED_BY']];
			$this->members[RoleDictionary::ROLE_RESPONSIBLE] 	= !empty($responsibles) ? $responsibles : [$members['RESPONSIBLE_ID']];
			$this->members[RoleDictionary::ROLE_ACCOMPLICE] 	= unserialize($members['ACCOMPLICES'], ['allowed_classes' => false]);
			$this->members[RoleDictionary::ROLE_AUDITOR] 		= unserialize($members['AUDITORS'], ['allowed_classes' => false]);
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
			if (
				(
					is_array($members)
					&& in_array($userId, $members)
				)
				|| $userId === $members
			)
			{
				$roles[] = $role;
			}
		}

		return $roles;
	}

	/**
	 * @param AccessibleUser $user
	 * @param $permissionId
	 * @return int
	 */
	public function getTemplatePermission(AccessibleUser $user, $permissionId): int
	{
		$permissions = $this->getTemplatePermissions();

		$value = 0;
		foreach ($user->getAccessCodes() as $ac)
		{
			if (!array_key_exists($ac, $permissions))
			{
				continue;
			}
			$value = ($permissions[$ac][$permissionId] > $value) ? $permissions[$ac][$permissionId] : $value;
		}

		return $value;
	}

	/**
	 * @return int
	 */
	public function getGroupId(): int
	{
		return 0;
	}

	/**
	 * @return bool
	 */
	public function isClosed(): bool
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isDeleted(): bool
	{
		$template = $this->loadTemplate();
		return $template['ZOMBIE'] === 'Y';
	}

	/**
	 * @return int|null
	 */
	public function getStatus(): ?int
	{
		return null;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getChecklist()
	{
		if (!$this->id)
		{
			return [];
		}
		return TemplateCheckListFacade::getByEntityId($this->id);
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
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getTemplatePermissions(): array
	{
		if ($this->permissions === null)
		{
			$this->permissions = [];

			$res = TasksTemplatePermissionTable::query()
				->addSelect('ACCESS_CODE')
				->addSelect('PERMISSION_ID')
				->addSelect('VALUE')
				->where('TEMPLATE_ID', $this->id)
				->exec()
				->fetchAll();

			foreach ($res as $row)
			{
				$this->permissions[$row['ACCESS_CODE']][$row['PERMISSION_ID']] = (int) $row['VALUE'];
			}

		}
		return $this->permissions;
	}

	/**
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function loadTemplate(): ?array
	{
		if (!$this->id)
		{
			return null;
		}
		if ($this->template === null)
		{
			$res = \Bitrix\Tasks\Internals\Task\TemplateTable::query()
				->addSelect('ID')
				->addSelect('ZOMBIE')
				->where('ID', $this->id)
				->exec()
				->fetch();

			if ($res)
			{
				$this->template = $res;
			}
		}
		return $this->template;
	}

	/**
	 * @param array $roles
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getDepartments(array $roles = []): array
	{
		$key = 'DEP_' . static::class . '_' . $this->getId() . '_' . implode(',', $roles);

		if (!array_key_exists($key, static::$cache))
		{
			$members = $this->getMembers();

			$userIds = [];

			foreach ($members as $role => $ids)
			{
				if (
					empty($roles)
					|| in_array($role, $roles)
				)
				{
					$userIds = array_merge($userIds, $ids);
				}
			}

			static::$cache[$key] = [];
			if (!empty($userIds))
			{
				$userIds = implode(',', $userIds);

				$res = \Bitrix\Tasks\Util\User::getList(
					[
						'filter' => [
							'@ID' => new SqlExpression($userIds),
						],
						'select' => ['ID', 'UF_DEPARTMENT']
					]
				);

				foreach ($res as $row)
				{
					if (is_array($row['UF_DEPARTMENT']) && !empty($row['UF_DEPARTMENT']))
					{
						static::$cache[$key] = array_merge(static::$cache[$key], $row['UF_DEPARTMENT']);
					}
				}
			}
		}
		return static::$cache[$key];
	}
}