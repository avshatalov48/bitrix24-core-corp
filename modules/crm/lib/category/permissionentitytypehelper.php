<?php

namespace Bitrix\Crm\Category;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\CategoryIdentifier;

class PermissionEntityTypeHelper
{
	protected $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * Convert entity category ID to permission entity type.
	 *
	 * @param int $categoryId Entity category id.
	 * @return string
	 */
	public function getPermissionEntityTypeForCategory(int $categoryId): string
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeId);

		return $categoryId > 0 ? "{$entityTypeName}_C{$categoryId}" : $entityTypeName;
	}

	/**
	 * Try to convert permission entity type to category ID.
	 * Returns -1 if conversion failed.
	 *
	 * @param string $permissionEntityType Permission entity type.
	 * @return int
	 */
	public function extractCategoryFromPermissionEntityType(string $permissionEntityType): int
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeId);

		if ($permissionEntityType === $entityTypeName)
		{
			return 0;
		}

		$escapedEntityTypeName = preg_quote($entityTypeName, '/');
		if(
			preg_match("/{$escapedEntityTypeName}_C(\d+)/", $permissionEntityType, $m) === 1
			&& is_array($m)
			&& count($m) === 2)
		{
			return (int)$m[1];
		}

		return -1;
	}

	/**
	 * Return all permission entity types available for entity
	 *
	 * @return array
	 */
	public function getAllPermissionEntityTypesForEntity(): array
	{
		$result = [];

		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		if ($factory && $factory->isCategoriesSupported())
		{
			$categories = $factory->getCategories();
			foreach ($categories as $category)
			{
				$result[] = $this->getPermissionEntityTypeForCategory($category->getId());
			}
		}
		else
		{
			$result = [
				\CCrmOwnerType::ResolveName($this->entityTypeId)
			];
		}

		return $result;
	}

	/**
	 * Check if $permissionEntityType belongs to entity $this->entityTypeId
	 *
	 * @param string $permissionEntityType
	 * @return bool
	 */
	public function doesPermissionEntityTypeBelongToEntity(string $permissionEntityType): bool
	{
		return($this->extractCategoryFromPermissionEntityType($permissionEntityType) >= 0);
	}

	/**
	 * Return list of permission types according to CATEGORY_ID in filter
	 *
	 * @param array $filter
	 *
	 * @return array|null
	 */
	public function getPermissionEntityTypesFromFilter(array $filter): ?array
	{
		$operationInfo = \Bitrix\Crm\UI\Filter\EntityHandler::findFieldOperation('CATEGORY_ID', $filter);
		if(is_array($operationInfo) && in_array($operationInfo['OPERATION'], ['=', 'IN']))
		{
			$permissionEntityTypes = [];
			foreach((array)$operationInfo['CONDITION'] as $categoryId)
			{
				if($categoryId >= 0)
				{
					$permissionEntityTypes[] = $this->getPermissionEntityTypeForCategory((int)$categoryId);
				}
			}
			$permissionEntityTypes = array_unique($permissionEntityTypes);

			return $permissionEntityTypes;
		}

		return null;
	}

	/**
	 * Return list of permission types from GetListEx options or full list of types instead.
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function getPermissionEntityTypesFromOptions(array $options): array
	{
		if(
			isset($options['RESTRICT_BY_ENTITY_TYPES'])
			&& is_array($options['RESTRICT_BY_ENTITY_TYPES'])
			&& !empty($options['RESTRICT_BY_ENTITY_TYPES'])
		)
		{
			$permissionEntityTypes = $options['RESTRICT_BY_ENTITY_TYPES'];
		}
		else
		{
			$permissionEntityTypes = $this->getAllPermissionEntityTypesForEntity();
		}

		return $permissionEntityTypes;
	}

	public function getAllowSkipOtherEntityTypesFromOptions(array $options): bool
	{
		return (
			isset($options['RESTRICT_BY_ENTITY_TYPES'])
			&& is_array($options['RESTRICT_BY_ENTITY_TYPES'])
			&& !empty($options['RESTRICT_BY_ENTITY_TYPES'])
		);
	}

	/**
	 * @param string $permissionEntityType DEAL_C1 for example
	 * @return array [$entityTypeId, $categoryId]
	 */
	public static function extractEntityEndCategoryFromPermissionEntityType(string $permissionEntityType): ?CategoryIdentifier
	{
		$entityTypeId = 0;
		$categoryId = 0;
		$entityTypeParts = explode('_', $permissionEntityType);
		if (count($entityTypeParts) === 1)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeParts[0]);
		}
		else
		{
			$categoryCode = array_pop($entityTypeParts);
			$entityTypeId = \CCrmOwnerType::ResolveID(implode('_', $entityTypeParts));
			if (\CCrmOwnerType::IsDefined($entityTypeId))
			{
				$helper = new self($entityTypeId);
				$categoryId = $helper->extractCategoryFromPermissionEntityType($permissionEntityType);
				if ($categoryId === -1)
				{
					return null; // wrong category
				}
			}
			else
			{
				return null;
			}
		}

		return CategoryIdentifier::createByParams($entityTypeId, $categoryId);
	}
}
