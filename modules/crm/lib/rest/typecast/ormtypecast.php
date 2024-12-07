<?php

namespace Bitrix\Crm\Rest\TypeCast;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Caster\BoolCaster;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use InvalidArgumentException;
use Bitrix\Main\ORM\Entity;

/**
 * It provides methods to cast records and fields to their appropriate types based on the type map of the data manager.
 */
class OrmTypeCast
{
	use Singleton;

	private const CAST_TO_INT = 'int';
	private const CAST_TO_FLOAT = 'float';
	private const CAST_TO_BOOL = 'bool';
	private const CAST_SKIP = 'skip';

	/** @var array<string, array> */
	private array $typeMapCache = [];

	private ?Caster $boolCaster = null;

	/**
	 * @param class-string<DataManager> $dataManager
	 * @param array<string, mixed> $record
	 * @return array
	 */
	public function castRecord(string $dataManager, array $record): array
	{
		$result = [];
		foreach ($record as $fieldName => $value)
		{
			$result[$fieldName] = $this->castField($dataManager, $fieldName, $value);
		}

		return $result;
	}

	/**
	 * @param class-string<DataManager> $dataManager
	 * @param string $fieldName
	 * @param mixed $value
	 * @return mixed
	 */
	public function castField(string $dataManager, string $fieldName, mixed $value): mixed
	{
		$map = $this->getTypeMap($dataManager);

		if (!isset($map[$fieldName]) || is_null($value) || is_array($value) || is_object($value))
		{
			return $value;
		}

		$boolCaster = $this->boolCaster ?: new BoolCaster();

		return match ($map[$fieldName]) {
			self::CAST_TO_INT => (int)$value,
			self::CAST_TO_FLOAT => (float)$value,
			self::CAST_TO_BOOL => $boolCaster->cast($value),
			default => $value,
		};
	}

	/**
	 * @param class-string<DataManager> $dataManager
	 * @return array
	 */
	private function getTypeMap(string $dataManager): array
	{
		if (!is_subclass_of($dataManager, DataManager::class))
		{
			throw new InvalidArgumentException('Data manager must be instance of DataManager');
		}

		if (isset($this->typeMapCache[$dataManager]))
		{
			return $this->typeMapCache[$dataManager];
		}

		$ormFields = $dataManager::getMap();

		$result = [];
		foreach ($ormFields as $fieldId => $field)
		{
			$field = is_array($field) ? (new Entity)->initializeField($fieldId, $field) : $field;

			$fieldClassName = get_class($field);

			$castType = match ($fieldClassName) {
				Fields\IntegerField::class => self::CAST_TO_INT,
				Fields\FloatField::class, Fields\DecimalField::class => self::CAST_TO_FLOAT,
				Fields\BooleanField::class => self::CAST_TO_BOOL,
				default => self::CAST_SKIP,
			};

			$result[$field->getName()] = $castType;
		}

		$this->typeMapCache[$dataManager] = $result;

		return $this->typeMapCache[$dataManager];
	}

	public function setBoolCaster(?Caster $boolCaster): OrmTypeCast
	{
		$this->boolCaster = $boolCaster;

		return $this;
	}
}
