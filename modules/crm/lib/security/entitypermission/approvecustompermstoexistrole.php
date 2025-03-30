<?php

namespace Bitrix\Crm\Security\EntityPermission;

use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;
use Bitrix\Crm\Service\Container;

final class ApproveCustomPermsToExistRole
{
	private const MAX_PROCESSED_ROLES_COUNT = 50;

	private const DONE = false;

	private const CONTINUE = true;

	public function execute(): bool
	{
		$defaultPermissions = $this->getDefaultPermissions();

		if (empty($defaultPermissions))
		{
			return self::DONE;
		}

		$roles = $this->getRoles();

		if (empty($roles))
		{
			$this->clearDefaultPermissions();

			return self::DONE;
		}

		$defaultPermission = array_shift($defaultPermissions);

		$this->log('Start applying default permission', $defaultPermission->toArray());

		$roleIds = [];

		$permissionRoleGroups = $defaultPermission->getRoleGroups();

		foreach ($roles as $role)
		{
			$roleGroupCode = (string)$role['GROUP_CODE'];
			if ($roleGroupCode === '' && in_array('CRM', $permissionRoleGroups, true))
			{
				$roleIds[] = $role['ID'];
			}
			elseif (
				\Bitrix\Crm\Security\Role\GroupCodeGenerator::isAutomatedSolutionGroupCode($roleGroupCode)
				&& in_array('AUTOMATED_SOLUTION', $permissionRoleGroups, true)
			)
			{
				$roleIds[] = $role['ID'];
			}
			elseif(in_array($roleGroupCode, $permissionRoleGroups, true))
			{
				$roleIds[] = $role['ID'];
			}
		}

		$this->log('Affected roleIds', ['roleIds' => $roleIds]);

		if (empty($roleIds))
		{
			$this->removeDefaultPermissionFromOptions($defaultPermission);

			return $this->needContinue($defaultPermissions);
		}

		RolePermissionLogContext::getInstance()->set(array_merge([
			'scenario' => 'ApproveCustomPermsToExistRole',
		], $defaultPermission->toArray()));

		$modelBuilder = RoleManagementModelBuilder::getInstance();
		$modelBuilder->clearEntitiesCache();
		$entities = $modelBuilder->getEntityNamesWithPermissionClass($defaultPermission);

		$existedPermissions = [];

		$this->log('Affected entities', ['entities' => $entities]);

		if (empty($entities))
		{
			$this->removeDefaultPermissionFromOptions($defaultPermission);

			return $this->needContinue($defaultPermissions);
		}

		$rolePermissions = RolePermissionTable::query()
			->whereIn('ROLE_ID', $roleIds)
			->whereIn('ENTITY', $entities)
			->where('PERM_TYPE', $defaultPermission->getPermissionType())
			->fetchCollection()
		;

		foreach ($rolePermissions as $rolePermission)
		{
			$existedPermissions[$this->getRolePermissionKey($rolePermission)] = $rolePermission->getId();
		}

		$maxProcessedRolesCount = $this->getMaxProcessedRolesCount();
		$processedRolesCount = 0;
		foreach ($entities as $entity)
		{
			foreach ($roleIds as $roleId)
			{
				$rolePermission = (new EO_RolePermission())
					->setRoleId($roleId)
					->setEntity($entity)
					->setPermType($defaultPermission->getPermissionType())
					->setAttr($defaultPermission->getAttr())
					->setSettings($defaultPermission->getSettings())
				;

				$rolePermissionKey = $this->getRolePermissionKey($rolePermission);
				if (isset($existedPermissions[$rolePermissionKey]))
				{
					continue;
				}

				$rolePermission->save();
				$processedRolesCount++;

				if ($processedRolesCount > $maxProcessedRolesCount)
				{
					return self::CONTINUE;
				}
			}
		}
		RolePermissionLogContext::getInstance()->clear();

		$this->removeDefaultPermissionFromOptions($defaultPermission);

		return $this->needContinue($defaultPermissions);
	}

	private function getRolePermissionKey(EO_RolePermission $rolePermission): string
	{
		return $rolePermission->getRoleId() . '-' . $rolePermission->getEntity();
	}

	/**
	 * @return DefaultPermission[]
	 */
	private function getDefaultPermissions(): array
	{
		try
		{
			$defaultPermissions = Json::decode(Option::get('crm', 'default_permissions'));
		}
		catch (ArgumentException)
		{
			return [];
		}

		if (!is_array($defaultPermissions))
		{
			$defaultPermissions = [];
		}

		$result = [];
		foreach ($defaultPermissions as $item)
		{
			$resultItem = DefaultPermission::createFromArray($item);
			if ($resultItem)
			{
				$result[] = $resultItem;
			}
		}

		return $result;
	}

	private function getRoles(): array
	{
		$dbResult = \CCrmRole::GetList(
			[],
			['IS_SYSTEM' => 'N']
		);

		$result = [];
		while ($row = $dbResult->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	private function needContinue(array $defaultPermissions): bool
	{
		return (empty($defaultPermissions) ? self::DONE : self::CONTINUE);
	}

	public function appendDefaultPermissionToOptions(DefaultPermission $defaultPermission): void
	{
		$defaultPermissions = $this->getDefaultPermissions();

		foreach ($defaultPermissions as &$item)
		{
			if ($item->getPermissionClass() === $defaultPermission->getPermissionClass())
			{
				$item = $defaultPermission;

				return;
			}
		}

		unset($item);

		$defaultPermissions[] = $defaultPermission->toArray();

		$this->saveOption($defaultPermissions);
	}

	private function removeDefaultPermissionFromOptions(DefaultPermission $defaultPermission): void
	{
		$defaultPermissions = $this->getDefaultPermissions();

		foreach ($defaultPermissions as $key => $item)
		{
			if ($item->getPermissionClass() === $defaultPermission->getPermissionClass())
			{
				unset($defaultPermissions[$key]);
			}
		}

		$this->saveOption($defaultPermissions);
	}

	private function clearDefaultPermissions(): void
	{
		\Bitrix\Main\Config\Option::delete('crm', ['name' => 'default_permissions']);
	}

	private function saveOption(array $defaultPermissions): void
	{
		Option::set('crm', 'default_permissions', Json::encode($defaultPermissions));
	}

	private function getMaxProcessedRolesCount(): int
	{
		$maxProcessedRolesCount = (int)Option::get('crm', 'ApproveCustomPermsToExistRoleMaxProcessedRolesCount', self::MAX_PROCESSED_ROLES_COUNT);

		return $maxProcessedRolesCount > 0 ? $maxProcessedRolesCount : self::MAX_PROCESSED_ROLES_COUNT;
	}

	private function log(string $message, array $context): void
	{
		Container::getInstance()->getLogger('Permissions')->info('ApproveCustomPermsToExistRole: ' . $message, $context);
	}

	public function hasWaitingPermission(\Bitrix\Crm\Security\Role\Manage\Permissions\Permission $permission): bool
	{
		$permissionClass = $permission::class;
		$code = $permission->code();

		$defaultPermissions = $this->getDefaultPermissions();

		foreach ($defaultPermissions as $defaultPermission)
		{
			if (
				$defaultPermission->getPermissionClass() === $permissionClass
				 && $defaultPermission->getPermissionType() === $code
			)
			{
				return true;
			}
		}

		return false;
	}
}
