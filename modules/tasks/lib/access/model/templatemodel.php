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
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\AccessibleTask;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Internals\Task\TemplateTable;

class TemplateModel
	implements AccessibleTask
{
	use DepartmentTrait;

	public const ROLE_OWNER = 'OWNER';

	private $id;
	private $members;

	private $permissions;

	public static function createNew(): self
	{
		$model = new self();
		return $model;
	}

	public static function createFromId(int $id): AccessibleItem
	{
		$model = new self();
		$model->setId($id);
		return $model;
	}

	private function __construct()
	{
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

			$responsibles = unserialize($members['RESPONSIBLES']);

			$this->members[RoleDictionary::ROLE_DIRECTOR] 		= [$members['CREATED_BY']];
			$this->members[RoleDictionary::ROLE_RESPONSIBLE] 	= !empty($responsibles) ? $responsibles : [$members['RESPONSIBLE_ID']];
			$this->members[RoleDictionary::ROLE_ACCOMPLICE] 	= unserialize($members['ACCOMPLICES']);
			$this->members[RoleDictionary::ROLE_AUDITOR] 		= unserialize($members['AUDITORS']);
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

	public function getGroupId(): int
	{
		return 0;
	}

	public function isClosed(): bool
	{
		return false;
	}

	public function getStatus(): ?int
	{
		return null;
	}

	public function getChecklist()
	{
		if (!$this->id)
		{
			return [];
		}
		return TemplateCheckListFacade::getByEntityId($this->id);
	}

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
}