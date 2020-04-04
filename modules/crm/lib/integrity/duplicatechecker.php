<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Main;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityPreset;
//IncludeModuleLangFile(__FILE__);
abstract class DuplicateChecker
{
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected function __construct($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity ID: '{$entityTypeID}' is not supported in current context");
		}
		$this->entityTypeID = $entityTypeID;
	}
	public function getEntityID()
	{
		return $this->entityTypeID;
	}
	abstract public function findDuplicates(\Bitrix\Crm\EntityAdapter $adapter, DuplicateSearchParams $params);
	public function findMultifieldDuplicates($type, \Bitrix\Crm\EntityAdapter $adapter, DuplicateSearchParams $params)
	{
		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		if($type !== 'EMAIL' && $type !== 'PHONE')
		{
			throw new Main\NotSupportedException("Type: '{$type}' is not supported in current context");
		}

		$allMultiFields =  $adapter->getFieldValue('FM');
		$multiFields = is_array($allMultiFields) && isset($allMultiFields[$type]) ? $allMultiFields[$type] : null;
		if(!is_array($multiFields) || empty($multiFields))
		{
			return array();
		}

		$criterions = array();
		$dups = array();
		foreach($multiFields as &$multiField)
		{
			$value = isset($multiField['VALUE']) ? $multiField['VALUE'] : '';
			if($value === '')
			{
				continue;
			}

			$criterion = new DuplicateCommunicationCriterion($type, $value);
			$isExists = false;
			foreach($criterions as $curCriterion)
			{
				/** @var DuplicateCriterion $curCriterion */
				if($criterion->equals($curCriterion))
				{
					$isExists = true;
					break;
				}
			}

			if($isExists)
			{
				continue;
			}
			$criterions[] = $criterion;
			$duplicate = $criterion->find();
			if($duplicate !== null)
			{
				$dups[] = $duplicate;
			}
		}
		unset($multiField);
		return $dups;
	}
	public function findRequisiteDuplicates(\Bitrix\Crm\EntityAdapter $adapter, DuplicateSearchParams $params)
	{
		$dups = array();

		$fieldNames = $params->getFieldNames();
		$processAllFields = empty($fieldNames);
		$processAllRequisiteFields = $processAllFields || in_array('RQ', $fieldNames, true);
		$requsiiteDupFieldsMap = EntityRequisite::getDuplicateCriterionFieldsMap();
		$requisiteFieldGroups = array();
		foreach ($requsiiteDupFieldsMap as $countryId => $fields)
		{
			foreach ($fields as $fieldName)
			{
				$groupId = $fieldName.'|'.$countryId;
				if ($processAllRequisiteFields || in_array('RQ.'.$groupId, $fieldNames, true))
				{
					$requisiteFieldGroups[$groupId] = array(
						'countryId' => $countryId,
						'fieldName' => $fieldName,
						'values' => array()
					);
				}
			}
		}
		foreach ($fieldNames as $fieldName)
		{
			$fieldNameLength = strlen($fieldName);
			if ($fieldNameLength > 3 && 'RQ.' === substr($fieldName, 0, 3)
				&& $fieldName !== 'RQ.BD' && ($fieldNameLength < 6 || 'RQ.BD.' !== substr($fieldName, 0, 6))
				&& !in_array(substr($fieldName, 3), array_keys($requisiteFieldGroups))
			)
			{
				throw new Main\NotSupportedException(
					"Field name: \"{$fieldName}\" is not supported in current context"
				);
			}
		}

		if (!empty($requisiteFieldGroups))
		{
			$allRequisites = $adapter->getFieldValue('RQ');

			// gather countries by presets
			$presetCountryMap = array();
			$presetIds = array();
			foreach ($allRequisites as $requisiteFields)
			{
				if (!(isset($requisiteFields['PRESET_COUNTRY_ID']) && $requisiteFields['PRESET_COUNTRY_ID'] > 0)
					&& isset($requisiteFields['PRESET_ID']) && $requisiteFields['PRESET_ID'] > 0)
				{
					$presetIds[] = (int)$requisiteFields['PRESET_ID'];
				}
			}
			if (!empty($presetIds))
			{
				$preset = new EntityPreset();
				$res = $preset->getList(array(
					'order' => array('SORT' => 'ASC'),
					'filter' => array(
						'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
						'=ID' => array_unique($presetIds)
					),
					'select' => array('ID', 'COUNTRY_ID')
				));
				while ($presetData = $res->fetch())
				{
					$countryId = (int)$presetData['COUNTRY_ID'];
					if ($countryId > 0)
						$presetCountryMap[(int)$presetData['ID']] = $countryId;
				}
			}
			unset($presetIds, $requisiteFields, $preset, $res, $presetData, $countryId);

			// gather values
			foreach ($allRequisites as $requisiteFields)
			{
				$countryId = 0;
				if (isset($requisiteFields['PRESET_COUNTRY_ID']) && $requisiteFields['PRESET_COUNTRY_ID'] > 0)
					$countryId = (int)$requisiteFields['PRESET_COUNTRY_ID'];
				$presetId = 0;
				if (isset($requisiteFields['PRESET_ID']) && $requisiteFields['PRESET_ID'] > 0)
					$presetId = (int)$requisiteFields['PRESET_ID'];
				if (isset($presetCountryMap[$presetId]))
					$countryId = $presetCountryMap[$presetId];
				if (isset($requisiteFields['ID']) && $countryId > 0)
				{
					foreach ($requisiteFields as $fieldName => $value)
					{
						$groupId = $fieldName.'|'.$countryId;
						if (isset($requisiteFieldGroups[$groupId]))
							$requisiteFieldGroups[$groupId]['values'][] = $value;
					}
				}
			}
			unset($presetCountryMap);

			$criterions = array();
			foreach($requisiteFieldGroups as $requsiiteFieldGroup)
			{
				foreach ($requsiiteFieldGroup['values'] as $value)
				{
					if($value === '')
					{
						continue;
					}

					$criterion = new DuplicateRequisiteCriterion(
						$requsiiteFieldGroup['countryId'],
						$requsiiteFieldGroup['fieldName'],
						$value
					);
					$isExists = false;
					foreach($criterions as $curCriterion)
					{
						/** @var DuplicateCriterion $curCriterion */
						if($criterion->equals($curCriterion))
						{
							$isExists = true;
							break;
						}
					}

					if($isExists)
					{
						continue;
					}

					$criterions[] = $criterion;
					$duplicate = $criterion->find();
					if($duplicate !== null)
					{
						$dups[] = $duplicate;
					}
				}
			}
		}

		return $dups;
	}
	public function findBankDetailDuplicates(\Bitrix\Crm\EntityAdapter $adapter, DuplicateSearchParams $params)
	{
		$dups = array();

		$fieldNames = $params->getFieldNames();
		$processAllFields = empty($fieldNames);
		$processAllRequisiteFields = $processAllFields || in_array('RQ', $fieldNames, true);
		$processAllBankDetailFields = $processAllFields || $processAllRequisiteFields || in_array('RQ.BD', $fieldNames, true);
		$bankDetailDupFieldsMap = EntityBankDetail::getDuplicateCriterionFieldsMap();
		$bankDetailFieldGroups = array();
		foreach ($bankDetailDupFieldsMap as $countryId => $fields)
		{
			foreach ($fields as $fieldName)
			{
				$groupId = $fieldName.'|'.$countryId;
				if ($processAllBankDetailFields || in_array('RQ.BD.'.$groupId, $fieldNames, true))
				{
					$bankDetailFieldGroups[$groupId] = array(
						'countryId' => $countryId,
						'fieldName' => $fieldName,
						'values' => array()
					);
				}
			}
		}
		foreach ($fieldNames as $fieldName)
		{
			if (strlen($fieldName) > 6 && 'RQ.BD.' === substr($fieldName, 0, 3)
				&& !in_array(substr($fieldName, 6), array_keys($bankDetailFieldGroups))
			)
			{
				throw new Main\NotSupportedException(
					"Field name: \"{$fieldName}\" is not supported in current context"
				);
			}
		}

		if (!empty($bankDetailFieldGroups))
		{
			$allRequisites = $adapter->getFieldValue('RQ');

			// gather countries by presets
			$presetCountryMap = array();
			$presetIds = array();
			foreach ($allRequisites as $requisiteFields)
			{
				if (!(isset($requisiteFields['PRESET_COUNTRY_ID']) && $requisiteFields['PRESET_COUNTRY_ID'] > 0)
					&& isset($requisiteFields['PRESET_ID']) && $requisiteFields['PRESET_ID'] > 0)
				{
					$presetIds[] = (int)$requisiteFields['PRESET_ID'];
				}
			}
			if (!empty($presetIds))
			{
				$preset = new EntityPreset();
				$res = $preset->getList(array(
					'order' => array('SORT' => 'ASC'),
					'filter' => array(
						'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
						'=ID' => array_unique($presetIds)
					),
					'select' => array('ID', 'COUNTRY_ID')
				));
				while ($presetData = $res->fetch())
				{
					$countryId = (int)$presetData['COUNTRY_ID'];
					if ($countryId > 0)
						$presetCountryMap[(int)$presetData['ID']] = $countryId;
				}
			}
			unset($presetIds, $requisiteFields, $preset, $res, $presetData, $countryId);

			// gather values
			foreach ($allRequisites as $requisiteFields)
			{
				if (is_array($requisiteFields['BD']) && !empty($requisiteFields['BD']))
				{
					$presetId = 0;
					if (isset($requisiteFields['PRESET_ID']) && $requisiteFields['PRESET_ID'] > 0)
						$presetId = (int)$requisiteFields['PRESET_ID'];

					$countryId = 0;
					if (isset($presetCountryMap[$presetId]))
						$countryId = $presetCountryMap[$presetId];
					else if (isset($requisiteFields['PRESET_COUNTRY_ID']) && $requisiteFields['PRESET_COUNTRY_ID'] > 0)
						$countryId = (int)$requisiteFields['PRESET_COUNTRY_ID'];

					if (isset($requisiteFields['ID']) && is_array($requisiteFields['BD']))
					{
						foreach ($requisiteFields['BD'] as $bankDetailFields)
						{
							if ($countryId <= 0 && isset($bankDetailFields['COUNTRY_ID']) && $bankDetailFields['COUNTRY_ID'] > 0)
								$countryId = (int)$bankDetailFields['COUNTRY_ID'];
							if ($countryId > 0)
							{
								foreach ($bankDetailFields as $fieldName => $value)
								{
									$groupId = $fieldName.'|'.$countryId;
									if (isset($bankDetailFieldGroups[$groupId]))
										$bankDetailFieldGroups[$groupId]['values'][] = $value;
								}
							}
						}
					}
				}
			}
			unset($presetCountryMap);

			$criterions = array();
			foreach($bankDetailFieldGroups as $bankDetailFieldGroup)
			{
				foreach ($bankDetailFieldGroup['values'] as $value)
				{
					if($value === '')
					{
						continue;
					}

					$criterion = new DuplicateBankDetailCriterion(
						$bankDetailFieldGroup['countryId'],
						$bankDetailFieldGroup['fieldName'],
						$value
					);
					$isExists = false;
					foreach($criterions as $curCriterion)
					{
						/** @var DuplicateCriterion $curCriterion */
						if($criterion->equals($curCriterion))
						{
							$isExists = true;
							break;
						}
					}

					if($isExists)
					{
						continue;
					}

					$criterions[] = $criterion;
					$duplicate = $criterion->find();
					if($duplicate !== null)
					{
						$dups[] = $duplicate;
					}
				}
			}
		}

		return $dups;
	}
}