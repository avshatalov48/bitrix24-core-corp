<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\StatusTable;

Loc::loadMessages(__FILE__);

class RequisiteDataProvider extends Main\Filter\DataProvider
{
	/** @var EntitySettings|null */
	protected $settings = null;
	/** @var EntityRequisite|null */
	protected $requisite = null;

	function __construct(EntitySettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Entity Requisite.
	 * @return EntityRequisite
	 */
	protected function getEntityRequisite()
	{
		if($this->requisite === null)
		{
			$this->requisite = new EntityRequisite();
		}
		return $this->requisite;
	}

	/**
	 * Get Settings
	 * @return EntitySettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields()
	{
		$requisite = $this->getEntityRequisite();

		$fieldList = EntityPreset::getSingleInstance()->getSettingsFieldsOfPresets(
			EntityPreset::Requisite,
			'active',
			array('FILTER_BY_COUNTRY_IDS' => $requisite->getAllowedRqFieldCountries())
		);

		if(empty($fieldList))
		{
			return array();
		}

		$activeCountryMap = array();
		$activeFieldMap = array();

		foreach($fieldList as $countryId => $fields)
		{
			foreach($fields as $fieldName)
			{
				$activeFieldMap[$countryId][$fieldName] = true;
				$activeCountryMap[$countryId] = true;
			}
		}

		if(empty($activeCountryMap))
		{
			return array();
		}

		$result = array();

		$currentCountryId = EntityPreset::getCurrentCountryId();
		$hideCountry = count($activeCountryMap) === 1 && isset($activeCountryMap[$currentCountryId]);
		$countrySort = array();
		if(isset($activeCountryMap[$currentCountryId]))
		{
			$countrySort[] = $currentCountryId;
		}

		foreach(array_keys($activeCountryMap) as $countryId)
		{
			if($countryId !== $currentCountryId)
			{
				$countrySort[] = $countryId;
			}
		}

		$fieldTitles = $requisite->getRqFieldTitleMap();
		$fieldTypes = $requisite->getFormFieldsTypes();
		$effectiveFields = array();
		foreach($requisite->getRqFiltrableFields() as $fieldName)
		{
			$effectiveFields[$fieldName] = true;
		}

		$countries = EntityPreset::getCountryList();
		$fieldNamePrefix = Loc::getMessage('CRM_REQUISITE_FILTER_PREFIX');
		foreach($countrySort as $countryId)
		{
			if(!isset($countries[$countryId]))
			{
				continue;
			}

			foreach($requisite->getRqFields() as $fieldName)
			{
				if(isset($effectiveFields[$fieldName])
					&& isset($activeFieldMap[$countryId][$fieldName])
					&& isset($fieldTitles[$fieldName][$countryId])
					&& !empty($fieldTitles[$fieldName][$countryId])
				)
				{
					if($fieldName !== EntityRequisite::ADDRESS)
					{
						$isPartial = false;
						$fieldId = $fieldName.'|'.$countryId;
						if ($requisite->isRqListField($fieldName))
						{
							$fieldType = 'list';
							$isPartial = true;
						}
						else
						{
							$fieldType = $fieldTypes[$fieldName] ?? 'text';
						}
						$fieldParams = [
							'type' => $fieldType,
							'name' => $fieldNamePrefix.
								($hideCountry ? '' : ' ('.$countries[$countryId].')').': '.
								$fieldTitles[$fieldName][$countryId],
						];
						if ($isPartial)
						{
							$fieldParams['partial'] = true;
						}
						$result[$fieldId] = $this->createField($fieldId, $fieldParams);
					}
					else
					{
						$addressTypeId = RequisiteAddress::Undefined;
						$addressTypeName = $fieldTitles[$fieldName][$countryId];
						$addressLabels = RequisiteAddress::getShortLabels(RequisiteAddress::Primary);
						foreach(array_keys($requisite->getAddressFieldMap(RequisiteAddress::Primary)) as $fieldKey)
						{
							if($fieldKey === 'ADDRESS_2'
								|| $fieldKey === 'COUNTRY_CODE'
								|| $fieldKey === 'LOC_ADDR_ID')
							{
								continue;
							}

							$fieldId = $fieldName.'|'.$countryId.'|'.$addressTypeId.'|'.$fieldKey;
							$result[$fieldId] = $this->createField(
								$fieldId,
								[
									'type' => 'text',
									'name' => $fieldNamePrefix.
										($hideCountry ? '' : ' ('.$countries[$countryId].')').': '.
										$addressTypeName.' - '.ToLower($addressLabels[$fieldKey]),
								]
							);
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public function prepareFieldData($fieldID)
	{
		$result = null;

		$matches = [];
		if (preg_match('/^(RQ_[A-Z0-9_]+)\|(\d{1,3})$/', $fieldID, $matches))
		{
			$fieldName = $matches[1];
			$countryId = (int)$matches[2];
			$requisite = EntityRequisite::getSingleInstance();
			if ($requisite->isRqListField($fieldName) && $requisite->checkRqFieldCountryId($fieldName, $countryId))
			{
				$countryCode = EntityPreset::getCountryCodeById($countryId);
				$statusEntityId = "{$fieldName}_{$countryCode}";
				$result = [
					'params' => array('multiple' => 'Y'),
					'items' => StatusTable::getStatusesList($statusEntityId)
				];

			}
		}

		return $result;
	}

	/**
	 * Create filter field.
	 * @param string $fieldID Field ID.
	 * @param array|null $params Field parameters (optional).
	 * @return Field
	 */
	protected function createField($fieldID, array $params = null)
	{
		return new Field($this, $fieldID, $params);
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		$requisite = new \Bitrix\Crm\EntityRequisite();
		$requisite->prepareEntityListFilter($filterValue);

		return $filterValue;
	}
}
