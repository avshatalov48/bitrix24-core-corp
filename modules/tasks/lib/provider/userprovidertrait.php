<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Provider;


use Bitrix\Intranet\Util;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Permission\PermissionRegistry;

trait UserProviderTrait
{
	private ?int $userId = null;
	private ?int $executorId = null;
	private ?AccessibleUser $userModel = null;
	private ?array $departmentMembers = null;
	private ?array $roles = null;
	private ?array $permissions = null;

	private function getUserModel(): AccessibleUser
	{
		if (!$this->userModel)
		{
			$this->userModel = UserModel::createFromId($this->executorId);
		}

		return $this->userModel;
	}

	private function getPermissions(): array
	{
		if (is_array($this->permissions))
		{
			return $this->permissions;
		}

		$roles = $this->getUserRoles();

		$this->permissions = PermissionRegistry::getInstance()->getPermissions($roles);

		return $this->permissions;
	}

	private function getUserRoles(): array
	{
		if ($this->roles === null)
		{
			$this->roles = $this->getUserModel()->getRoles();
		}

		return $this->roles;
	}

	private function getDepartmentMembers(): array
	{
		if ($this->departmentMembers === null)
		{
			$departments = $this->getUserModel()->getUserDepartments();
			$res = Util::getDepartmentEmployees([
				'DEPARTMENTS' 	=> $departments,
				'RECURSIVE' 	=> 'N',
				'ACTIVE' 		=> 'Y',
				'SKIP' 			=> [],
				'SELECT' 		=> null
			]);

			$this->departmentMembers = [];
			while ($row = $res->GetNext())
			{
				$this->departmentMembers[] = (int) $row['ID'];
			}
		}

		return $this->departmentMembers;
	}
}