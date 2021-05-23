<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
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

use Bitrix\Crm\Conversion\LeadConversionDispatcher;

global $USER_FIELD_MANAGER, $DB, $USER, $APPLICATION;
$CCrmLead = new CCrmLead();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('LEAD');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;

$bEdit = false;
$bCopy = false;
$bVarsFromForm = false;

if (!empty($arParams['ELEMENT_ID']))
{
	$bEdit = true;
}
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}

if($bEdit)
{
	$isPermitted = CCrmLead::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
}
elseif($bCopy)
{
	$isPermitted = CCrmLead::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$isPermitted = CCrmLead::CheckCreatePermission($userPermissions);
}

if(!$isPermitted)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('LEAD', array($arParams['ELEMENT_ID']))
	: array();

// external context ID
$arResult['EXTERNAL_CONTEXT'] = isset($_REQUEST['external_context']) ? $_REQUEST['external_context'] : '';

//Show error message if required
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
	$errorID = mb_strtolower($_GET['error']);
	if(preg_match('/^crm_err_/', $errorID) === 1)
	{
		if(!isset($_SESSION[$errorID]))
		{
			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_LEAD_EDIT'],
					array('lead_id' => $arParams['ELEMENT_ID'])
				)
			);
		}

		$errorMessage = strval($_SESSION[$errorID]);
		unset($_SESSION[$errorID]);
		if($errorMessage !== '')
		{
			ShowError(htmlspecialcharsbx($errorMessage));
			return;
		}
	}
}

if($bEdit)
{
	CCrmLead::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $userPermissions);
}

if ($bEdit || $bCopy)
{
	$obFields = CCrmLead::GetListEx(
		array(),
		array('=ID' => $arParams['ELEMENT_ID'], 'CHECK_PERMISSIONS'=> 'N')
	);
	$arFields = is_object($obFields) ? $obFields->GetNext() : false;
	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}

	if($bEdit && $arResult['CAN_CONVERT'])
	{
		$arResult['CONVERSION_CONFIGS'] = LeadConversionDispatcher::getJavaScriptConfigurations();
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
	if (isset($_GET['is_return_customer']))
	{
		$arFields['~IS_RETURN_CUSTOMER'] = $_GET['is_return_customer'];
		CUtil::decodeURIComponent($arFields['~IS_RETURN_CUSTOMER']);
		$arFields['IS_RETURN_CUSTOMER'] = htmlspecialcharsbx($arFields['~IS_RETURN_CUSTOMER']);
	}
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

$productDataFieldName = 'LEAD_PRODUCT_DATA';

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	if(isset($_POST['cancel']))
	{
		if(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
		{
			$arResult['EXTERNAL_EVENT'] = array(
				'NAME' => 'onCrmEntityCreate',
				'IS_CANCELED' => true,
				'PARAMS' => array(
					'isCanceled' => true,
					'context' => $arResult['EXTERNAL_CONTEXT'],
					'entityTypeName' => CCrmOwnerType::LeadName
				)
			);
			$this->IncludeComponentTemplate('event');
			return;
		}
		else
		{
			LocalRedirect(
				isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
					? $_REQUEST['backurl']
					: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array())
			);
		}
	}
	elseif(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']))
	{
		$arFields = array();

		if(isset($_POST['TITLE']))
		{
			$arFields['TITLE'] = trim($_POST['TITLE']);
		}

		if(isset($_POST['COMMENTS']))
		{
			$comments = isset($_POST['COMMENTS']) ? trim($_POST['COMMENTS']) : '';
			if($comments !== '' && mb_strpos($comments, '<') !== false)
			{
				$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);
			}
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

		if(isset($_POST['COMPANY_ID']))
		{
			$arFields['COMPANY_ID'] = (int)(is_array($_POST['COMPANY_ID']) ? $_POST['COMPANY_ID'][0] : $_POST['COMPANY_ID']);
		}

		if(isset($_POST['CONTACT_ID']))
		{
			$arFields['CONTACT_ID'] = (int)(is_array($_POST['CONTACT_ID']) ? $_POST['CONTACT_ID'][0] : $_POST['CONTACT_ID']);
		}

		if(isset($_REQUEST['is_return_customer']))
		{
			$arFields['IS_RETURN_CUSTOMER'] = mb_strtoupper($_REQUEST['is_return_customer']) === 'Y' ? 'Y' : 'N';
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
			$prodJson = isset($_POST[$productDataFieldName]) ? strval($_POST[$productDataFieldName]) : '';
			$arProd = $arResult['PRODUCT_ROWS'] = $prodJson <> '' ? CUtil::JsObjectToPhp($prodJson) : array();
			if(!empty($arProd))
			{
				if($bCopy)
				{
					for($rowInd = 0, $rowQty = count($arProd); $rowInd < $rowQty; $rowInd++)
					{
						unset($arProd[$rowInd]['ID']);
					}
				}

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

		$USER_FIELD_MANAGER->EditFormAddFields(CCrmLead::$sUFEntityID, $arFields);
		if($bCopy)
		{
			$CCrmUserType->CopyFileFields($arFields);
		}

		$arResult['ERROR_MESSAGE'] = '';

		if (!$CCrmLead->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false))
		{
			if (!empty($CCrmLead->LAST_ERROR))
				$arResult['ERROR_MESSAGE'] .= $CCrmLead->LAST_ERROR;
			else
				$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
		}

		$arBizProcParametersValues = $CCrmBizProc->CheckFields(
			$bEdit ? $arResult['ELEMENT']['ID'] : false,
			false,
			$arResult['ELEMENT']['ASSIGNED_BY'],
			$bEdit ? $arEntityAttr : null
		);

		if ($arBizProcParametersValues === false)
		{
			$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;
		}

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
						'ADDRESS_FIELDS' => $addressFieldNames
					)
				);
			}
			else
			{
				$ID = $CCrmLead->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
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
		$starter->setUserIdFromCurrent();
		if (!$bEdit)
		{
			$starter->runOnAdd();
		}
		else
		{
			$starter->runOnUpdate($arFields, $arResult['ELEMENT']);
		}
		//end automation

		$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

		if (!empty($arResult['ERROR_MESSAGE']))
		{
			ShowError($arResult['ERROR_MESSAGE']);

			$obligatoryFields = array('ID' => $ID);
			//region STATUS_ID is required for edit permissions
			$dbResult = CCrmLead::GetListEx(
				array(),
				array('=ID' => $arParams['ELEMENT_ID'], 'CHECK_PERMISSIONS'=> 'N', false, false, array('STATUS_ID'))
			);

			if(is_object($dbResult))
			{
				$fields = $dbResult->Fetch();
				if(is_array($fields))
				{
					$obligatoryFields['STATUS_ID'] = $fields['STATUS_ID'];
				}
			}
			//endregion

			$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
				array_merge($obligatoryFields, $arFields),
				CCrmLead::GetFields()
			);

		}
		else
		{
			if (isset($_POST['apply']))
			{
				if (CCrmLead::CheckUpdatePermission($ID))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_LEAD_EDIT'],
							array('lead_id' => $ID)
						)
					);
				}
			}
			elseif (isset($_POST['saveAndAdd']))
			{
				LocalRedirect(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_LEAD_EDIT'],
						array('lead_id' => 0)
					)
				);
			}
			elseif (isset($_POST['saveAndView']))
			{
				if(CCrmLead::CheckReadPermission($ID))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_LEAD_SHOW'],
							array('lead_id' => $ID)
						)
					);
				}
			}

			// save
			if(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
			{
				$info = $arResult['INFO'] = CCrmEntitySelectorHelper::PrepareEntityInfo(
					CCrmOwnerType::LeadName,
					$ID,
					array(
						'ENTITY_EDITOR_FORMAT' => true,
						'REQUIRE_REQUISITE_DATA' => true,
						'NAME_TEMPLATE' =>
							isset($arParams['NAME_TEMPLATE'])
								? $arParams['NAME_TEMPLATE']
								: \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
					)

				);

				$arResult['EXTERNAL_EVENT'] = array(
					'NAME' => 'onCrmEntityCreate',
					'IS_CANCELED' => false,
					'PARAMS' => array(
						'isCanceled' => false,
						'context' => $arResult['EXTERNAL_CONTEXT'],
						'entityTypeName' => CCrmOwnerType::LeadName,
						'entityInfo' => $info
					)
				);
				$this->IncludeComponentTemplate('event');
				return;
			}
			else
			{
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));
			}
		}
	}
}
elseif(isset($_GET['delete']) && check_bitrix_sessid())
{
	if ($bEdit)
	{
		$entityID = $arParams['ELEMENT_ID'];
		$arResult['ERROR_MESSAGE'] = '';

		if (!CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::LeadName, $entityID, $userPermissions, $arEntityAttr))
		{
			$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
		}
		elseif (!$CCrmBizProc->Delete($entityID, $arEntityAttr))
		{
			$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;
		}

		if ($arResult['ERROR_MESSAGE'] === ''
			&& !$CCrmLead->Delete(
				$arResult['ELEMENT']['ID'],
				array('CHECK_DEPENDENCIES' => true, 'PROCESS_BIZPROC' => false)))
		{
			/** @var CApplicationException $ex */
			$ex = $APPLICATION->GetException();
			$arResult['ERROR_MESSAGE'] = ($ex instanceof CApplicationException)
				? $ex->GetString() : GetMessage('CRM_DELETE_ERROR');
		}
	}
	else
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');
	}

	if ($arResult['ERROR_MESSAGE'] === '')
	{
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST']));
	}
	else
	{
		$errorID = uniqid('crm_err_');
		$_SESSION[$errorID] = $arResult['ERROR_MESSAGE'];

		LocalRedirect(
			CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_LEAD_EDIT'],
					array('lead_id' => $arResult['ELEMENT']['ID'])
				),
				array('error' => $errorID)
			)
		);
	}
}

if ($arResult['ELEMENT']['IS_RETURN_CUSTOMER'] == 'Y')
{
	$arResult['CAN_CONVERT_TO_CONTACT'] = false;
	$arResult['CAN_CONVERT_TO_COMPANY'] = false;
	$arResult['FORM_ID'] = 'CRM_RETURN_CUSTOMER_LEAD_EDIT_V12';
}
else
{
	$arResult['FORM_ID'] = 'CRM_LEAD_EDIT_V12';
}
$arResult['GRID_ID'] = 'CRM_LEAD_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_LEAD_LIST'];
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

//region Reset status in copy mode
if($bCopy)
{
	if(!empty($arResult['STATUS_LIST']))
	{
		reset($arResult['STATUS_LIST']);
		$arResult['ELEMENT']['STATUS_ID'] = $arResult['ELEMENT']['~STATUS_ID'] = key($arResult['STATUS_LIST']);
	}
	else
	{
		unset($arResult['ELEMENT']['STATUS_ID']);
	}
}
//endregion

$arResult['EDIT'] = $bEdit;
$arResult['IS_COPY'] = $bCopy;
$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_lead_info',
	'name' => GetMessage('CRM_SECTION_LEAD2'),
	'type' => 'section',
	'isTactile' => true
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'text'
);

if($arResult['ELEMENT']['STATUS_ID'] !== 'CONVERTED')
{
	unset($arResult['STATUS_LIST']['CONVERTED']);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'STATUS_ID',
		'name' => GetMessage('CRM_FIELD_STATUS_ID'),
		'params' => array(),
		'items' => $arResult['STATUS_LIST'],
		'type' => 'list',
		'value' => (isset($arResult['ELEMENT']['~STATUS_ID']) ? $arResult['ELEMENT']['~STATUS_ID'] : '')
	);
}
else
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'STATUS_ID',
		'name' => GetMessage('CRM_FIELD_STATUS_ID'),
		'params' => array(),
		'type' => 'label',
		'value' => $arResult['~STATUS_LIST']['CONVERTED']
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_STATUS_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['STATUS_DESCRIPTION']) ? $arResult['ELEMENT']['STATUS_DESCRIPTION'] : ''
);
$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(($bEdit || $bCopy) && isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'items' => $arResult['CURRENCY_LIST'],
	'type' => 'list',
	'value' => $currencyID
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'params' => array('size' => 21),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'list',
	'items' => $arResult['SOURCE_LIST'],
	'value' => (isset($arResult['ELEMENT']['SOURCE_ID']) ? $arResult['ELEMENT']['SOURCE_ID'] : '')
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['SOURCE_DESCRIPTION'] : ''
);
$arResult['RESPONSIBLE_SELECTOR_PARAMS'] = array(
	'NAME' => 'crm_lead_edit_resonsible',
	'INPUT_NAME' => 'ASSIGNED_BY_ID',
	'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
	'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']

);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => $arResult['RESPONSIBLE_SELECTOR_PARAMS'],
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : (\Bitrix\Crm\Settings\LeadSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N'),
	'title' => GetMessage('CRM_FIELD_OPENED_TITLE')
);
// PRODUCT_ROWS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS2'),
	'type' => 'section'
);

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

$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'lead_'.strval($arParams['ELEMENT_ID']) : 'new_lead').'_product_editor';

$sProductsHtml = '';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => 'L',
	'PERMISSION_TYPE' => 'WRITE',
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
	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW']
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
	'',
	$componentSettings,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sProductsHtml = ob_get_contents();
ob_end_clean();
unset($componentSettings);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO2'),
	'type' => 'section'
);


if ($arResult['ELEMENT']['IS_RETURN_CUSTOMER'] != 'Y')
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'HONORIFIC',
		'name' => GetMessage('CRM_FIELD_HONORIFIC'),
		'type' => 'list',
		'items' => array('0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')) + $arResult['HONORIFIC_LIST'],
		'value' => isset($arResult['ELEMENT']['~HONORIFIC']) ? $arResult['ELEMENT']['~HONORIFIC'] : ''
	);

	$lastNameID = $arResult['FORM_ID'].'_LAST_NAME';
	$lastNameCaptionID = $arResult['FORM_ID'].'_LAST_NAME_CAP';
	if($enableDupControl)
	{
		$arResult['DUPLICATE_CONTROL']['LAST_NAME_ID'] = $lastNameID;
		$arResult['DUPLICATE_CONTROL']['LAST_NAME_CAPTION_ID'] = $lastNameCaptionID;
	}

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'LAST_NAME',
		'name' => GetMessage('CRM_FIELD_LAST_NAME'),
		'nameWrapper' => $lastNameCaptionID,
		'params' => array('id' => $lastNameID, 'size' => 50),
		'type' => 'text',
		'value' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
	);

	$nameID = $arResult['FORM_ID'].'_NAME';
	$nameCaptionID = $arResult['FORM_ID'].'_NAME_CAP';
	if($enableDupControl)
	{
		$arResult['DUPLICATE_CONTROL']['NAME_ID'] = $nameID;
		$arResult['DUPLICATE_CONTROL']['NAME_CAPTION_ID'] = $nameCaptionID;
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'NAME',
		'name' => GetMessage('CRM_LEAD_FIELD_NAME'),
		'nameWrapper' => $nameCaptionID,
		'params' => array('id' => $nameID, 'size' => 50),
		'type' => 'text',
		'value' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
	);

	$secondNameID = $arResult['FORM_ID'].'_SECOND_NAME';
	$secondNameCaptionID = $arResult['FORM_ID'].'_SECOND_NAME_CAP';
	if($enableDupControl)
	{
		$arResult['DUPLICATE_CONTROL']['SECOND_NAME_ID'] = $secondNameID;
		$arResult['DUPLICATE_CONTROL']['SECOND_NAME_CAPTION_ID'] = $secondNameCaptionID;
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'SECOND_NAME',
		'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
		'nameWrapper' => $secondNameCaptionID,
		'params' => array('id' => $secondNameID, 'size' => 50),
		'type' => 'text',
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
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'BIRTHDATE',
		'name' => GetMessage('CRM_LEAD_EDIT_FIELD_BIRTHDATE'),
		'type' => 'date_short',
		'value' => $birthDate
	);

	$emailEditorID = uniqid('LFM_EMAIL_');
	$emailEditorCaptionID =$emailEditorID.'_CAPTION';
	if($enableDupControl)
	{
		$arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_ID'] = $emailEditorID;
		$arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_CAPTION_ID'] = $emailEditorCaptionID;
	}
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
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
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'EMAIL',
		'name' => GetMessage('CRM_FIELD_EMAIL'),
		'nameWrapper' => $emailEditorCaptionID,
		'type' => 'custom',
		'value' => $sVal
	);

	$phoneEditorID = uniqid('LFM_PHONE_');
	$phoneEditorCaptionID =$phoneEditorID.'_CAPTION';
	if($enableDupControl)
	{
		$arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_ID'] = $phoneEditorID;
		$arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_CAPTION_ID'] = $phoneEditorCaptionID;
	}
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
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
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'PHONE',
		'name' => GetMessage('CRM_FIELD_PHONE'),
		'nameWrapper' => $phoneEditorCaptionID,
		'type' => 'custom',
		'value' => $sVal
	);
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
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
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'WEB',
		'name' => GetMessage('CRM_FIELD_WEB'),
		'type' => 'custom',
		'value' => $sVal
	);
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
		array(
			'FM_MNEMONIC' => 'LFM',
			'ENTITY_ID' => 'LEAD',
			'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
			'TYPE_ID' => 'IM',
			'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'IM',
		'name' => GetMessage('CRM_FIELD_MESSENGER'),
		'type' => 'custom',
		'value' => $sVal
	);

	$companyTitleID = $arResult['FORM_ID'].'_COMPANY_TITLE';
	$companyTitleCaptionID = $arResult['FORM_ID'].'_COMPANY_TITLE_CAP';
	if($enableDupControl)
	{
		$arResult['DUPLICATE_CONTROL']['COMPANY_TITLE_ID'] = $companyTitleID;
		$arResult['DUPLICATE_CONTROL']['COMPANY_TITLE_CAPTION_ID'] = $companyTitleCaptionID;
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'COMPANY_TITLE',
		'name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
		'nameWrapper' => $companyTitleCaptionID,
		'params' => array('id'=> $companyTitleID, 'size' => 50),
		'value' => isset($arResult['ELEMENT']['~COMPANY_TITLE']) ?  $arResult['ELEMENT']['~COMPANY_TITLE'] : '',
		'type' => 'text'
	);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'POST',
		'name' => GetMessage('CRM_FIELD_POST'),
		'params' => array('size' => 50),
		'type' => 'text',
		'value' => isset($arResult['ELEMENT']['POST']) ? $arResult['ELEMENT']['~POST'] : ''
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDRESS',
		'name' => GetMessage('CRM_FIELD_ADDRESS'),
		'type' => 'address',
		'componentParams' => array(
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			'DATA' => array(
				'ADDRESS' => array('NAME' => 'ADDRESS', 'IS_MULTILINE' => true, 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS']) ? $arResult['ELEMENT']['~ADDRESS'] : ''),
				'ADDRESS_2' => array('NAME' => 'ADDRESS_2', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_2']) ? $arResult['ELEMENT']['~ADDRESS_2'] : ''),
				'CITY' => array('NAME' => 'ADDRESS_CITY','VALUE' => isset($arResult['ELEMENT']['~ADDRESS_CITY']) ? $arResult['ELEMENT']['~ADDRESS_CITY'] : ''),
				'REGION' => array('NAME' => 'ADDRESS_REGION','VALUE' => isset($arResult['ELEMENT']['~ADDRESS_REGION']) ? $arResult['ELEMENT']['~ADDRESS_REGION'] : ''),
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
			)
		)
	);
}
else
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CONTACT_ID',
		'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
		'type' => 'crm_single_client_selector',
		'componentParams' => array(
			'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "LEAD_{$arParams['ELEMENT_ID']}" : 'NEWLEAD',
			'ENTITY_TYPE' => CCrmOwnerType::ContactName,
			'ENTITY_ID' => !empty($arResult['ELEMENT']['CONTACT_ID'])? $arResult['ELEMENT']['CONTACT_ID'] : 0,
			'ENTITY_INPUT_NAME' => 'CONTACT_ID',
			'ENABLE_REQUISITES'=> false,
			'ENABLE_ENTITY_CREATION'=> CCrmContact::CheckCreatePermission($userPermissions),
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		)
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'COMPANY_ID',
		'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
		'type' => 'crm_single_client_selector',
		'componentParams' => array(
			'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "LEAD_{$arParams['ELEMENT_ID']}" : 'NEWLEAD',
			'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
			'ENTITY_ID' => !empty($arResult['ELEMENT']['COMPANY_ID'])? $arResult['ELEMENT']['COMPANY_ID'] : 0,
			'ENTITY_INPUT_NAME' => 'COMPANY_ID',
			'ENABLE_REQUISITES'=> false,
			'ENABLE_ENTITY_CREATION'=> CCrmCompany::CheckCreatePermission($userPermissions),
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		)
	);
}


ob_start();
$ar = array(
	'inputName' => 'COMMENTS',
	'inputId' => 'COMMENTS',
	'height' => '180',
	'content' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => true,
	'bSaveOnBlur' => true,
	'toolbarConfig' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'BackColor', 'ForeColor',
		'CreateLink', 'DeleteLink',
		'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
	)
);
$LHE = new CLightHTMLEditor;
$LHE->Show($ar);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);


$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$bVarsFromForm || (isset($arParams['VALUES']) && !empty($arParams['VALUES'])),
	false,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (IsModuleInstalled('bizproc') && CBPRuntime::isFeatureEnabled())
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'LEAD');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentLead', 'LEAD'),
			$bEdit ? array('crm', 'CCrmDocumentLead', 'LEAD_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentLead', $bEdit ? 'LEAD_'.$arResult['ELEMENT']['ID'] : 'LEAD_0'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowId' => $arDocumentState['ID'] > 0 ? $arDocumentState['ID'] : $arDocumentState['TEMPLATE_ID'],
				'CreatedBy' => $arResult['ELEMENT']['ASSIGNED_BY'],
				'UserIsAdmin' => $USER->IsAdmin()
			)
		);

		if (!$canViewWorkflow)
			continue;

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'section_bp_name_'.$bizProcIndex,
			'name' => $arDocumentState['TEMPLATE_NAME'],
			'type' => 'section'
		);
		if ($arDocumentState['TEMPLATE_DESCRIPTION'] != '')
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_DESC_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_TEMPLATE_DESC'),
				'type' => 'label',
				'colspan' => true,
				'value' => $arDocumentState['TEMPLATE_DESCRIPTION']
			);
		}
		if (!empty($arDocumentState['STATE_MODIFIED']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_MODIFIED_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_MODIFIED'),
				'type' => 'label',
				'value' => $arDocumentState['STATE_MODIFIED']
			);
		}
		if (!empty($arDocumentState['STATE_NAME']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_NAME_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_NAME'),
				'type' => 'label',
				'value' => $arDocumentState['STATE_TITLE'] <> '' ? $arDocumentState['STATE_TITLE'] : $arDocumentState['STATE_NAME']
			);
		}

		if ($arDocumentState['ID'] == '')
		{
			ob_start();
			CBPDocument::StartWorkflowParametersShow(
				$arDocumentState['TEMPLATE_ID'],
				$arDocumentState['TEMPLATE_PARAMETERS'],
				'form_'.$arResult['FORM_ID'],
				$bVarsFromForm
			);
			$sVal = ob_get_contents();
			ob_end_clean();

			if($sVal !== '')
			{
				$arResult['FIELDS']['tab_1'][] = array(
					'id' => 'BP_PARAMETERS',
					'name' => GetMessage('CRM_FIELD_BP_PARAMETERS'),
					'colspan' => true,
					'type' => 'custom',
					'value' => "<table>$sVal</table>"
				);
			}
		}

		$_arEvents = CBPDocument::GetAllowableEvents($USER->GetID(), $CCrmBizProc->arCurrentUserGroups, $arDocumentState);
		if (count($_arEvents) > 0)
		{
			$arEvent = array('' => GetMessage('CRM_FIELD_BP_EMPTY_EVENT'));
			foreach ($_arEvents as $_arEvent)
				$arEvent[$_arEvent['NAME']] = $_arEvent['TITLE'];

			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_EVENTS',
				'name' => GetMessage('CRM_FIELD_BP_EVENTS'),
				'params' => array(),
				'items' => $arEvent,
				'type' => 'list',
				'value' => (isset($_REQUEST['bizproc_event_'.$bizProcIndex]) ? $_REQUEST['bizproc_event_'.$bizProcIndex] : '')
			);

			$arResult['FORM_CUSTOM_HTML'] = '
					<input type="hidden" name="bizproc_id_'.$bizProcIndex.'" value="'.$arDocumentState["ID"].'">
					<input type="hidden" name="bizproc_template_id_'.$bizProcIndex.'" value="'.$arDocumentState["TEMPLATE_ID"].'">
			';
		}
	}

	if ($bizProcIndex > 0)
		$arResult['BIZPROC'] = true;
}

if ($bCopy)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');

?>
