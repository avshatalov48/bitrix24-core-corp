<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateIndexTypeSettingsTable;
use Bitrix\Crm\Integrity\Entity\DuplicateIndexTable;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Crm\Integrity\Volatile\TypeInfo;
use CCrmFieldMulti;
use CCrmOwnerType;

abstract class BaseField
{
	/** @var int */
	protected $volatileTypeId;

	/** @var int */
	protected $entityTypeId;

	/** @var int */
	protected $fieldCategory;

	protected function __construct(
		int $volatileTypeId,
		int $entityTypeId = CCrmOwnerType::Undefined
	)
	{
		$this->volatileTypeId = $volatileTypeId;
		$this->entityTypeId = $entityTypeId;
		$this->fieldCategory = FieldCategory::UNDEFINED;
	}

	private static function getVolatileTypeInfo(): array
	{
		return TypeInfo::getInstance()->get();
	}

	protected function prepareCode(string $value): string
	{
		$result = '';

		$value = trim($value);
		if($value !== '')
		{
			$result = mb_strtolower($value);
		}

		return $result;
	}

	public function isNull(): bool
	{
		return false;
	}

	public static function getInstance(int $volatileTypeId): BaseField
	{
		/*
		Example settings:

		b_crm_dp_index_type_settings
		---------------------------------------------------------------------------------------------
				ID  ACTIVE  DESCRIPTION                 ENTITY_TYPE_ID  STATE_ID  FIELD_PATH  FIELD_NAME
		  33554432  N       Company name                             4         0              TITLE
		  67108864  N       Google address                           4         0              UF_CRM_1588599299
		 134217728  N       Company name in requisites               4         0  RQ.RU       NAME
		 268435456  N       UF - String                              4         0  RQ.RU       UF_CRM_1528447491
		 536870912  N       Work phone                               4         0  FM          WEB
		1073741824  N       Address                                  3         0              ADDRESS
		2147483648  N       Bank account number                      4         0  RQ.RU.BD    RQ_ACC_NUM
		*/
		$result = null;

		$volatileTypeInfo = static::getVolatileTypeInfo();

		if (isset($volatileTypeInfo[$volatileTypeId]))
		{
			$typeInfo = $volatileTypeInfo[$volatileTypeId];
			$fieldPath = $typeInfo['FIELD_PATH'];
			$fieldName = $typeInfo['FIELD_NAME'];
			$entityTypeId = $typeInfo['ENTITY_TYPE_ID'];

			$categoryInfo = FieldCategory::getInstance()->getCategoryByPath("$fieldPath.$fieldName");

			switch ($categoryInfo['categoryId'])
			{
				case FieldCategory::ENTITY:
					$result = new Field($volatileTypeId, $entityTypeId, $fieldName);
					break;
				case FieldCategory::ADDRESS:
					$result = new AddressField($volatileTypeId, $entityTypeId);
					break;
				case FieldCategory::MULTI:
					$result = new MultiField(
						$volatileTypeId,
						$entityTypeId,
						$categoryInfo['params']['multiFieldType']
					);
					break;
				case FieldCategory::REQUISITE:
					$result = new RequisiteField(
						$volatileTypeId,
						$entityTypeId,
						$fieldName,
						$categoryInfo['params']['countryId']
					);
					break;
				case FieldCategory::BANK_DETAIL:
					$result = new BankDetailField(
						$volatileTypeId,
						$entityTypeId,
						$fieldName,
						$categoryInfo['params']['countryId']
					);
					break;
			}
		}

		if ($result === null)
		{
			$result = new NullField($volatileTypeId);
		}

		return $result;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getFieldCategoryId(): int
	{
		return $this->fieldCategory;
	}

	public function prepareCodes(array $values): array
	{
		$result = [];

		foreach($values as $value)
		{
			if(!is_string($value))
			{
				continue;
			}

			$value = $this->prepareCode($value);
			if($value !== '')
			{
				$result[] = $value;
			}
		}

		return array_unique($result);
	}

	public function getMatchName(): string
	{
		$typeInfo = TypeInfo::getInstance()->getById($this->volatileTypeId);

		return $typeInfo['DESCRIPTION'] ?? '';
	}

	public function getValues(int $entityId): array
	{
		return [];
	}
}