<?php

use Bitrix\Crm\Category\ItemCategoryUserField;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\UserField\Display;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;

IncludeModuleLangFile(__FILE__);

class CCrmUserType
{
	public $sEntityID = '';

	protected $cUFM = null;
	protected $options = [];
	protected $fieldNamePrefix;

	private $arFields = null;
	/** @var array|null  */
	private static $enumerationItems = [];

	public function GetAbstractFields(?array $params = [])
	{
		if($this->arFields === null)
		{
			$this->arFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID, false);

			$this->arFields = $this->postFilterFields($this->arFields);

			if (empty($params['skipUserFieldVisibilityCheck']))
			{
				$this->arFields = $this->postFilterAccessCheck(
					$this->arFields,
					Container::getInstance()->getContext()->getUserId()
				);
			}

			$this->arFields = $this->appendNamePrefix($this->arFields);
		}

		return $this->arFields;
	}

	public function GetFields(array $params = [])
	{
		return $this->GetAbstractFields($params);
	}
	public function GetFieldNames()
	{
		$results = array();
		$fields = $this->GetAbstractFields();
		foreach($fields as $field)
		{
			$label = isset($field['EDIT_FORM_LABEL']) ? $field['EDIT_FORM_LABEL'] : '';

			if($label === '')
			{
				$label = isset($field['LIST_COLUMN_LABEL']) ? $field['LIST_COLUMN_LABEL'] : '';
			}

			if($label === '')
			{
				$label = $field['FIELD_NAME'];
			}

			$results[$field['FIELD_NAME']] = $label;
		}
		return $results;
	}

	protected function GetUserFields($entity_id, $value_id = 0, $LANG = false, $user_id = false)
	{
		$result = $this->cUFM->GetUserFields($entity_id, $value_id, $LANG, $user_id);

		$isReread = false;
		if($this->isMyCompany())
		{
			$obUserType = false;
			foreach(CCrmCompany::getMyCompanyAdditionalUserFields() as $ufieldName => $description)
			{
				if(!isset($result[$ufieldName]))
				{
					if(!$obUserType)
					{
						$obUserType = new \CUserTypeEntity();
					}
					$obUserType->Add($description);
					$isReread = true;
				}
			}
		}

		if($isReread)
		{
			$result = $this->cUFM->GetUserFields($entity_id, $value_id, $LANG, $user_id);
		}

		$result = $this->postFilterFields($result);
		$result = $this->postFilterAccessCheck($result, $user_id);

		return $result;
	}

	function __construct(CUserTypeManager $cUFM, $sEntityID, $options = [])
	{
		$this->cUFM = $cUFM;
		$this->sEntityID = $sEntityID;
		$this->options = $options;
	}

	/**
	 * @param array $options
	 *
	 * @return $this
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * @param array $option
	 *
	 * @return $this
	 */
	public function setOption(array $option)
	{
		$this->options = array_merge($this->options, $option);

		return $this;
	}

	public function PrepareListFilterValues(array &$arFilterFields, array $arFilter = null, $sFormName = 'form1', $bVarsFromForm = true)
	{
		global $APPLICATION;
		$arUserFields = $this->GetAbstractFields();
		foreach($arFilterFields as &$arField)
		{
			$fieldID = $arField['id'];
			if(!isset($arUserFields[$fieldID]))
			{
				continue;
			}

			$arUserField = $arUserFields[$fieldID];
			if($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'employee')
			{
				continue;
			}

			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' ||
				$arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_element' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_section')
			{
				// Fix #29649. Allow user to add not multiple fields with height 1 item.
				if($arUserField['MULTIPLE'] !== 'Y'
					&& isset($arUserField['SETTINGS']['LIST_HEIGHT'])
					&& intval($arUserField['SETTINGS']['LIST_HEIGHT']) > 1)
				{
					$arUserField['MULTIPLE'] = 'Y';
				}

				//as the system presets the filter can not work with the field names containing []
				if ($arUserField['SETTINGS']['DISPLAY'] == 'CHECKBOX')
					$arUserField['SETTINGS']['DISPLAY'] = '';
			}

			$params = array(
				'arUserField' => $arUserField,
				'arFilter' => $arFilter,
				'bVarsFromForm' => $bVarsFromForm,
				'form_name' => 'filter_'.$sFormName,
				'bShowNotSelected' => true
			);

			$userType = $arUserField['USER_TYPE']['USER_TYPE_ID'];
			$templateName = $userType;
			if($userType === 'date')
			{
				$templateName = 'datetime';
				$params['bShowTime'] = false;
			}

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:crm.field.filter',
				$templateName,
				$params,
				false,
				array('HIDE_ICONS' => true)
			);
			$sVal = ob_get_contents();
			ob_end_clean();

			$arField['value'] = $sVal;
		}
		unset($field);
	}

	public function PrepareListFilterFields(&$arFilterFields, &$arFilterLogic, $fieldsParams = [])
	{
		$arUserFields = $this->GetAbstractFields($fieldsParams);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if ($arUserField['SHOW_FILTER'] === 'N' || $arUserField['USER_TYPE']['BASE_TYPE'] === 'file')
			{
				continue;
			}

			$ID = $arUserField['ID'];
			$typeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];
			$isMultiple = isset($arUserField['MULTIPLE']) && $arUserField['MULTIPLE'] === 'Y';

			$fieldLabel = isset($arUserField['LIST_FILTER_LABEL']) ? $arUserField['LIST_FILTER_LABEL'] : '';
			if($fieldLabel === '')
			{
				if(isset($userField['LIST_COLUMN_LABEL']))
				{
					$fieldLabel = $arUserField['LIST_COLUMN_LABEL'];
				}
				elseif(isset($userField['EDIT_FORM_LABEL']))
				{
					$fieldLabel = $arUserField['EDIT_FORM_LABEL'];
				}
			}

			if($typeID === 'employee')
			{
				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'dest_selector',
					'params' => array(
						'context' => 'CRM_UF_FILTER_'.$FIELD_NAME,
						'multiple' => 'N',
						'contextCode' => 'U',
						'enableAll' => 'N',
						'enableSonetgroups' => 'N',
						'allowEmailInvitation' => 'N',
						'allowSearchEmailUsers' => 'N',
						'departmentSelectDisable' => 'Y',
						'isNumeric' => 'Y',
						'prefix' => 'U',
					)

				);
				continue;
			}
			elseif($typeID === 'string' || $typeID === 'url' || $typeID === 'address' || $typeID === 'money')
			{
				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'text'
				);
				continue;
			}
			elseif($typeID === 'integer' || $typeID === 'double')
			{
				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'number'
				);
				continue;
			}
			elseif($typeID === 'boolean')
			{
				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'checkbox',
					'valueType' => 'numeric'
				);
				continue;
			}
			elseif($typeID === 'datetime' || $typeID === 'date')
			{
				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'date',
					'time' => $typeID === 'datetime'
				);
				continue;
			}
			elseif($typeID === 'enumeration')
			{
				$enumEntity = new \CUserFieldEnum();
				$dbResultEnum = $enumEntity->GetList(
					array('SORT' => 'ASC'),
					array('USER_FIELD_ID' => $ID)
				);

				$listItems = array();
				while($enum = $dbResultEnum->Fetch())
				{
					$listItems[$enum['ID']] = $enum['VALUE'];
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'list',
					'params' => array('multiple' => 'Y'),
					'items' => $listItems
				);
				continue;
			}
			elseif($typeID === 'iblock_element')
			{
				$listItems = array();
				$enity = new CUserTypeIBlockElement();
				$dbResult = $enity->GetList($arUserField);
				if(is_object($dbResult))
				{
					$qty = 0;
					$limit = 200;

					while($ary = $dbResult->Fetch())
					{
						$listItems[$ary['ID']] = $ary['NAME'];
						$qty++;
						if($qty === $limit)
						{
							break;
						}
					}
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'list',
					'params' => array('multiple' => 'Y'),
					'items' => $listItems
				);
				continue;
			}
			elseif($typeID === 'iblock_section')
			{
				$listItems = array();
				$enity = new CUserTypeIBlockSection();
				$dbResult = $enity->GetList($arUserField);

				if(is_object($dbResult))
				{
					$qty = 0;
					$limit = 200;

					while($ary = $dbResult->Fetch())
					{
						$listItems[$ary['ID']] = isset($ary['DEPTH_LEVEL']) && $ary['DEPTH_LEVEL']  > 1
							? str_repeat('. ', ($ary['DEPTH_LEVEL'] - 1)).$ary['NAME'] : $ary['NAME'];
						$qty++;
						if($qty === $limit)
						{
							break;
						}
					}
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'list',
					'params' => array('multiple' => 'Y'),
					'items' => $listItems
				);
				continue;
			}
			elseif($typeID === 'crm')
			{
				$settings = isset($arUserField['SETTINGS']) && is_array($arUserField['SETTINGS'])
					? $arUserField['SETTINGS'] : array();

				$entityTypeNames = array();
				$supportedEntityTypeNames = array(
					CCrmOwnerType::LeadName,
					CCrmOwnerType::DealName,
					CCrmOwnerType::ContactName,
					CCrmOwnerType::CompanyName,
					CCrmOwnerType::OrderName
				);
				foreach($supportedEntityTypeNames as $entityTypeName)
				{
					if(isset($settings[$entityTypeName]) && $settings[$entityTypeName] === 'Y')
					{
						$entityTypeNames[] = $entityTypeName;
					}
				}

				$destSelectorParams = array(
					'apiVersion' => 3,
					'context' => 'CRM_UF_FILTER_ENTITY',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'enableCrm' => 'Y',
					'multiple' => ($isMultiple ? 'Y' : 'N'),
					'convertJson' => 'Y'
				);

				$entityTypeCounter = 0;
				foreach($entityTypeNames as $entityTypeName)
				{
					switch($entityTypeName)
					{
						case \CCrmOwnerType::LeadName:
							$destSelectorParams['enableCrmLeads'] = 'Y';
							$destSelectorParams['addTabCrmLeads'] = 'Y';
							$entityTypeCounter++;
							break;
						case \CCrmOwnerType::DealName:
							$destSelectorParams['enableCrmDeals'] = 'Y';
							$destSelectorParams['addTabCrmDeals'] = 'Y';
							$entityTypeCounter++;
							break;
						case \CCrmOwnerType::ContactName:
							$destSelectorParams['enableCrmContacts'] = 'Y';
							$destSelectorParams['addTabCrmContacts'] = 'Y';
							$entityTypeCounter++;
							break;
						case \CCrmOwnerType::CompanyName:
							$destSelectorParams['enableCrmCompanies'] = 'Y';
							$destSelectorParams['addTabCrmCompanies'] = 'Y';
							$entityTypeCounter++;
							break;
						case \CCrmOwnerType::OrderName:
							$destSelectorParams['enableCrmOrders'] = 'Y';
							$destSelectorParams['addTabCrmOrders'] = 'Y';
							$entityTypeCounter++;
							break;
						default:
					}
				}
				if ($entityTypeCounter <= 1)
				{
					$destSelectorParams['addTabCrmLeads'] = 'N';
					$destSelectorParams['addTabCrmDeals'] = 'N';
					$destSelectorParams['addTabCrmContacts'] = 'N';
					$destSelectorParams['addTabCrmCompanies'] = 'N';
					$destSelectorParams['addTabCrmOrders'] = 'N';
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'dest_selector',
					'params' => $destSelectorParams
				);
				continue;
			}
			elseif($typeID === 'crm_status')
			{
				$listItems = array();
				if(isset($arUserField['SETTINGS'])
					&& is_array($arUserField['SETTINGS'])
					&& isset($arUserField['SETTINGS']['ENTITY_TYPE'])
				)
				{
					$entityType = $arUserField['SETTINGS']['ENTITY_TYPE'];
					if($entityType !== '')
					{
						$listItems = CCrmStatus::GetStatusList($entityType);
					}
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'list',
					'params' => array('multiple' => 'Y'),
					'items' => $listItems
				);
				continue;
			}

			$arFilterFields[] = array(
				'id' => $FIELD_NAME,
				'name' => htmlspecialcharsex($fieldLabel),
				'type' => 'custom',
				'value' => ''
			);

			// Fix issue #49771 - do not treat 'crm' type values as strings. To suppress filtration by LIKE.
			// Fix issue #56844 - do not treat 'crm_status' type values as strings. To suppress filtration by LIKE.
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'string' && $arUserField['USER_TYPE']['USER_TYPE_ID'] !== 'crm' && $arUserField['USER_TYPE']['USER_TYPE_ID'] !== 'crm_status')
				$arFilterLogic[] = $FIELD_NAME;
		}
	}

	public function ListAddFilterFields(&$arFilterFields, &$arFilterLogic, $sFormName = 'form1', $bVarsFromForm = true)
	{
		global $APPLICATION;
		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$userTypeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];
			if ($arUserField['SHOW_FILTER'] != 'N' && $arUserField['USER_TYPE']['BASE_TYPE'] != 'file')
			{
				if($userTypeID === 'employee')
				{
					$arFilterFields[] = array(
						'id' => $FIELD_NAME,
						'name' => htmlspecialcharsex($arUserField['LIST_FILTER_LABEL']),
						'type' => 'user',
						'enable_settings' => false
					);
					continue;
				}

				if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' ||
					$userTypeID == 'iblock_element' || $userTypeID == 'iblock_section')
				{
					// Fix #29649. Allow user to add not multiple fields with height 1 item.
					if($arUserField['MULTIPLE'] !== 'Y'
						&& isset($arUserField['SETTINGS']['LIST_HEIGHT'])
						&& intval($arUserField['SETTINGS']['LIST_HEIGHT']) > 1)
					{
						$arUserField['MULTIPLE'] = 'Y';
					}

					//as the system presets the filter can not work with the field names containing []
					if ($arUserField['SETTINGS']['DISPLAY'] == 'CHECKBOX')
						$arUserField['SETTINGS']['DISPLAY'] = '';
				}

				$templateName = $userTypeID;
				$params = array(
					'arUserField' => $arUserField,
					'bVarsFromForm' => $bVarsFromForm,
					'form_name' => 'filter_'.$sFormName,
					'bShowNotSelected' => true
				);

				if($templateName === 'date')
				{
					$templateName = 'datetime';
					$params['bShowTime'] = false;
				}

				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:crm.field.filter',
					$templateName,
					$params,
					false,
					array('HIDE_ICONS' => true)
				);
				$sVal = ob_get_contents();
				ob_end_clean();

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => htmlspecialcharsex($arUserField['LIST_FILTER_LABEL']),
					'type' => 'custom',
					'value' => $sVal
				);

				// Fix issue #49771 - do not treat 'crm' type values as strings. To suppress filtration by LIKE.
				// Fix issue #56844 - do not treat 'crm_status' type values as strings. To suppress filtration by LIKE.
				if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'string' && $userTypeID !== 'crm' && $userTypeID !== 'crm_status')
					$arFilterLogic[] = $FIELD_NAME;
			}
		}
	}

	public function GetEntityFields($ID, $userId = false)
	{
		return $this->GetUserFields($this->sEntityID, $ID, LANGUAGE_ID, $userId);
	}
	public function AddFields(&$arFilterFields, $ID, $sFormName = 'form1', $bVarsFromForm = false, $bShow = false, $bParentComponent = false, $arOptions = array())
	{
		global $APPLICATION;

		$arOptions = is_array($arOptions) ? $arOptions : array();
		/** @var \Bitrix\Crm\UserField\FileViewer|null $fileViewer */
		$fileViewer = isset($arOptions['FILE_VIEWER']) ? $arOptions['FILE_VIEWER'] : null;
		$fileUrlTemplate = isset($arOptions['FILE_URL_TEMPLATE']) ? $arOptions['FILE_URL_TEMPLATE'] : '';

		$skipRendering = isset($arOptions['SKIP_RENDERING']) ? $arOptions['SKIP_RENDERING'] : array();
		$isTactile = isset($arOptions['IS_TACTILE']) ? $arOptions['IS_TACTILE'] : false;
		$defaultValues = isset($arOptions['DEFAULT_VALUES']) ? $arOptions['DEFAULT_VALUES'] : array();
		$fieldNameTemplate = isset($arOptions['FIELD_NAME_TEMPLATE']) ? $arOptions['FIELD_NAME_TEMPLATE'] : '';

		try
		{
			$arUserFields = $this->GetUserFields($this->sEntityID, $ID, LANGUAGE_ID);
		}
		catch (\Bitrix\Main\ObjectException $e)
		{
			$arUserFields = array();
		}

		$count = 0;
		foreach($arUserFields as $FIELD_NAME => &$arUserField)
		{
			if(!isset($arUserField['ENTITY_VALUE_ID']))
			{
				$arUserField['ENTITY_VALUE_ID'] = intval($ID);
			}

			$viewMode = $bShow;
			if(!$viewMode && $arUserField['EDIT_IN_LIST'] === 'N')
			{
				//Editing is not allowed for this field
				$viewMode = true;
			}
			$userTypeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];

			if(in_array($userTypeID, $skipRendering, true))
			{
				$value = isset($arUserField['VALUE']) ? $arUserField['VALUE'] : '';
				if($userTypeID === 'string' || $userTypeID === 'double')
				{
					$fieldType = 'text';
				}
				elseif($userTypeID === 'boolean')
				{
					$fieldType = 'checkbox';
					$value = intval($value) > 0 ? 'Y' : 'N';
				}
				elseif($userTypeID === 'datetime')
				{
					$fieldType = 'date';
				}
				else
				{
					$fieldType = $userTypeID;
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => ('' != $arUserField['EDIT_FORM_LABEL'] ? $arUserField['EDIT_FORM_LABEL'] : $arUserField['FIELD_NAME']),
					'type' => $fieldType,
					'value' => $value,
					'required' => !$viewMode && $arUserField['MANDATORY'] == 'Y' ? true : false,
					'isTactile' => $isTactile
				);
			}
			else
			{
				if(isset($defaultValues[$FIELD_NAME]))
				{
					$arUserField['VALUE'] = $defaultValues[$FIELD_NAME];

					if(isset($arUserField['SETTINGS']) && isset($arUserField['SETTINGS']['DEFAULT_VALUE']))
					{
						if(!is_array($arUserField['SETTINGS']['DEFAULT_VALUE']))
						{
							$arUserField['SETTINGS']['DEFAULT_VALUE'] = $defaultValues[$FIELD_NAME];
						}
						elseif(isset($arUserField['SETTINGS']['DEFAULT_VALUE']['VALUE']))
						{
							$arUserField['SETTINGS']['DEFAULT_VALUE']['VALUE'] = $defaultValues[$FIELD_NAME];
						}
					}
				}

				if ($userTypeID === 'employee')
				{
					if ($viewMode)
					{
						if (!is_array($arUserField['VALUE']))
							$arUserField['VALUE'] = array($arUserField['VALUE']);
						ob_start();
						foreach ($arUserField['VALUE'] as $k)
						{
							$APPLICATION->IncludeComponent('bitrix:main.user.link',
								'',
								array(
									'ID' => $k,
									'HTML_ID' => 'crm_'.$FIELD_NAME,
									'USE_THUMBNAIL_LIST' => 'Y',
									'SHOW_YEAR' => 'M',
									'CACHE_TYPE' => 'A',
									'CACHE_TIME' => '3600',
									'NAME_TEMPLATE' => '',//$arParams['NAME_TEMPLATE'],
									'SHOW_LOGIN' => 'Y',
								),
								false,
								array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
							);
						}
						$sVal = ob_get_contents();
						ob_end_clean();
					}
					else
					{
						$val = !$bVarsFromForm ? $arUserField['VALUE'] : (isset($GLOBALS[$FIELD_NAME]) ? $GLOBALS[$FIELD_NAME] : '');
						$val_string = '';
						if (is_array($val))
							foreach ($val as $_val)
							{	if (empty($_val))
									continue;
								$rsUser = CUser::GetByID($_val);
								$val_string .=  CUser::FormatName(CSite::GetNameFormat(false).' [#ID#], ', $rsUser->Fetch(), true, false);
							}
						else if (!empty($val))
						{
							$rsUser = CUser::GetByID($val);
							$val_string .=  CUser::FormatName(CSite::GetNameFormat(false).' [#ID#], ', $rsUser->Fetch(), true, false);
						}
						ob_start();
						$GLOBALS['APPLICATION']->IncludeComponent('bitrix:intranet.user.selector',
							'',
							array(
								'INPUT_NAME' => $FIELD_NAME,
								'INPUT_VALUE' => $val,
								'INPUT_VALUE_STRING' => $val_string,
								'MULTIPLE' => $arUserField['MULTIPLE']
							),
							false,
							array('HIDE_ICONS' => 'Y')
						);
						$sVal = ob_get_contents();
						ob_end_clean();
					}
				}
				else
				{
					if($viewMode && $userTypeID === 'file' && ($fileViewer || $fileUrlTemplate !== ''))
					{
						// In view mode we have to use custom rendering for hide real file URL's ('bitrix:system.field.view' can't do it)
						$fileIDs = isset($arUserField['VALUE'])
							? (is_array($arUserField['VALUE'])
								? $arUserField['VALUE']
								: array($arUserField['VALUE']))
							: array();

						$fieldUrlTemplate = $fileViewer !== null
							? $fileViewer->getUrl($ID, $FIELD_NAME)
							: CComponentEngine::MakePathFromTemplate(
								$fileUrlTemplate,
								array('owner_id' => $ID, 'field_name' => $FIELD_NAME)
							);

						ob_start();
						CCrmViewHelper::RenderFiles($fileIDs, $fieldUrlTemplate, 480, 480);
						$sVal = ob_get_contents();
						ob_end_clean();
					}
					else
					{
						$fieldUrlTemplate = $fileViewer !== null
							? $fileViewer->getUrl($ID, $FIELD_NAME)
							: CComponentEngine::MakePathFromTemplate(
								$fileUrlTemplate,
								array('owner_id' => $ID, 'field_name' => $FIELD_NAME)
							);

						if($fieldNameTemplate !== '')
						{
							$arUserField['FIELD_NAME'] = str_replace('#FIELD_NAME#', $FIELD_NAME, $fieldNameTemplate);
						}

						ob_start();
						$APPLICATION->IncludeComponent(
							'bitrix:system.field.'.($viewMode ? 'view' : 'edit'),
							$userTypeID,
							array(
								'arUserField' => $arUserField,
								'bVarsFromForm' => $bVarsFromForm,
								'form_name' => 'form_'.$sFormName,
								'FILE_MAX_HEIGHT' => 480,
								'FILE_MAX_WIDTH' => 480,
								'FILE_SHOW_POPUP' => true,
								'SHOW_FILE_PATH' => false,
								'SHOW_NO_VALUE' => true,
								'FILE_URL_TEMPLATE' => $fieldUrlTemplate,
							),
							false,
							array('HIDE_ICONS' => 'Y')
						);
						$sVal = ob_get_contents();
						ob_end_clean();
					}
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					//'name' => htmlspecialcharsbx($arUserField['EDIT_FORM_LABEL']),
					'name' => ('' != $arUserField['EDIT_FORM_LABEL'] ? $arUserField['EDIT_FORM_LABEL'] : $arUserField['FIELD_NAME']),
					'type' => 'custom',
					'value' => $sVal,
					'required' => !$viewMode && $arUserField['MANDATORY'] == 'Y' ? true : false,
					'isTactile' => $isTactile
				);
			}
			$count++;
		}
		unset($arUserField);

		return $count;
	}

	private static function TryResolveEnumerationID($value, &$enums, &$ID, $fieldName = 'VALUE')
	{
		$fieldName = strval($fieldName);
		if($fieldName === '')
		{
			$fieldName = 'VALUE';
		}

		// 1. Try to interpret value as enum ID
		if(isset($enums[$value]))
		{
			$ID = $value;
			return true;
		}

		// 2. Try to interpret value as enum VALUE or XML_ID
		$uv = mb_strtoupper(trim($value));

		$success = false;
		foreach($enums as $enumID => &$enum)
		{
			if(mb_strtoupper($enum[$fieldName]) === $uv || (isset($enum['XML_ID']) && $enum['XML_ID'] === $value))
			{
				$ID = $enumID;
				$success = true;
				break;
			}
		}
		unset($enum);
		return $success;
	}

	private static function InternalizeEnumValue(&$value, &$enums, $fieldName = 'VALUE')
	{
		$enumID = '';
		if(is_array($value))
		{
			foreach($value as $k => $v)
			{
				if(self::TryResolveEnumerationID($v, $enums, $enumID, $fieldName))
				{
					$value[$k] = $enumID;
				}
				else
				{
					unset($value[$k]);
				}
			}
		}
		elseif(is_string($value) && $value !== '')
		{
			if(self::TryResolveEnumerationID($value, $enums, $enumID, $fieldName))
			{
				$value = $enumID;
			}
			else
			{
				$value = '';
			}
		}
	}

	private static function TryInternalizeCrmEntityID($type, $value, &$ID)
	{
		if($value === '')
		{
			return false;
		}

		if(preg_match('/^\[([A-Z]+)\]/i', $value, $m) > 0)
		{
			$valueType = CCrmOwnerType::Undefined;
			$prefix = mb_strtoupper($m[1]);
			if($prefix === 'L')
			{
				$valueType = CCrmOwnerType::Lead;
			}
			elseif($prefix === 'C')
			{
				$valueType = CCrmOwnerType::Contact;
			}
			elseif($prefix === 'CO')
			{
				$valueType = CCrmOwnerType::Company;
			}
			elseif($prefix === 'D')
			{
				$valueType = CCrmOwnerType::Deal;
			}
			elseif($prefix === 'O')
			{
				$valueType = CCrmOwnerType::Order;
			}

			if($valueType !== CCrmOwnerType::Undefined && $valueType !== $type)
			{
				return false;
			}

			$value = mb_substr($value, mb_strlen($m[0]));
		}

		// 1. Try to interpret data as entity ID
		// 2. Try to interpret data as entity name
		if($type === CCrmOwnerType::Lead)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmLead::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmLead::GetListEx(array(), array('=TITLE'=> $value), false, false, array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}
		}
		elseif($type === CCrmOwnerType::Contact)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmContact::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			// Try to interpret value as FULL_NAME
			$rsEntities = CCrmContact::GetListEx(array(), array('=FULL_NAME'=> $value, '@CATEGORY_ID' => 0,), false, false, array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}

			if(preg_match('/\s*([^\s]+)\s+([^\s]+)\s*/', $value, $match) > 0)
			{
				// Try to interpret value as '#NAME# #LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=NAME'=> $match[1], '=LAST_NAME'=> $match[2], '@CATEGORY_ID' => 0,),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}

				// Try to interpret value as '#LAST_NAME# #NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $match[1], '=NAME'=> $match[2], '@CATEGORY_ID' => 0,),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}
			else
			{
				// Try to interpret value as '#LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $value, '@CATEGORY_ID' => 0,),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}
		}
		elseif($type === CCrmOwnerType::Company)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmCompany::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmCompany::GetList(array(), array('=TITLE'=> $value, '@CATEGORY_ID' => 0,), array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}
		}
		elseif($type === CCrmOwnerType::Deal)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmDeal::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmDeal::GetList(array(), array('=TITLE'=> $value), array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}
		}
		return false;
	}

	private static function InternalizeCrmEntityValue(&$value, array $field)
	{
		$settings = isset($field['SETTINGS']) ? $field['SETTINGS'] : null;
		if(!is_array($settings))
		{
			return;
		}

		$isContactEnabled = isset($settings['CONTACT']) && mb_strtoupper($settings['CONTACT']) === 'Y';
		$isCompanyEnabled = isset($settings['COMPANY']) && mb_strtoupper($settings['COMPANY']) === 'Y';
		$isLeadEnabled = isset($settings['LEAD']) && mb_strtoupper($settings['LEAD']) === 'Y';
		$isDealEnabled = isset($settings['DEAL']) && mb_strtoupper($settings['DEAL']) === 'Y';

		if(is_array($value))
		{
			foreach($value as $k => $v)
			{
				$entityID = 0;
				if($isLeadEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Lead, $v, $entityID))
				{
					$value[$k] = ($isContactEnabled || $isCompanyEnabled || $isDealEnabled)
						? "L_{$entityID}" : "{$entityID}";
				}
				elseif($isContactEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Contact, $v, $entityID))
				{
					$value[$k] = ($isCompanyEnabled || $isLeadEnabled || $isDealEnabled)
						? "C_{$entityID}" : "{$entityID}";
				}
				elseif($isCompanyEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Company, $v, $entityID))
				{
					$value[$k] = ($isContactEnabled || $isLeadEnabled || $isDealEnabled)
						? "CO_{$entityID}" : "{$entityID}";
				}
				elseif($isDealEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Deal, $v, $entityID))
				{
					$value[$k] = ($isContactEnabled || $isCompanyEnabled || $isLeadEnabled)
						? "D_{$entityID}" : "{$entityID}";
				}
			}
		}
		elseif(is_string($value) && $value !== '')
		{
			$entityID = 0;
			if($isLeadEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Lead, $value, $entityID))
			{
				$value = ($isContactEnabled || $isCompanyEnabled || $isDealEnabled)
					? "L_{$entityID}" : "{$entityID}";
			}
			elseif($isContactEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Contact, $value, $entityID))
			{
				$value = ($isCompanyEnabled || $isLeadEnabled || $isDealEnabled)
					? "C_{$entityID}" : "{$entityID}";
			}
			elseif($isCompanyEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Company, $value, $entityID))
			{
				$value = ($isContactEnabled || $isLeadEnabled || $isDealEnabled)
					? "CO_{$entityID}" : "{$entityID}";
			}
			elseif($isDealEnabled && self::TryInternalizeCrmEntityID(CCrmOwnerType::Deal, $value, $entityID))
			{
				$value = ($isContactEnabled || $isCompanyEnabled || $isLeadEnabled)
					? "D_{$entityID}" : "{$entityID}";
			}
		}
	}

	public function Internalize($name, $data, $delimiter = ',', $arUserField = null)
	{
		$delimiter = strval($delimiter);
		if($delimiter === '')
		{
			$delimiter = ',';
		}

		if(!$arUserField)
		{
			$arUserFields = $this->GetAbstractFields();
			$arUserField = isset($arUserFields[$name]) ? $arUserFields[$name] : null;
		}

		if(!$arUserField)
		{
			return $data; // return original data
		}

		$isMultiple = $arUserField['MULTIPLE'] === 'Y';
		if($isMultiple && !is_array($data))
		{
			$data = array_filter(array_map('trim', explode($delimiter, $data)));
		}

		$typeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];
		if($typeID === 'file')
		{
			$result = null;
			if(!$isMultiple)
			{
				if(CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_UPLOAD' => true)))
				{
					$result = $file;
				}
			}
			elseif(is_array($data))
			{
				$files = array();
				foreach($data as $datum)
				{
					if(CCrmFileProxy::TryResolveFile($datum, $file, array('ENABLE_UPLOAD' => true)))
					{
						$files[] = $file;
					}
				}
				$result = $files;
			}
			$data = $result;
		}
		elseif($typeID === 'enumeration')
		{
			// Processing for type 'enumeration'

			$enums = array();
			$rsEnum = CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => $arUserField['ID']));
			while ($arEnum = $rsEnum->Fetch())
			{
				$enums[$arEnum['ID']] = $arEnum;
			}

			self::InternalizeEnumValue($data, $enums);

		}
		elseif($typeID === 'employee')
		{
			// Processing for type 'employee' (we have to implement custom processing since CUserTypeEmployee::GetList doesn't provide VALUE property)
			$enums = array();

			$rsEnum = CUser::GetList(
				'last_name',
				'asc',
				array(),
				array('FIELDS' => array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL'))
			);
			while ($arEnum = $rsEnum->Fetch())
			{
				$arEnum['VALUE'] = CUser::FormatName(CSite::GetNameFormat(false), $arEnum, false, true);
				$enums[$arEnum['ID']] = $arEnum;
			}

			self::InternalizeEnumValue($data, $enums);
		}
		elseif($typeID === 'crm')
		{
			// Processing for type 'crm' (is link to LEAD, CONTACT, COMPANY or DEAL)
			self::InternalizeCrmEntityValue($data, $arUserField);
		}
		elseif($typeID === 'boolean')
		{
			$yes = mb_strtoupper(GetMessage('MAIN_YES'));
			//$no = strtoupper(GetMessage('MAIN_NO'));

			if(is_array($data))
			{
				foreach($data as &$v)
				{
					$s = mb_strtoupper($v);
					$v = ($s === $yes || $s === 'Y' || $s === 'YES' || (is_numeric($s) && intval($s) > 0)) ? 1 : 0;
				}
				unset($v);
			}
			elseif(is_string($data) && $data !== '')
			{
				$s = mb_strtoupper($data);
				$data = ($s === $yes || $s === 'Y' || $s === 'YES' || (is_numeric($s) && intval($s) > 0)) ? 1 : 0;
			}
			elseif(isset($arUserField['SETTINGS']['DEFAULT_VALUE']))
			{
				$data = $arUserField['SETTINGS']['DEFAULT_VALUE'];
			}
			else
			{
				$data = 0;
			}
		}
		elseif($typeID === 'datetime')
		{
			if(is_array($data))
			{
				foreach($data as &$v)
				{
					if(!CheckDateTime($v))
					{
						$timestamp = strtotime($v);
						$v = is_int($timestamp) && $timestamp > 0 ? ConvertTimeStamp($timestamp, 'FULL') : '';
					}
				}
				unset($v);
			}
			elseif(is_string($data) && $data !== '')
			{
				if(!CheckDateTime($data))
				{
					$timestamp = strtotime($data);
					$data = is_int($timestamp) && $timestamp > 0 ? ConvertTimeStamp($timestamp, 'FULL') : '';
				}
			}
		}
		elseif(is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'getlist')))
		{
			// Processing for type user defined class

			$rsEnum = call_user_func_array(
				array($arUserField['USER_TYPE']['CLASS_NAME'], 'getlist'),
				array($arUserField)
			);

			$enums = array();
			if (is_object($rsEnum))
			{
				while ($arEnum = $rsEnum->GetNext())
				{
					$enums[strval($arEnum['ID'])] = $arEnum;
				}
			}

			$fieldName = 'VALUE';
			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'iblock_section'
				|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'iblock_element')
			{
				$fieldName = '~NAME';
			}

			self::InternalizeEnumValue($data, $enums, $fieldName);
		}
		return $data;
	}

	public function ListAddEnumFieldsValue(
		$arParams,
		&$rawValues,
		&$preparedValues,
		$delimiter = '<br />',
		$isInExportMode = false,
		$options = []
	)
	{
		$options = is_array($options) ? $options : [];
		if (isset($arParams['GRID_ID']))
		{
			$options['GRID_ID'] = $arParams['GRID_ID'];
		}
		if (!is_array($preparedValues))
		{
			$preparedValues = [];
		}
		if (!is_array($rawValues))
		{
			$rawValues = [];
		}

		$rawValues = $this->normalizeBooleanValues($rawValues);

		$entityTypeId = CCrmOwnerType::ResolveIDByUFEntityID($this->sEntityID);
		$display = Display::createByEntityTypeId($entityTypeId);
		$displayOptions =
			\Bitrix\Crm\Service\Display\Options::createFromArray($options)
				->setMultipleFieldsDelimiter((string)$delimiter)
		;
		$context = ($isInExportMode ? Field::EXPORT_CONTEXT : Field::GRID_CONTEXT);
		$strategy = (new \Bitrix\Crm\UserField\DisplayStrategy\BulkStrategy($entityTypeId))->setContext($context);
		$strategy->setDisplayOptions($displayOptions);

		$display->setStrategy($strategy);

		foreach($rawValues as $itemId => $itemFields)
		{
			$display->addValues((int)$itemId, (array)$itemFields);
		}
		$result = $display->getAllValues();

		foreach ($result as $id => $values)
		{
			$preparedValues[$id] = array_merge_recursive(
				(is_array($preparedValues[$id]) ? $preparedValues[$id] : []),
				$values
			);
		}
	}

	public function normalizeBooleanValues(array $values): array
	{
		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $FIELD_NAME => &$arUserField)
		{
			$isMultiple = $arUserField['MULTIPLE'] == 'Y';
			foreach ($values as $ID => $data)
			{
				if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
				{
					$arVal = $values[$ID][$FIELD_NAME] ?? '';
					if (!is_array($arVal))
					{
						$arVal = [$arVal];
					}

					foreach ($arVal as $val)
					{
						$val = (string)$val;

						if ($val == '')
						{
							//Empty value is always 'N' (not default field value)
							$val = 'N';
						}

						if ($isMultiple)
						{
							$values[$ID][$FIELD_NAME][] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';
						}
						else
						{
							$values[$ID][$FIELD_NAME] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';
						}
					}
				}
			}
		}

		return $values;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Component\EntityList\UserField\GridHeaders::append
	 */
	public function ListAddHeaders(&$arHeaders, $bImport = false)
	{
		(new \Bitrix\Crm\Component\EntityList\UserField\GridHeaders($this))
			->setForImport((bool)$bImport)
			->setWithEnumFieldValues(true)
			->append($arHeaders)
		;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Component\EntityList\UserField\GridHeaders::append
	 */
	public function appendGridHeaders(array &$headers): void
	{
		(new \Bitrix\Crm\Component\EntityList\UserField\GridHeaders($this))
			->setWithEnumFieldValues(false)
			->setWithHtmlSpecialchars(false)
			->append($headers)
		;
	}

	// Get Fields Metadata
	public function PrepareFieldsInfo(&$fieldsInfo, array $params = [])
	{
		$arUserFields = $this->GetAbstractFields($params);

		$enumFields = [];
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$userTypeID = $arUserField['USER_TYPE_ID'];
			$settings = isset($arUserField['SETTINGS']) ? $arUserField['SETTINGS'] : [];
			$userType = ($arUserField['USER_TYPE'] ?? []);

			$title = $arUserField['EDIT_FORM_LABEL']
				?? $arUserField['LIST_COLUMN_LABEL']
				?? $arUserField['LIST_FILTER_LABEL']
				?? $FIELD_NAME;

			$info = [
				'TITLE' => $title,
				'TYPE' => $userTypeID,
				'ATTRIBUTES' => [CCrmFieldInfoAttr::Dynamic],
				'SETTINGS' => $settings,
				'USER_TYPE' => $userType,
				'LABELS' => [
					'LIST' => isset($arUserField['LIST_COLUMN_LABEL']) ? $arUserField['LIST_COLUMN_LABEL'] : '',
					'FORM' => isset($arUserField['EDIT_FORM_LABEL']) ? $arUserField['EDIT_FORM_LABEL'] : '',
					'FILTER' => isset($arUserField['LIST_FILTER_LABEL']) ? $arUserField['LIST_FILTER_LABEL'] : ''
				],
			];

			$isMultiple = isset($arUserField['MULTIPLE']) && $arUserField['MULTIPLE'] === 'Y';
			$isRequired = isset($arUserField['MANDATORY']) && $arUserField['MANDATORY'] === 'Y';
			if($isMultiple || $isRequired)
			{
				if($isMultiple)
				{
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Multiple;
				}

				if($isRequired)
				{
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Required;
				}
			}

			if($userTypeID === 'string')
			{
				$info['SETTINGS'] = $arUserField['SETTINGS'];
			}
			if($userTypeID === 'enumeration')
			{
				$info['SETTINGS'] = $arUserField['SETTINGS'];

				if(isset(self::$enumerationItems[$this->sEntityID][$FIELD_NAME]))
				{
					$info['ITEMS'] = self::$enumerationItems[$this->sEntityID][$FIELD_NAME];
				}
				else
				{
					$fieldID = $arUserField['ID'];
					if(isset($arUserField['USER_TYPE']) && isset($arUserField['USER_TYPE']['CLASS_NAME']))
					{
						$enumFields[$arUserField['USER_TYPE']['CLASS_NAME']][$fieldID] =
							['ID' => $fieldID, 'NAME' => $FIELD_NAME];
					}
				}
			}
			elseif($userTypeID === 'crm_status')
			{
				$info['CRM_STATUS_TYPE'] = isset($settings['ENTITY_TYPE']) ? $settings['ENTITY_TYPE'] : '';
			}

			$fieldsInfo[$FIELD_NAME] = &$info;
			unset($info);
		}

		foreach($enumFields as $fieldClassName => $fields)
		{
			$enumResult = call_user_func_array(
				[$fieldClassName, 'GetListMultiple'],
				[array_values($fields)]
			);

			$items = [];
			while($enum = $enumResult->GetNext())
			{
				$fieldID = $enum['~USER_FIELD_ID'];
				if(!isset($fields[$fieldID]))
				{
					continue;
				}

				$fieldName = $fields[$fieldID]['NAME'];

				if(!isset($fieldsInfo[$fieldName]['ITEMS']))
				{
					$fieldsInfo[$fieldName]['ITEMS'] = [];
				}

				if(!isset($items[$fieldName]))
				{
					$items[$fieldName] = [];
				}

				$fieldsInfo[$fieldName]['ITEMS'][] =
				$items[$fieldName][] =
					['ID' => $enum['~ID'], 'VALUE' => $enum['~VALUE']];
			}

			if (!isset(self::$enumerationItems[$this->sEntityID]))
			{
				self::$enumerationItems[$this->sEntityID] = [];
			}
			self::$enumerationItems[$this->sEntityID] = array_merge(self::$enumerationItems[$this->sEntityID], $items);
		}
	}

	public static function PrepareEnumerationInfos(array $userFields)
	{
		$results = array();
		$map = array();
		$callbacks = array();
		foreach($userFields as $userField)
		{
			if(!isset($userField['USER_TYPE']['CLASS_NAME']))
			{
				continue;
			}

			$className = $userField['USER_TYPE']['CLASS_NAME'];
			if(!isset($callbacks[$className]))
			{
				$callbacks[$className] = array();
			}

			$callbacks[$className][] = $userField;
			$map[$userField['ID']] = $userField['FIELD_NAME'];
		}

		foreach($callbacks as $className => $userFields)
		{
			$enumResult = call_user_func_array(
				array($className, 'GetListMultiple'),
				array($userFields)
			);
			while($enum = $enumResult->GetNext())
			{
				if(!isset($enum['USER_FIELD_ID']))
				{
					continue;
				}

				$fieldID = $enum['USER_FIELD_ID'];
				if(!isset($map[$fieldID]))
				{
					continue;
				}

				$fieldName = $map[$fieldID];
				if(!isset($results[$fieldName]))
				{
					$results[$fieldName] = array();
				}

				$results[$fieldName][] = array('ID' => $enum['~ID'], 'VALUE' => $enum['~VALUE']);
			}
		}
		return $results;
	}

	public function AddBPFields(&$arHeaders, $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$arUserFields = $this->GetAbstractFields(['skipUserFieldVisibilityCheck' => true]);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$beditable = true;
			$editable = array();
			$userTypeID =  $arUserField['USER_TYPE']['USER_TYPE_ID'];
			if ($userTypeID == 'boolean')
			{
				$sType = "UF:boolean";
				$editable = $arUserField['SETTINGS'];
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'employee')
				$sType = 'user';
			else if (in_array($userTypeID, array('string', 'double', 'boolean', 'integer', 'datetime', 'file', 'employee'/*, 'enumeration'*/)))
			{
				if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum')
				{
					$arUserField['USER_TYPE']['BASE_TYPE'] = 'select';
				}
				$sType = $userTypeID;
				if($sType === 'employee')
				{
					//Fix for #37173
					$sType = 'user';
				}
				if($sType === 'integer')
				{
					$sType = 'int';
				}

				if ($sType === 'datetime')
				{
					$arUserField['SETTINGS']['EDIT_IN_LIST'] = $arUserField['EDIT_IN_LIST'];
					$editable = $arUserField['SETTINGS'];
				}
			}
			else
			{
				if ($userTypeID == 'enumeration')
					$sType = 'select';
				else
					$sType = 'UF:'.$userTypeID;
				$editable = array();
				if ('iblock_element' == $userTypeID || 'iblock_section' == $userTypeID ||
					'crm_status' == $userTypeID || 'crm' == $userTypeID)
				{
					$editable = $arUserField['SETTINGS'];
				}
				elseif (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$fl = (COption::GetOptionString("crm", "bp_version", 2) == 2);
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					while($ar = $rsEnum->GetNext())
					{
						if (isset($ar[$fl ? 'XML_ID' : 'ID']))
						{
							$editable[$ar[$fl ? 'XML_ID' : 'ID']] = $ar['~VALUE'] ?? $ar['VALUE'];
						}
					}
				}
			}

			$fieldTitle = trim($arUserField['EDIT_FORM_LABEL']) !== '' ? $arUserField['EDIT_FORM_LABEL'] : $arUserField['FIELD_NAME'];

			$arHeaders[$FIELD_NAME] = array(
				'Name' => $fieldTitle,
				'Options' => $editable,
				'Type' => $sType,
				'Filterable' => $arUserField['MULTIPLE'] != 'Y',
				'Editable' => $beditable,
				'Multiple' => $arUserField['MULTIPLE'] == 'Y',
				'Required' => $arUserField['MANDATORY'] == 'Y',
			);

			if($userTypeID === 'date')
			{
				$arHeaders[$FIELD_NAME]['BaseType'] = 'date';
			}

			if($userTypeID === 'boolean')
			{
				$arHeaders[$FIELD_NAME]['Type'] = $arHeaders[$FIELD_NAME]['BaseType'] = 'bool';
			}

			if($userTypeID === 'enumeration' || $userTypeID === 'crm')
			{
				$arHeaders[$FIELD_NAME.'_PRINTABLE'] = array(
					'Name' => $fieldTitle.' ('.(isset($arOptions['PRINTABLE_SUFFIX']) ? $arOptions['PRINTABLE_SUFFIX'] : 'text').')',
					'Options' => $editable,
					'Type' => 'string',
					'Filterable' => $arUserField['MULTIPLE'] != 'Y',
					'Editable' => false,
					'Multiple' => $arUserField['MULTIPLE'] == 'Y',
					'Required' => false,
				);
			}

			if ($userTypeID === 'resourcebooking')
			{
				$arHeaders[$FIELD_NAME]['Editable'] = false;

				$arHeaders[$FIELD_NAME.'.SERVICE_NAME'] = array(
					'Name' => $fieldTitle.': '.GetMessage("CRM_USERTYPE_RESOURCEBOOKING_SERVICE_NAME"),
					'Type' => 'string',
				);
				$arHeaders[$FIELD_NAME.'.DATE_FROM'] = array(
					'Name' => $fieldTitle.': '.GetMessage("CRM_USERTYPE_RESOURCEBOOKING_DATE_FROM"),
					'Type' => 'datetime',
				);
				$arHeaders[$FIELD_NAME.'.DATE_TO'] = array(
					'Name' => $fieldTitle.': '.GetMessage("CRM_USERTYPE_RESOURCEBOOKING_DATE_TO"),
					'Type' => 'datetime',
				);
				$arHeaders[$FIELD_NAME.'.USERS'] = array(
					'Name' => $fieldTitle.': '.GetMessage("CRM_USERTYPE_RESOURCEBOOKING_USERS"),
					'Type' => 'user',
					'Multiple' => true,
				);
			}
		}
	}

	public function AddWebserviceFields(&$obFields)
	{
		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$defVal = '';
			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'employee')
				continue;
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum')
			{
				$sType = 'int';
				if (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					$obFieldValues = new CXMLCreator('CHOISES');
					while($ar = $rsEnum->GetNext())
					{
						$obFieldValue = new CXMLCreator('CHOISE', true);
						$obFieldValue->setAttribute('id', $ar['ID']);
						$obFieldValue->setData(htmlspecialcharsbx($ar['VALUE']));
						$obFieldValues->addChild($obFieldValue);
					}
				}
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'file')
				$sType = 'file';
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'boolean')
				$sType = 'boolean';
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'double' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'integer')
				$sType = 'int';
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
			{
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE']['VALUE'];
				$sType = 'datetime';
			}
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'string')
				$sType = 'string';
			else
				$sType = 'string';

			if (empty($defVal) && isset($arUserField['SETTINGS']['DEFAULT_VALUE']) && !is_array($arUserField['SETTINGS']['DEFAULT_VALUE']))
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE'];

			$obField = CXMLCreator::createTagAttributed('Field id="'.$FIELD_NAME.'" name="'.htmlspecialcharsbx($arUserField['EDIT_FORM_LABEL']).'" type="'.$sType.'" default="'.$defVal.'" require="'.($arUserField['MANDATORY'] == 'Y' ? 'true' : 'false').'" multy="'.($arUserField['MULTIPLE'] == 'Y' ? 'true' : 'false').'"', '');
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' && $obFieldValues instanceof CXMLCreator)
			{
				$obField->addChild($obFieldValues);
				unset($obFieldValues);
			}
			$obFields->addChild($obField);
		}
	}

	public function AddRestServiceFields(&$arFields)
	{
		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$defVal = '';
			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'employee')
				continue;
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum')
			{
				$sType = 'enum';
				if (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					$arValues = array();
					while($ar = $rsEnum->GetNext())
					{
						$arValues[] = array('ID' => $ar['ID'], 'NAME' => $ar['VALUE']);
					}
				}
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'file')
				$sType = 'file';
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
				$sType = 'boolean';
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'double' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'integer')
				$sType = 'int';
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
			{
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE']['VALUE'];
				$sType = 'datetime';
			}
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'string')
				$sType = 'string';
			else
				$sType = 'string';

			if (empty($defVal) && isset($arUserField['SETTINGS']['DEFAULT_VALUE']) && !is_array($arUserField['SETTINGS']['DEFAULT_VALUE']))
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE'];

			$arField = array('ID' => $FIELD_NAME, 'NAME' => $arUserField['EDIT_FORM_LABEL'], 'TYPE' => $sType, 'DEFAULT' => $defVal, 'REQUIRED' => $arUserField['MANDATORY'] == 'Y', 'MULTIPLE' => $arUserField['MULTIPLE'] == 'Y');

			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' && is_array($arValues) && count($arValues) > 0)
			{
				$arField['VALUES'] = $arValues;
			}

			$arFields[] = $arField;
		}
	}

	public function PrepareUpdate(&$arFields, $arOptions = null)
	{
		$isNew = is_array($arOptions) && isset($arOptions['IS_NEW']) && $arOptions['IS_NEW'];
		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$typeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];

			// Skip datetime - there is custom logic.
			if($typeID === 'datetime' || $typeID === 'date')
			{
				continue;
			}

			if($isNew && $arUserField['EDIT_IN_LIST'] === 'N' && isset($arUserField['SETTINGS']['DEFAULT_VALUE']) && !isset($arFields[$FIELD_NAME]))
			{
				$arFields[$FIELD_NAME] = $arUserField['SETTINGS']['DEFAULT_VALUE'];
			}

			if ($typeID == 'boolean' && isset($arFields[$FIELD_NAME]))
			{
				if ($arUserField['MULTIPLE'] == 'Y' && is_array($arFields[$FIELD_NAME]))
				{
					foreach ($arFields[$FIELD_NAME] as $k => $val)
					{
						if (!empty($val) && ($val == 'Y' || $val == 1 || $val === true))
							$arFields[$FIELD_NAME][$k] = 1;
						else
							$arFields[$FIELD_NAME][$k] = 0;
					}
				}
				else
				{
					if (!empty($arFields[$FIELD_NAME]) && ($arFields[$FIELD_NAME] == 'Y' || $arFields[$FIELD_NAME] == '1' || $arFields[$FIELD_NAME] === true))
						$arFields[$FIELD_NAME] = 1;
					else
						$arFields[$FIELD_NAME] = 0;
				}
			}
			elseif ($typeID == 'employee' && $arUserField['MULTIPLE'] == 'N')
			{
				if (is_array($arFields[$FIELD_NAME]))
				{
					$arFields[$FIELD_NAME] = current($arFields[$FIELD_NAME]);
				}
			}
			elseif ($typeID == 'crm' && isset($arFields[$FIELD_NAME]))
			{
				if (!is_array($arFields[$FIELD_NAME]))
				{
					$arFields[$FIELD_NAME] = explode(';', $arFields[$FIELD_NAME]);
				}
				else
				{
					$ar = [];
					foreach ($arFields[$FIELD_NAME] as $value)
					{
						if (!is_array($value))
						{
							$value = explode(';', $value);
						}

						foreach ($value as $val)
						{
							if (!empty($val))
							{
								$ar[$val] = $val;
							}
						}
					}
					$arFields[$FIELD_NAME] = $ar;
				}

				if ($arUserField['MULTIPLE'] != 'Y')
				{
					if (isset($arFields[$FIELD_NAME][0]))
						$arFields[$FIELD_NAME] = $arFields[$FIELD_NAME][0];
					else
						$arFields[$FIELD_NAME] = '';
				}
			}
		}
	}

	public function InternalizeFields(&$arFields, $delimiter = '<br />')
	{
		foreach($arFields as $name => &$data)
		{
			$data = self::Internalize($name, $data, $delimiter);
		}
		unset($data);
	}

	public function PrepareExternalFormFields(array $arData, $delimiter = '<br />')
	{
		$arFields = array();
		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $userFieldName => $arUserField)
		{
			if(isset($arData[$userFieldName]))
			{
				$arFields[$userFieldName] = self::Internalize($userFieldName, $arData[$userFieldName], $delimiter, $arUserField);
			}
		}
		return $arFields;
	}

	public function NormalizeFields(&$arFields)
	{
		if (empty($arFields))
			return false;

		$arUserFields = $this->GetAbstractFields();
		$bNorm = false;
		foreach($arFields as $k => $FIELD_NAME)
		{
			if (mb_strpos($FIELD_NAME,  $this->fieldNamePrefix . 'UF_') === 0)
			{
				if (!isset($arUserFields[$FIELD_NAME]))
				{
					$bNorm = true;
					unset($arFields[$k]);
				}
			}
		}
		return $bNorm;
	}

	function ListPrepareFilter(&$arFilter)
	{
		if(!is_array($arFilter) || empty($arFilter))
		{
			return;
		}

		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if (array_key_exists($FIELD_NAME, $arFilter) && $arFilter[$FIELD_NAME] !== null)
			{
				$value = $arFilter[$FIELD_NAME];
				unset($arFilter[$FIELD_NAME]);
			}
			else
			{
				continue;
			}

			//HACK: Force exact filtration mode for crm status and crm types. Configuration component never did not do it.
			$userTypeID = isset($arUserField['USER_TYPE_ID']) ? $arUserField['USER_TYPE_ID'] : '';
			$forceExactMode = $userTypeID === 'crm_status' || $userTypeID === 'crm';

			if (
				$arUserField['USER_TYPE']['BASE_TYPE'] != 'file'
				&& (
					is_array($value)
					|| (string) $value !== ''
					|| $value === false
				)
			)
			{
				if ($arUserField['SHOW_FILTER'] == 'I' || $forceExactMode)
				{
					$arFilter['='.$FIELD_NAME] = $value;
				}
				else if($arUserField['SHOW_FILTER'] == 'E')
				{
					$arFilter['%'.$FIELD_NAME] = $value;
				}
				else
				{
					$arFilter[$FIELD_NAME] = $value;
				}
			}
		}
	}

	public function CheckFields($arFields, $ID = 0)
	{
		return $this->cUFM->CheckFields($this->sEntityID, $ID, $arFields);
	}

	public static function GetTaskBindingField()
	{
		$dbResult = CUserTypeEntity::GetList(
			array(),
			array(
			'ENTITY_ID' => 'TASKS_TASK',
				'FIELD_NAME' => 'UF_CRM_TASK',
			)
		);

		return $dbResult ? $dbResult->Fetch() : null;
	}
	public static function GetCalendarEventBindingField()
	{
		$dbResult = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'CALENDAR_EVENT',
				'FIELD_NAME' => 'UF_CRM_CAL_EVENT',
			)
		);

		return $dbResult ? $dbResult->Fetch() : null;
	}

	public function CopyFileFields(array &$fields)
	{
		$userFields = $this->GetAbstractFields();
		foreach($userFields as $fieldName => $userFieldInfo)
		{
			if($userFieldInfo['USER_TYPE_ID'] === 'file')
			{
				if(isset($userFieldInfo['MULTIPLE']) && $userFieldInfo['MULTIPLE'] === 'Y')
				{
					$results = array();
					if(is_array($fields[$fieldName]))
					{
						foreach($fields[$fieldName] as $fileInfo)
						{
							//HACK: Deletion flag may contain fileID or boolean value.
							$isDeleted = isset($fileInfo['del']) && ($fileInfo['del'] === true || $fileInfo['del'] === $fileInfo['old_id']);
							if($isDeleted)
							{
								continue;
							}

							if($fileInfo['tmp_name'] !== '')
							{
								$results[] = $fileInfo;
							}
							elseif($fileInfo['old_id'] !== '')
							{
								$isResolved = \CCrmFileProxy::TryResolveFile($fileInfo['old_id'], $file, array('ENABLE_ID' => true));
								if($isResolved)
								{
									$results[] = $file;
								}
							}
						}
					}
					$fields[$fieldName] = $results;
				}
				else
				{
					$fileInfo = $fields[$fieldName];
					//HACK: Deletion flag may contain fileID or boolean value.
					$isDeleted = isset($fileInfo['del']) && ($fileInfo['del'] === true || $fileInfo['del'] === $fileInfo['old_id']);
					if(!$isDeleted  && $fileInfo['tmp_name'] === '' && $fileInfo['old_id'] !== '')
					{
						$isResolved = \CCrmFileProxy::TryResolveFile($fields[$fieldName]['old_id'], $file, array('ENABLE_ID' => true));
						if($isResolved)
						{
							$fields[$fieldName] = $file;
						}
					}
				}
			}
		}
	}

	public function PrepareForSave(array &$fields)
	{
		$userFields = $this->GetAbstractFields();
		foreach($userFields as $fieldName => $userFieldInfo)
		{
			if($userFieldInfo['USER_TYPE_ID'] === 'file')
			{
				if(isset($userFieldInfo['MULTIPLE']) && $userFieldInfo['MULTIPLE'] === 'Y')
				{
					$results = array();
					if(is_array($fields[$fieldName]))
					{
						foreach($fields[$fieldName] as $data)
						{
							if(\CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_ID' => true)))
							{
								$results[] = $file;
							}
						}
					}
					$fields[$fieldName] = $results;
				}
				else
				{
					if(\CCrmFileProxy::TryResolveFile($fields[$fieldName], $file, array('ENABLE_ID' => true)))
					{
						$fields[$fieldName] = $file;
					}
				}
			}
		}
	}

	public static function onBeforeGetPublicEdit(\Bitrix\Main\Event $event)
	{
		$eventParameters = $event->getParameters();

		if($eventParameters[1]['CONTEXT'] == 'CRM_EDITOR')
		{
			$eventParameters[0]['SETTINGS']['LABEL_CHECKBOX'] = $eventParameters[0]['EDIT_FORM_LABEL'];
		}

		$event->setParameters($eventParameters);
	}

	/**
	 * @return bool
	 */
	protected function isMyCompany()
	{
		return (
			$this->sEntityID === CCrmCompany::GetUserFieldEntityID() &&
			isset($this->options['isMyCompany']) &&
			$this->options['isMyCompany'] === true
		);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	protected function postFilterFields(array $fields)
	{
		// remove invoice reserved fields
		if ($this->sEntityID === CCrmInvoice::GetUserFieldEntityID())
		{
			foreach (CCrmInvoice::GetUserFieldsReserved() as $ufId)
			{
				if (isset($fields[$ufId]))
				{
					unset($fields[$ufId]);
				}
			}
		}

		if(!$this->isMyCompany())
		{
			foreach(CCrmCompany::getMyCompanyAdditionalUserFields() as $ufieldName => $description)
			{
				if(isset($fields[$ufieldName]))
				{
					unset($fields[$ufieldName]);
				}
			}
		}

		if(!empty($fields) && isset($this->options['categoryId']))
		{
			$entityTypeId = CCrmOwnerType::ResolveIDByUFEntityID($this->sEntityID);
			$fields = (new ItemCategoryUserField($entityTypeId))->filter($this->options['categoryId'], $fields);
		}

		return $fields;
	}

	protected function postFilterAccessCheck(array $userFields, ?int $userId): array
	{
		if (!$userId)
		{
			$userId = \CCrmSecurityHelper::GetCurrentUserID();
		}

		return VisibilityManager::getVisibleUserFields($userFields, $userId);
	}

	public function setFieldNamePrefix(string $prefix): void
	{
		$this->fieldNamePrefix = $prefix;
	}

	protected function appendNamePrefix(array $fields): array
	{
		if(!$this->fieldNamePrefix)
		{
			return $fields;
		}

		$result = [];
		foreach ($fields as $field)
		{
			$fieldName = $this->fieldNamePrefix . $field['FIELD_NAME'];
			$field['FIELD_NAME'] = $fieldName;

			$result[$fieldName] = $field;
		}

		return $result;
	}
}
