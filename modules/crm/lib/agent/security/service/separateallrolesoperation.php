<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_Role_Collection;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Security\Role\Repositories\PermissionRepository;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;

final class SeparateAllRolesOperation
{
	/**
	 * @param RoleSeparator[] $separators
	 * @return bool
	 */
	public function run(array $separators): bool
	{
		if (empty($separators))
		{
			return false;
		}

		$roleCollection = $this->findAllRoles();
		if ($roleCollection->isEmpty())
		{
			return false;
		}

		RolePermissionLogContext::getInstance()->set([
			'scenario' => 'separate roles agent',
		]);

		$roleCollectionSeparator = new RoleCollectionSeparator($roleCollection, $separators);
		$separateResult = $roleCollectionSeparator->separate();

		if (!$separateResult->getSeparatedRoles()->isEmpty())
		{
			array_map(static fn (EO_Role $role): Result => $role->save(), $separateResult->getSeparatedRoles()->getAll());
			$this->deleteEmptyRoles($separateResult->getChangedRoles());
		}
		RolePermissionLogContext::getInstance()->clear();

		if (!$separateResult->getPermissionsToRemove()->isEmpty())
		{
			$this->deleteEmptyPermissions($separateResult->getPermissionsToRemove());
		}

		\CCrmRole::ClearCache();

		return false;
	}

	private function findAllRoles(): EO_Role_Collection
	{
		/** @throws ArgumentException */
		$nullOrEmpty = static function (string $fieldName): ConditionTree {
			return (new ConditionTree())
				->logic(ConditionTree::LOGIC_OR)
				->whereNull($fieldName)
				->where($fieldName, '');
		};

		$roleCollection = RoleTable::query()
			->setSelect(['*', 'PERMISSIONS'])
			->where('IS_SYSTEM', 'N')
			->where($nullOrEmpty('GROUP_CODE'))
			->where($nullOrEmpty('CODE'))
			->fetchCollection();

		$roleCollection->fillRelations();

		return $roleCollection;
	}

	private function deleteEmptyRoles(EO_Role_Collection $changedRoles): void
	{
		foreach ($changedRoles as $role)
		{
			$existedValueblePermission = RolePermissionTable::query()
				->where('ROLE_ID', $role->getId())
				->whereIn('PERM_TYPE', [
					'READ',
					'ADD',
					'WRITE',
					'DELETE',
					'EXPORT',
					'IMPORT',
					'AUTOMATION',
				])
				->whereIn('ATTR', [
					UserPermissions::PERMISSION_SELF,
					UserPermissions::PERMISSION_DEPARTMENT,
					UserPermissions::PERMISSION_SUBDEPARTMENT,
					UserPermissions::PERMISSION_OPENED,
					UserPermissions::PERMISSION_ALL,
					UserPermissions::PERMISSION_CONFIG,
				])
				->setLimit(1)
				->setSelect(['ID'])
				->fetch();

			if (!$existedValueblePermission)
			{
				RolePermissionLogContext::getInstance()->set([
					'scenario' => 'separate roles, delete empty role',
				]);
				PermissionRepository::getInstance()->deleteRole($role->getId());
				RolePermissionLogContext::getInstance()->clear();
			}
		}
	}

	private function deleteEmptyPermissions(EO_RolePermission_Collection $emptyPermissions): void
	{
		$logContext = RolePermissionLogContext::getInstance();
		$logContext->set([
			'scenario' => 'separate roles agent, delete empty permissions',
		]);
		$logContext->disableOrmEventsLog();
		foreach ($emptyPermissions as $emptyPermission)
		{
			Container::getInstance()->getLogger('Permissions')->info(
				"Deleted empty permissions in role #{ROLE_ID}",
				$logContext->appendTo($emptyPermission->collectValues(Values::ALL, \Bitrix\Main\ORM\Fields\FieldTypeMask::SCALAR))
			);
			RolePermissionTable::delete($emptyPermission->getId());
		}
		$logContext->enableOrmEventsLog();
		$logContext->clear();
	}
}
