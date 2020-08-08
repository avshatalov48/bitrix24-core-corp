<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Main\ArgumentNullException;

class FieldMatcher
{
	public static function getMatchedEntities($personTypeId)
	{
		$personTypeId = (int)$personTypeId;
		if ($personTypeId <= 0)
		{
			throw new ArgumentNullException('personTypeId');
		}

		$properties = [];

		$matches = OrderPropsMatchTable::getList([
			'select' => ['SALE_PROP_ID', 'CRM_ENTITY_TYPE'],
			'filter' => ['=SALE_PROPERTY.PERSON_TYPE_ID' => $personTypeId]
		]);

		foreach ($matches as $match)
		{
			$properties[$match['SALE_PROP_ID']] = $match['CRM_ENTITY_TYPE'];
		}

		return $properties;
	}

	public static function getMatchedProperties($personTypeId)
	{
		$personTypeId = (int)$personTypeId;
		if ($personTypeId <= 0)
		{
			throw new ArgumentNullException('personTypeId');
		}

		$properties = [];

		$matches = OrderPropsMatchTable::getList([
			'select' => ['SALE_PROP_ID', 'CRM_ENTITY_TYPE', 'CRM_FIELD_TYPE', 'CRM_FIELD_CODE', 'SETTINGS'],
			'filter' => ['=SALE_PROPERTY.PERSON_TYPE_ID' => $personTypeId]
		]);

		foreach ($matches as $match)
		{
			$properties[$match['SALE_PROP_ID']] = $match;
		}

		return $properties;
	}

	/**
	 * @param $entityTypeId
	 * @return \CCrmContact|\CCrmCompany|string
	 */
	protected static function getEntityClass($entityTypeId)
	{
		$className = '';

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			$className = '\CCrmContact';
		}
		elseif ($entityTypeId === \CCrmOwnerType::Company)
		{
			$className = '\CCrmCompany';
		}

		return $className;
	}

	protected static function parseGeneralFieldType($entityTypeId, $entityId, $matches)
	{
		$entityClassName = static::getEntityClass($entityTypeId);

		$dbResult = $entityClassName::GetListEx(
			[],
			['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['*', 'UF_*']
		);
		$fields = $dbResult->Fetch();

		if (!is_array($fields))
		{
			$fields = [];
		}

		$values = [];

		foreach ($matches as $match)
		{
			if (isset($fields[$match['CRM_FIELD_CODE']]))
			{
				if (
					(int)$entityTypeId === \CCrmOwnerType::Contact
					&& $match['CRM_FIELD_CODE'] === 'FULL_NAME'
				)
				{
					$values[$match['SALE_PROP_ID']] = $entityClassName::PrepareFormattedName(
						$fields,
						PersonNameFormatter::LastFirstSecondFormat
					);
				}
				else
				{
					$values[$match['SALE_PROP_ID']] = $fields[$match['CRM_FIELD_CODE']];
				}
			}
		}

		return $values;
	}

	protected static function parseMultiFieldType($entityTypeId, $entityId, $matches)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);

		$multiFields = [];
		$entityMultiFields = \CCrmFieldMulti::GetEntityFields($entityTypeName, $entityId, null);

		foreach ($entityMultiFields as $multiField)
		{
			$multiFields[$multiField['TYPE_ID'].'_'.$multiField['VALUE_TYPE']] = $multiField['VALUE'];
		}

		$values = [];

		foreach ($matches as $match)
		{
			if (isset($multiFields[$match['CRM_FIELD_CODE']]))
			{
				$values[$match['SALE_PROP_ID']] = $multiFields[$match['CRM_FIELD_CODE']];
			}
		}

		return $values;
	}

	protected static function parseRequisiteFieldType($entityTypeId, $entityId, $matches)
	{
		$addresses = RequisiteAddress::getByEntities($entityTypeId, [$entityId]);

		$requisiteResult = EntityRequisite::getSingleInstance()->getList([
			'select' => ['*', 'UF_*'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId,
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1
		]);
		foreach ($requisiteResult as $requisite)
		{
			if (isset($addresses[$entityId][$requisite['ID']]))
			{
				$requisite[EntityRequisite::ADDRESS] = $addresses[$entityId][$requisite['ID']];
			}
		}

		$values = [];

		if (!empty($requisite))
		{
			foreach ($matches as $match)
			{
				if ($match['CRM_FIELD_CODE'] === EntityRequisite::ADDRESS)
				{
					$settings = $match['SETTINGS'];

					// ToDo parse RQ_ADDR_CODE === 'LOCATION'
					if (isset($requisite[$match['CRM_FIELD_CODE']][$settings['RQ_ADDR_TYPE']][$settings['RQ_ADDR_CODE']]))
					{
						$values[$match['SALE_PROP_ID']] = $requisite[$match['CRM_FIELD_CODE']][$settings['RQ_ADDR_TYPE']][$settings['RQ_ADDR_CODE']];
					}
				}
				else
				{
					if (isset($requisite[$match['CRM_FIELD_CODE']]))
					{
						$values[$match['SALE_PROP_ID']] = $requisite[$match['CRM_FIELD_CODE']];
					}
				}
			}
		}

		return $values;
	}

	protected static function parseBankDetailFieldType($entityTypeId, $entityId, $matches)
	{
		$requisiteResult = EntityRequisite::getSingleInstance()->getList([
			'select' => ['ID', 'NAME', 'PRESET_ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId
			]
		]);
		foreach ($requisiteResult->fetchAll() as $requisite)
		{
			$requisites[$requisite['ID']] = $requisite;
		}

		$bankDetail = [];

		if (!empty($requisites))
		{
			$requisiteResult = EntityBankDetail::getSingleInstance()->getList([
				'select' => ['*', 'UF_*'],
				'filter' => [
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
					'ENTITY_ID' => array_keys($requisites),
				],
				'order' => ['ID' => 'DESC'],
				'limit' => 1
			]);
			$bankDetail = $requisiteResult->fetch();
		}

		$values = [];

		if (!empty($bankDetail))
		{
			foreach ($matches as $match)
			{
				if (isset($bankDetail[$match['CRM_FIELD_CODE']]))
				{
					$values[$match['SALE_PROP_ID']] = $bankDetail[$match['CRM_FIELD_CODE']];
				}
			}
		}

		return $values;
	}

	public static function getPropertyValues($entityTypeId, $entityId)
	{
		$matches = OrderPropsMatchTable::getList([
			'select' => ['SALE_PROP_ID', 'CRM_ENTITY_TYPE', 'CRM_FIELD_TYPE', 'CRM_FIELD_CODE', 'SETTINGS'],
			'filter' => ['=CRM_ENTITY_TYPE' => $entityTypeId]
		]);

		$matchByFieldType = [];

		foreach ($matches as $match)
		{
			$matchByFieldType[$match['CRM_FIELD_TYPE']][] = $match;
		}

		$properties = [];

		foreach ($matchByFieldType as $fieldType => $matches)
		{
			switch($fieldType)
			{
				case BaseEntityMatcher::GENERAL_FIELD_TYPE:
					$typeProperties = static::parseGeneralFieldType($entityTypeId, $entityId, $matches);
					break;

				case BaseEntityMatcher::MULTI_FIELD_TYPE:
					$typeProperties = static::parseMultiFieldType($entityTypeId, $entityId, $matches);
					break;

				case BaseEntityMatcher::REQUISITE_FIELD_TYPE:
					$typeProperties = static::parseRequisiteFieldType($entityTypeId, $entityId, $matches);
					break;

				case BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE:
					$typeProperties = static::parseBankDetailFieldType($entityTypeId, $entityId, $matches);
					break;

				default:
					$typeProperties = [];
			}

			foreach ($typeProperties as $key => $value)
			{
				$properties[$key] = $value;
			}
		}

		return $properties;
	}
}