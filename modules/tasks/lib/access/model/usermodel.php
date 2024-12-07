<?php

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Permission\PermissionRegistry;
use Bitrix\Tasks\Access\Role\TasksRoleRelationTable;
use Bitrix\Tasks\Integration\Intranet;

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

class UserModel extends \Bitrix\Main\Access\User\UserModel
	implements AccessibleUser
{
	private
		$permissions,
		$allSubordinates;

	private
		$isEmail,
		$isExtranet;

	public function getAllSubordinates(): array
	{
		if ($this->allSubordinates === null)
		{
			$this->allSubordinates = [];
			if ($this->userId === 0)
			{
				return $this->allSubordinates;
			}

			$this->allSubordinates = Intranet\User::getSubordinate($this->userId, null, true, true);
		}
		return $this->allSubordinates;
	}

	public function getRoles(): array
	{
		if ($this->roles === null)
		{
			$this->roles = [];
			if ($this->userId === 0 || empty($this->getAccessCodes()))
			{
				return $this->roles;
			}

			$res = TasksRoleRelationTable::query()
				->addSelect('ROLE_ID')
				->whereIn('RELATION', $this->getAccessCodes())
				->exec();
			foreach ($res as $row)
			{
				$this->roles[] = (int) $row['ROLE_ID'];
			}
		}
		return $this->roles;
	}

	public function getPermission(string $permissionId): ?int
	{
		$permissions = $this->getPermissions();
		if (in_array($permissionId, $permissions))
		{
			return PermissionDictionary::VALUE_YES;
		}

		return PermissionDictionary::VALUE_NO;
	}

	public function isEmail(): bool
	{
		if ($this->isEmail === null)
		{
			$this->isEmail = Intranet\User::isEmail($this->userId);
		}
		return $this->isEmail;
	}

	public function isExtranet()
	{
		if ($this->isExtranet === null)
		{
			$this->isExtranet = !Intranet\User::isIntranet($this->userId);
		}
		return $this->isExtranet;
	}

	private function getPermissions(): array
	{
		if (is_array($this->permissions))
		{
			return $this->permissions;
		}

		$roles = $this->getRoles();
		$this->permissions = PermissionRegistry::getInstance()->getPermissions($roles);

		return $this->permissions;
	}
}