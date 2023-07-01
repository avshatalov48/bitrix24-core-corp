<?php

namespace Bitrix\Crm\Component\EntityDetails\SaleProps;

use Bitrix\Crm\Order;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Services;

Loc::loadMessages(__FILE__);

/**
 * Trait ComponentTrait
 * @package Bitrix\Crm\Component\EntityDetails\SaleProps\ComponentTrait
 */
trait ComponentTrait
{
	/** @var array|null */
	private $propertyMap;

	/**
	 * @param Sale\EntityPropertyValueCollection $entityPropertyValueCollection
	 * @param string $entityPropertyClassname
	 * @param int $personTypeId
	 * @param bool $isNew
	 * @return int[]
	 */
	public function prepareProperties(
		Sale\EntityPropertyValueCollection $entityPropertyValueCollection,
		string $entityPropertyClassname,
		int $personTypeId,
		bool $isNew
	)
	{
		$rawProperties = [];
		$result = ['PERSON_TYPE_ID' => $personTypeId];
		$allowConfig = $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

		$filter = ['ACTIVE' => 'Y'];
		if ($isNew)
		{
			$filter['PERSON_TYPE_ID'] = $personTypeId;
		}

		$propertiesData = $entityPropertyClassname::getList(
			[
				'filter' => $filter,
				'order' => ['SORT'],
			]
		);

		while ($property = $propertiesData->fetch())
		{
			$rawProperties[$property['ID']] = $property;
		}

		/** @var Sale\PropertyValue $propertyValue */
		foreach ($entityPropertyValueCollection as $propertyValue)
		{
			$property = $propertyValue->getProperty();
			$value = $propertyValue->getValue();

			if (empty($property['ID']))
			{
				if (!empty($value) && !is_array($value))
				{
					$fieldValues = $propertyValue->getFieldValues();
					if (isset($rawProperties[$fieldValues['ORDER_PROPS_ID']]))
					{
						$property = $rawProperties[$fieldValues['ORDER_PROPS_ID']];
						$property['ORDER_PROPS_ID'] = $fieldValues['ORDER_PROPS_ID'];
					}

					$property['ID'] = 'n'.$propertyValue->getId();
					$property['ENABLE_MENU'] = false;
					$property['IS_DRAG_ENABLED'] = false;
					$preparedData = $this->formatProperty($property);
					$result['ACTIVE'][] = $preparedData;
				}
			}
			else
			{
				$property['ENABLE_MENU'] = $allowConfig;
				$preparedData = $this->formatProperty($property);
				if (isset($property['IS_HIDDEN']) && $property['IS_HIDDEN'] === 'Y')
				{
					$result["HIDDEN"][] = $preparedData;
				}
				else
				{
					$result['ACTIVE'][] = $preparedData;
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $property
	 * @return array
	 */
	public function formatProperty(array $property)
	{
		$orderPropsId = (int)($property['ORDER_PROPS_ID'] ?? 0);
		$propertyId = $orderPropsId > 0 ? $orderPropsId : $property['ID'];
		$name = 'PROPERTY_' . $property['ID'];
		$data = array(
			'propertyId' => $propertyId,
			'personTypeId' => $property['PERSON_TYPE_ID'],
			'type' => $property['TYPE']
		);

		$linked = null;
		if ($linkInfo = $this->getPropertyLinkInfo($property))
		{
			$linked = Loc::getMessage("CRM_ORDER_PROPERTY_TITLE_LINK", array(
				'#CAPTION#' => !empty($linkInfo['CAPTION']) ? htmlspecialcharsbx($linkInfo['CAPTION']) : $property['NAME'],
				'#ENTITY_NAME#' => $linkInfo['ENTITY_NAME']
			));
		}

		$simplePropertyTypes = ['STRING', 'NUMBER', 'ENUM', 'DATE', 'Y/N'];
		if (!in_array($property['TYPE'], $simplePropertyTypes, true))
		{
			$data += array(
				'edit' => "{$name}_EDIT_HTML",
				'view' => "{$name}_VIEW_HTML",
				'empty' => "{$name}_EMPTY_HTML",
				'type' => $property['TYPE'],
				'classNames' => ['crm-entity-widget-content-block-field-'.mb_strtolower($property['TYPE'])]
			);
		}
		elseif ($property['TYPE'] === 'ENUM')
		{
			$list = Order\PropertyValue::loadOptions($propertyId);
			$options = array();
			if($property['MULTIPLE'] !== 'Y')
			{
				$options['NOT_SELECTED'] = Loc::getMessage('CRM_ORDER_NOT_SELECTED');
				$options['NOT_SELECTED_VALUE'] = '';
			}
			$data['items'] = \CCrmInstantEditorHelper::PrepareListOptions($list, $options);
		}
		elseif ($property['TYPE'] === 'DATE')
		{
			$data['enableTime'] = $property['TIME'] === 'Y';
		}
		elseif ($lineCount = $this->getPropertyRowsCount($property))
		{
			$data['lineCount'] = (string)$lineCount;
		}

		return array(
			'name' => $name,
			'title' => $property['NAME'],
			'type' => $this->resolvePropertyType($property['TYPE'], $property['MULTIPLE'] === 'Y'),
			'editable' => true,
			'required' => isset($property['REQUIRED']) && $property['REQUIRED'] === 'Y',
			'enabledMenu' => $property['ENABLE_MENU'] === true,
			'transferable' => false,
			'linked' => $linked,
			'isDragEnabled' => isset($property['IS_DRAG_ENABLED']) && $property['IS_DRAG_ENABLED'] !== false,
			'optionFlags' => (isset($property['SHOW_ALWAYS']) && $property['SHOW_ALWAYS'] === 'Y') ? 1 : 0,
			'data' => $data
		);
	}

	/**
	 * Extract count of rows from property array or return default value
	 * @param array $propertyParams
	 * @return int|null
	 */
	protected function getPropertyRowsCount(array $propertyParams): ?int
	{
		$isMultiline = isset($propertyParams['MULTILINE']) && $propertyParams['MULTILINE'] === 'Y';
		if (
			$isMultiline
			&& $propertyParams['TYPE'] === 'STRING'
			&& ((int)$propertyParams['ROWS'] > 1)
		)
		{
			return (int)$propertyParams['ROWS'];
		}

		if ($isMultiline && $propertyParams['TYPE'] === 'STRING')
		{
			return $this->getDefaultTextareaRowsCount();
		}

		return null;
	}

	/**
	 * Get default count of rows in textarea input
	 * @return int
	 */
	protected function getDefaultTextareaRowsCount(): int
	{
		return 3;
	}

	/**
	 * @param $property
	 * @return array|mixed
	 */
	private function getPropertyLinkInfo($property)
	{
		if(!$this->propertyMap && (int)($property['PERSON_TYPE_ID']) > 0)
		{
			$matchedProperties = Order\Matcher\FieldMatcher::getMatchedProperties($property['PERSON_TYPE_ID']);
			foreach ($matchedProperties as $id => $match)
			{
				$entity = null;
				$entityName = '';
				if ((int)$match['CRM_ENTITY_TYPE'] === \CCrmOwnerType::Contact)
				{
					$entity = \CCrmOwnerType::ContactName;
					$entityName = Loc::getMessage('CRM_ENTITY_CONTACT');
				}
				elseif ((int)$match['CRM_ENTITY_TYPE'] === \CCrmOwnerType::Company)
				{
					$entity = \CCrmOwnerType::CompanyName;
					$entityName = Loc::getMessage('CRM_ENTITY_COMPANY');
				}

				if ((int)$match['CRM_FIELD_TYPE'] === Order\Matcher\BaseEntityMatcher::REQUISITE_FIELD_TYPE)
				{
					$entity = \CCrmOwnerType::RequisiteName;
				}

				if ((int)$match['CRM_FIELD_TYPE'] === Order\Matcher\BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE)
				{
					$entity = 'BANK_DETAIL';
				}

				$field = $match['CRM_FIELD_CODE'];
				if ($field === 'RQ_ADDR')
				{
					$entity = 'ADDRESS';
					$field = $match['SETTINGS']['RQ_ADDR_CODE'];
				}

				if (!empty($entity))
				{
					$this->propertyMap[$id] = [
						'ENTITY_NAME' => $entityName,
						'CAPTION' => Order\Matcher\FieldSynchronizer::getFieldCaption($entity, $field)
					];
				}


			}
		}

		return $this->propertyMap[$property['ID']];
	}

	/**
	 * @param $propertyType
	 * @param bool $isMultiple
	 * @return string
	 */
	protected function resolvePropertyType($propertyType, $isMultiple = false)
	{
		switch($propertyType)
		{
			case 'STRING' :
				return 'text';
			case 'NUMBER' :
				return 'number';
			case 'Y/N' :
				return 'boolean';
			case 'DATE' :
				return 'datetime';
			case 'ENUM' :
				if($isMultiple)
					return 'multilist';
				return 'list';
			case 'FILE' :
				return 'order_property_file';
		}
		return 'custom';
	}

	/**
	 * @param Sale\EntityPropertyValueCollection $entityPropertyValueCollection
	 * @return array
	 */
	public function getPropertyEntityData(Sale\EntityPropertyValueCollection $entityPropertyValueCollection)
	{
		$properties = array();
		$propertyCollection = $entityPropertyValueCollection;

		/**@var Sale\PropertyValue $property*/
		foreach ($propertyCollection as $property)
		{
			$code = null;
			$propertyData = $property->getProperty();
			if ((int)$propertyData['ID'] > 0)
			{
				$code = (int)$propertyData['ID'];
			}
			elseif (is_array($property->getValue()) || $property->getValue() <> '')
			{
				$code = 'n'.$property->getId();
			}

			if (empty($code))
			{
				continue;
			}

			$simplePropertyTypes = ['STRING', 'NUMBER', 'ENUM', 'DATE', 'Y/N'];
			if (!in_array($property->getType(), $simplePropertyTypes, true))
			{
				$params = $property->getProperty();
				$name = "PROPERTY_{$code}";
				$params['ONCHANGE'] = "BX.onCustomEvent('CrmOrderPropertySetCustom', ['{$name}']);";

				if ($property->getType() === 'LOCATION')
				{
					$params['IS_SEARCH_LINE'] = true;
				}

				$html = Sale\Internals\Input\Manager::getEditHtml(
					$name,
					$params,
					$property->getValue()
				);

				$properties["{$name}_EDIT_HTML"] = $html;
				$properties["{$name}_VIEW_HTML"] = $property->getValue() ? $property->getViewHtml() : "";
				$properties["{$name}_EMPTY_HTML"] = Loc::getMessage('CRM_ORDER_NOT_SELECTED');
			}

			$properties['PROPERTY_'.$code] = $property->getValue();
		}

		return $properties;
	}
}
