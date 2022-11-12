<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
if (!CModule::IncludeModule('mobile'))
{
	ShowError(GetMessage('CRM_MOBILE_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

global $USER_FIELD_MANAGER, $DB, $USER, $APPLICATION;
$CCrmLead = new CCrmLead();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('LEAD');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$bEdit = false;
$bCopy = false;
$bVarsFromForm = false;

$entityID = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['lead_id']))
{
	$entityID = $arParams['ELEMENT_ID'] = intval($_REQUEST['lead_id']);
}
$arResult['ELEMENT_ID'] = $entityID;

if (!empty($arParams['ELEMENT_ID']))
{
	$bEdit = true;
}
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}

$arResult["IS_EDIT_PERMITTED"] = false;
$arResult["IS_VIEW_PERMITTED"] = false;
$arResult["IS_DELETE_PERMITTED"] = CCrmLead::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if($bEdit)
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmLead::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"])
	{
		$arResult["IS_VIEW_PERMITTED"] = CCrmLead::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
}
elseif($bCopy)
{
	$arResult["IS_VIEW_PERMITTED"] = CCrmLead::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmLead::CheckCreatePermission($userPermissions);
}

if(!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"])
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('LEAD', array($arParams['ELEMENT_ID']))
	: array();

if($bEdit)
{
	CCrmLead::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $userPermissions);
}

$arResult['ELEMENT'] = array();

if ($bEdit || $bCopy)
{
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	$obFields = CCrmLead::GetListEx(
		array(),
		array('=ID' => $arParams['ELEMENT_ID'], 'CHECK_PERMISSIONS'=> 'N')
	);
	$arFields = is_object($obFields) ? $obFields->GetNext() : false;

	if(!is_array($arFields))
	{
		ShowError(GetMessage('CRM_LEAD_EDIT_NOT_FOUND', array("#ID#" => $arParams['ELEMENT_ID'])));
		return;
	}

	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}

	if ($bCopy)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
	}

	//HACK: MSSQL returns '.00' for zero value
	if(isset($arFields['~OPPORTUNITY']))
	{
		$arFields['~OPPORTUNITY'] = $arFields['OPPORTUNITY'] = floatval($arFields['~OPPORTUNITY']);
	}

	if(isset($arFields['~OPPORTUNITY_ACCOUNT']))
	{
		$arFields['~OPPORTUNITY_ACCOUNT'] = $arFields['OPPORTUNITY_ACCOUNT'] = floatval($arFields['~OPPORTUNITY_ACCOUNT']);
	}
}
else
{
	$arResult['MODE'] = 'CREATE';

	$arFields = array(
		'ID' => 0
	);
	if (isset($_GET['title']))
	{
		$arFields['~TITLE'] = $_GET['title'];
		CUtil::decodeURIComponent($arFields['~TITLE']);
		$arFields['TITLE'] = htmlspecialcharsbx($arFields['~TITLE']);
	}
	if (isset($_GET['honorific']))
	{
		$arFields['~HONORIFIC'] = $_GET['honorific'];
		CUtil::decodeURIComponent($arFields['~HONORIFIC']);
		$arFields['HONORIFIC'] = htmlspecialcharsbx($arFields['~HONORIFIC']);
	}
	if (isset($_GET['name']))
	{
		$arFields['~NAME'] = $_GET['name'];
		CUtil::decodeURIComponent($arFields['~NAME']);
		$arFields['NAME'] = htmlspecialcharsbx($arFields['~NAME']);
	}
	if (isset($_GET['second_name']))
	{
		$arFields['~SECOND_NAME'] = $_GET['second_name'];
		CUtil::decodeURIComponent($arFields['~SECOND_NAME']);
		$arFields['SECOND_NAME'] = htmlspecialcharsbx($arFields['~SECOND_NAME']);
	}
	if (isset($_GET['last_name']))
	{
		$arFields['~LAST_NAME'] = $_GET['last_name'];
		CUtil::decodeURIComponent($arFields['~LAST_NAME']);
		$arFields['LAST_NAME'] = htmlspecialcharsbx($arFields['~LAST_NAME']);
	}
	if (isset($_GET['address']))
	{
		$arFields['~ADDRESS'] = $_GET['address'];
		CUtil::decodeURIComponent($arFields['~ADDRESS']);
		$arFields['ADDRESS'] = htmlspecialcharsbx($arFields['~ADDRESS']);
	}
	if (isset($_GET['address_2']))
	{
		$arFields['~ADDRESS_2'] = $_GET['address_2'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_2']);
		$arFields['ADDRESS_2'] = htmlspecialcharsbx($arFields['~ADDRESS_2']);
	}
	if (isset($_GET['address_city']))
	{
		$arFields['~ADDRESS_CITY'] = $_GET['address_city'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_CITY']);
		$arFields['ADDRESS_CITY'] = htmlspecialcharsbx($arFields['~ADDRESS_CITY']);
	}
	if (isset($_GET['address_postal_code']))
	{
		$arFields['~ADDRESS_POSTAL_CODE'] = $_GET['address_postal_code'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_POSTAL_CODE']);
		$arFields['ADDRESS_POSTAL_CODE'] = htmlspecialcharsbx($arFields['~ADDRESS_POSTAL_CODE']);
	}
	if (isset($_GET['address_region']))
	{
		$arFields['~ADDRESS_REGION'] = $_GET['address_region'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_REGION']);
		$arFields['ADDRESS_REGION'] = htmlspecialcharsbx($arFields['~ADDRESS_REGION']);
	}
	if (isset($_GET['address_province']))
	{
		$arFields['~ADDRESS_PROVINCE'] = $_GET['address_province'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_PROVINCE']);
		$arFields['ADDRESS_PROVINCE'] = htmlspecialcharsbx($arFields['~ADDRESS_PROVINCE']);
	}
	if (isset($_GET['address_country']))
	{
		$arFields['~ADDRESS_COUNTRY'] = $_GET['address_country'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_COUNTRY']);
		$arFields['ADDRESS_COUNTRY'] = htmlspecialcharsbx($arFields['~ADDRESS_COUNTRY']);
	}
	if (isset($_GET['email']) || isset($_GET['phone']) || isset($_GET['tel']))
	{
		if(isset($_GET['email']))
		{
			$email = $_GET['email'];
			CUtil::decodeURIComponent($email);
			trim($email);
		}
		else
		{
			$email = '';
		}

		if(isset($_GET['phone']) || isset($_GET['tel']))
		{
			$phone = isset($_GET['phone']) ? $_GET['phone'] : $_GET['tel'];
			CUtil::decodeURIComponent($phone);
			trim($phone);
		}
		else
		{
			$phone = '';
		}

		$arFields['FM'] = array();
		if($email !== '')
		{
			$arFields['FM']['EMAIL'] = array(
				'n0' => array('VALUE' => $email, 'VALUE_TYPE' => 'WORK')
			);
		}
		if($phone !== '')
		{
			$arFields['FM']['PHONE'] = array(
				'n0' => array('VALUE' => $phone, 'VALUE_TYPE' => 'WORK'));
		}
	}
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

$productDataFieldName = $arResult["productDataFieldName"] = 'LEAD_PRODUCT_DATA';

//region Post handler
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && $arResult["IS_EDIT_PERMITTED"])
{
	$bVarsFromForm = true;
	if(isset($_POST['save']) || isset($_POST['apply']))
	{
		CUtil::JSPostUnescape();

		$arFields = array();

		if(isset($_POST['TITLE']))
		{
			$arFields['TITLE'] = trim($_POST['TITLE']);
		}

		if(isset($_POST['COMMENTS']))
		{
			$comments = isset($_POST['COMMENTS']) ? trim($_POST['COMMENTS']) : '';
			$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);
			$arFields['COMMENTS'] = $comments;
		}

		if(isset($_POST['COMPANY_TITLE']))
		{
			$arFields['COMPANY_TITLE'] = trim($_POST['COMPANY_TITLE']);
		}

		if(isset($_POST['HONORIFIC']))
		{
			$arFields['HONORIFIC'] = trim($_POST['HONORIFIC']);
		}

		if(isset($_POST['LAST_NAME']))
		{
			$arFields['LAST_NAME'] = trim($_POST['LAST_NAME']);
		}

		if(isset($_POST['NAME']))
		{
			$arFields['NAME'] = trim($_POST['NAME']);
		}

		if(isset($_POST['SECOND_NAME']))
		{
			$arFields['SECOND_NAME'] = trim($_POST['SECOND_NAME']);
		}

		if(!$bEdit && (!isset($arFields['TITLE']) || $arFields['TITLE'] === ''))
		{
			if((isset($arFields['NAME']) && $arFields['NAME'] !== '')
				|| (isset($arFields['LAST_NAME']) && $arFields['LAST_NAME'] !== ''))
			{
				$arFields['TITLE'] = CCrmLead::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($arFields['HONORIFIC']) ? $arFields['HONORIFIC'] : '',
						'NAME' => isset($arFields['NAME']) ? $arFields['NAME'] : '',
						'SECOND_NAME' => isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : ''
					)
				);
			}
			else
			{
				$arFields['TITLE'] = GetMessage('CRM_LEAD_EDIT_DEFAULT_TITLE');
			}
		}

		if(isset($_POST['POST']))
		{
			$arFields['POST'] = trim($_POST['POST']);
		}

		$addressFieldNames = array();

		if(isset($_POST['ADDRESS']))
		{
			$arFields['ADDRESS'] = trim($_POST['ADDRESS']);
			$addressFieldNames[] = 'ADDRESS';
		}

		if(isset($_POST['ADDRESS_2']))
		{
			$arFields['ADDRESS_2'] = trim($_POST['ADDRESS_2']);
			$addressFieldNames[] = 'ADDRESS_2';
		}

		if(isset($_POST['ADDRESS_CITY']))
		{
			$arFields['ADDRESS_CITY'] = trim($_POST['ADDRESS_CITY']);
			$addressFieldNames[] = 'ADDRESS_CITY';
		}

		if(isset($_POST['ADDRESS_POSTAL_CODE']))
		{
			$arFields['ADDRESS_POSTAL_CODE'] = trim($_POST['ADDRESS_POSTAL_CODE']);
			$addressFieldNames[] = 'ADDRESS_POSTAL_CODE';
		}

		if(isset($_POST['ADDRESS_REGION']))
		{
			$arFields['ADDRESS_REGION'] = trim($_POST['ADDRESS_REGION']);
			$addressFieldNames[] = 'ADDRESS_REGION';
		}

		if(isset($_POST['ADDRESS_PROVINCE']))
		{
			$arFields['ADDRESS_PROVINCE'] = trim($_POST['ADDRESS_PROVINCE']);
			$addressFieldNames[] = 'ADDRESS_PROVINCE';
		}

		if(isset($_POST['ADDRESS_COUNTRY']))
		{
			$arFields['ADDRESS_COUNTRY'] = trim($_POST['ADDRESS_COUNTRY']);
			$addressFieldNames[] = 'ADDRESS_COUNTRY';
		}

		if(isset($_POST['ADDRESS_COUNTRY_CODE']))
		{
			$arFields['ADDRESS_COUNTRY_CODE'] = trim($_POST['ADDRESS_COUNTRY_CODE']);
			$addressFieldNames[] = 'ADDRESS_COUNTRY_CODE';
		}

		if(isset($_POST['SOURCE_DESCRIPTION']))
		{
			$arFields['SOURCE_DESCRIPTION'] = trim($_POST['SOURCE_DESCRIPTION']);
		}

		if(isset($_POST['STATUS_DESCRIPTION']))
		{
			$arFields['STATUS_DESCRIPTION'] = trim($_POST['STATUS_DESCRIPTION']);
		}

		if(isset($_POST['OPPORTUNITY']))
		{
			$arFields['OPPORTUNITY'] = trim($_POST['OPPORTUNITY']);
		}

		if(isset($_POST['SOURCE_ID']))
		{
			$arFields['SOURCE_ID'] = trim($_POST['SOURCE_ID']);
		}

		if(isset($_POST['STATUS_ID']))
		{
			$arFields['STATUS_ID'] = trim($_POST['STATUS_ID']);
		}

		if(isset($_POST['STATUS_ID']))
		{
			$arFields['STATUS_ID'] = trim($_POST['STATUS_ID']);
		}

		if(isset($_POST['OPENED']))
		{
			$arFields['OPENED'] = mb_strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
		}
		elseif(!$bEdit)
		{
			$arFields['OPENED'] = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}

		if(isset($_POST['ASSIGNED_BY_ID']))
		{
			$arFields['ASSIGNED_BY_ID'] = (int)(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']);
		}

		if(isset($_POST['LFM']))
		{
			$arFields['FM'] = $_POST['LFM'];
		}

		if(isset($_POST['BIRTHDATE']))
		{
			$arFields['BIRTHDATE'] = $_POST['BIRTHDATE'];
		}

		if(isset($_POST['CURRENCY_ID']))
		{
			$arFields['CURRENCY_ID'] = $_POST['CURRENCY_ID'];
		}

		$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
		if(!($currencyID !== '' && CCrmCurrency::IsExists($currencyID)))
		{
			$currencyID = CCrmCurrency::GetBaseCurrencyID();
		}

		$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
		if(!($currencyID !== '' && CCrmCurrency::IsExists($currencyID)))
		{
			$currencyID = $arFields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		}
		$arFields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($currencyID);

		$originID = isset($_REQUEST['origin_id']) ? $_REQUEST['origin_id'] : '';
		if($originID !== '')
		{
			$arFields['ORIGIN_ID'] = $originID;
		}

		$processProductRows = array_key_exists($productDataFieldName, $_POST);
		$arProd = array();
		if($processProductRows)
		{
			$arProd = isset($_POST[$productDataFieldName]) ? ($_POST[$productDataFieldName]) : array();
			if(!empty($arProd))
			{
				// SYNC OPPORTUNITY WITH PRODUCT ROW SUM TOTAL
				$params = array(
					'CONTACT_ID' => 0,
					'COMPANY_ID' => 0,
					'CURRENCY_ID' => $arFields['CURRENCY_ID']
				);
				$result = CCrmProductRow::CalculateTotalInfo('L', 0, false, $params, $arProd);
				$arFields['OPPORTUNITY'] = isset($result['OPPORTUNITY']) ? $result['OPPORTUNITY'] : 0.0;
			}
		}

		// Product row settings
		$productRowSettings = array();
		$productRowSettingsFieldName = $productDataFieldName.'_SETTINGS';
		if(array_key_exists($productRowSettingsFieldName, $_POST))
		{
			$settingsJson = isset($_POST[$productRowSettingsFieldName]) ? strval($_POST[$productRowSettingsFieldName]) : '';
			$arSettings = $settingsJson <> '' ? CUtil::JsObjectToPhp($settingsJson) : array();
			if(is_array($arSettings))
			{
				$productRowSettings['ENABLE_DISCOUNT'] = isset($arSettings['ENABLE_DISCOUNT']) ? $arSettings['ENABLE_DISCOUNT'] === 'Y' : false;
				$productRowSettings['ENABLE_TAX'] = isset($arSettings['ENABLE_TAX']) ? $arSettings['ENABLE_TAX'] === 'Y' : false;
			}
		}
		unset($productRowSettingsFieldName, $settingsJson, $arSettings);

		$USER_FIELD_MANAGER->EditFormAddFields(CCrmLead::$sUFEntityID, $arFields, array('FORM' => $_POST));
		if($bCopy)
		{
			$CCrmUserType->CopyFileFields($arFields);
		}

		$arResult['ERROR_MESSAGE'] = '';

		if (!$CCrmLead->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false, array('DISABLE_USER_FIELD_CHECK' => true)))
		{
			if (!empty($CCrmLead->LAST_ERROR))
				$arResult['ERROR_MESSAGE'] .= $CCrmLead->LAST_ERROR;
			else
				$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
		}

		if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($bEdit ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $bEdit ? $arEntityAttr : null)) === false)
			$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

		if (empty($arResult['ERROR_MESSAGE']))
		{
			$DB->StartTransaction();

			$bSuccess = false;
			if ($bEdit)
			{
				$bSuccess = $CCrmLead->Update(
					$arResult['ELEMENT']['ID'],
					$arFields,
					true,
					true,
					array(
						'REGISTER_SONET_EVENT' => true,
						'ADDRESS_FIELDS' => $addressFieldNames,
						'DISABLE_USER_FIELD_CHECK' => true
					)
				);
			}
			else
			{
				$ID = $CCrmLead->Add($arFields, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
				$bSuccess = $ID !== false;
				if($bSuccess)
				{
					$arResult['ELEMENT']['ID'] = $ID;
				}
			}

			if ($bSuccess)
			{
				// Save settings
				if(is_array($productRowSettings) && count($productRowSettings) > 0)
				{
					$arSettings = CCrmProductRow::LoadSettings('L', $arResult['ELEMENT']['ID']);
					foreach ($productRowSettings as $k => $v)
						$arSettings[$k] = $v;
					CCrmProductRow::SaveSettings('L', $arResult['ELEMENT']['ID'], $arSettings);
				}
				unset($arSettings);
			}

			if($bSuccess
				&& $processProductRows
				&& ($bEdit || !empty($arProd)))
			{
				// Suppress owner synchronization
				$bSuccess = CCrmLead::SaveProductRows($arResult['ELEMENT']['ID'], $arProd, true, true, false);
				if(!$bSuccess)
				{
					$arResult['ERROR_MESSAGE'] = GetMessage('PRODUCT_ROWS_SAVING_ERROR');
				}
			}

			if($bSuccess)
			{
				$DB->Commit();
			}
			else
			{
				$DB->Rollback();
				$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
			}
		}

		if (empty($arResult['ERROR_MESSAGE']))
		{
			if (!$CCrmBizProc->StartWorkflow($arResult['ELEMENT']['ID'], $arBizProcParametersValues))
				$arResult['ERROR_MESSAGE'] = $CCrmBizProc->LAST_ERROR;
		}

		//Region automation
		$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $arResult['ELEMENT']['ID']);
		$starter->setContextToMobile()->setUserIdFromCurrent();
		if(!$bEdit)
		{
			$starter->runOnAdd();
		}
		else
		{
			$starter->runOnUpdate($arFields, is_array($arResult['ELEMENT']) ? $arResult['ELEMENT'] : []);
		}
		//end automation

		$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

		$arJsonData = array();
		if (!empty($arResult['ERROR_MESSAGE']))
		{
			$arJsonData = array("error" => str_replace("<br>", "\n", preg_replace("/<br( )?(\/)?>/i", "\n", $arResult['ERROR_MESSAGE'])));
		}
		else
		{
			$arJsonData = array("success" => "Y", "itemId" => $ID);
		}

		$APPLICATION->RestartBuffer();
		echo \Bitrix\Main\Web\Json::encode($arJsonData);
		CMain::FinalActions();
		die();
	}
}
//endregion

if($bCopy)
{
	$arResult['ELEMENT']['STATUS_ID'] = 'NEW';
}

$arResult['FORM_ID'] = 'CRM_LEAD_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_LEAD_LIST_V12';
$arResult['STATUS_LIST'] = array();
$arResult['~STATUS_LIST'] = CCrmStatus::GetStatusList('STATUS');
$arResult['DUPLICATE_CONTROL'] = array();
$enableDupControl = $arResult['DUPLICATE_CONTROL']['ENABLED'] =
	!$bEdit && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Lead);

foreach ($arResult['~STATUS_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($userPermissions->GetPermType('LEAD', $bEdit ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}

$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
$arResult['HONORIFIC_LIST'] = CCrmStatus::GetStatusList('HONORIFIC');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EDIT'] = $bEdit;
$arResult['IS_COPY'] = $bCopy;

$arResult['LEAD_VIEW_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['LEAD_VIEW_URL_TEMPLATE'],
	array('lead_id' => $entityID)
);
$arResult['LEAD_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['LEAD_EDIT_URL_TEMPLATE'],
	array('lead_id' => $entityID)
);

$arParams['CONTACT_SELECTOR_URL_TEMPLATE'] = CHTTP::urlAddParams($arParams['CONTACT_SELECTOR_URL_TEMPLATE'], array("event" => "onCrmConvertSelectContactForLead_".$entityID));
$arParams['COMPANY_SELECTOR_URL_TEMPLATE'] = CHTTP::urlAddParams($arParams['COMPANY_SELECTOR_URL_TEMPLATE'], array("event" => "onCrmConvertSelectContactForLead_".$entityID));

/*============= fields for main.interface.form =========*/
$arResult['FIELDS'] = array();

$arResult['FIELDS'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label'
);

if($arResult['ELEMENT']['STATUS_ID'] !== 'CONVERTED')
{
	if (in_array($arResult['ELEMENT']['~STATUS_ID'], array_keys($arResult['STATUS_LIST'])))
	{
		unset($arResult['STATUS_LIST']['CONVERTED']);

		if ($arResult["IS_EDIT_PERMITTED"])
			$value = (isset($arResult['ELEMENT']['~STATUS_ID']) ? $arResult['ELEMENT']['~STATUS_ID'] : '');
		else
			$value = (isset($arResult['ELEMENT']['~STATUS_ID']) ? $arResult['STATUS_LIST'][$arResult['ELEMENT']['~STATUS_ID']] : '');

		$arResult['FIELDS'][] = array(
			'id' => 'STATUS_ID',
			'name' => GetMessage('CRM_FIELD_STATUS_ID'),
			'params' => array(),
			'items' => $arResult['STATUS_LIST'],
			'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
			'value' => $value
		);
	}
}
else
{
	$arResult['FIELDS'][] = array(
		'id' => 'STATUS_ID',
		'name' => GetMessage('CRM_FIELD_STATUS_ID'),
		'params' => array(),
		'type' => 'label',
		'value' => htmlspecialcharsbx($arResult['~STATUS_LIST']['CONVERTED'])
	);
}

$arResult['FIELDS'][] = array(
	'id' => 'STATUS_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_STATUS_DESCRIPTION'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'textarea' : 'label',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['STATUS_DESCRIPTION']) ? $arResult['ELEMENT']['STATUS_DESCRIPTION'] : ''
);

$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(($bEdit || $bCopy) && isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

if ($arResult["IS_EDIT_PERMITTED"])
	$value = $currencyID;
else
	$value = $arResult['CURRENCY_LIST'][$currencyID];

$arResult['FIELDS'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'items' => $arResult['CURRENCY_LIST'],
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'value' => $value
);
$arResult['FIELDS'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : ''
);

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['SOURCE_ID']) ? $arResult['ELEMENT']['SOURCE_ID'] : '');
else
	$value = (isset($arResult['ELEMENT']['SOURCE_ID']) ? $arResult['SOURCE_LIST'][$arResult['ELEMENT']['SOURCE_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['SOURCE_LIST'],
	'value' => $value
);
$arResult['FIELDS'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'textarea' : 'label',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['SOURCE_DESCRIPTION'] : ''
);

$arResult['FIELDS'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user',
	'canDrop' => false,
	'item' => CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()),
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS'][] = array(
	'id' => 'OPENED',
	'type' => 'checkbox',
	"items" => array(
		"Y" => GetMessage('CRM_FIELD_OPENED')
	),
	'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getOpenedFlag(),
);

$arResult['HONORIFIC_LIST'] = CCrmStatus::GetStatusList('HONORIFIC');

if ($arResult["IS_EDIT_PERMITTED"])
	$value = isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['ELEMENT']['~HONORIFIC'] : '';
else
	$value = (isset($arResult['ELEMENT']['~HONORIFIC']) ?$arResult['HONORIFIC_LIST'][$arResult['ELEMENT']['~HONORIFIC']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'HONORIFIC',
	'name' => GetMessage('CRM_FIELD_HONORIFIC'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => array('0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')) + $arResult['HONORIFIC_LIST'],
	'value' => $value
);

//contact
$lastNameID = $arResult['FORM_ID'].'_LAST_NAME';
$lastNameCaptionID = $arResult['FORM_ID'].'_LAST_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['LAST_NAME_ID'] = $lastNameID;
	$arResult['DUPLICATE_CONTROL']['LAST_NAME_CAPTION_ID'] = $lastNameCaptionID;
}

$arResult['FIELDS'][] = array(
	'id' => 'LAST_NAME',
	'name' => GetMessage('CRM_FIELD_LAST_NAME'),
	'nameWrapper' => $lastNameCaptionID,
	'params' => array('id' => $lastNameID, 'size' => 50),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'value' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
);

$nameID = $arResult['FORM_ID'].'_NAME';
$nameCaptionID = $arResult['FORM_ID'].'_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['NAME_ID'] = $nameID;
	$arResult['DUPLICATE_CONTROL']['NAME_CAPTION_ID'] = $nameCaptionID;
}
$arResult['FIELDS'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_LEAD_FIELD_NAME'),
	'nameWrapper' => $nameCaptionID,
	'params' => array('id' => $nameID, 'size' => 50),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'value' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
);

$secondNameID = $arResult['FORM_ID'].'_SECOND_NAME';
$secondNameCaptionID = $arResult['FORM_ID'].'_SECOND_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['SECOND_NAME_ID'] = $secondNameID;
	$arResult['DUPLICATE_CONTROL']['SECOND_NAME_CAPTION_ID'] = $secondNameCaptionID;
}
$arResult['FIELDS'][] = array(
	'id' => 'SECOND_NAME',
	'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
	'nameWrapper' => $secondNameCaptionID,
	'params' => array('id' => $secondNameID, 'size' => 50),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'value' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : '',
);

$birthDate = isset($arResult['ELEMENT']['BIRTHDATE']) ? $arResult['ELEMENT']['BIRTHDATE'] : '';
if($birthDate !== '')
{
	//To preserve user value if failed to get timestamp
	$birthDateTimestamp = MakeTimeStamp($birthDate);
	if($birthDateTimestamp !== false)
	{
		$birthDate = ConvertTimeStamp($birthDateTimestamp, 'SHORT', SITE_ID);
	}
}
$arResult['FIELDS'][] = array(
	'id' => 'BIRTHDATE',
	'name' => GetMessage('CRM_LEAD_EDIT_FIELD_BIRTHDATE'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'value' => $birthDate
);

//-- multi fields
$phoneEditorID = uniqid('COMFM_PHONE_');
ob_start(); //phone
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
	array(
		'FM_MNEMONIC' => 'LFM',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'EDITOR_ID' => $phoneEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();

$fields = array(
	'id' => 'PHONE',
	'type' => 'custom',
	'value' => $sVal
);
if (!$arParams["RESTRICTED_MODE"])
	$fields['name'] = GetMessage('CRM_FIELD_PHONE');

$arResult['FIELDS'][] = $fields;

$emailEditorID = uniqid('COMFM_EMAIL_');
ob_start(); //email
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
	array(
		'FM_MNEMONIC' => 'LFM',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'EDITOR_ID' => $emailEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();

$fields = array(
	'id' => 'EMAIL',
	'type' => 'custom',
	'value' => $sVal
);
if (!$arParams["RESTRICTED_MODE"])
	$fields['name'] = GetMessage('CRM_FIELD_EMAIL');

$arResult['FIELDS'][] = $fields;

ob_start(); //web
$APPLICATION->IncludeComponent($arParams["RESTRICTED_MODE"] ? 'bitrix:crm.field_multi.view' : 'bitrix:crm.field_multi.edit', 'mobile',
	array(
		'FM_MNEMONIC' => 'LFM',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();

$fields = array(
	'id' => 'WEB',
	'type' => 'custom',
	'value' => $sVal
);
if (!$arParams["RESTRICTED_MODE"])
	$fields['name'] = GetMessage('CRM_FIELD_WEB');

$arResult['FIELDS'][] = $fields;
//-- multifields

$companyTitleID = $arResult['FORM_ID'].'_COMPANY_TITLE';
$companyTitleCaptionID = $arResult['FORM_ID'].'_COMPANY_TITLE_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['COMPANY_TITLE_ID'] = $companyTitleID;
	$arResult['DUPLICATE_CONTROL']['COMPANY_TITLE_CAPTION_ID'] = $companyTitleCaptionID;
}
$arResult['FIELDS'][] = array(
	'id' => 'COMPANY_TITLE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
	'nameWrapper' => $companyTitleCaptionID,
	'params' => array('id'=> $companyTitleID, 'size' => 50),
	'value' => isset($arResult['ELEMENT']['~COMPANY_TITLE']) ?  $arResult['ELEMENT']['~COMPANY_TITLE'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label'
);
$arResult['FIELDS'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'params' => array('size' => 50),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'value' => isset($arResult['ELEMENT']['POST']) ? $arResult['ELEMENT']['~POST'] : ''
);

//address
if ($arParams["RESTRICTED_MODE"])
{
	if (class_exists('Bitrix\Crm\Format\AddressFormatter'))
	{
		$addressHtml = Bitrix\Crm\Format\AddressFormatter::getSingleInstance()->formatHtmlMultiline(
			Bitrix\Crm\LeadAddress::mapEntityFields($arResult['ELEMENT'])
		);
	}
	else
	{
		$addressHtml =  Bitrix\Crm\Format\LeadAddressFormatter::format(
			$arResult['ELEMENT'],
			array('SEPARATOR' => Bitrix\Crm\Format\AddressSeparator::HtmlLineBreak, 'NL2BR' => true)
		);
	}
}
else
{
	$addressFields = array(
		'ADDRESS' => array('NAME' => 'ADDRESS', 'IS_MULTILINE' => true, 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS']) ? $arResult['ELEMENT']['~ADDRESS'] : ''),
		'ADDRESS_2' => array('NAME' => 'ADDRESS_2', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_2']) ? $arResult['ELEMENT']['~ADDRESS_2'] : ''),
		'CITY' => array('NAME' => 'ADDRESS_CITY', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_CITY']) ? $arResult['ELEMENT']['~ADDRESS_CITY'] : ''),
		'REGION' => array('NAME' => 'ADDRESS_REGION', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_REGION']) ? $arResult['ELEMENT']['~ADDRESS_REGION'] : ''),
		'PROVINCE' => array('NAME' => 'ADDRESS_PROVINCE', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_PROVINCE']) ? $arResult['ELEMENT']['~ADDRESS_PROVINCE'] : ''),
		'POSTAL_CODE' => array('NAME' => 'ADDRESS_POSTAL_CODE', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_POSTAL_CODE']) ? $arResult['ELEMENT']['~ADDRESS_POSTAL_CODE'] : ''),
		'COUNTRY' => array(
			'NAME' => 'ADDRESS_COUNTRY',
			'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_COUNTRY']) ? $arResult['ELEMENT']['~ADDRESS_COUNTRY'] : '',
			'LOCALITY' => array(
				'TYPE' => 'COUNTRY',
				'NAME' => 'ADDRESS_COUNTRY_CODE',
				'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_COUNTRY_CODE']) ? $arResult['ELEMENT']['~ADDRESS_COUNTRY_CODE'] : ''
			)
		)
	);
	$addressHtml = CCrmMobileHelper::PrepareAddressFormFields($addressFields);
}
$arResult['FIELDS'][] = array(
	'id' => 'ADDRESS',
	'name' => GetMessage('CRM_FIELD_ADDRESS'),
	'type' => 'custom',
	'value' => $addressHtml
);
//--address

if ($arResult["IS_EDIT_PERMITTED"])
	$fieldType = $arParams['RESTRICTED_MODE'] ? 'custom' : 'textarea';
else
	$fieldType = 'label';

if (isset($arResult['ELEMENT']['~COMMENTS']) && $arResult['MODE'] == "EDIT")
{
	$arResult['ELEMENT']['~COMMENTS'] = htmlspecialcharsback($arResult['ELEMENT']['~COMMENTS']);
}
$arResult['FIELDS'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => $fieldType,
	'value' => $arResult['ELEMENT']['~COMMENTS']
);

// Product rows
$arResult["PAGEID_PRODUCT_SELECTOR_BACK"] = "crmLeadEditPage";
$arResult["ON_PRODUCT_SELECT_EVENT_NAME"] = "onCrmSelectProductForLead_".$arParams['ELEMENT_ID'];
$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] = CHTTP::urlAddParams($arParams['PRODUCT_SELECTOR_URL_TEMPLATE'], array(
	"event" => $arResult["ON_PRODUCT_SELECT_EVENT_NAME"],
	"pageIdProductSelectorBack" => $arResult["PAGEID_PRODUCT_SELECTOR_BACK"]
));
$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'lead_'.strval($arParams['ELEMENT_ID']) : 'new_lead').'_product_editor';

$bTaxMode = CCrmTax::isTaxMode();
// Determine person type
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
$personTypeId = 0;
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if (intval($arResult['ELEMENT']['COMPANY_ID']) > 0)
		$personTypeId = $arPersonTypes['COMPANY'];
	elseif (intval($arResult['ELEMENT']['CONTACT_ID']) > 0)
		$personTypeId = $arPersonTypes['CONTACT'];
}

$sProductsHtml = '';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => 'L',
	'PERMISSION_TYPE' => $arParams['RESTRICTED_MODE'] ? 'READ' : 'WRITE',
	'INIT_EDITABLE' => 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => ($bTaxMode && isset($arResult['ELEMENT']['LOCATION_ID'])) ? $arResult['ELEMENT']['LOCATION_ID'] : '',
	//'EXCH_RATE' => $exchRate,
	'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
	'TOTAL_SUM' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : null,
	'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
	'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName,
	'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],

	"RESTRICTED_MODE" => $arParams["RESTRICTED_MODE"],
	"PRODUCT_SELECTOR_URL_TEMPLATE" => $arParams["PRODUCT_SELECTOR_URL_TEMPLATE"],
	"ON_PRODUCT_SELECT_EVENT_NAME" => $arResult["ON_PRODUCT_SELECT_EVENT_NAME"]
);
if (is_array($productRowSettings) && count($productRowSettings) > 0)
{
	if (isset($productRowSettings['ENABLE_DISCOUNT']))
		$componentSettings['ENABLE_DISCOUNT'] = $productRowSettings['ENABLE_DISCOUNT'] ? 'Y' : 'N';
	if (isset($productRowSettings['ENABLE_TAX']))
		$componentSettings['ENABLE_TAX'] = $productRowSettings['ENABLE_TAX'] ? 'Y' : 'N';
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
	'mobile',
	$componentSettings,
	false,
	array('HIDE_ICONS' => 'Y')
);
$sProductsHtml .= ob_get_contents();
ob_end_clean();
unset($componentSettings);

if (!empty($sProductsHtml))
{
	$arResult['FIELDS'][] = array(
		'id' => 'PRODUCT_ROWS',
		'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
		'type' => 'custom',
		'value' => $sProductsHtml
	);
}

//user fields
$CCrmUserType = new CCrmMobileHelper();
$CCrmUserType->prepareUserFields(
	$arResult['FIELDS'],
	CCrmLead::$sUFEntityID,
	$arResult['ELEMENT']['ID'],
	false,
	'lead_details',
	$USER->GetID()
);

if ($arParams['RESTRICTED_MODE'])
{
	$arResult['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Lead, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';

	$arResult['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['EVENT_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Lead, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';
}

$this->IncludeComponentTemplate();
