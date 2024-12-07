<?php

namespace Bitrix\Crm\EntityForm;

use Bitrix\Crm\Service\Container;
use Bitrix\Ui\EntityForm\Scope;

class ScopeAccess extends \Bitrix\Ui\EntityForm\ScopeAccess
{

	/**
	 * @param int $scopeId
	 * @return bool
	 */
	public function canRead(int $scopeId): bool
	{
		$scope = Scope::getInstance()->getById($scopeId);
		return (
			isset($scope) && $this->canAddByEntityTypeId($scope['ENTITY_TYPE_ID'])
			|| Scope::getInstance()->isHasScope($scopeId)
		);
	}

	/**
	 * @return bool
	 */
	public function canAdd(): bool
	{
		return Container::getInstance()->getUserPermissions($this->userId)->canWriteConfig();
	}

	public function canAddByEntityTypeId(string $entityTypeId): bool
	{
		$crmEntityTypeId = $this->getCrmEntityTypeIdByEntityTypeId($entityTypeId);
		return Container::getInstance()->getUserPermissions($this->userId)->isAdminForEntity($crmEntityTypeId);
	}

	/**
	 * @param int $scopeId
	 * @return bool
	 */
	public function canUpdate(int $scopeId): bool
	{
		$scope = Scope::getInstance()->getById($scopeId);
		return (isset($scope) && $this->canAddByEntityTypeId($scope['ENTITY_TYPE_ID']) && $scope['CATEGORY'] === $this->moduleId);
	}

	/**
	 * @param array|int $scopeIds
	 * @return bool
	 */
	public function canDelete($scopeIds): bool
	{
		if (!is_array($scopeIds))
		{
			$scopeIds = [$scopeIds];
		}

		foreach ($scopeIds as $scopeId)
		{
			if (!$this->canUpdate($scopeId))
			{
				return false;
			}
		}

		return true;
	}

	public function isAdmin(): bool
	{
		return \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions($this->userId)->isAdmin();
	}

	public function isAdminForEntityTypeId(string $entityTypeId): bool
	{
		$crmEntityTypeId = $this->getCrmEntityTypeIdByEntityTypeId($entityTypeId);
		return \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions($this->userId)->isAdminForEntity($crmEntityTypeId);
	}

	private function getCrmEntityTypeIdByEntityTypeId(string $entityTypeId): int
	{
		// Resolve the CRM entity type ID using the current entity type name.
		$crmEntityTypeId = \CCrmOwnerType::ResolveID($entityTypeId);
		// Check if the resolved CRM entity type ID is defined.
		if (\CCrmOwnerType::IsDefined($crmEntityTypeId))
		{
			return $crmEntityTypeId;
		}

		$firstUnderscoreIndex = strpos($entityTypeId, '_');
		// Loop while there is an underscore in the entity type ID string.
		while ($firstUnderscoreIndex)
		{
			// Find the last underscore in the entity type ID string.
			$lastUnderscoreIndex = strrpos($entityTypeId, '_');
			$entityTypeName = $entityTypeId;
			// Loop while there is an underscore in the entity type name.
			while ($lastUnderscoreIndex)
			{
				$crmEntityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
				if (\CCrmOwnerType::IsDefined($crmEntityTypeId))
				{
					return $crmEntityTypeId;
				}
				// Update the entity type name by removing the last underscore and the following characters.
				$entityTypeName = substr($entityTypeId, 0, $lastUnderscoreIndex);
				$lastUnderscoreIndex = strrpos($entityTypeName, '_');
			}

			$crmEntityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
			if (\CCrmOwnerType::IsDefined($crmEntityTypeId))
			{
				return $crmEntityTypeId;
			}
			// Update the entity type ID by removing the first underscore and the preceding characters.
			$entityTypeId = substr($entityTypeId, $firstUnderscoreIndex+1);
			$firstUnderscoreIndex = strpos($entityTypeId, '_');
		}

		return $crmEntityTypeId;
	}
}
