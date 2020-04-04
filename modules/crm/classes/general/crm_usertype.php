<?php

IncludeModuleLangFile(__FILE__);

class CCrmUserType
{
	protected $cUFM = null;
	public $sEntityID = '';
	private $arFields = null;
	/** @var array|null  */
	private static $enumerationItems = null;
	protected $options;

	protected function GetAbstractFields()
	{
		if($this->arFields === null)
		{
			$this->arFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID, false);

			$this->arFields = $this->postFilterFields($this->arFields);
		}

		return $this->arFields;
	}

	public function GetFields()
	{
		return $this->GetAbstractFields();
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
	 * @return $this
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;
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

	public function PrepareListFilterFields(&$arFilterFields, &$arFilterLogic)
	{
		$arUserFields = $this->GetAbstractFields();
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
					'type' => 'custom_entity',
					'selector' => array(
						'TYPE' => 'user',
						'DATA' => array('ID' => strtolower($FIELD_NAME), 'FIELD_ID' => $FIELD_NAME)
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

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => $fieldLabel,
					'type' => 'custom_entity',
					'selector' => array(
						'TYPE' => 'crm_entity',
						'DATA' => array(
							'ID' => strtolower($FIELD_NAME),
							'FIELD_ID' => $FIELD_NAME,
							'ENTITY_TYPE_NAMES' => $entityTypeNames,
							'IS_MULTIPLE' => $isMultiple
						)
					)
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

	public function GetEntityFields($ID)
	{
		return $this->GetUserFields($this->sEntityID, $ID, LANGUAGE_ID);
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
		$uv = strtoupper(trim($value));

		$success = false;
		foreach($enums as $enumID => &$enum)
		{
			if(strtoupper($enum[$fieldName]) === $uv || (isset($enum['XML_ID']) && $enum['XML_ID'] === $value))
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
			$prefix = strtoupper($m[1]);
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

			$value = substr($value, strlen($m[0]));
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
			$rsEntities = CCrmContact::GetListEx(array(), array('=FULL_NAME'=> $value), false, false, array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}

			if(preg_match('/\s*([^\s]+)\s+([^\s]+)\s*/', $value, $match) > 0)
			{
				// Try to interpret value as '#NAME# #LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=NAME'=> $match[1], '=LAST_NAME'=> $match[2]),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}

				// Try to interpret value as '#LAST_NAME# #NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $match[1], '=NAME'=> $match[2]),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}
			else
			{
				// Try to interpret value as '#LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $value),  false, false, array('ID'));
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

			$rsEntities = CCrmCompany::GetList(array(), array('=TITLE'=> $value), array('ID'));
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

		$isContactEnabled = isset($settings['CONTACT']) && strtoupper($settings['CONTACT']) === 'Y';
		$isCompanyEnabled = isset($settings['COMPANY']) && strtoupper($settings['COMPANY']) === 'Y';
		$isLeadEnabled = isset($settings['LEAD']) && strtoupper($settings['LEAD']) === 'Y';
		$isDealEnabled = isset($settings['DEAL']) && strtoupper($settings['DEAL']) === 'Y';

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
			if(!$isMultiple)
			{
				if(CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_UPLOAD' => true)))
				{
					$data = $file;
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
				$data = $files;
			}
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
				$by = 'last_name',
				$order = 'asc',
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
			$yes = strtoupper(GetMessage('MAIN_YES'));
			//$no = strtoupper(GetMessage('MAIN_NO'));

			if(is_array($data))
			{
				foreach($data as &$v)
				{
					$s = strtoupper($v);
					$v = ($s === $yes || $s === 'Y' || $s === 'YES' || (is_numeric($s) && intval($s) > 0)) ? 1 : 0;
				}
				unset($v);
			}
			elseif(is_string($data) && $data !== '')
			{
				$s = strtoupper($data);
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
			while($arEnum = $rsEnum->GetNext())
			{
				$enums[strval($arEnum['ID'])] = $arEnum;
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

	public function ListAddEnumFieldsValue($arParams, &$arValue, &$arReplaceValue, $delimiter = '<br />', $textonly = false, $arOptions = array())
	{
		$arUserFields = $this->GetAbstractFields();
		$bSecondLoop = false;
		$arValuePrepare = array();

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		// The first loop to collect all the data fields
		foreach($arUserFields as $FIELD_NAME => &$arUserField)
		{
			$isMultiple = $arUserField['MULTIPLE'] == 'Y';
			foreach ($arValue as $ID => $data)
			{
				if(!$isMultiple)
				{
					$isEmpty = !isset($arValue[$ID][$FIELD_NAME]) && $arUserField['USER_TYPE']['USER_TYPE_ID'] != 'boolean';
				}
				else
				{
					$isEmpty = !isset($arValue[$ID][$FIELD_NAME]) || $arValue[$ID][$FIELD_NAME] === false;
				}

				if($isEmpty)
				{
					continue;
				}

				if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
				{
					if (isset($arValue[$ID][$FIELD_NAME]))
						$arValue[$ID][$FIELD_NAME] == ($arValue[$ID][$FIELD_NAME] == 1 || $arValue[$ID][$FIELD_NAME] == 'Y' ? 'Y' : 'N');

					$arVal = $arValue[$ID][$FIELD_NAME];
					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $val)
					{
						$val = (string)$val;

						if (strlen($val) <= 0)
						{
							//Empty value is always 'N' (not default field value)
							$val = 'N';
						}

						$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').($val == 1 ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'));
						if ($isMultiple)
						{
							$arValue[$ID][$FIELD_NAME][] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';
						}
						else
						{
							$arValue[$ID][$FIELD_NAME] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';
						}
					}
				}
				elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm_status')
				{
					$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
					$arReplaceValue[$ID][$FIELD_NAME] = isset($ar[$arValue[$ID][$FIELD_NAME]])? $ar[$arValue[$ID][$FIELD_NAME]]: '';
				}
				elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm')
				{
					$arParams['CRM_ENTITY_TYPE'] = Array();
					if ($arUserField['SETTINGS']['LEAD'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'LEAD';
					if ($arUserField['SETTINGS']['CONTACT'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'CONTACT';
					if ($arUserField['SETTINGS']['COMPANY'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'COMPANY';
					if ($arUserField['SETTINGS']['DEAL'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'DEAL';

					$arParams['CRM_PREFIX'] = false;
					if (count($arParams['CRM_ENTITY_TYPE']) > 1)
						$arParams['CRM_PREFIX'] = true;

					$bSecondLoop = true;
					$arVal = $arValue[$ID][$FIELD_NAME];
					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $value)
					{
						if($arParams['CRM_PREFIX'])
						{
							$ar = explode('_', $value);
							$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
							$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][CUserTypeCrm::GetLongEntityType($ar[0])][intval($ar[1])] = intval($ar[1]);
						}
						else
						{
							if (is_numeric($value))
							{
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][$arParams['CRM_ENTITY_TYPE'][0]][] = $value;
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][$arParams['CRM_ENTITY_TYPE'][0]][$value] = $value;
							}
							else
							{
								$ar = explode('_', $value);
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][CUserTypeCrm::GetLongEntityType($ar[0])][intval($ar[1])] = intval($ar[1]);
							}
						}
					}
					$arReplaceValue[$ID][$FIELD_NAME] = '';
				}
				elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'file'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'employee'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_element'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'enumeration'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_section')
				{
					$bSecondLoop = true;
					$arVal = $arValue[$ID][$FIELD_NAME];
					$arReplaceValue[$ID][$FIELD_NAME] = '';

					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $value)
					{
						if($value === '' || $value <= 0)
						{
							continue;
						}
						$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][$value] = $value;
						$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['ID'][] = $value;
					}
				}
				elseif(!$textonly
					&& ($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'address'
						|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'money'
						|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'url'
						|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'resourcebooking'
					))
				{
					if($isMultiple)
					{
						$value = array();
						if(is_array($arValue[$ID][$FIELD_NAME]))
						{
							$valueCount = count($arValue[$ID][$FIELD_NAME]);
							for($i = 0; $i < $valueCount; $i++)
							{
								$value[] = htmlspecialcharsback($arValue[$ID][$FIELD_NAME][$i]);
							}
						}
					}
					else
					{
						$value = htmlspecialcharsback($arValue[$ID][$FIELD_NAME]);
					}

					$arReplaceValue[$ID][$FIELD_NAME] = $this->cUFM->GetPublicView(
						array_merge(
							$arUserField,
							array('ENTITY_VALUE_ID' => $ID, 'VALUE' => $value)
						),
						array(
							'CONTEXT' => 'CRM_GRID'
						)
					);
				}
				else if ($isMultiple && is_array($arValue[$ID][$FIELD_NAME]))
				{
					array_walk($arValue[$ID][$FIELD_NAME], create_function('&$v',  '$v = htmlspecialcharsbx($v);'));
					$arReplaceValue[$ID][$FIELD_NAME] = implode($delimiter, $arValue[$ID][$FIELD_NAME]);
				}
			}
		}
		unset($arUserField);

		// The second loop for special field
		if($bSecondLoop)
		{
			$arValueReplace = Array();
			$arList = Array();
			foreach($arValuePrepare as $KEY => $VALUE)
			{
				// collect multi data
				if ($KEY == 'iblock_section')
				{
					$dbRes = CIBlockSection::GetList(array('left_margin' => 'asc'), array('ID' => $VALUE['ID']), false);
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'file')
				{
					$dbRes = CFile::GetList(Array(), array('@ID' => implode(',', $VALUE['ID'])));
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'iblock_element')
				{
					$dbRes = CIBlockElement::GetList(array('SORT' => 'DESC', 'NAME' => 'ASC'), array('ID' => $VALUE['ID']), false);
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'employee')
				{
					$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', array('ID' => implode('|', $VALUE['ID'])));
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'enumeration')
				{
					foreach ($VALUE['ID'] as $___value)
					{
						$rsEnum = CUserFieldEnum::GetList(array(), array('ID' => $___value));
						while ($arRes = $rsEnum->Fetch())
							$arList[$KEY][$arRes['ID']] = $arRes;
					}
				}
				elseif ($KEY == 'crm')
				{
					if (isset($VALUE['LEAD']) && !empty($VALUE['LEAD']))
					{
						$dbRes = CCrmLead::GetListEx(array('TITLE' => 'ASC', 'LAST_NAME' => 'ASC', 'NAME' => 'ASC'), array('ID' => $VALUE['LEAD']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['LEAD'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['CONTACT']) && !empty($VALUE['CONTACT']))
					{
						$dbRes = CCrmContact::GetListEx(array('LAST_NAME' => 'ASC', 'NAME' => 'ASC'), array('=ID' => $VALUE['CONTACT']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['CONTACT'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['COMPANY']) && !empty($VALUE['COMPANY']))
					{
						$dbRes = CCrmCompany::GetListEx(array('TITLE' => 'ASC'), array('ID' => $VALUE['COMPANY']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['COMPANY'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['DEAL']) && !empty($VALUE['DEAL']))
					{
						$dbRes = CCrmDeal::GetListEx(array('TITLE' => 'ASC'), array('ID' => $VALUE['DEAL']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['DEAL'][$arRes['ID']] = $arRes;
					}
				}

				// assemble multi data
				foreach ($VALUE['FIELD'] as $ID => $arFIELD_NAME)
				{
					foreach ($arFIELD_NAME as $FIELD_NAME => $FIELD_VALUE)
					{
						foreach ($FIELD_VALUE as $FIELD_VALUE_NAME => $FIELD_VALUE_ID)
						{
							if ($KEY == 'iblock_section')
							{
								$sname = htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['NAME']);
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							if ($KEY == 'iblock_element')
							{
								$sname = htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['NAME']);
								if(!$textonly)
								{
									$surl = GetIBlockElementLinkById($arList[$KEY][$FIELD_VALUE_ID]['ID']);
									if ($surl && strlen($surl) > 0)
									{
										$sname = '<a href="'.$surl.'">'.$sname.'</a>';
									}
								}
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'employee')
							{
								$sname = '';
								if(is_array($arList[$KEY][$FIELD_VALUE_ID]))
								{
									$sname = CUser::FormatName(CSite::GetNameFormat(false), $arList[$KEY][$FIELD_VALUE_ID], false, true);
									if(!$textonly)
									{
										$ar['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_user_profile'), array('user_id' => $arList[$KEY][$FIELD_VALUE_ID]['ID']));
										$sname = 	'<a href="'.$ar['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arParams['GRID_ID'].'_'.$arList[$KEY][$FIELD_VALUE_ID]['ID'].'" bx-tooltip-user-id="'.$arList[$KEY][$FIELD_VALUE_ID]['ID'].'">'.$sname.'</a>';
									}
								}
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'enumeration')
							{
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['VALUE']);
							}
							else if ($KEY == 'file')
							{
								$fileInfo = $arList[$KEY][$FIELD_VALUE_ID];
								if($textonly)
								{
									$fileUrl = CFile::GetFileSRC($fileInfo);
								}
								else
								{
									$fileUrlTemplate = isset($arOptions['FILE_URL_TEMPLATE'])
										? $arOptions['FILE_URL_TEMPLATE'] : '';

									$fileUrl = $fileUrlTemplate === ''
										? CFile::GetFileSRC($fileInfo)
										: CComponentEngine::MakePathFromTemplate(
											$fileUrlTemplate,
											array('owner_id' => $ID, 'field_name' => $FIELD_NAME, 'file_id' => $fileInfo['ID'])
										);
								}

								$sname = $textonly ? $fileUrl : '<a href="'.htmlspecialcharsbx($fileUrl).'" target="_blank">'.htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['FILE_NAME']).'</a>';
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'crm')
							{
								foreach($FIELD_VALUE_ID as $CID)
								{
									$link = '';
									$title = '';
									$prefix = '';
									if ($FIELD_VALUE_NAME == 'LEAD')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'), array('lead_id' => $CID));
										$title = $arList[$KEY]['LEAD'][$CID]['TITLE'];
										$prefix = 'L';
									}
									elseif ($FIELD_VALUE_NAME == 'CONTACT')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'), array('contact_id' => $CID));
										if(isset($arList[$KEY]['CONTACT'][$CID]))
										{
											$title = CCrmContact::PrepareFormattedName($arList[$KEY]['CONTACT'][$CID]);
										}
										$prefix = 'C';
									}
									elseif ($FIELD_VALUE_NAME == 'COMPANY')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'), array('company_id' => $CID));
										$title = $arList[$KEY]['COMPANY'][$CID]['TITLE'];
										$prefix = 'CO';
									}
									elseif ($FIELD_VALUE_NAME == 'DEAL')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'), array('deal_id' => $CID));
										$title = $arList[$KEY]['DEAL'][$CID]['TITLE'];
										$prefix = 'D';
									}
									elseif ($FIELD_VALUE_NAME == 'ORDER')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_order_details'), array('order_id' => $CID));
										$title = $arList[$KEY]['ORDER'][$CID]['TITLE'];
										$prefix = 'O';
									}

									$sname = htmlspecialcharsbx($title);
									if(!$textonly)
									{
										Bitrix\Main\UI\Extension::load("ui.tooltip");
										$sname = '<a href="'.$link.'" target="_blank" bx-tooltip-user-id="'.$CID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.'.strtolower($FIELD_VALUE_NAME).'.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon'.($FIELD_VALUE_NAME == 'LEAD' || $FIELD_VALUE_NAME == 'DEAL' || $FIELD_VALUE_NAME == 'QUOTE' ? '_no_photo': '_'.strtolower($FIELD_VALUE_NAME)).'">'.$sname.'</a>';
									}
									else
									{
										$sname = "[$prefix]$sname";
									}
									$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
								}
							}
						}
					}
				}
			}
		}
	}

	public function ListAddHeaders(&$arHeaders, $bImport = false)
	{
		$arUserFields = $this->GetAbstractFields();
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			//NOTE: SHOW_IN_LIST affect only default fields. All fields are allowed in list.
			//if(!isset($arUserField['SHOW_IN_LIST']) || $arUserField['SHOW_IN_LIST'] !== 'Y')
			//	continue;

			$editable = true;
			$sType = $arUserField['USER_TYPE']['BASE_TYPE'];
			if ($arUserField['EDIT_IN_LIST'] === 'N'
				|| $arUserField['MULTIPLE'] === 'Y'
				||$arUserField['USER_TYPE']['BASE_TYPE'] === 'file'
				|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'employee'
				|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'crm')
				$editable = false;
			else if (in_array($arUserField['USER_TYPE']['USER_TYPE_ID'], array('enumeration', 'iblock_section', 'iblock_element')))
			{
				$sType = 'list';
				$editable = array(
					'items' => array('' => '')
				);
				if (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					while($ar = $rsEnum->GetNext())
						$editable['items'][$ar['ID']] = htmlspecialcharsback($ar['VALUE']);
				}
			}
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
			{
				$sType = 'list';
				//Default value must be placed at first position.
				$defaultValue = isset($arUserField['SETTINGS']['DEFAULT_VALUE']) ? (int)$arUserField['SETTINGS']['DEFAULT_VALUE'] : 0;
				if($defaultValue === 1)
				{
					$editable = array('items' => array('1' => GetMessage('MAIN_YES'), '0' => GetMessage('MAIN_NO')));
				}
				else
				{
					$editable = array('items' => array('0' => GetMessage('MAIN_NO'), '1' => GetMessage('MAIN_YES')));
				}
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
				$sType = 'date';
			elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm_status')
			{
				$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
				$sType = 'list';
				$editable = array(
					'items' => Array('' => '') + $ar
				);
			}
			elseif(substr($arUserField['USER_TYPE']['USER_TYPE_ID'], 0, 5) === 'rest_')
			{
				// skip REST type fields here
				continue;
			}

			if($sType === 'string')
			{
				$sType = 'text';
			}
			elseif($sType === 'int' || $sType === 'double')
			{
				//HACK: \CMainUIGrid::prepareEditable does not recognize 'number' type
				$sType = 'int';
			}

			$arHeaders[$FIELD_NAME] = array(
				'id' => $FIELD_NAME,
				'name' => htmlspecialcharsbx($arUserField['LIST_COLUMN_LABEL']),
				'sort' => $arUserField['MULTIPLE'] == 'N' ? $FIELD_NAME : false,
				'default' => $arUserField['SHOW_IN_LIST'] == 'Y',
				'editable' => $editable,
				'type' => $sType
			);

			if ($bImport)
				$arHeaders[$FIELD_NAME]['mandatory'] = $arUserField['MANDATORY'] === 'Y' ? 'Y' : 'N';
		}
	}

	// Get Fields Metadata
	public function PrepareFieldsInfo(&$fieldsInfo)
	{
		$arUserFields = $this->GetAbstractFields();

		$enumFields = array();
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$userTypeID = $arUserField['USER_TYPE_ID'];
			$settings = isset($arUserField['SETTINGS']) ? $arUserField['SETTINGS'] : array();

			$info = array(
				'TYPE' => $userTypeID,
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Dynamic),
				'LABELS' => array(
					'LIST' => isset($arUserField['LIST_COLUMN_LABEL']) ? $arUserField['LIST_COLUMN_LABEL'] : '',
					'FORM' => isset($arUserField['EDIT_FORM_LABEL']) ? $arUserField['EDIT_FORM_LABEL'] : '',
					'FILTER' => isset($arUserField['LIST_FILTER_LABEL']) ? $arUserField['LIST_FILTER_LABEL'] : ''
				)
			);

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

				if(self::$enumerationItems == null)
				{
					self::$enumerationItems = array();
				}

				if(isset(self::$enumerationItems[$FIELD_NAME]))
				{
					$info['ITEMS'] = self::$enumerationItems[$FIELD_NAME];
				}
				else
				{
					$fieldID = $arUserField['ID'];
					if(isset($arUserField['USER_TYPE']) && isset($arUserField['USER_TYPE']['CLASS_NAME']))
					{
						$enumFields[$arUserField['USER_TYPE']['CLASS_NAME']][$fieldID] =
							array('ID' => $fieldID, 'NAME' => $FIELD_NAME);
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
				array($fieldClassName, 'GetListMultiple'),
				array(array_values($fields))
			);

			$items = array();
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
					$fieldsInfo[$fieldName]['ITEMS'] = array();
				}

				if(!isset($items[$fieldName]))
				{
					$items[$fieldName] = array();
				}

				$fieldsInfo[$fieldName]['ITEMS'][] =
				$items[$fieldName][] =
					array('ID' => $enum['~ID'], 'VALUE' => $enum['~VALUE']);
			}

			if(self::$enumerationItems == null)
			{
				self::$enumerationItems = $items;
			}
			else
			{
				self::$enumerationItems = array_merge(self::$enumerationItems, $items);
			}
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

		$arUserFields = $this->GetAbstractFields();
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
					$arUserField['USER_TYPE']['BASE_TYPE'] = 'select';
				$sType = $userTypeID;
				if($sType === 'employee')
				{
					//Fix for #37173
					$sType = 'user';
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
						$editable[$ar[$fl ? 'XML_ID' : 'ID']] = $ar['VALUE'];
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
					'Type' => $sType,
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
			if($typeID === 'datetime')
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
					list(, $arFields[$FIELD_NAME]) = each($arFields[$FIELD_NAME]);
			}
			elseif ($typeID == 'crm' && isset($arFields[$FIELD_NAME]))
			{
				if (!is_array($arFields[$FIELD_NAME]))
					$arFields[$FIELD_NAME] = explode(';', $arFields[$FIELD_NAME]);
				else
				{
					$ar = Array();
					foreach ($arFields[$FIELD_NAME] as $value)
						foreach(explode(';', $value) as $val)
							if (!empty($val))
								$ar[$val] = $val;
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
			if (strpos($FIELD_NAME, 'UF_') === 0)
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
			if (isset($arFilter[$FIELD_NAME]))
			{
				$value = $arFilter[$FIELD_NAME];
				unset($arFilter[$FIELD_NAME]);
			}
			else
				continue;

			//HACK: Force exact filtration mode for crm status and crm types. Configuration component never did not do it.
			$userTypeID = isset($arUserField['USER_TYPE_ID']) ? $arUserField['USER_TYPE_ID'] : '';
			$forceExactMode = $userTypeID === 'crm_status' || $userTypeID === 'crm';

			if ($arUserField['USER_TYPE']['BASE_TYPE'] != 'file' && (is_array($value) || strlen($value) > 0))
			{
				if ($arUserField['SHOW_FILTER'] == 'I' || $forceExactMode)
					$arFilter['='.$FIELD_NAME] = $value;
				else if($arUserField['SHOW_FILTER'] == 'E')
					$arFilter['%'.$FIELD_NAME] = $value;
				else
					$arFilter[$FIELD_NAME] = $value;
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

		return $fields;
	}
}

?>
