<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;


/**
 * Class CustomFieldsController
 * @package Bitrix\Sale\Internals
 */
final class CustomFieldsController
{
	private static $instance = null;

	/**
	 * CustomFieldsController constructor.
	 */
	private function __construct() {}

	/**
	 * @return CustomFieldsController
	 */
	public static function getInstance() : CustomFieldsController
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param Entity $entity
	 * @return Entity
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function save(Entity $entity)
	{
		$dbRes = CustomFieldsTable::getList([
			'select' => ['ID', 'FIELD'],
			'filter' => [
				'=ENTITY_ID' => $entity->getId(),
				'=ENTITY_TYPE' => $entity::getRegistryEntity(),
				'=ENTITY_REGISTRY_TYPE' => $entity::getRegistryType()
			]
		]);

		$customFieldArray = [];
		while ($data = $dbRes->fetch())
		{
			$customFieldArray[$data['FIELD']] = $data;
		}

		foreach ($entity::getCustomizableFields() as $field)
		{
			if ($entity->isMarkedFieldCustom($field))
			{
				if (!isset($customFieldArray[$field]))
				{
					CustomFieldsTable::add([
						'ENTITY_ID' => $entity->getId(),
						'ENTITY_TYPE' => $entity::getRegistryEntity(),
						'ENTITY_REGISTRY_TYPE' => $entity::getRegistryType(),
						'FIELD' => $field
					]);
				}
			}
			else
			{
				if (isset($customFieldArray[$field]))
				{
					CustomFieldsTable::delete($customFieldArray[$field]['ID']);
				}
			}
		}

		return $entity;
	}

	/**
	 * @param Entity $entity
	 * @return Entity
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function initialize(Entity $entity)
	{
		$dbRes = CustomFieldsTable::getList([
			'select' => ['ID', 'FIELD'],
			'filter' => [
				'=ENTITY_ID' => $entity->getId(),
				'=ENTITY_TYPE' => $entity::getRegistryEntity(),
				'=ENTITY_REGISTRY_TYPE' => $entity::getRegistryType()
			]
		]);

		while ($data = $dbRes->fetch())
		{
			$entity->markFieldCustom($data['FIELD']);
		}

		return $entity;
	}
}