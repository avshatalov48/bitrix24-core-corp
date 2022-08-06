<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

use Bitrix\Crm\BankDetailTable;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use CCrmOwnerType;

class BankDetailField extends BaseField
{
	/** @var string */
	protected $fieldName;

	/** @var int */
	protected $countryId;

	protected function getFieldName(): string
	{
		return $this->fieldName;
	}

	protected function getCountryId(): string
	{
		return $this->countryId;
	}

	protected function isAllowedBankDetailCountry(int $countryId): bool
	{
		$bankDetail = EntityBankDetail::getSingleInstance();

		return $bankDetail->checkCountryId($countryId);
	}

	protected function isAllowedBankDetailField(string $fieldName): bool
	{
		static $allowedRequisiteFieldMap = null;

		if ($allowedRequisiteFieldMap === null)
		{
			$bankDetail = EntityBankDetail::getSingleInstance();
			$allowedRequisiteFieldMap = array_fill_keys(
				array_keys($bankDetail->getFieldsInfo()),
				true
			);
		}

		return isset($allowedRequisiteFieldMap[$fieldName]);
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

	public function __construct(int $volatileTypeId, int $entityTypeId, string $fieldName, int $countryId)
	{
		parent::__construct($volatileTypeId, $entityTypeId);
		$this->fieldName = $fieldName;
		$this->countryId = $countryId;
		$this->fieldCategory = FieldCategory::BANK_DETAIL;
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
		$countryId = $this->getCountryId();

		if (
			$this->isAllowedBankDetailCountry($countryId)
			&& $this->isAllowedBankDetailField($fieldName)
		)
		{
			$query = new Query(BankDetailTable::getEntity());
			$query->registerRuntimeField('',
				new ReferenceField('REF_RQ',
					RequisiteTable::getEntity(),
					[
						'=this.ENTITY_ID' => 'ref.ID',
						'=this.ENTITY_TYPE_ID' => ['?', CCrmOwnerType::Requisite],
					],
					['join_type' => 'INNER']
				)
			);
			$query->setSelect([$fieldName]);
			$query->setFilter(
				[
					'=REF_RQ.ENTITY_TYPE_ID' => $entityTypeId,
					'=REF_RQ.ENTITY_ID' => $entityId,
					'=REF_RQ.PRESET.COUNTRY_ID' => $countryId,
				]
			);
			$query->setOrder(['SORT' => 'ASC', 'ID' => 'ASC']);
			$res = $query->exec();
			if (is_object($res))
			{
				while ($row = $res->fetch())
				{
					if (is_array($row))
					{
						$values = array_merge($values, $this->getValuesFromData($row));
					}
				}
			}
		}

		return $values;
	}
}
