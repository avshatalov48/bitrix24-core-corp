<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Provider;


use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Permission\TasksPermissionTable;

trait UserProviderTrait
{
	private $userId;
	private $executorId;
	private $userModel;
	private $departmentMembers;
	private $roles;
	private $permissions;

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
		if ($this->permissions === null)
		{
			$roles = $this->getUserRoles();
			if (empty($roles))
			{
				return [];
			}

			$res = TasksPermissionTable::getList([
				'select' => ['PERMISSION_ID'],
				'filter' => [
					'@ROLE_ID' => $roles,
					'=VALUE' => PermissionDictionary::VALUE_YES
				]
			]);

			$this->permissions = [];
			foreach ($res as $row)
			{
				$this->permissions[$row['PERMISSION_ID']] = $row['PERMISSION_ID'];
			}
		}

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
			$res = \Bitrix\Intranet\Util::getDepartmentEmployees([
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