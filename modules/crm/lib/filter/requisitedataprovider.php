<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\RequisiteAddress;

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
		$addressTypes = array();
		foreach(RequisiteAddress::getClientTypeInfos() as $typeInfo)
		{
			$addressTypes[$typeInfo['id']] = $typeInfo['name'];
		}

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
						$fieldId = $fieldName.'|'.$countryId;
						$result[$fieldId] = $this->createField(
							$fieldId,
							array(
								'type' => isset($fieldTypes[$fieldName])
									? $fieldTypes[$fieldName] : 'text',
								'name' => $fieldNamePrefix.
									($hideCountry ? '' : ' ('.$countries[$countryId].')').': '.
									$fieldTitles[$fieldName][$countryId],
							)
						);
					}
					elseif(!empty($addressTypes))
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
								array(
									'type' => 'text',
									'name' => $fieldNamePrefix.
										($hideCountry ? '' : ' ('.$countries[$countryId].')').': '.
										$addressTypeName.' - '.ToLower($addressLabels[$fieldKey])
								)
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
		return null;
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
}