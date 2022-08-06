<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;

class RequisiteField extends BaseField
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

	protected function getPresetInfo(int $presetId): array
	{
		static $presetInfoMap = [];

		$defaultPresetInfo = [
			'countryId' => 0,
			'fieldMap' => [],
		];

		if ($presetId <= 0)
		{
			return $defaultPresetInfo;
		}

		if (!isset($presetInfoMap[$presetId]))
		{
			$presetInfo = $defaultPresetInfo;

			$preset = EntityPreset::getSingleInstance();
			$res = $preset->getList(
				[
					'order' => ['SORT' => 'ASC'],
					'filter' => ['=ID' => $presetId],
					'select' => ['COUNTRY_ID', 'SETTINGS'],
				]
			);
			while ($row = $res->fetch())
			{
				$countryId = (int)$row['COUNTRY_ID'];
				if ($countryId > 0 && is_array($row['SETTINGS']))
				{
					$presetInfo['countryId'] = $countryId;

					foreach ($preset->settingsGetFields($row['SETTINGS']) as $fieldInfo)
					{
						if (
							isset($fieldInfo['FIELD_NAME'])
							&& is_string($fieldInfo['FIELD_NAME'])
							&& $fieldInfo['FIELD_NAME'] !== ''
						)
						{
							$presetInfo['fieldMap'][$fieldInfo['FIELD_NAME']] = true;
						}
					}
				}
			}

			$presetInfoMap[$presetId] = $presetInfo;
		}

		return $presetInfoMap[$presetId];
	}

	protected function getPresetCountryId(int $presetId): int
	{
		$presetInfo = $this->getPresetInfo($presetId);

		return $presetInfo['countryId'];
	}

	protected function isFieldInPreset(int $presetId, string $fieldName): bool
	{
		$result = false;

		if ($presetId > 0 && $fieldName !== '')
		{
			$presetInfo = $this->getPresetInfo($presetId);
			if (isset($presetInfo['fieldMap'][$fieldName]))
			{
				$result = true;
			}
		}

		return $result;
	}

	protected function isAllowedRequisteCountry(int $countryId): bool
	{
		$requisite = EntityRequisite::getSingleInstance();

		return $requisite->checkCountryId($countryId);
	}

	protected function isAllowedRequisteField(string $fieldName): bool
	{
		static $allowedRequisiteFieldMap = null;

		if ($allowedRequisiteFieldMap === null)
		{
			$requisite = EntityRequisite::getSingleInstance();
			$allowedRequisiteFieldMap = array_fill_keys(
				array_merge(
					array_keys($requisite->getFieldsInfo()),
					$requisite->getUserFields()
				),
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
		$this->fieldCategory = FieldCategory::REQUISITE;
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
			$this->isAllowedRequisteCountry($countryId)
			&& $this->isAllowedRequisteField($fieldName)
		)
		{
			$requisite = EntityRequisite::getSingleInstance();
			$res = $requisite->getList(
				[
					'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
					'filter' => [
						'=ENTITY_TYPE_ID' => $entityTypeId,
						'=ENTITY_ID' => $entityId,
						'=PRESET.COUNTRY_ID' => $countryId
					],
					'select' => [$fieldName]
				]
			);
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
