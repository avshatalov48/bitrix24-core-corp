<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['ENTITY_ID'] = $_REQUEST['entity_id'] ?? $arParams['FIELS_ENTITY_ID'];
$arResult['FIELD_ID'] = $_REQUEST['field_id'] ?? $arParams['FIELS_FIELD_ID'];
$arResult['CATEGORY_ID'] = $_REQUEST['category_id'] ?? 0;

global $USER_FIELD_MANAGER;

$CCrmFields = new CCrmFields($USER_FIELD_MANAGER, $arResult['ENTITY_ID']);
if ($CCrmFields->CheckError())
{
	$ex = $APPLICATION->GetException();
	ShowError($ex->GetString());
	return;
}

$arResult['NEW_FIELD'] = false;
if (!$arResult['FIELD_ID'])
	$arResult['NEW_FIELD'] = true;

$arResult['FIELD'] = array();
if (!$arResult['NEW_FIELD'] && !($arResult['FIELD'] = $CCrmFields->GetByName($arResult['FIELD_ID'])))
{
	ShowError(GetMessage('CRM_FIELDS_EDIT_WRONG_FIELD'));
	return;
}

global $USER_FIELD_MANAGER;
$userField = $arResult['FIELD']['USER_TYPE'] ?? null;
$userTypeId = $arResult['FIELD']['USER_TYPE']['USER_TYPE_ID'] ?? $_POST['USER_TYPE_ID'] ?? null;
if (!$userField)
{
	$userField = $USER_FIELD_MANAGER->GetUserType($userTypeId);
}
$className = $userField['CLASS_NAME'] ?? null;
if (!is_a($className, \Bitrix\Main\UserField\Types\BaseType::class, true))
{
	$className = \Bitrix\Main\UserField\Types\BaseType::class;
}
$arResult['DISABLE_MULTIPLE'] = $userTypeId === 'boolean';
$arResult['DISABLE_MANDATORY'] = $userTypeId === 'boolean';
if (method_exists($className, 'isMandatorySupported'))
{
	$arResult['DISABLE_MANDATORY'] = !$className::isMandatorySupported();
}
if (method_exists($className, 'isMultiplicitySupported'))
{
	$arResult['DISABLE_MULTIPLE'] = !$className::isMultiplicitySupported();
}
if(isset($arResult['FIELD']['ID']))
{
	//HACK: is required for obtain a multilang support for EDIT_FORM_LABEL
	$fieldData = CUserTypeEntity::GetByID($arResult['FIELD']['ID']);
	if($fieldData)
	{
		$arResult['FIELD']['EDIT_FORM_LABEL'] = $fieldData['EDIT_FORM_LABEL'];
		$arResult['FIELD']['LIST_COLUMN_LABEL'] = $fieldData['LIST_COLUMN_LABEL'];
		$arResult['FIELD']['LIST_FILTER_LABEL'] = $fieldData['LIST_FILTER_LABEL'];
		$arResult['FIELD']['ERROR_MESSAGE'] = $fieldData['ERROR_MESSAGE'];
		$arResult['FIELD']['HELP_MESSAGE'] = $fieldData['HELP_MESSAGE'];
	}
}

$arResult['GRID_ID'] = 'field_list';
$arResult['FORM_ID'] = 'field_edit';

$arResult['~ENTITY_LIST_URL'] = $arParams['~ENTITY_LIST_URL'];
$arResult['ENTITY_LIST_URL'] = htmlspecialcharsbx($arParams['~ENTITY_LIST_URL']);

$arResult['~FIELDS_LIST_URL'] = str_replace('#entity_id#', $arResult['ENTITY_ID'], $arParams['~FIELDS_LIST_URL']);
$arResult['FIELDS_LIST_URL'] = htmlspecialcharsbx($arResult['~FIELDS_LIST_URL']);

$arResult['~FIELD_EDIT_URL'] = str_replace(array('#entity_id#', '#field_id#'), array($arResult['ENTITY_ID'], $arResult['FIELD_ID']), $arParams['~FIELD_EDIT_URL']);
$arResult['FIELD_EDIT_URL'] = htmlspecialcharsbx($arResult['~FIELD_EDIT_URL']);

$arResult['~FIELD_ADD_URL'] = str_replace(array('#entity_id#', '#field_id#'), array($arResult['ENTITY_ID'], 0), $arParams['~FIELD_EDIT_URL']);
$arResult['FIELD_ADD_URL'] = htmlspecialcharsbx($arResult['~FIELD_ADD_URL']);

$arLangs = array();
$dbResLangs = CLanguage::GetList();
while($arLang = $dbResLangs->Fetch())
{
	$arLangs[$arLang['LID']] = array(
		'LID' => $arLang['LID'],
		'NAME' => $arLang['NAME']
	);
}

$arResult['LANGUAGES'] = $arLangs;

//Assume there was no error
$bVarsFromForm = false;
$arResult['USE_MULTI_LANG_LABEL'] = !$arResult['NEW_FIELD'];

$ufAddRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getUserFieldAddRestriction();
$resourceBookingRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getResourceBookingRestriction();
$hasRestrictions = false;
if ($arResult['NEW_FIELD'] && $ufAddRestriction->isExceeded((int)CCrmOwnerType::ResolveIDByUFEntityID($arResult['ENTITY_ID'])))
{
	$hasRestrictions = true;
	$arResult['RESTRICTION_CALLBACK'] = $ufAddRestriction->prepareInfoHelperScript();
}
if (
	!$hasRestrictions
	&& $arResult['NEW_FIELD']
	&& isset($_POST['USER_TYPE_ID'])
	&& $_POST['USER_TYPE_ID'] === 'resourcebooking'
	&& !$resourceBookingRestriction->hasPermission()
)
{
	$hasRestrictions = true;
	$arResult['RESTRICTION_CALLBACK'] = $resourceBookingRestriction->prepareInfoHelperScript();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	//When Save or Apply buttons was pressed
	if(isset($_POST['save']) || isset($_POST['apply']))
	{
		$strError = '';

		if ($hasRestrictions)
		{
			\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();
			$strError .= \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR');
		}

		//Gather fields for update
		$arField = array(
			'USER_TYPE_ID' => $_POST['USER_TYPE_ID'],
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'CATEGORY_ID' => $arResult['CATEGORY_ID'],
			'SORT' => $_POST['SORT'],
			'MULTIPLE' => ($_POST['MULTIPLE'] ?? null) === 'Y' ? 'Y' : 'N',
			'MANDATORY' => ($_POST['MANDATORY'] ?? 'N') === 'Y' ? 'Y' : 'N',
			'SHOW_FILTER' => $_POST['SHOW_FILTER'] === 'Y' ? 'E' : 'N', // E - 'By mask' is default
			'SHOW_IN_LIST' => $_POST['SHOW_IN_LIST'] === 'Y' ? 'Y' : 'N'
		);

		if(isset($_POST['USE_MULTI_LANG_LABEL']) && $_POST['USE_MULTI_LANG_LABEL'] === 'Y')
		{
			foreach($arLangs as $lid => $arLang)
			{
				$formLabel = isset($_POST['EDIT_FORM_LABEL']) && isset($_POST['EDIT_FORM_LABEL'][$lid]) ? trim($_POST['EDIT_FORM_LABEL'][$lid], " \n\r\t\x0") : '';
				if($formLabel === '')
				{
					$strError .= GetMessage('CC_BLFE_BAD_FIELD_NAME_LANG', array('#LANG_NAME#' => !empty($arLang['NAME']) ? $arLang['NAME'] : $lid)).'<br>';
				}

				$arField['EDIT_FORM_LABEL'][$lid] = $arField['LIST_COLUMN_LABEL'][$lid] = $arField['LIST_FILTER_LABEL'][$lid] = $formLabel;
			}
		}
		else
		{
			$formLabel = isset($_POST['COMMON_EDIT_FORM_LABEL']) ? trim($_POST['COMMON_EDIT_FORM_LABEL'], " \n\r\t\x0") : '';
			if($formLabel === '')
			{
				$strError .= GetMessage('CC_BLFE_BAD_FIELD_NAME').'<br>';
			}
			else
			{
				foreach($arLangs as $lid => $arLang)
				{
					$arField['EDIT_FORM_LABEL'][$lid] = $arField['LIST_COLUMN_LABEL'][$lid] = $arField['LIST_FILTER_LABEL'][$lid] = $formLabel;
				}
			}
		}

		switch ($arField['USER_TYPE_ID'])
		{
			case 'string':
				$arField['SETTINGS']['DEFAULT_VALUE'] = $_POST['DEFAULT_VALUE'];
				$arField['SETTINGS']['ROWS'] = $_POST['ROWS'];
				break;
			case 'url':
			case 'integer':
			case 'money':
				$arField['SETTINGS']['DEFAULT_VALUE'] = $_POST['DEFAULT_VALUE'];
			break;
			case 'double':
				$arField['SETTINGS']['DEFAULT_VALUE'] = $_POST['DEFAULT_VALUE'];
				$arField['SETTINGS']['PRECISION'] = 2;
			break;

			case 'boolean':
				$arField['SETTINGS']['DEFAULT_VALUE'] = $_POST['B_DEFAULT_VALUE'];
				$arField['MULTIPLE'] = 'N';
				$arField['SETTINGS']['DISPLAY'] = $_POST['B_DISPLAY'];
				$arField['SETTINGS']['LABEL_CHECKBOX'] = $arField['EDIT_FORM_LABEL'][LANGUAGE_ID];
			break;

			case 'datetime':
			case 'date':
				$arField['SETTINGS']['DEFAULT_VALUE']['VALUE'] = $_POST['DT_DEFAULT_VALUE'];
				$arField['SETTINGS']['DEFAULT_VALUE']['TYPE'] = $_POST['DT_TYPE'];
			break;

			case 'enumeration':
				$isNew = $arResult['NEW_FIELD'];
				$arField['SETTINGS']['DISPLAY'] = $_POST['E_DISPLAY'];
				$arField['SETTINGS']['LIST_HEIGHT'] = isset($_POST['E_LIST_HEIGHT']) ? $_POST['E_LIST_HEIGHT'] : 5;
				if ($arField['SETTINGS']['LIST_HEIGHT'] === '')
				{
					$arField['SETTINGS']['LIST_HEIGHT'] = 5;
				}
				$arField['SETTINGS']['CAPTION_NO_VALUE'] = isset($_POST['E_CAPTION_NO_VALUE']) ? trim($_POST['E_CAPTION_NO_VALUE']) : '';
				//create values 'map'
				$max_sort = 0;
				$arListMap = array();

				if(isset($_POST['LIST']) && is_array($_POST['LIST']))
				{
					$enumFieldMap = array();
					if(!$isNew)
					{
						$enumEntity = new CUserFieldEnum();
						$enumResult = $enumEntity->GetList(array(), array('USER_FIELD_ID' => $arResult['FIELD']['ID']));
						while($enumFields = $enumResult->Fetch())
						{
							$enumFieldMap[$enumFields['ID']] = $enumFields;
						}
					}

					foreach($_POST['LIST'] as $key => $value)
					{
						if($value['SORT'] > $max_sort)
							$max_sort = intval($value['SORT']);

						$trimValue = trim($value['VALUE'], " \t\n\r");

						if (mb_substr($key, 0, 1) == 'n' && $trimValue <> '' && isset($arListMap[$trimValue]))
							continue;

						if (mb_substr($key, 0, 1) === 'n')
						{
							$value['XML_ID'] = md5(uniqid('', true));
						}
						elseif(!$isNew && isset($enumFieldMap[$key]) && isset($enumFieldMap[$key]['XML_ID']))
						{
							//HACK: Protect XML_ID from change
							$value['XML_ID'] = $enumFieldMap[$key]['XML_ID'];
						}

						$arListMap[$trimValue] = $max_sort;
						$arField['LIST'][$key] = $value;
					}
				}
				if(!is_array($arField['LIST']))
					$arField['LIST'] = array();

				//Import values from textarea
				if(isset($_POST['LIST_TEXT_VALUES']) && mb_strlen($_POST['LIST_TEXT_VALUES']))
				{
					foreach(explode("\n", $_POST['LIST_TEXT_VALUES']) as $value_line)
					{
						$value = trim($value_line, " \t\n\r");
						if($value === '')
						{
							continue;
						}

						$xmlID = '';
						$match = null;
						if(preg_match('/^\[([^\]]+)\]\s*(.+)/', $value, $match) === 1 && count($match) === 3)
						{
							$xmlID = $match[1];
							$value = $match[2];
						}

						if(!isset($arListMap[$value]))
						{
							if($xmlID === '')
							{
								$xmlID = md5($value);
							}

							$max_sort += 10;
							$key = "n{$max_sort}";
							$arListMap[$value] = $key;
							$arField['LIST'][$key] = array(
								'SORT' => $max_sort,
								'VALUE' => $value,
								'XML_ID' => $xmlID
							);
						}
					}
				}

				if(isset($_POST['LIST_DEF']) && is_array($_POST['LIST_DEF']))
				{
					foreach($_POST['LIST_DEF'] as $def)
					{
						if($def === '')
						{
							continue;
						}

						$def = intval($def);
						if($def > 0 && isset($arField['LIST'][$def]))
						{
							$arField['LIST'][$def]['DEF'] = 'Y';
						}
					}

					foreach ($arField['LIST'] as $i => $arEnum)
					{
						if (empty($arEnum['DEF']) || $arEnum['DEF'] !== 'Y')
						{
							$arField['LIST'][$i]['DEF'] = 'N';
						}
					}
				}



			break;

			case 'iblock_section':
				$arField['SETTINGS']['IBLOCK_TYPE_ID'] = $_POST['IB_IBLOCK_TYPE_ID'];
				$arField['SETTINGS']['IBLOCK_ID'] = $_POST['IB_IBLOCK_ID'];
				$arField['SETTINGS']['DEFAULT_VALUE'] = $_POST['IB_DEFAULT_VALUE'];
				$arField['SETTINGS']['DISPLAY'] = $_POST['IB_DISPLAY'];
				$arField['SETTINGS']['LIST_HEIGHT'] = $_POST['IB_LIST_HEIGHT'];
				$arField['SETTINGS']['ACTIVE_FILTER'] = isset($_POST['IB_ACTIVE_FILTER']) && $_POST['IB_ACTIVE_FILTER'] == 'Y'? 'Y': 'N';
			break;

			case 'iblock_element':
				$arField['SETTINGS']['IBLOCK_TYPE_ID'] = $_POST['IB_IBLOCK_TYPE_ID'];
				$arField['SETTINGS']['IBLOCK_ID'] = (isset($_POST['IB_IBLOCK_ID']) ? (int)$_POST['IB_IBLOCK_ID'] : 0);
				if ($arField['SETTINGS']['IBLOCK_ID'] <= 0)
				{
					$strError .= GetMessage('CC_BLFE_ERR_IBLOCK_ELEMENT_BAD_IBLOCK_ID').'<br>';
				}
				$arField['SETTINGS']['DEFAULT_VALUE'] = $_POST['IB_DEFAULT_VALUE'];
				$arField['SETTINGS']['DISPLAY'] = $_POST['IB_DISPLAY'];
				$arField['SETTINGS']['LIST_HEIGHT'] = $_POST['IB_LIST_HEIGHT'];
				$arField['SETTINGS']['ACTIVE_FILTER'] = isset($_POST['IB_ACTIVE_FILTER']) && $_POST['IB_ACTIVE_FILTER'] == 'Y'? 'Y': 'N';
			break;

			case 'crm_status':
				$arField['SETTINGS']['ENTITY_TYPE'] = $_POST['ENTITY_TYPE'];
				if($arField['SHOW_FILTER'] !== 'N')
				{
					$arField['SHOW_FILTER'] = 'I'; // Force exact match for 'CRM STATUS' field type
				}
			break;

			case 'crm':
				$typeSettings = $_POST['ENTITY_TYPE'];
				foreach ($typeSettings as $entityTypeName => $status)
				{
					if (\CCrmOwnerType::ResolveID($entityTypeName))
					{
						$arField['SETTINGS'][$entityTypeName] = $status;
					}
				}
				if($arField['SHOW_FILTER'] !== 'N')
				{
					$arField['SHOW_FILTER'] = 'I'; // Force exact match for 'CRM' field type
				}
			break;
			case 'employee':
				if($arField['SHOW_FILTER'] !== 'N')
				{
					$arField['SHOW_FILTER'] = 'I'; // Force exact match for 'USER' field type
				}
				break;
			case 'address':
				break;
			default:
				$arField['SHOW_FILTER'] = 'N';
			break;
		}

		if(!$strError)
		{
			if($arResult['NEW_FIELD'])
			{
				$arResult['FIELD_ID'] = $arField['FIELD_NAME'] = $CCrmFields->GetNextFieldId();
				$res = $CCrmFields->AddField($arField);
			}
			else
				$res = $CCrmFields->UpdateField($arResult['FIELD']['ID'], $arField);

			if($res)
			{
				//Save default value for 'SHOW_IN_LIST'
				if($arResult['NEW_FIELD'])
				{
					$defaultShowInList = CUserOptions::GetOption('crm', 'uf_show_in_list', 'N');
					if($arField['SHOW_IN_LIST'] === 'N' && $defaultShowInList !== 'N')
					{
						CUserOptions::DeleteOption('crm', 'uf_show_in_list');
					}
					elseif($arField['SHOW_IN_LIST'] === 'Y' && $defaultShowInList !== 'Y')
					{
						CUserOptions::SetOption('crm', 'uf_show_in_list', 'Y');
					}
				}

				//Register/Unregister fild in entity list -->
				$gridID = CCrmGridOptions::GetDefaultGrigID(
					CCrmOwnerType::ResolveIDByUFEntityID($arResult['ENTITY_ID'])
				);

				if($arField['SHOW_IN_LIST'] === 'Y')
				{
					CCrmGridOptions::AddVisibleColumn($gridID, $arResult['FIELD_ID']);
				}
				else
				{
					CCrmGridOptions::RemoveVisibleColumn($gridID, $arResult['FIELD_ID']);
				}
				//<-- Register/Unregister fild in entity list

				//Clear components cache
				$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_fields_list_'.$arResult['ENTITY_ID']);

				//And go to proper page
				if (isset($_POST['apply']))
				{
					LocalRedirect(str_replace(
							array('#entity_id#', '#field_id#'),
							array($arResult['ENTITY_ID'], $arResult['FIELD_ID']),
							$arParams['~FIELD_EDIT_URL'])
					);
				}

				if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
				{
					$arResult['CLOSE_SLIDER'] = true;
					$this->IncludeComponentTemplate();
					return;
				}
				else
				{
					LocalRedirect($arResult['~FIELDS_LIST_URL']);
				}
			}
			else
			{
				$ex = $APPLICATION->GetException();
				ShowError($ex->GetString());
				$bVarsFromForm = true;
			}
		}
		else
		{
			ShowError($strError);
			$bVarsFromForm = true;
		}
	}
	elseif(isset($_POST['action']) && $_POST['action']==='type_changed')
	{
		$bVarsFromForm = true;
	}
	elseif($arResult['FIELD_ID'] && isset($_POST['action']) && $_POST['action']==='delete')
	{
		$CCrmFields->DeleteField($arResult['FIELD']['ID']);

		$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_fields_list_'.$arResult['ENTITY_ID']);

		if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
		{
			$arResult['CLOSE_SLIDER'] = true;
			$this->IncludeComponentTemplate();
			return;
		}
		else
		{
			LocalRedirect($arResult['~FIELDS_LIST_URL']);
		}
	}
	else
	{
		LocalRedirect($arResult['~ENTITY_LIST_URL']);
	}
}

if($bVarsFromForm)
{//There was an error so display form values
	$arResult['FIELD']['SORT'] = $_POST['SORT'];
	if(!empty($_POST['EDIT_FORM_LABEL']))
	{
		foreach($arLangs as $lid => $arLang)
		{
			$arResult['FIELD']["EDIT_FORM_LABEL[{$lid}]"] = isset($_POST['EDIT_FORM_LABEL'][$lid]) ? $_POST['EDIT_FORM_LABEL'][$lid] : '';
		}
	}

	$arResult['FIELD']['MANDATORY'] = $_POST['MANDATORY'];
	$arResult['FIELD']['MULTIPLE'] = $_POST['MULTIPLE'];
	$arResult['FIELD']['USER_TYPE_ID'] = $_POST['USER_TYPE_ID'];
	$arResult['FIELD']['DEFAULT_VALUE'] = $_POST['DEFAULT_VALUE'] ?? null;
	$arResult['FIELD']['SHOW_IN_LIST'] = $_POST['SHOW_IN_LIST'] == 'Y' ? 'Y' : 'N';
	if($_POST['USER_TYPE_ID'] === 'file')
	{
		$arResult['ENABLE_SHOW_FILTER'] = false;
		$arResult['FIELD']['SHOW_FILTER'] = 'N';
	}

	if ($arResult['FIELD']['USER_TYPE_ID'] === 'enumeration')
	{
		$arResult['FIELD']['E_DISPLAY'] = $_POST['E_DISPLAY'] ?? null;
		$arResult['FIELD']['E_LIST_HEIGHT'] = $_POST['E_LIST_HEIGHT'] ?? null;
		$arResult['FIELD']['E_CAPTION_NO_VALUE'] = $_POST['E_CAPTION_NO_VALUE'] ?? null;
	}

	if ($_POST['USER_TYPE_ID'] == 'string')
	{
		$arResult['FIELD']['ROWS'] = isset($_POST['ROWS']) ? $_POST['ROWS'] : 1;
	}

	if ($arResult['DISABLE_MULTIPLE'])
	{
		$arResult['FIELD']['MULTIPLE'] = 'N';
	}
	if ($arResult['DISABLE_MANDATORY'])
	{
		$arResult['FIELD']['MANDATORY'] = 'N';
	}

	if(isset($_POST['LIST']) && is_array($_POST['LIST']))
	{
		$n = 0;
		$arResult['LIST'] = array();
		foreach($_POST['LIST'] as $k => $v)
		{
			if(preg_match("/^n(\d+)$/", $k, $match))
			{
				if(intval($match[1]) > $n)
					$n = intval($match[1]);
			}
			$arResult['LIST'][$k] = array(
				'ID' => $k,
				'SORT' => $v['SORT'],
				'VALUE' => htmlspecialcharsbx($v['VALUE']),
			);
		}
		while($n >= 0)
		{
			if(array_key_exists('n'.$n, $arResult['LIST']))
			{
				if($arResult['LIST']['n'.$n]['VALUE'] <> '')
					break;
				else
					unset($arResult['LIST']['n'.$n]);
			}

			$n--;
		}
		$arResult['LIST'][] = array(
			'ID' => 'n'.($n+1),
			'SORT' => 500,
			'NAME' => '',
		);
	}
	elseif($arResult['FIELD']['USER_TYPE_ID'] == 'enumeration')
	{
		$arResult['LIST'] = array();
		$arResult['LIST'][] = array(
			'ID' => 'n0',
			'SORT' => 500,
			'NAME' => '',
		);
	}
	else
		$arResult['LIST'] = false;

	$arResult['FIELD']['LIST_TEXT_VALUES'] = $_POST['LIST_TEXT_VALUES'] ?? null;

	if(isset($_POST['LIST_DEF']) && is_array($_POST['LIST_DEF']))
	{
		$n = 0;
		$arResult['LIST_DEF'] = array();
		foreach($_POST['LIST_DEF'] as $def)
		{
			if(array_key_exists($def, $arResult['LIST']))
				$arResult['LIST_DEF'][$def] = true;
		}
	}
	elseif($arResult['FIELD']['USER_TYPE_ID'] == 'enumeration')
		$arResult['LIST_DEF'] = array();
	else
		$arResult['LIST_DEF'] = false;

	$arResult['USE_MULTI_LANG_LABEL'] = isset($_POST['USE_MULTI_LANG_LABEL']) && $_POST['USE_MULTI_LANG_LABEL'] === 'Y';
	$arResult['FIELD']['COMMON_EDIT_FORM_LABEL'] = isset($_POST['COMMON_EDIT_FORM_LABEL']) ? $_POST['COMMON_EDIT_FORM_LABEL'] : '';
}
elseif($arResult['FIELD_ID'])
{
	$userTypeID = $arResult['FIELD']['USER_TYPE_ID'];
	switch ($userTypeID)
	{
		case 'string':
			$arResult['FIELD']['DEFAULT_VALUE'] = $arResult['FIELD']['SETTINGS']['DEFAULT_VALUE'];
			$arResult['FIELD']['ROWS'] = isset($arResult['FIELD']['SETTINGS']['ROWS'])
				? $arResult['FIELD']['SETTINGS']['ROWS'] : 1;
			break;
		case 'url':
		case 'integer':
		case 'double':
		case 'money':
			$arResult['FIELD']['DEFAULT_VALUE'] = $arResult['FIELD']['SETTINGS']['DEFAULT_VALUE'];
		break;

		case 'boolean':
			$arResult['FIELD']['B_DEFAULT_VALUE'] = $arResult['FIELD']['SETTINGS']['DEFAULT_VALUE'];
			$arResult['FIELD']['B_DISPLAY'] = $arResult['FIELD']['SETTINGS']['DISPLAY'];
		break;

		case 'crm_status':
			$arResult['FIELD']['ENTITY_TYPE'] = $arResult['FIELD']['SETTINGS']['ENTITY_TYPE'];
		break;

		case 'crm':
			foreach ($arResult['FIELD']['SETTINGS'] as $entityTypeName => $status)
			{
				if (\CCrmOwnerType::ResolveID($entityTypeName))
				{
					$arResult['FIELD'][$entityTypeName] = $status;
				}
			}
		break;

		case 'iblock_section':
			$arResult['FIELD']['IB_IBLOCK_TYPE_ID'] = $arResult['FIELD']['SETTINGS']['IBLOCK_TYPE_ID'] ?? null;
			$arResult['FIELD']['IB_IBLOCK_ID'] = $arResult['FIELD']['SETTINGS']['IBLOCK_ID'];
			$arResult['FIELD']['IB_DEFAULT_VALUE'] = $arResult['FIELD']['SETTINGS']['DEFAULT_VALUE'];
			$arResult['FIELD']['IB_DISPLAY'] = $arResult['FIELD']['SETTINGS']['DISPLAY'];
			$arResult['FIELD']['IB_LIST_HEIGHT'] = $arResult['FIELD']['SETTINGS']['LIST_HEIGHT'];
			$arResult['FIELD']['IB_ACTIVE_FILTER'] = $arResult['FIELD']['SETTINGS']['ACTIVE_FILTER'] == 'Y'? 'Y': 'N';
		break;

		case 'iblock_element':
			$arResult['FIELD']['IB_IBLOCK_TYPE_ID'] = $arResult['FIELD']['SETTINGS']['IBLOCK_TYPE_ID'] ?? null;
			$arResult['FIELD']['IB_IBLOCK_ID'] = $arResult['FIELD']['SETTINGS']['IBLOCK_ID'];
			$arResult['FIELD']['IB_DEFAULT_VALUE'] = $arResult['FIELD']['SETTINGS']['DEFAULT_VALUE'];
			$arResult['FIELD']['IB_DISPLAY'] = $arResult['FIELD']['SETTINGS']['DISPLAY'];
			$arResult['FIELD']['IB_LIST_HEIGHT'] = $arResult['FIELD']['SETTINGS']['LIST_HEIGHT'];
			$arResult['FIELD']['IB_ACTIVE_FILTER'] = $arResult['FIELD']['SETTINGS']['ACTIVE_FILTER'] == 'Y'? 'Y': 'N';
		break;

		case 'datetime':
		case 'date':
		{
			if($userTypeID === 'datetime')
			{
				$arResult['FIELD']['DT_DEFAULT_VALUE'] = CDatabase::FormatDate(
					$arResult['FIELD']['SETTINGS']['DEFAULT_VALUE']['VALUE'],
					'YYYY-MM-DD HH:MI:SS',
					CLang::GetDateFormat('FULL')
				);
			}
			else
			{
				$arResult['FIELD']['DT_DEFAULT_VALUE'] = CDatabase::FormatDate(
					$arResult['FIELD']['SETTINGS']['DEFAULT_VALUE']['VALUE'],
					'YYYY-MM-DD',
					CLang::GetDateFormat('SHORT')
				);
			}
			$arResult['FIELD']['DT_TYPE'] = $arResult['FIELD']['SETTINGS']['DEFAULT_VALUE']['TYPE'];
		}
		break;

		case 'enumeration':
			$arResult['LIST'] = array();
			$arResult['LIST_DEF'] = array();
			if (is_callable(array($arResult['FIELD']['USER_TYPE']['CLASS_NAME'], 'GetList')))
			{
				$rsEnum = call_user_func_array(array($arResult['FIELD']['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arResult['FIELD']));
				while($ar = $rsEnum->GetNext())
				{
					$arResult['LIST'][$ar['ID']] = $ar;
					if($ar['DEF'] == 'Y')
						$arResult['LIST_DEF'][$ar['ID']] = true;
				}
			}
			$arResult['LIST'][] = array(
				'ID' => 'n0',
				'SORT' => 500,
				'NAME' => '',
			);
			$data['LIST_TEXT_VALUES'] = '';
			$arResult['FIELD']['E_CAPTION_NO_VALUE'] = $arResult['FIELD']['SETTINGS']['CAPTION_NO_VALUE'];
			$arResult['FIELD']['E_DISPLAY'] = $arResult['FIELD']['SETTINGS']['DISPLAY'];
			$arResult['FIELD']['E_LIST_HEIGHT'] = $arResult['FIELD']['SETTINGS']['LIST_HEIGHT'];
		break;
		case 'file':
			$arResult['ENABLE_SHOW_FILTER'] = false;
		break;
	}

	$commonEditFormLabel = '';
	if(!empty($arResult['FIELD']['EDIT_FORM_LABEL']))
	{
		$labels = array();
		foreach($arLangs as $lid => $arLang)
		{
			$label = isset($arResult['FIELD']['EDIT_FORM_LABEL'][$lid]) ? $arResult['FIELD']['EDIT_FORM_LABEL'][$lid] : '';
			if(!in_array($label, $labels, true))
			{
				$labels[] = $label;
			}
			$arResult['FIELD']["EDIT_FORM_LABEL[{$lid}]"] = $label;
			if($lid === LANGUAGE_ID)
			{
				$commonEditFormLabel = $label;
			}
		}
	}
	$arResult['FIELD']['COMMON_EDIT_FORM_LABEL'] = $commonEditFormLabel;
	//If is only one label switch off using of multilang labels
	$arResult['USE_MULTI_LANG_LABEL'] = count($labels) !== 1;
}
else
{//New one
	$arResult['FIELD']['SORT'] = 100;
	$arResult['FIELD']['EDIT_FORM_LABEL['.LANGUAGE_ID.']'] = GetMessage('CRM_FIELDS_EDIT_NAME_DEFAULT');
	$arResult['FIELD']['COMMON_EDIT_FORM_LABEL'] = GetMessage('CRM_FIELDS_EDIT_NAME_DEFAULT');
	$arResult['FIELD']['USER_TYPE_ID'] = 'string';
	$arResult['FIELD']['MANDATORY'] = 'N';
	$arResult['FIELD']['MULTIPLE'] = 'N';
	$arResult['FIELD']['DEFAULT_VALUE'] = '';
	$arResult['FIELD']['ROWS'] = '1';
	$arResult['FIELD']['LIST_TEXT_VALUES'] = '';
	$arResult['LIST'] = false;
	$arResult['LIST_DEF'] = false;
	$arResult['FIELD']['SHOW_FILTER'] = 'Y';
	$arResult['FIELD']['SHOW_IN_LIST'] = CUserOptions::GetOption('crm', 'uf_show_in_list', 'N');
}



$arResult['FORM_DATA'] = array();
foreach($arResult['FIELD'] as $key => $value)
{
	$arResult['FORM_DATA']['~'.$key] = $value;
	if(is_array($value))
	{
		foreach($value as $key1 => $value1)
		{
			if (!is_array($value1))
				$value[$key1] = htmlspecialcharsbx($value1);
		}
		$arResult['FORM_DATA'][$key] = $value;
	}
	else
	{
		$arResult['FORM_DATA'][$key] = htmlspecialcharsbx($value);
	}
}

//region Disable money field due to invalid layout in old forms
$arFieldTypes = $CCrmFields->GetFieldTypes();
if(!\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
{
	unset($arFieldTypes['money']);
}
//endregion

$arResult['TYPES'] = array();
foreach($arFieldTypes as $key => $ar)
{
	// 'resourcebooking' (UF from calendar module) - is available only for several entity types.
	// So here we are trying to show it only for whose entities.
	if ($ar['ID'] == 'resourcebooking' && !\Bitrix\Crm\Integration\Calendar::isResourceBookingAvailableForEntity($arParams['FIELS_ENTITY_ID']))
	{
		continue;
	}
	$arResult['TYPES'][$ar['ID']] = $ar['NAME'];
}

$arResult['FIELD']['ADDITIONAL_FIELDS'] = CCrmFields::GetAdditionalFields($arResult['FIELD']['USER_TYPE_ID'], $arResult['FIELD']);

$this->IncludeComponentTemplate();

$fieldEditlabel = !empty($arResult['FIELD_ID']) && !empty($arResult['FIELD'])
	? (!empty($arResult['FIELD']['EDIT_FORM_LABEL'][LANGUAGE_ID]) ? $arResult['FIELD']['EDIT_FORM_LABEL'][LANGUAGE_ID] : $arResult['FIELD_ID'])
	: '';

if(empty($arResult['FIELD_ID']))
{
	$APPLICATION->SetTitle(GetMessage('CC_BLFE_TITLE_NEW'));
}
else
{
	$APPLICATION->SetTitle(
		GetMessage(
			'CC_BLFE_TITLE_EDIT',
			array('#NAME#' => htmlspecialcharsex($fieldEditlabel))
		)
	);
}

$arEntityIds = CCrmFields::GetEntityTypes();
$arResult['ENTITY_NAME'] = $arEntityIds[$arResult['ENTITY_ID']]['NAME'];

$APPLICATION->AddChainItem(GetMessage('CRM_FIELDS_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);
$APPLICATION->AddChainItem($arResult['ENTITY_NAME'], $arResult['~FIELDS_LIST_URL']);
if(!empty($arResult['FIELD_ID']))
{
	$APPLICATION->AddChainItem($fieldEditlabel, $arResult['~FIELD_EDIT_URL']);
}
