<?php

namespace Bitrix\Crm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\PhoneNumber\Parser;

/**
 * Class FieldMultiTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FieldMulti_Query query()
 * @method static EO_FieldMulti_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FieldMulti_Result getById($id)
 * @method static EO_FieldMulti_Result getList(array $parameters = [])
 * @method static EO_FieldMulti_Entity getEntity()
 * @method static \Bitrix\Crm\EO_FieldMulti createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_FieldMulti_Collection createCollection()
 * @method static \Bitrix\Crm\EO_FieldMulti wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_FieldMulti_Collection wakeUpCollection($rows)
 */
class FieldMultiTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_field_multi';
	}

	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Fields\StringField('ENTITY_ID'))
				->configureRequired()
				->configureSize(16),
			(new Fields\IntegerField('ELEMENT_ID'))
				->configureRequired(),
			(new Fields\StringField('TYPE_ID'))
				->configureRequired()
				->configureSize(16),
			(new Fields\StringField('VALUE_TYPE'))
				->configureRequired()
				->configureSize(50),
			(new Fields\StringField('COMPLEX_ID'))
				->configureRequired()
				->configureSize(100),
			(new Fields\StringField('VALUE'))
				->configureRequired()
				->configureSize(250),
		];
	}

	public static function prepareFilter(array $entities, ?array $typeIds = []): array
	{
		$filter = [];

		if(!empty($typeIds))
		{
			$filter['=TYPE_ID'] = $typeIds;
		}
		if(!empty($entities))
		{
			$entitiesFilter = [
				'LOGIC' => 'OR',
			];
			foreach($entities as $entity)
			{
				$entitiesFilter[] = [
					"=ENTITY_ID" => $entity['NAME'],
					"=ELEMENT_ID" => $entity['ID'],
				];
			}
			$filter[] = $entitiesFilter;
		}

		return $filter;
	}

	public static function isImOpenLinesValue(string $value): bool
	{
		return preg_match('/^imol\|/', $value) === 1;
	}

	public static function prepareItemData(array $row): ?array
	{
		$value = $row['VALUE'] ?? '';
		if(empty($value))
		{
			return null;
		}

		$valueType = $row['VALUE_TYPE'];
		$multiFieldComplexID = $row['COMPLEX_ID'];
		$typeID = $row['TYPE_ID'];

		if(
			$typeID === 'PHONE'
			|| $typeID === 'EMAIL'
			|| ($typeID === 'IM' && static::isImOpenLinesValue($value))
		)
		{
			$formattedValue = $typeID === 'PHONE'
				? Parser::getInstance()->parse($value)->format()
				: $value;

			// keys are a mess, made to fit data from old api.
			return [
				'ID' => $row['ID'],
				'TYPE_ID' => $typeID,
				'ENTITY_ID' => $row['ELEMENT_ID'],
				'ELEMENT_ID' => $row['ELEMENT_ID'],
				'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($row['ENTITY_ID']),
				'ENTITY_TYPE_ID' => $row['ENTITY_ID'],
				'VALUE' => $value,
				'VALUE_TYPE' => $valueType,
				'VALUE_FORMATTED' => $formattedValue,
				'COMPLEX_ID' => $multiFieldComplexID,
				'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($multiFieldComplexID, false),
			];
		}

		return null;
	}

	public static function rearrangeDataByTypesAndEntities(array $data): array
	{
		$result = [];

		foreach ($data as $record)
		{
			$entityTypeId = $record['ENTITY_TYPE_ID'] ?? \CCrmOwnerType::ResolveID($record['ENTITY_TYPE_NAME']);
			$entityId = $record['ENTITY_ID'] ?? $record['ELEMENT_ID'];
			$typeId = $record['TYPE_ID'] ?? null;
			if($entityTypeId > 0 && $entityId > 0 && !empty($typeId))
			{
				$entityKey = $entityTypeId . '_' . $entityId;
				$result[$typeId][$entityKey][] = $record;
			}
		}

		return $result;
	}

	final public static function fetchByOwner(ItemIdentifier $owner): Result
	{
		return static::getList([
			'select' => ['*'],
			'filter' => [
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($owner->getEntityTypeId()),
				'=ELEMENT_ID' => $owner->getEntityId(),
			],
		]);
	}

	final public static function fetchByMultipleOwners(int $entityTypeId, array $entityIds): Result
	{
		return static::getList([
			'select' => ['*'],
			'filter' => [
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeId),
				'@ELEMENT_ID' => $entityIds,
			],
		]);
	}

	final public static function fetchPhoneIdsByOwner(ItemIdentifier $owner): array
	{
		return static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($owner->getEntityTypeId()),
				'=ELEMENT_ID' => $owner->getEntityId(),
				'=TYPE_ID' => 'PHONE',
			],
		])->fetchCollection()->getList('ID');
	}
}
