<?php

namespace Bitrix\Crm\Security\AccessAttribute;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Crm\Security;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ORM\Entity;

class Manager
{
	/**
	 * Get tablet class for access attribute table by crm entity type
	 *
	 * @param string $entityTypeName
	 * @return string
	 */
	public static function getEntityDataClass(string $entityTypeName): string
	{
		return self::getEntity($entityTypeName)->getDataClass();
	}

	/**
	 * Create ORM entity for access attribute table by crm entity type
	 *
	 * @param string $entityTypeName
	 * @return Entity
	 */
	public static function getEntity(string $entityTypeName): Entity
	{
		static $entities = [];
		if (!isset($entities[$entityTypeName]))
		{
			$entities[$entityTypeName] = self::compileEntity($entityTypeName);
		}

		return $entities[$entityTypeName];
	}

	private static function compileEntity(string $entityTypeName): Entity
	{
		if (\CCrmOwnerType::ResolveID($entityTypeName) === \CCrmOwnerType::Undefined)
		{
			throw new ArgumentOutOfRangeException('entityTypeName');
		}
		$controller = Security\Manager::resolveController($entityTypeName);
		if($controller === null)
		{
			throw new NotSupportedException("Permission entity type: '{$entityTypeName}' is not supported in current context");
		}

		$entityTypeNameLower = strtolower($entityTypeName);

		$entityTypeNameFormatted = ucfirst(preg_replace('/[^a-z0-9]/', '', $entityTypeNameLower));
		$entityClassName = $entityTypeNameFormatted . 'AccessAttributeTable';

		$tableName = 'b_crm_access_attr_' . $entityTypeNameLower;

		if (class_exists($entityClassName))
		{
			// rebuild if it already exists
			Entity::destroy($entityClassName);
			$entity = Entity::getInstance($entityClassName);
		}
		else
		{
			$entity = Entity::compileEntity($entityClassName, [], [
				'table_name' => $tableName,
				'parent' => EntityAccessAttributeTable::class,
				'namespace' => __NAMESPACE__,
			]);
		}

		return $entity;
	}
}
