<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model\Member;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Main\UserAccessTable;
use Bitrix\Tasks\Access\Permission\TasksPermissionTable;
use Bitrix\Tasks\Access\Role\TasksRoleRelationTable;
use Bitrix\Tasks\Access\AccessibleTask;

abstract class BaseList
	implements MemberListInterface
{
	/* @var AccessibleUser $user */
	protected $user;
	/* @var \Bitrix\Tasks\Access\AccessibleTask $task */
	protected $task;

	private $departmentMembers;

	public function __construct(AccessibleUser $user, AccessibleTask $task)
	{
		$this->user = $user;
		$this->task = $task;
	}

	abstract public function getAccesibleUsers(): ?array;
	abstract public function getHasRightUsers(): ?array;

	protected function getDepartmentMembers(): array
	{
		if ($this->departmentMembers === null)
		{
			$departments = $this->user->getUserDepartments();
			$res = \Bitrix\Intranet\Util::getDepartmentEmployees(array(
				'DEPARTMENTS' 	=> $departments,
				'RECURSIVE' 	=> 'N',
				'ACTIVE' 		=> 'Y',
				'SKIP' 			=> [],
				'SELECT' 		=> null
			));

			$this->departmentMembers = [];
			while ($row = $res->GetNext())
			{
				$departmentMembers[] = $row['ID'];
			}
		}

		return $this->departmentMembers;
	}
	protected function getDepartmentManager(): array
	{
		$userDepartments = $this->user->getUserDepartments();

		$departmentManagers = [];
		foreach ($userDepartments as $department)
		{
			$departmentManagers[] = \CIntranetUtils::GetDepartmentManagerID($department);
		}
		return $departmentManagers;
	}
	protected function getNonDepartmentManager(): array
	{
		$userDepartments = $this->user->getUserDepartments();
		$structure = \CIntranetUtils::GetStructure();

		$managers = [];
		foreach ($structure['DATA'] as $departmentId => $department)
		{
			if (!in_array($departmentId, $userDepartments) && $department['UF_HEAD'] > 0)
			{
				$managers[] = $department['UF_HEAD'];
			}
		}

		return $managers;
	}
	protected function getNonDepartmentMembers(): array
	{
		$departmentMembers = $this->getDepartmentMembers();
		$departmentMembers[] = $this->user->getUserId();

		$res = \CUser::getList(
			$by = null, $order = null,
			[
				'ACTIVE' => 'Y'
			],
			[
				'FIELDS' => ['ID']
			]
		);

		$users = [];
		while ($row = $res->GetNext())
		{
			if (!in_array($row['ID'], $departmentMembers))
			{
				$users[] = $row['ID'];
			}
		}

		return $users;
	}

	protected function getHasPermissionUsers(string $permission): array
	{
		$roles = $this->getHasRightRoles($permission);
		if (empty($roles))
		{
			return [];
		}

		$accessCodes = $this->getHasRightAccessCodes($roles);
		if (empty($accessCodes))
		{
			return [];
		}

		$res = UserAccessTable::query()
			->addSelect("USER_ID")
			->whereIn("ACCESS_CODE", $accessCodes)
			->exec()
			->fetchAll();

		$userIds = [];
		foreach ($res as $row)
		{
			$userIds[] = $row["USER_ID"];
		}

		return array_unique($userIds);
	}
	protected function getHasRightAccessCodes(array $roles): array
	{
		$res = TasksRoleRelationTable::query()
			->addSelect("RELATION")
			->whereIn("ROLE_ID", $roles)
			->exec()
			->fetchAll();

		$ac = [];
		foreach ($res as $row)
		{
			$ac[] = $row['RELATION'];
		}
		return $ac;
	}
	protected function getHasRightRoles(string $permission): array
	{
		$res = TasksPermissionTable::query()
			->addSelect("ROLE_ID")
			->where("PERMISSION_ID", $permission)
			->where('VALUE', '>', 0)
			->exec()
			->fetchAll();

		$roles = [];
		foreach ($res as $row)
		{
			$roles[] = $row['ROLE_ID'];
		}

		return $roles;
	}
}