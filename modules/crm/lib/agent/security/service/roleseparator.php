<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\RoleRelationTable;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Security\Role\Utils\RolePermissionChecker;

abstract class RoleSeparator
{
	protected const SUPPORT_PERMS = ['HIDE_SUM', 'MYCARDVIEW', 'TRANSITION'];
	/** @var PermissionExtender[] $extenders */
	protected array $extenders = [];

	final public function separate(EO_Role &$role): RoleSeparateResult
	{
		$originalRole = clone $role;
		$copy = $this->copy($role);

		$result = new RoleSeparateResult($copy);

		$transmitter = $this->getPermissionTransmitter($originalRole, $copy);

		$permissions  = $role->getPermissions()?->getAll() ?? [];
		foreach ($permissions as $permission)
		{
			if ($this->isPossibleToTransmit($permission) && $role->getPermissions()?->has($permission))
			{
				if (RolePermissionChecker::isPermissionEmpty(PermissionModel::createFromEntityObject($permission)))
				{
					$result->addPermissionToRemove($permission);
				}
				else
				{
					$transmitter->transmit($permission);
				}
			}
		}

		$this->expandPermissions($copy, $originalRole);

		if ($this->containsOnlySupportPermissions($copy))
		{
			foreach ($copy->getPermissions()?->getAll() ?? [] as $permission) // if role contains only not important permissions, they should be removed to disable influence of this role to separated entity
			{
				if ($this->isPossibleToTransmit($permission))
				{
					$result->addPermissionToRemove($permission);
				}
			}

			$role = $originalRole;
			$result->setSeparatedRole(null);

			return $result;
		}

		return $result;
	}

	public function expandBy(PermissionExtender $extender): static
	{
		$this->extenders[] = $extender;

		return $this;
	}

	protected function copy(EO_Role $role): EO_Role
	{
		$copy = RoleTable::createObject(false)
			->setName($role->getName())
			->setGroupCode($this->generateGroupCode())
			->setIsSystem('N')
		;

		$relations = $role->getRelations()?->getAll() ?? [];
		foreach ($relations as $relation)
		{
			$copy->addToRelations(
				RoleRelationTable::createObject()
					->setRelation($relation->getRelation())
					->setRole($copy)
				,
			);
		}

		return $copy;
	}

	protected function containsOnlySupportPermissions(EO_Role $role): bool
	{
		$permissionTypes = $role->getPermissions()?->getPermTypeList() ?? [];
		$diff = array_diff($permissionTypes, self::SUPPORT_PERMS);

		return empty($diff);
	}

	protected function expandPermissions(EO_Role $copy, EO_Role $originalRole): void
	{
		foreach ($this->extenders as $extender)
		{
			$extender->expand($copy, $originalRole);
		}
	}

	protected function getPermissionTransmitter(EO_Role $originalRole, EO_Role $copyRole): PermissionTransmitter
	{
		return new PermissionTransmitter\Move($originalRole, $copyRole);
	}

	abstract protected function isPossibleToTransmit(EO_RolePermission $permission): bool;

	abstract protected function generateGroupCode(): string;
}
