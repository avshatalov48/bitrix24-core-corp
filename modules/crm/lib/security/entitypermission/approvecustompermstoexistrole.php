<?php

namespace Bitrix\Crm\Security\EntityPermission;

use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;

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

		$roleIds = $this->getRoleIds();

		if (empty($roleIds))
		{
			return self::DONE;
		}

		$defaultPermission = array_shift($defaultPermissions);

		$entities = (RoleManagementModelBuilder::getInstance())
			->getEntityNamesWithPermissionClass($defaultPermission)
		;
		$existedPermissions = [];
		if (empty($entities) && !$this->needContinue($defaultPermissions))
		{
			return self::DONE;
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
			$newRolePermissions = RolePermissionTable::createCollection();

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

				$newRolePermissions[] = $rolePermission;
			}

			if (!$newRolePermissions->isEmpty())
			{
				$newRolePermissions->save(true);
				$processedRolesCount++;
			}

			if ($processedRolesCount > $maxProcessedRolesCount)
			{
				return self::CONTINUE;
			}
		}

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

	private function getRoleIds(): array
	{
		$dbResult = \CCrmRole::GetList(
			[],
			['IS_SYSTEM' => 'N']
		);

		$result = [];
		while ($row = $dbResult->fetch())
		{
			$result[] = $row['ID'];
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

	private function saveOption(array $defaultPermissions): void
	{
		Option::set('crm', 'default_permissions', Json::encode($defaultPermissions));
	}

	private function getMaxProcessedRolesCount(): int
	{
		$maxProcessedRolesCount = (int)Option::get('crm', 'ApproveCustomPermsToExistRoleMaxProcessedRolesCount', self::MAX_PROCESSED_ROLES_COUNT);

		return $maxProcessedRolesCount > 0 ? $maxProcessedRolesCount : self::MAX_PROCESSED_ROLES_COUNT;
	}

	public function hasWaitingPermission(string $code): bool
	{
		$defaultPermissions = $this->getDefaultPermissions();

		foreach ($defaultPermissions as $defaultPermission)
		{
			if ($defaultPermission->getPermissionType() === $code)
			{
				return true;
			}
		}

		return false;
	}
}