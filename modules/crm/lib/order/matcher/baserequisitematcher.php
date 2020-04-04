<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\ArgumentNullException;

abstract class BaseRequisiteMatcher
{
	private $entityTypeId = null;
	private $entityId = null;

	protected $properties = [];
	protected $duplicateControl = null;

	public function __construct($entityTypeId, $entityId)
	{
		if (empty($entityTypeId))
		{
			throw new ArgumentNullException('$entityTypeId');
		}

		if (empty($entityId))
		{
			throw new ArgumentNullException('$entityTypeId');
		}

		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;

		$this->duplicateControl = BaseEntityMatcher::getDefaultDuplicateMode();
	}

	public function setProperties(array $properties)
	{
		$this->properties = $properties;
	}

	public function setDuplicateControlMode($mode)
	{
		if (array_key_exists($mode, BaseEntityMatcher::DUPLICATE_CONTROL_MODES))
		{
			$this->duplicateControl = BaseEntityMatcher::DUPLICATE_CONTROL_MODES[$mode];
		}
	}

	protected function getEntityTypeId()
	{
		return $this->entityTypeId;
	}

	protected function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * @return EntityBankDetail|EntityRequisite|null|string|void
	 */
	abstract protected function getEntity();

	/**
	 * @param array $entity
	 * @return array|void
	 */
	abstract protected function normalizeHashArray(array $entity);

	/**
	 * @param $entityTypeId
	 * @param $entityIds
	 * @return array|void
	 */
	abstract protected function loadExistingEntities();

	/**
	 * @param $entityIds
	 * @param $properties
	 * @param $existingEntities
	 * @return array|void
	 */
	abstract protected function getEntitiesToMatch();

	protected function getEntityHash(array $entity)
	{
		$normalizedEntity = $this->normalizeHashArray($entity);

		return md5(implode('/', $normalizedEntity));
	}

	protected function addEntity($entityFields)
	{
		$result = $this->getEntity()->add($entityFields);

		if ($result->isSuccess())
		{
			$entityFields += ['ID' => $result->getId()];
		}

		return $entityFields;
	}

	protected function updateEntity($requisite, $existingEntity)
	{
		$result = $this->getEntity()->update($existingEntity['ID'], $requisite);

		if ($result->isSuccess())
		{
			$existingEntity = array_merge($existingEntity, $requisite);
		}

		return $existingEntity;
	}

	protected function isEqualEntity($requisite, $existingEntity)
	{
		foreach ($requisite as $key => $reqField)
		{
			if (!empty($existingEntity[$key]))
			{
				if (is_array($requisite[$key]) && is_array($existingEntity[$key]))
				{
					if (!$this->isEqualEntity($requisite[$key], $existingEntity[$key]))
					{
						return false;
					}
				}
				elseif ($existingEntity[$key] != $reqField)
				{
					return false;
				}
			}
		}

		return true;
	}

	protected function matchEntity($entityFields, $existingEntities)
	{
		foreach ($existingEntities as $existingEntity)
		{
			if ($this->isEqualEntity($entityFields, $existingEntity))
			{
				return $this->updateEntity($entityFields, $existingEntity);
			}
		}

		return [];
	}

	public function match()
	{
		$matched = [];

		$entitiesToMatch = $this->getEntitiesToMatch();
		$existingEntities = $this->loadExistingEntities();

		foreach ($entitiesToMatch as $entityToMatch)
		{
			if (!empty($existingEntities))
			{
				$matchedEntity = $this->matchEntity($entityToMatch, $existingEntities);
			}

			if (empty($matchedEntity))
			{
				$matchedEntity = $this->addEntity($entityToMatch);
			}

			if (!empty($matchedEntity))
			{
				$matched[] = $matchedEntity;
			}
		}

		return $matched;
	}
}