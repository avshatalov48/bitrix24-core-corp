<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Item;

class AttributesProvider
{
	protected $userId;
	protected $userAttributes;
	protected $entityAttributes;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getUserAttributes(): array
	{
		if (!$this->userAttributes)
		{
			$this->userAttributes = $this->loadUserAttributes();
		}

		return $this->userAttributes;
	}

	public function getEntityAttributes(): array
	{
		if (!$this->entityAttributes)
		{
			$this->entityAttributes = $this->loadEntityAttributes();
		}

		return $this->entityAttributes;
	}

	public function getEntityListAttributes(string $permissionEntityType, string $operation): array
	{
		$result = [];
		$permissions = $this->getUserPermissions();

		if (!isset($permissions[$permissionEntityType][$operation]))
		{
			return $result;
		}

		$userAttributes = $this->getUserAttributes();
		$defaultPermission = $permissions[$permissionEntityType][$operation]['-'];
		foreach (array_keys($permissions[$permissionEntityType][$operation]) as $statusFieldName)
		{
			if ($statusFieldName === '-' && count($permissions[$permissionEntityType][$operation]) == 1)
			{
				$permission = $defaultPermission;

				$result = array_merge(
					$result,
					$this->prepareAttributesByPermission($userAttributes, $permission)
				);
			}
			else
			{
				// we can use factory instead of hardcoded statuses list for each entity type
				$statusIds = $this->getEntityStatusIds($permissionEntityType, $statusFieldName);

				foreach ($statusIds as $statusId)
				{
					$permission = $defaultPermission;
					if (isset($permissions[$permissionEntityType][$operation][$statusFieldName][$statusId]))
					{
						$permission = $permissions[$permissionEntityType][$operation][$statusFieldName][$statusId];
					}
					$result = array_merge(
						$result,
						$this->prepareAttributesByPermission($userAttributes, $permission, $statusFieldName . $statusId)
					);
				}
			}
		}

		return $result;
	}

	protected function loadUserAttributes(): array
	{
		$attributesByUser = [];

		$userAccessCodes = $this->getUserAccessCodes();
		foreach ($userAccessCodes as $accessCode)
		{
			if (mb_strpos($accessCode['ACCESS_CODE'], 'DR') !== 0)
			{
				$attributesByUser[mb_strtoupper($accessCode['PROVIDER_ID'])][] = $accessCode['ACCESS_CODE'];
			}
		}

		if (!empty($attributesByUser['INTRANET']))
		{
			foreach ($attributesByUser['INTRANET'] as $iDepartment)
			{
				if (mb_substr($iDepartment, 0, 1) === 'D')
				{
					$departmentTree = $this->getSubDepartmentsIds((int)mb_substr($iDepartment, 1));
					foreach ($departmentTree as $departmentId)
					{
						$attributesByUser['SUBINTRANET'][] = 'D' . $departmentId;
					}
				}
			}
		}

		return $attributesByUser;
	}

	public function loadEntityAttributes(): array
	{
		$result = [
			'INTRANET' => [],
		];
		$userAttributes = $this->getUserAttributes();
		if (!empty($userAttributes['INTRANET']))
		{
			//HACK: Removing intranet subordination relations, otherwise staff will get access to boss's entities
			foreach ($userAttributes['INTRANET'] as $code)
			{
				if (mb_strpos($code, 'IU') !== 0)
				{
					$result['INTRANET'][] = $code;
				}
			}
			$userId = $this->getUserId();
			$result['INTRANET'][] = "IU{$userId}";
		}

		return $result;
	}

	protected function getUserPermissions(): array
	{
		return \CCrmRole::GetUserPerms($this->getUserId());
	}

	protected function prepareAttributesByPermission(array $userAttributes, string $permission, $statusRestriction = null): array
	{
		$result = [];
		$partOfResult = [];

		if ($permission == UserPermissions::PERMISSION_NONE)
		{
			return [];
		}
		elseif ($permission == UserPermissions::PERMISSION_OPENED)
		{
			$partOfResult[] = 'O';
			foreach ($userAttributes['USER'] as $userId)
			{

				$result[] = $statusRestriction ? [$statusRestriction, $userId] : [$userId];
			}
		}
		elseif ($permission != UserPermissions::PERMISSION_ALL)
		{
			if ($permission >= UserPermissions::PERMISSION_SELF)
			{
				foreach ($userAttributes['USER'] as $userId)
				{
					$result[] =  $statusRestriction ? [$statusRestriction, $userId] : [$userId];
				}
			}
			if ($permission >= UserPermissions::PERMISSION_DEPARTMENT && isset($userAttributes['INTRANET']))
			{
				foreach ($userAttributes['INTRANET'] as $departmentId)
				{
					//HACK: SKIP IU code it is not required for this method
					if ($departmentId != '' && mb_substr($departmentId, 0, 2) === 'IU')
					{
						continue;
					}

					if (!in_array($departmentId, $partOfResult))
					{
						$partOfResult[] = $departmentId;
					}
				}
			}
			if ($permission >= UserPermissions::PERMISSION_SUBDEPARTMENT && isset($userAttributes['SUBINTRANET']))
			{
				foreach ($userAttributes['SUBINTRANET'] as $departmentId)
				{
					if ($departmentId != '' && mb_substr($departmentId, 0, 2) === 'IU')
					{
						continue;
					}

					if (!in_array($departmentId, $partOfResult))
					{
						$partOfResult[] = $departmentId;
					}
				}
			}
		}
		else //self::PERM_ALL
		{
			$result[] = $statusRestriction ? [$statusRestriction] : [];
		}

		if (!empty($partOfResult))
		{
			$result[] = $statusRestriction
				? array_merge([$statusRestriction], $partOfResult)
				: $partOfResult;
		}

		return $result;
	}

	protected function getUserAccessCodes(): array
	{
		$result = [];
		$userAccessCodes = \CAccess::GetUserCodes($this->getUserId());
		while ($accessCode = $userAccessCodes->Fetch())
		{
			$result[] = $accessCode;
		}

		return $result;
	}

	protected function getSubDepartmentsIds($departmentId): array
	{
		if (\Bitrix\Main\Loader::includeModule('intranet'))
		{
			return \CIntranetUtils::GetDeparmentsTree($departmentId, true);
		}

		return [];
	}

	protected function getEntityStatusIds(string $permissionEntityType, string $statusFieldName): array
	{
		static $cache = [];
		if (isset($cache[$permissionEntityType][$statusFieldName]))
		{
			return $cache[$permissionEntityType][$statusFieldName];
		}

		$statusIds = [];
		$entityTypeName = UserPermissions::getEntityNameByPermissionEntityType($permissionEntityType);
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (
			$factory
			&& $factory->isStagesSupported()
			&& $factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID) === $statusFieldName
		)
		{
			$categoryId = UserPermissions::getCategoryIdFromPermissionEntityType($permissionEntityType);
			$stages = $factory->getStages($categoryId);
			foreach ($stages->getAll() as $stage)
			{
				$statusIds[] = $stage->getStatusId();
			}
		}
		$cache[$permissionEntityType][$statusFieldName] = $statusIds;

		return $statusIds;
	}
}
