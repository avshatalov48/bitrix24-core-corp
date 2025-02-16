<?php

namespace Bitrix\Crm\Security\Role\Repositories;

use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Manage\DTO\Restrictions;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\RoleRelationTable;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use CCrmRole;

class PermissionRepository
{
	use Singleton;

	public function getRole(int $roleId): ?array
	{
		$roles = CCrmRole::GetList(array(), array('ID' => $roleId));
		$roleRow = $roles->Fetch();

		if ($roleRow)
		{
			return $roleRow;
		}

		return null;
	}

	/**
	 * @param bool $excludeSystemRoles
	 * @return array
	 */
	public function getAllRoles(bool $excludeSystemRoles = true): array
	{
		$query = RoleTable::query()
			->setSelect(['ID', 'NAME', 'IS_SYSTEM', 'CODE', 'GROUP_CODE'])
		;

		if ($excludeSystemRoles)
		{
			$query->where('IS_SYSTEM', 'N');
		}

		return $query->fetchAll();
	}

	public function getRoleAssignedPermissions(int $roleId): array
	{
		$ct = new ConditionTree();
		$ct->logic(ConditionTree::LOGIC_OR);
		$ct->where('ATTR', '<>', '');
		$ct->where('FIELD_VALUE', '<>', '');
		$ct->where('SETTINGS', '<>', '');


		$query = RolePermissionTable::query()
			->setSelect(['ENTITY', 'PERM_TYPE', 'FIELD', 'FIELD_VALUE', 'ATTR', 'SETTINGS'])
			->where('ROLE_ID', $roleId)
			->where($ct)
		;

		$rows = $query->fetchAll();
		$result = [];
		foreach ($rows as $row)
		{
			$row['ATTR'] = trim($row['ATTR']); // In Postgres, if the ATTR field is empty, then a space is returned
			$result[] = $row;
		}

		return $result;
	}

	/**
	 * @param EntityDTO[] $permissionEntities
	 * @return array
	 */
	public function getDefaultRoleAssignedPermissions(array $permissionEntities): array
	{
		$result = [];

		foreach ($permissionEntities as $entity)
		{
			foreach ($entity->permissions() as $permission)
			{
				$attr = $permission->getDefaultAttribute();
				$settings = $permission->getDefaultSettings();
				if ($attr === null && empty($settings))
				{
					continue;
				}

				$result[] = [
					'ENTITY' => $entity->code(),
					'PERM_TYPE' => $permission->code(),
					'FIELD' => '-',
					'FIELD_VALUE' => null,
					'ATTR' => trim($attr),
					'SETTINGS' => $settings,
				];
			}
		}

		return $result;
	}

	public function getTariffRestrictions(): Restrictions
	{
		$restriction = RestrictionManager::getPermissionControlRestriction();

		$hasPermission = $restriction->hasPermission();

		return new Restrictions(
			$hasPermission,
			$hasPermission ? null : $restriction->prepareInfoHelperScript(),
			$hasPermission ? null : $restriction->getInfoHelperId(),
		);
	}

	/**
	 * @param int $roleId
	 * @param PermissionModel[] $removedPerms
	 * @param PermissionModel[] $changedPerms
	 * @return Result
	 */
	public function applyRolePermissionData(int $roleId, array $removedPerms, array $changedPerms): Result
	{
		$result = new Result();
		$connection = Application::getConnection();

		$connection->startTransaction();

		try {
			RolePermissionTable::removePermissions($roleId, $removedPerms);
			RolePermissionTable::appendPermissions($roleId, $changedPerms);

			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	/**
	 * @param int[] $roleIds
	 * @return array
	 */
	public function queryActualPermsByRoleIds(array $roleIds): array
	{
		$query = RolePermissionTable::query()
			->setSelect(['ROLE_ID', 'ENTITY', 'FIELD', 'FIELD_VALUE', 'PERM_TYPE', 'ATTR', 'SETTINGS'])
			->whereIn('ROLE_ID', $roleIds)
		;

		return $query->fetchAll();
	}

	/**
	 * @param int[] $roleIds
	 * @return array
	 */
	public function queryRolesRelations(array $roleIds): array
	{
		return RoleRelationTable::query()
			->setSelect(['ROLE_ID', 'RELATION'])
			->whereIn('ROLE_ID', $roleIds)
			->fetchAll();
	}

	public function deleteRole(int $roleId): Result
	{
		$CCrmRole = new CCrmRole();
		$CCrmRole->Delete($roleId);

		return new Result();
	}

	public function addRole(string $name, ?string $groupCode = null): AddResult
	{
		return RoleTable::add(['NAME' => $name, 'GROUP_CODE' => $groupCode]);
	}

	public function saveRolesRelations(array $perms): void
	{
		$CCrmRole = new CcrmRole();
		$CCrmRole->SetRelation($perms);
	}

	public function saveRoleRelations(int $roleId, array $relations): void
	{
		$existedRelations = RoleRelationTable::query()
			->where('ROLE_ID', $roleId)
			->setSelect(['RELATION'])
			->fetchCollection()->getRelationList();

		sort($existedRelations);
		sort($relations);
		if ($existedRelations !== $relations)
		{
			RoleRelationTable::updateForRole($roleId, $relations);
		}
	}

	public function updateRole(int $id, string $name): void
	{
		$existedRole = RoleTable::query()
			->setSelect(['ID', 'NAME'])
			->where('ID', $id)
			->fetch()
		;
		if (!$existedRole || $existedRole['NAME'] === $name)
		{
			return;
		}

		RoleTable::update($id, ['NAME' => $name]);
	}

	public function updateOrCreateRole(int $id, string $name, ?string $groupCode = null): int
	{
		if ($id === 0)
		{
			$addResult = $this->addRole($name, $groupCode);

			return $addResult->getId();
		}

		$this->updateRole($id, $name);

		return $id;
	}
}
