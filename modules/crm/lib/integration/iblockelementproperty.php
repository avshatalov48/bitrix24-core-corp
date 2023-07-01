<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\UserField\Renderer;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class IBlockElementProperty
{
	const USER_TYPE = 'ECrm';

	protected static $listDefaultEntity = array(
		'VISIBLE' => 'Y', 'LEAD' => 'Y', 'CONTACT' => 'Y', 'COMPANY' => 'Y', 'DEAL' => 'Y');
	protected static $listDefaultEntityKey = array('D' => 'DEAL', 'C' => 'CONTACT', 'CO' => 'COMPANY', 'L' => 'LEAD');

	/**
	 * Returns property type description.
	 *
	 * @return array
	 */
	public static function getUserTypeDescription()
	{
		$className = get_called_class();
		return array(
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => self::USER_TYPE,
			'DESCRIPTION' => Loc::getMessage('CRM_IBLOCK_PROPERTY_BIND_CRM_ELEMENT'),
			'GetPublicEditHTML' => array($className, 'getPublicEditHTML'),
			'GetPublicEditHTMLMulty' => array($className, 'getPublicEditHTMLMulty'),
			'GetPublicViewHTML' => array($className, 'getPublicViewHTML'),
			'GetPublicViewHTMLMulty' => array($className, 'getPublicViewHTMLMulty'),
			'GetPropertyFieldHtml' => array($className, 'getPropertyFieldHtml'),
			'GetPropertyFieldHtmlMulty' => array($className, 'getPropertyFieldHtmlMulty'),
			'GetAdminListViewHTML' => array($className, 'getAdminListViewHTML'),
			'PrepareSettings' => array($className, 'prepareSettings'),
			'GetSettingsHTML' => array($className, 'getSettingsHTML'),
			'CheckFields' => array($className, 'checkFields'),
			'GetLength' => array($className, 'getLength'),
			'ConvertToDB' => array($className, 'convertToDB'),
			'ConvertFromDB' => array($className, 'convertFromDB'),
			'GetValuePrintable' => array($className, 'getValuePrintable'),
			'AddFilterFields' => array($className, 'addFilterFields'),
			'GetUIFilterProperty' => array($className, 'getUIFilterProperty'),
			'GetUIEntityEditorProperty' => array($className, 'GetUIEntityEditorProperty'),
			'GetUIEntityEditorPropertyViewHtml' => array($className, 'GetUIEntityEditorPropertyViewHtml'),
			'GetUIEntityEditorPropertyEditHtml' => array($className, 'GetUIEntityEditorPropertyEditHtml'),
		);
	}

	/**
	 * Return html for public edit value.
	 *
	 * @param array $property Property data.
	 * @param array $value Current value.
	 * @param array $controlSettings Form data.
	 * @return string
	 */
	public static function getPublicEditHTMLMulty($property, $value, $controlSettings)
	{
		global $APPLICATION;

		$fieldName = !empty($controlSettings['VALUE']) ? $controlSettings['VALUE'] : '';
		$formLable = !empty($controlSettings['DESCRIPTION']) ? $controlSettings['DESCRIPTION'] : '';
		$multiple = !empty($controlSettings['MULTIPLE']) ? $controlSettings['MULTIPLE'] : $property['MULTIPLE'];
		$isRequired = !empty($property['IS_REQUIRED']) ? $property['IS_REQUIRED'] : 'N';
		$createNewEntity = true;
		$listValue = array();
		if(!empty($value['VALUE']))
		{
			if(!is_array($value['VALUE']))
			{
				$value['VALUE'] = [$value['VALUE']];
			}
			$listValue = $value['VALUE'];
		}
		elseif(is_array($value))
		{
			foreach($value as $dataValue)
			{
				if(isset($dataValue['VALUE']))
				{
					if(is_array($dataValue['VALUE']))
					{
						$listValue = $dataValue['VALUE'];
					}
					else
					{
						$listValue[] = $dataValue['VALUE'];
					}
				}
				elseif (is_scalar($dataValue) && $dataValue !== '')
				{
					$listValue[] = $dataValue;
				}
				else
				{
					$listValue[] = null;
				}
			}
		}

		if (isset($property['PROPERTY_USER_TYPE']) && is_array($property['PROPERTY_USER_TYPE']))
		{
			$userType = $property['PROPERTY_USER_TYPE'];
		}
		else
		{
			$userType = array();
			if (!empty($property['USER_TYPE']))
			{
				$userType['USER_TYPE'] = $property['USER_TYPE'];
				$createNewEntity = false;
			}
			else
			{
				return  '';
			}
		}

		$userField = array(
			'ENTITY_ID' => 'BIND_CRM_ELEMENT_'.$property['IBLOCK_ID'],
			'FIELD_NAME' => $fieldName,
			'USER_TYPE_ID' => 'crm',
			'MULTIPLE' => $multiple,
			'MANDATORY' => $isRequired,
			'EDIT_FORM_LABEL' => $formLable,
			'VALUE' => $listValue,
			'SETTINGS' => is_array($property['USER_TYPE_SETTINGS'])
				? $property['USER_TYPE_SETTINGS'] : static::$listDefaultEntity,
			'USER_TYPE' => $userType
		);
		$field = new Renderer($userField, [
			'mode' => 'main.edit',
			'bVarsFromForm' => false,
			'form_name' => $controlSettings['FORM_NAME'],
			'createNewEntity' => $createNewEntity,
		]);

		return $field->render();
	}

	/**
	 * Return html for public edit value.
	 *
	 * @param array $property Property data.
	 * @param array $value Current value.
	 * @param array $controlSettings Form data.
	 * @return string
	 */
	public static function getPublicEditHTML($property, $value, $controlSettings)
	{
		return static::getPublicEditHTMLMulty($property, $value, $controlSettings);
	}

	/**
	 * The method should return the html display for editing property values in the administrative part.
	 *
	 * @param array $property Property data.
	 * @param array $value Current value.
	 * @param array $controlSettings Form data.
	 * @return string
	 */
	public static function getPropertyFieldHtml($property, $value, $controlSettings)
	{
		return static::getPropertyFieldHtmlMulty($property, $value, $controlSettings);
	}

	/**
	 * The method should return the html display for editing property multiple values in the administrative part.
	 *
	 * @param array $property Property data.
	 * @param array $value Current value.
	 * @param array $controlSettings Form data.
	 * @return string
	 */
	public static function getPropertyFieldHtmlMulty($property, $value, $controlSettings)
	{
		return static::getPublicEditHTMLMulty($property, $value, $controlSettings);
	}

	/**
	 * Return html for public view value.
	 *
	 * @param array $property Property data.
	 * @param array $value Current value.
	 * @param array $controlSettings Form data.
	 * @return string
	 */
	public static function getAdminListViewHTML($property, $value, $controlSettings)
	{
		return static::getPublicViewHTMLMulty($property, $value, $controlSettings);
	}

	/**
	 * Return html for public view value.
	 *
	 * @param array $property Property data.
	 * @param array $value Current value.
	 * @param array $controlSettings Form data.
	 * @return string
	 */
	public static function getPublicViewHTMLMulty($property, $value, $controlSettings)
	{
		global $APPLICATION;

		$fieldName = !empty($controlSettings['VALUE']) ? $controlSettings['VALUE'] : '';
		$formLable = !empty($controlSettings['DESCRIPTION']) ? $controlSettings['DESCRIPTION'] : '';
		$multiple = !empty($controlSettings['MULTIPLE']) ? $controlSettings['MULTIPLE'] : $property['MULTIPLE'];
		$isRequired = !empty($property['IS_REQUIRED']) ? $property['IS_REQUIRED'] : 'N';
		$listValue = array();
		if(!empty($value['VALUE']))
		{
			if(!is_array($value['VALUE']))
				$value['VALUE'] = array($value['VALUE']);
			$listValue = $value['VALUE'];
		}
		elseif(is_array($value))
		{
			foreach($value as $dataValue)
			{
				if(isset($dataValue['VALUE']))
				{
					if(is_array($dataValue['VALUE']))
					{
						$listValue = $dataValue['VALUE'];
					}
					else
					{
						$listValue[] = $dataValue['VALUE'];
					}
				}
			}
		}
		switch($controlSettings['MODE'])
		{
			case 'CSV_EXPORT':
				return implode(',', $listValue);
			case 'EXCEL_EXPORT':
				return self::getEntityForExcelById($property, $listValue);
		}

		if (is_array($property['PROPERTY_USER_TYPE']))
		{
			$userType = $property['PROPERTY_USER_TYPE'];
		}
		else
		{
			$userType = array();
			if (!empty($property['USER_TYPE']))
			{
				$userType['USER_TYPE'] = $property['USER_TYPE'];
			}
		}

		$userField = array(
			'ENTITY_ID' => 'BIND_CRM_ELEMENT_'.$property['IBLOCK_ID'],
			'FIELD_NAME' => $fieldName,
			'USER_TYPE_ID' => 'crm',
			'MULTIPLE' => $multiple,
			'MANDATORY' => $isRequired,
			'EDIT_FORM_LABEL' => $formLable,
			'VALUE' => $listValue,
			'SETTINGS' => is_array($property['USER_TYPE_SETTINGS'])
				? $property['USER_TYPE_SETTINGS'] : static::$listDefaultEntity,
			'USER_TYPE' => $userType
		);

		$field = new Renderer($userField, [
			'mode' => 'main.view',
			'bVarsFromForm' => false,
			'form_name' => $controlSettings['FORM_NAME'],
		]);

		return $field->render();
	}

	/**
	 * Return html for public view value.
	 *
	 * @param array $property Property data.
	 * @param array $value Current value.
	 * @param array $controlSettings Form data.
	 * @return string
	 */
	public static function getPublicViewHTML($property, $value, $controlSettings)
	{
		return static::getPublicViewHTMLMulty($property, $value, $controlSettings);
	}

	/**
	 * Prepare settings for property.
	 *
	 * @param array $property Property data.
	 * @return array
	 */
	public static function prepareSettings($property)
	{
		if(!is_array($property['USER_TYPE_SETTINGS']))
			$property['USER_TYPE_SETTINGS'] = array();

		foreach(static::$listDefaultEntity as $entity => $entityMark)
		{
			if(!array_key_exists($entity, $property['USER_TYPE_SETTINGS']))
				$property['USER_TYPE_SETTINGS'][$entity] = 'N';
		}

		return $property;
	}

	/**
	 * Returns html for show in edit property page.
	 *
	 * @param array $property Property data.
	 * @param array $controlSettings Form data.
	 * @param array $propertyFields Property fields for edit form.
	 * @return string
	 */
	public static function getSettingsHTML($property, $controlSettings, &$propertyFields)
	{
		$html = '';

		if (!is_array($property['USER_TYPE_SETTINGS']))
		{
			$property['USER_TYPE_SETTINGS'] = array_merge(
				\Bitrix\Crm\Integration\BizProc\FieldType\Crm::getDefaultFieldSettings(),
				static::$listDefaultEntity
			);
		}
		$property['USER_TYPE_SETTINGS']['VISIBLE'] = $property['USER_TYPE_SETTINGS']['VISIBLE'] ?? 'Y';

		$callbackFunction = $controlSettings['CALLBACK_FUNCTION'] ?? 'console.log';
		$settings = $property['USER_TYPE_SETTINGS'];
		$settings['buttonLabel'] = Loc::getMessage('CRM_IBLOCK_PROPERTY_ENTITY_SAVE');
		$settings['settingsName'] = $controlSettings["NAME"];
		$settings['isAssociativeValues'] = true;
		$settings['collectSettingsFunctionName'] = 'WFSFormOptionsECrm';
		$htmlPieces = \Bitrix\Crm\Integration\BizProc\FieldType\Crm::renderSettingsHtmlPieces(
			$callbackFunction,
			$settings
		);

		$useBp = !empty($controlSettings['USE_BP']) && isset($controlSettings['CALLBACK_FUNCTION']);

		$html .= '<tr>';
		if (!$useBp)
		{
			$html .= '<td>'.Loc::getMessage('CRM_IBLOCK_PROPERTY_SETTINGS_LABLE_ENTITY').'</td>';
		}
		$html .= '<td>';
		$html .= $htmlPieces['inputs'];
		$html .= '</td></tr>';

		if($useBp)
		{
			$html .= $htmlPieces['button'];
			$html .= "<script>\n" . $htmlPieces['collectSettingsFunction'] . "\n</script>";
		}
		else
		{
			$html .= '<tr>';
			$html .= '<td>'.Loc::getMessage('CRM_IBLOCK_PROPERTY_SETTINGS_LABLE_VISIBLE').'</td>';
			$html .= '<td><input type="checkbox" name="'.$controlSettings["NAME"].'[VISIBLE]" value="Y" '
				.($property['USER_TYPE_SETTINGS']['VISIBLE'] == "Y" ? 'checked="checked"':'').'></td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * Check fields before inserting into the database.
	 *
	 * @param array $property Property data.
	 * @param $value
	 * @return array An empty array, if no errors.
	 */
	public static function checkFields($property, $value)
	{
		return array();
	}

	/**
	 * Get the length of the value. Checks completion of mandatory.
	 *
	 * @param array $property Property data.
	 * @param $value
	 * @return int
	 */
	public static function getLength($property, $value)
	{
		if(is_array($value['VALUE']))
		{
			$value['VALUE'] = array_diff($value['VALUE'], array(''));
			$value['VALUE'] = implode(',', $value['VALUE']);
			return mb_strlen(trim($value['VALUE'], "\n\r\t"));
		}
		else
		{
			return mb_strlen(trim($value['VALUE'], "\n\r\t"));
		}
	}

	/**
	 * Convert the property value into a format suitable for storage in a database.
	 *
	 * @param array $property Property data.
	 * @param $value
	 * @return mixed
	 */
	public static function convertToDB($property, $value)
	{
		if(is_array($value['VALUE']))
			$value['VALUE'] = serialize($value['VALUE']);
		return $value;
	}

	/**
	 * Convert the value of properties suitable format for storage in a database in the format processing.
	 *
	 * @param array $property Property data.
	 * @param $value
	 * @return mixed
	 */
	public static function convertFromDB($property, $value)
	{
		$unserialize = unserialize($value['VALUE'], ['allowed_classes' => false]);
		if($unserialize !== false)
			$value['VALUE'] = $unserialize;
		return $value;
	}

	/**
	 * Get a printable the entity crm.
	 *
	 * @param array $property Property data.
	 * @param array $listValue List entity id.
	 * @param string $formatSeparator Separator.
	 * @return string
	 */
	public static function getValuePrintable($property, array $listValue, $formatSeparator)
	{
		$result = '';

		$defaultType = '';
		if(is_array($property['USER_TYPE_SETTINGS']))
		{
			foreach($property['USER_TYPE_SETTINGS'] as $typeName => $flag)
			{
				if($flag === 'Y')
				{
					$defaultType = $typeName;
					break;
				}
			}
		}
		if($defaultType === '')
			$defaultType = 'LEAD';

		$valueView = array();
		foreach($listValue as $value)
			static::prepareValueView($value, $defaultType, $valueView);

		foreach($valueView as $entityType => $listEntity)
		{
			$result .= '[b]'.Loc::getMessage('CRM_IBLOCK_PROPERTY_ENTITY_'.$entityType).': [/b]';
			$result .= implode($formatSeparator, $listEntity).' ';
		}

		return $result;
	}

	/**
	 * Add values in filter.
	 *
	 * @param array $property Property data.
	 * @param array $controlSettings Form data.
	 * @param array &$filter Filter data.
	 * @param bool &$filtered Marker filter.
	 * @return void
	 */
	public static function addFilterFields($property, $controlSettings, &$filter, &$filtered)
	{
		$filtered = false;

		if(isset($_REQUEST[$controlSettings['VALUE']]))
		{
			$listEntityValue = $_REQUEST[$controlSettings['VALUE']];
		}
		elseif(isset($controlSettings["FILTER_ID"]))
		{
			$filterOption = new \Bitrix\Main\UI\Filter\Options($controlSettings["FILTER_ID"]);
			$filterData = $filterOption->getFilter();
			if(!empty($filterData[$controlSettings['VALUE']]))
				$listEntityValue = $filterData[$controlSettings['VALUE']];
		}

		if(!empty($listEntityValue))
		{
			$listEntityValue = (array) $listEntityValue;

			$values = [];
			foreach ($listEntityValue as $listEntityVal)
			{
				if (!is_string($listEntityVal))
				{
					continue;
				}

				try
				{
					$value = Json::decode($listEntityVal);
				}
				catch (\Bitrix\Main\ArgumentException $e)
				{
					$value = null;
				}

				if (is_array($value))
				{
					$values = array_merge_recursive($values, $value);
				}
			}
			if(empty($values))
			{
				return;
			}

			$usePrefix = self::isUsePrefix($property);

			$filter[$controlSettings['VALUE']] = array();
			foreach($values as $entityType => $listEntityId)
			{
				if($usePrefix)
				{
					$entityPrefix = \CCrmOwnerTypeAbbr::ResolveByTypeName($entityType);
					foreach($listEntityId as $entityId)
					{
						$filter[$controlSettings['VALUE']][] = $entityPrefix.'_'.$entityId;
					}
				}
				else
				{
					foreach($listEntityId as $entityId)
					{
						$filter[$controlSettings['VALUE']][] = $entityId;
					}
				}
			}
			$filtered = true;
		}
	}

	/**
	 * Returns filter field description.
	 *
	 * @param array $property
	 * @param array $strHTMLControlName
	 * @param array &$field
	 * @return void
	 */
	public static function getUIFilterProperty($property, $strHTMLControlName, &$field)
	{
		$field["type"] = "custom_entity";
		$field["filterable"] = "";
	}

	protected static function prepareValueView($value, $defaultType = '', array &$valueView)
	{
		$parts = explode('_', $value);
		if(count($parts) > 1)
		{
			$entityName = \CCrmOwnerType::getCaption(
				\CCrmOwnerType::resolveID(\CCrmOwnerTypeAbbr::resolveName($parts[0])), $parts[1], false);

			$defaultType = mb_strtolower(static::$listDefaultEntityKey[$parts[0]]);
			$entityUrl = \CComponentEngine::makePathFromTemplate(
				Option::get('crm', 'path_to_'.$defaultType.'_show'), array(''.$defaultType.'_id' => $parts[1]));

			$valueView[mb_strtoupper($defaultType)][] = '[url='.$entityUrl.']'.$entityName.'[/url]';
		}
		elseif($value && $defaultType !== '')
		{
			$entityName = \CCrmOwnerType::getCaption(
				\CCrmOwnerType::resolveID($defaultType),
				$value,
				false
			);

			$defaultType = mb_strtolower($defaultType);
			$entityUrl = \CComponentEngine::makePathFromTemplate(
				Option::get('crm', 'path_to_'.$defaultType.'_show'), array(''.$defaultType.'_id' => $value));

			$valueView[mb_strtoupper($defaultType)][] = '[url='.$entityUrl.']'.$entityName.'[/url]';
		}
	}

	protected static function getEntityForExcelById($property, $listEntityValues)
	{
		$result = '';
		$usePrefix = true;
		$tmpArray = array();
		if(is_array($property['USER_TYPE_SETTINGS']))
		{
			if(array_key_exists('VISIBLE', $property['USER_TYPE_SETTINGS']))
				unset($property['USER_TYPE_SETTINGS']['VISIBLE']);
			$tmpArray = array_filter($property['USER_TYPE_SETTINGS'], function($mark)
			{
				return $mark == "Y";
			});
			if(count($tmpArray) == 1)
			{
				$usePrefix = false;
			}
		}

		$listEntityValue = array();
		$preparedData = array();
		if($usePrefix)
		{
			foreach($listEntityValues as $entityIdWithPrefix)
			{
				$explode = explode('_', $entityIdWithPrefix);
				$entityType = \CCrmOwnerTypeAbbr::resolveName($explode[0]);
				$listEntityValue[$entityType][] = $explode[1];
			}
		}
		else
		{
			$entityType = array_shift(array_keys($tmpArray));
			foreach($listEntityValues as $entityId)
			{
				$listEntityValue[$entityType][] = $entityId;
			}
		}

		foreach($listEntityValue as $entityType => $listEntityId)
		{
			switch($entityType)
			{
				case 'LEAD':
				{
					$queryObject = \CCrmLead::getListEx(array('TITLE' => 'ASC'), array('=ID' => $listEntityId), false,
						false, array('ID', 'TITLE'));
					while($entityData = $queryObject->fetch())
					{
						$preparedData[$entityType][] = array(
							'NAME' => $entityData['TITLE'],
							'LINK' => \CComponentEngine::makePathFromTemplate(Option::get('crm', 'path_to_lead_show'),
								array('lead_id' => $entityData['ID']))
						);
					}
					break;
				}
				case 'CONTACT':
				{
					$queryObject = \CCrmContact::getListEx(array('TITLE' => 'ASC'), array('=ID' => $listEntityId), false,
						false, array('ID', 'FULL_NAME'));
					while($entityData = $queryObject->fetch())
					{
						$preparedData[$entityType][] = array(
							'NAME' => $entityData['FULL_NAME'],
							'LINK' => \CComponentEngine::makePathFromTemplate(Option::get('crm', 'path_to_contact_show'),
								array('contact_id' => $entityData['ID']))
						);
					}
					break;
				}
				case 'COMPANY':
				{
					$queryObject = \CCrmCompany::getListEx(array('TITLE' => 'ASC'), array('ID' => $listEntityId), false,
						false, array('ID', 'TITLE'));
					while($entityData = $queryObject->fetch())
					{
						$preparedData[$entityType][] = array(
							'NAME' => $entityData['TITLE'],
							'LINK' => \CComponentEngine::makePathFromTemplate(Option::get('crm', 'path_to_company_show'),
								array('company_id' => $entityData['ID']))
						);
					}
					break;
				}
				case 'DEAL':
				{
					$queryObject = \CCrmDeal::getListEx(array('TITLE' => 'ASC'), array('ID' => $listEntityId), false,
						false, array('ID', 'TITLE'));
					while($entityData = $queryObject->fetch())
					{
						$preparedData[$entityType][] = array(
							'NAME' => $entityData['TITLE'],
							'LINK' => \CComponentEngine::makePathFromTemplate(Option::get('crm', 'path_to_deal_show'),
								array('deal_id' => $entityData['ID']))
						);
					}
					break;
				}
			}
		}
		foreach($preparedData as $entityType => $listEntityData)
		{
			$result .= Loc::getMessage('CRM_IBLOCK_PROPERTY_ENTITY_'.$entityType).': <br>';
			foreach($listEntityData as $entity)
				$result .= '<a href="'.$entity['LINK'].'">'.htmlspecialcharsbx($entity['NAME']).'</a><br>';
		}
		return $result;
	}

	public static function isUsePrefix(array $property)
	{
		if (is_array($property['USER_TYPE_SETTINGS']))
		{
			if (array_key_exists('VISIBLE', $property['USER_TYPE_SETTINGS']))
				unset($property['USER_TYPE_SETTINGS']['VISIBLE']);
			$tmpArray = array_filter($property['USER_TYPE_SETTINGS'], function($mark)
			{
				return $mark == "Y";
			});
			if (count($tmpArray) == 1)
			{
				return false;
			}
		}

		return true;
	}

	public static function GetUIEntityEditorProperty($settings, $value)
	{
		return [
			'type' => 'custom'
		];
	}

	public static function GetUIEntityEditorPropertyViewHtml(array $params = [])
	{
		if (!empty($params['VALUE']))
		{
			return static::getPublicViewHTML($params['SETTINGS'], ['VALUE' => $params['VALUE']], ['VALUE' => $params['FIELD_NAME']]);
		}

		return '';
	}

	public static function GetUIEntityEditorPropertyEditHtml(array $params = [])
	{
		if (is_array($params['VALUE']))
		{
			$value = [];
			foreach ($params['VALUE'] as $element)
			{
				$value[] = ['VALUE' => $element];
			}
		}
		else
		{
			$value = ['VALUE' => $params['VALUE']];
		}
		return static::getPublicEditHTML($params['SETTINGS'], $value, ['VALUE' => $params['FIELD_NAME']]);
	}
}
