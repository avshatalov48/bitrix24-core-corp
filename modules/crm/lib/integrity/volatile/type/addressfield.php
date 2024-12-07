<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

use Bitrix\Crm\AddressTable;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Format\RequisiteAddressFormatter;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Crm\PresetTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use CCrmOwnerType;

class AddressField extends BaseField
{
	protected function getAddressList(int $entityId): array
	{
		$addressList = [];

		$entityTypeId = $this->getEntityTypeId();

		switch ($entityTypeId)
		{
			case CCrmOwnerType::Lead:
				$res = AddressTable::getList(
					[
						'filter' => [
							'=ENTITY_TYPE_ID' => $entityTypeId,
							'=ENTITY_ID' => $entityId,
						],
					]
				);
				break;
			case CCrmOwnerType::Company:
			case CCrmOwnerType::Contact:
				$query = new Query(AddressTable::getEntity());
				$query->addSelect('*');
				$query->addSelect('PRESET.COUNTRY_ID', 'COUNTRY_ID');
				$query->registerRuntimeField('',
					new ReferenceField('RQ',
						RequisiteTable::getEntity(),
						[
							'=ref.ID' => 'this.ENTITY_ID',
							'=ref.ENTITY_TYPE_ID' => new SqlExpression($entityTypeId),
							'=ref.ENTITY_ID' => new SqlExpression($entityId)
						],
						['join_type' => 'INNER']
					)
				);
				$query->registerRuntimeField('',
					new ReferenceField('PRESET',
						PresetTable::getEntity(),
						['=ref.ID' => 'this.RQ.PRESET_ID'],
						['join_type' => 'LEFT']
					)
				);
				$query->setFilter(['ENTITY_TYPE_ID' => CCrmOwnerType::Requisite]);
				$res = $query->exec();
				break;
			default:
				$res = null;
		}

		if (is_object($res))
		{
			while($row = $res->fetch())
			{
				$addressList[] = [
					'TYPE_ID' => (int)$row['TYPE_ID'],
					'ADDRESS_1' => $row['ADDRESS_1'] ?? '',
					'ADDRESS_2' => $row['ADDRESS_2'] ?? '',
					'CITY' => $row['CITY'] ?? '',
					'POSTAL_CODE' => $row['POSTAL_CODE'] ?? '',
					'REGION' => $row['REGION'] ?? '',
					'PROVINCE' => $row['PROVINCE'] ?? '',
					'COUNTRY' => $row['COUNTRY'] ?? '',
					'COUNTRY_CODE' => $row['COUNTRY_CODE'] ?? '',
					'LOC_ADDR_ID' => (int)($row['LOC_ADDR_ID'] ?? 0),
					'COUNTRY_ID' => (int)($row['COUNTRY_ID'] ?? 0),
				];
			}
		}

		return $addressList;
	}

	protected function getValuesFromData(array $data): array
	{
		$result = [];

		/*$fieldName = $this->getFieldName();

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
		}*/

		return $result;
	}

	protected function prepareCode(string $value): string
	{
		$regExps = [];

		//\u00AB « left-pointing double angle quotation mark
		//\u00BB » right-pointing double angle quotation mark
		//\u201E „ double low-9 quotation mark
		//\u201F ? double high-reversed-9 quotation mark
		//\u2018 ‘ left single quotation mark
		//\u2019 ’ right single quotation mark
		//\u201C “ left double quotation mark
		//\u201D ” right double quotation mark
		$regExps[] = '/[\\x{00AB}\\x{00BB}\\x{2018}\\x{2019}\\x{201C}\\x{201D}\\x{201E}\\x{201F}]/u';

		$regExps[] = '/["`\'\\-,.;:\\s]/iu';

		return mb_strtolower(trim(preg_replace($regExps, '', $value)));
	}

	public function __construct(int $volatileTypeId, int $entityTypeId)
	{
		parent::__construct($volatileTypeId, $entityTypeId);
		$this->fieldCategory = FieldCategory::ADDRESS;
	}

	public function getMatchName(): string
	{
		$matchName = parent::getMatchName();

		return $matchName === '' ? 'ADDRESS' : $matchName;
	}

	public function getValues(int $entityId): array
	{
		$values = [];

		$addressList = $this->getAddressList($entityId);
		foreach ($addressList as $addressFields)
		{

			$value = AddressFormatter::getSingleInstance()->formatTextComma(
				$addressFields,
				RequisiteAddressFormatter::getFormatByCountryId($addressFields['COUNTRY_ID'])
			);
			if ($value != '')
			{
				$values[] = $value;
			}
		}

		return $values;
	}
}