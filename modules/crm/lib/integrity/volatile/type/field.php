<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Query\Query;

class Field extends BaseField
{
	/** @var string */
	protected $fieldName;

	protected function __construct(
		int $volatileTypeId,
		int $entityTypeId,
		string $fieldName
	)
	{
		parent::__construct($volatileTypeId, $entityTypeId);
		$this->fieldName = $fieldName;
		$this->fieldCategory = FieldCategory::ENTITY;
	}

	protected function getFieldName(): string
	{
		return $this->fieldName;
	}

	protected function getValuesFromData(array $data): array
	{
		$result = [];

		$fieldName = $this->getFieldName();

		if (array_key_exists($fieldName, $data) && $data[$fieldName] !== null)
		{
			$value = $data[$fieldName];
			if (!is_array($value))
			{
				$value = [$value];
			}
			if(!empty($value))
			{
				foreach ($value as $singleValue)
				{
					$singleValue = !is_array($singleValue) ? (string)$singleValue : '';
					if ($singleValue !== '')
					{
						$result[] = $singleValue;
					}
				}
			}
		}

		return $result;
	}

	public function getMatchName(): string
	{
		$matchName = parent::getMatchName();

		return $matchName === '' ? $this->getFieldName() : $matchName;
	}

	public function getValues(int $entityId): array
	{
		$values = [];

		$entityTypeId = $this->getEntityTypeId();
		$fieldName = $this->getFieldName();

		$entity = Container::getInstance()->getFactory($entityTypeId)->getDataClass()::getEntity();
		if ($entity->hasField($fieldName))
		{
			$row = (new Query($entity))->addSelect($fieldName)->where('ID', $entityId)->fetch();
			if (is_array($row))
			{
				$values = $this->getValuesFromData($row);
			}
		}

		return $values;
	}
}
