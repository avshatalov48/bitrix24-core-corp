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

if (IsModuleInstalled('bizproc') && !CModule::IncludeModule('bizproc'))
{
	ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
	return;
}

global $USER_FIELD_MANAGER, $DB, $USER;
$CCrmDeal = new CCrmDeal();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('DEAL');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$isEditMode = false;
$isCopyMode = false;
$bVarsFromForm = false;

$entityID = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['deal_id']))
{
	$entityID = $arParams['ELEMENT_ID'] = intval($_REQUEST['deal_id']);
}
$arResult['ELEMENT_ID'] = $entityID;

if (!empty($arParams['ELEMENT_ID']))
	$isEditMode = true;

if (!empty($_REQUEST['copy']))
{
	$isCopyMode = true;
	$isEditMode = false;
}

//region Category
$arResult['CATEGORY_ID'] = -1;
if($isEditMode || $isCopyMode)
{
	$categoryID = CCrmDeal::GetCategoryID($arParams['ELEMENT_ID']);
	if($categoryID >= 0)
	{
		$arResult['CATEGORY_ID'] = $categoryID;
	}
}
elseif(isset($_REQUEST['category_id']))
{
	$categoryID = (int)$_REQUEST['category_id'];
	if($categoryID === 0 || Bitrix\Crm\Category\DealCategory::isEnabled($categoryID))
	{
		$arResult['CATEGORY_ID'] = $categoryID;
	}
}

if($arResult['CATEGORY_ID'] < 0)
{
	if($isEditMode)
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}
	else
	{
		$categoryIDs = CCrmDeal::GetPermittedToCreateCategoryIDs($userPermissions);
		if(!empty($categoryIDs))
		{
			$arResult['CATEGORY_ID'] = $categoryIDs[0];
		}
		else
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}
	}
}
//endregion

$isConverting = isset($arParams['CONVERT']) && $arParams['CONVERT'];
//New Conversion Scheme
$conversionWizard = null;
if(isset($_REQUEST['lead_id']) && $_REQUEST['lead_id'] > 0)
{
	$srcLeadID = (int)$_REQUEST['lead_id'];
	$conversionWizard = \Bitrix\Crm\Conversion\LeadConversionWizard::load($srcLeadID);
	if($conversionWizard !== null)
	{
		$arResult['LEAD_ID'] = $srcLeadID;
	}
}
elseif (isset($_REQUEST['conv_quote_id']) && $_REQUEST['conv_quote_id'] > 0)
{
	$srcQuoteID = (int)$_REQUEST['conv_quote_id'];
	if($srcQuoteID > 0)
	{
		$conversionWizard = \Bitrix\Crm\Conversion\QuoteConversionWizard::load($srcQuoteID);
		if($conversionWizard !== null)
		{
			$arResult['QUOTE_ID'] = $srcQuoteID;
		}
	}
}

$arResult["IS_EDIT_PERMITTED"] = false;
$arResult["IS_VIEW_PERMITTED"] = false;
$arResult["IS_DELETE_PERMITTED"] = CCrmDeal::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if($isEditMode)
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmDeal::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"])
	{
		$arResult["IS_VIEW_PERMITTED"] = CCrmDeal::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
}
elseif($isCopyMode)
{
	$arResult["IS_VIEW_PERMITTED"] = CCrmDeal::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmDeal::CheckCreatePermission($userPermissions);
}

if(!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"])
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('DEAL', array($arParams['ELEMENT_ID']))
	: array();

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;

$bTaxMode = CCrmTax::isTaxMode();
$arResult['TAX_MODE'] = $bTaxMode ? 'Y' : 'N';

if($isEditMode)
{
	CCrmDeal::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $userPermissions);
	if($arResult['CAN_CONVERT'])
	{
		$config = \Bitrix\Crm\Conversion\DealConversionConfig::load();
		if($config === null)
		{
			$config = \Bitrix\Crm\Conversion\DealConversionConfig::getDefault();
		}

		$arResult['CONVERSION_CONFIG'] = $config;
	}
}

$requisiteIdLinked = 0;
$bankDetailIdLinked = 0;

$arFields = null;
if ($conversionWizard !== null)
{
	$arResult['MODE'] = 'CONVERT';

	$arFields = array('ID' => 0);
	$conversionWizard->prepareDataForEdit(CCrmOwnerType::Deal, $arFields, true);
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend(CCrmOwnerType::Deal);

	if(isset($arFields['PRODUCT_ROWS']))
	{
		$arResult['PRODUCT_ROWS'] = $arFields['PRODUCT_ROWS'];
	}
}
elseif ($isEditMode || $isCopyMode)
{
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => $arParams["RESTRICTED_MODE"] ? 'READ' : 'WRITE'
	);
	$obFields = CCrmDeal::GetListEx(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if(!is_array($arFields))
	{
		ShowError(GetMessage('CRM_DEAL_EDIT_NOT_FOUND', array("#ID#" => $arParams['ELEMENT_ID'])));
		return;
	}

	if ($arFields === false)
	{
		$isEditMode = false;
		$isCopyMode = false;
	}

	if ($isCopyMode)
	{
		if(isset($arFields['LEAD_ID']))
		{
			unset($arFields['LEAD_ID']);
		}

		if(isset($arFields['~LEAD_ID']))
		{
			unset($arFields['~LEAD_ID']);
		}

		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'DEAL', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
	}

	if(is_array($arFields))
	{
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
}
else
{
	$arResult['MODE'] = 'CREATE';

	$arFields = array('ID' => 0);

	$beginDate = time() + CTimeZone::GetOffset();
	$time = localtime($beginDate, true);
	$beginDate -= $time['tm_sec'];

	$arFields['BEGINDATE'] = ConvertTimeStamp($beginDate, 'FULL', SITE_ID);
	$arFields['CLOSEDATE'] = ConvertTimeStamp($beginDate + 7 * 86400, 'FULL', SITE_ID);

	$extVals =  isset($arParams['~VALUES']) && is_array($arParams['~VALUES']) ? $arParams['~VALUES'] : array();
	if (count($extVals) > 0)
	{
		if(isset($extVals['PRODUCT_ROWS']) && is_array($extVals['PRODUCT_ROWS']))
		{
			$arResult['PRODUCT_ROWS'] = $extVals['PRODUCT_ROWS'];
			unset($extVals['PRODUCT_ROWS']);
		}

		$arFields = array_merge($arFields, $extVals);
		$arFields = CCrmComponentHelper::PrepareEntityFields(
			$arFields,
			CCrmDeal::GetFields()
		);
		// hack for UF
		$_REQUEST = $_REQUEST + $extVals;
	}

	if (isset($_GET['contact_id']))
	{
		$arFields['CONTACT_ID'] = intval($_GET['contact_id']);
	}
	if (isset($_GET['company_id']))
	{
		$arFields['COMPANY_ID'] = intval($_GET['company_id']);
	}
	if (isset($_GET['title']))
	{
		$arFields['~TITLE'] = $_GET['title'];
		CUtil::decodeURIComponent($arFields['~TITLE']);
		$arFields['TITLE'] = htmlspecialcharsbx($arFields['~TITLE']);
	}
}

// requisite link
if ($conversionWizard !== null || $isEditMode || $isCopyMode)
{
	$requisiteEntityList = array();
	$requisite = new \Bitrix\Crm\EntityRequisite();
	if ($isEditMode || $isCopyMode)
	{
		if ($arParams['ELEMENT_ID'] > 0)
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $arParams['ELEMENT_ID']);
	}
	else if ($conversionWizard !== null)
	{
		if (isset($arFields['QUOTE_ID']) && $arFields['QUOTE_ID'] > 0)
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Quote, 'ENTITY_ID' => $arFields['QUOTE_ID']);
	}
	if (isset($arFields['COMPANY_ID']) && $arFields['COMPANY_ID'] > 0)
		$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $arFields['COMPANY_ID']);
	if (isset($arFields['CONTACT_ID']) && $arFields['CONTACT_ID'] > 0)
		$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $arFields['CONTACT_ID']);
	$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
	if (is_array($requisiteInfoLinked))
	{
		if (isset($requisiteInfoLinked['REQUISITE_ID']))
			$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
		if (isset($requisiteInfoLinked['BANK_DETAIL_ID']))
			$bankDetailIdLinked = (int)$requisiteInfoLinked['BANK_DETAIL_ID'];
	}
	unset($requisiteEntityList, $requisite, $requisiteInfoLinked);
}

$isExternal = $isEditMode && isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']) && intval($arFields['ORIGINATOR_ID']) > 0 && intval($arFields['ORIGIN_ID']) > 0;

$arResult['ELEMENT'] = is_array($arFields) ? $arFields : null;
unset($arFields);

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_DEAL_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_DEAL_LIST_V12';

$productDataFieldName = $arResult["productDataFieldName"] = 'DEAL_PRODUCT_DATA';

if($isConverting)
{
	$bVarsFromForm = true;
}
else
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && $arResult["IS_EDIT_PERMITTED"])
	{
		if (!$isEditMode)
		{
			$originatorId = isset($_POST['EXTERNAL_SALE_ID']) ? (int)$_POST['EXTERNAL_SALE_ID'] : 0;
			$originId = isset($_POST['SYNC_ORDER_ID']) ? (int)$_POST['SYNC_ORDER_ID'] : 0;
		}
		else
		{
			$originatorId = (int)$arResult['ELEMENT']['ORIGINATOR_ID'];
			$originId = (int)$arResult['ELEMENT']['ORIGIN_ID'];
		}

		if ($originId > 0 && !isset($_POST['apply']))
		{
			//Emulation of "Apply" button click for sale order popup.
			$_POST['apply'] = $_REQUEST['apply'] = 'Y';
		}

		$bVarsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['continue']))
		{
			CUtil::JSPostUnescape();

			$arSrcElement = ($isEditMode || $isCopyMode) ? $arResult['ELEMENT'] : array();
			$arFields = array();

			$title = isset($_POST['TITLE']) ? trim($_POST['TITLE']) : '';
			if($title !== '')
			{
				$arFields['TITLE'] = $title;
			}
			elseif(!$isEditMode)
			{
				$arFields['TITLE'] = GetMessage('CRM_DEAL_EDIT_DEFAULT_TITLE');
			}
			elseif(isset($arSrcElement['~TITLE']))
			{
				$arFields['TITLE'] = $arSrcElement['~TITLE'];
			}

			if(isset($_POST['COMMENTS']))
			{
				$comments = isset($_POST['COMMENTS']) ? trim($_POST['COMMENTS']) : '';
				if($comments !== '' && strpos($comments, '<') !== false)
				{
					$sanitizer = new CBXSanitizer();
					$sanitizer->ApplyDoubleEncode(false);
					$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
					//Crutch for for Chrome line break behaviour in HTML editor.
					$sanitizer->AddTags(array('div' => array()));
					$sanitizer->AddTags(array('a' => array('href', 'title', 'name', 'style', 'alt', 'target')));
					$comments = $sanitizer->SanitizeHtml($comments);
				}
				$arFields['COMMENTS'] = $comments;
			}

			if(isset($_POST['PROBABILITY']))
			{
				$arFields['PROBABILITY'] = (int)$_POST['PROBABILITY'];
			}
			elseif(isset($arSrcElement['PROBABILITY']))
			{
				$arFields['PROBABILITY'] = (int)$arSrcElement['PROBABILITY'];
			}

			if(isset($_POST['TYPE_ID']))
			{
				$arFields['TYPE_ID'] = trim($_POST['TYPE_ID']);
			}
			elseif(isset($arSrcElement['TYPE_ID']))
			{
				$arFields['TYPE_ID'] = $arSrcElement['TYPE_ID'];
			}

			if(isset($_POST['STAGE_ID']))
			{
				$arFields['STAGE_ID'] = trim($_POST['STAGE_ID']);
			}
			elseif(isset($arSrcElement['STAGE_ID']))
			{
				$arFields['STAGE_ID'] = $arSrcElement['STAGE_ID'];
			}

			if(isset($_POST['OPENED']))
			{
				$arFields['OPENED'] = strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
			}
			elseif(isset($arSrcElement['OPENED']))
			{
				$arFields['OPENED'] = $arSrcElement['OPENED'];
			}
			elseif(!$isEditMode && !$isCopyMode)
			{
				$arFields['OPENED'] = \Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			}

			if(isset($_POST['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = (int)(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']);
			}
			elseif(isset($arSrcElement['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = $arSrcElement['ASSIGNED_BY_ID'];
			}

			if ($bTaxMode)
			{
				$arFields['LOCATION_ID'] = $_POST['LOC_CITY'];
			}

			if(isset($_POST['BEGINDATE']))
			{
				$arFields['BEGINDATE'] = trim($_POST['BEGINDATE']);
			}
			elseif(isset($arSrcElement['BEGINDATE']))
			{
				$arFields['BEGINDATE'] = $arSrcElement['BEGINDATE'];
			}

			if(isset($_POST['CLOSEDATE']))
			{
				$arFields['CLOSEDATE'] = trim($_POST['CLOSEDATE']);
			}
			elseif(isset($arSrcElement['CLOSEDATE']))
			{
				$arFields['CLOSEDATE'] = $arSrcElement['CLOSEDATE'];
			}

			if(isset($_POST['CLOSED']))
			{
				$arFields['CLOSED'] = $_POST['CLOSED'] == 'Y' ? 'Y' : 'N';
			}
			elseif(isset($arSrcElement['CLOSED']))
			{
				$arFields['CLOSED'] = $arSrcElement['CLOSED'];
			}

			if(isset($_POST['OPPORTUNITY']))
			{
				$arFields['OPPORTUNITY'] = trim($_POST['OPPORTUNITY']);
			}
			elseif(isset($arSrcElement['OPPORTUNITY']))
			{
				$arFields['OPPORTUNITY'] = $arSrcElement['OPPORTUNITY'];
			}

			if(isset($_POST['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = $_POST['CURRENCY_ID'];
			}
			elseif(isset($arSrcElement['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = $arSrcElement['CURRENCY_ID'];
			}

			$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
			if(!($currencyID !== '' && CCrmCurrency::IsExists($currencyID)))
			{
				$currencyID = $arFields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
			}
			$arFields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($currencyID);

			if(isset($_POST['CONTACT_ID']))
			{
				$contactID = intval($_POST['CONTACT_ID']);
				if($contactID <= 0)
				{
					$arFields['CONTACT_ID'] = 0;
				}
				elseif(CCrmContact::CheckReadPermission($contactID))
				{
					$arFields['CONTACT_ID'] = $contactID;
				}
				elseif(isset($arSrcElement['CONTACT_ID']))
				{
					$arFields['CONTACT_ID'] = $arSrcElement['CONTACT_ID'];
				}
			}
			elseif(isset($arSrcElement['CONTACT_ID']))
			{
				$arFields['CONTACT_ID'] = $arSrcElement['CONTACT_ID'];
			}

			if(isset($_POST['NEW_CONTACT_ID']))
			{
				$arResult['NEW_CONTACT_ID'] = $_POST['NEW_CONTACT_ID'];
			}

			if(isset($_POST['COMPANY_ID']))
			{
				$companyID = intval($_POST['COMPANY_ID']);
				if($companyID <= 0)
				{
					$arFields['COMPANY_ID'] = 0;
				}
				elseif(CCrmCompany::CheckReadPermission($companyID))
				{
					$arFields['COMPANY_ID'] = $companyID;
				}
				elseif(isset($arSrcElement['COMPANY_ID']))
				{
					$arFields['COMPANY_ID'] = $arSrcElement['COMPANY_ID'];
				}
			}
			elseif(isset($arSrcElement['COMPANY_ID']))
			{
				$arFields['COMPANY_ID'] = $arSrcElement['COMPANY_ID'];
			}

			$companyRequisiteId = isset($_POST['COMPANY_REQUISITE_ID']) ? (int)$_POST['COMPANY_REQUISITE_ID'] : 0;
			if ($companyRequisiteId < 0)
				$companyRequisiteId = 0;
			$companyBankDetailId = isset($_POST['COMPANY_BANK_DETAIL_ID']) ? (int)$_POST['COMPANY_BANK_DETAIL_ID'] : 0;
			if ($companyBankDetailId < 0)
				$companyBankDetailId = 0;
			$contactRequisiteId = isset($_POST['CONTACT_REQUISITE_ID']) ? (int)$_POST['CONTACT_REQUISITE_ID'] : 0;
			if ($contactRequisiteId < 0)
				$contactRequisiteId = 0;
			$contactBankDetailId = isset($_POST['CONTACT_BANK_DETAIL_ID']) ? (int)$_POST['CONTACT_BANK_DETAIL_ID'] : 0;
			if ($contactBankDetailId < 0)
				$contactBankDetailId = 0;
			if ($arFields['COMPANY_ID'] > 0 && $companyRequisiteId > 0)
			{
				$requisiteIdLinked = $companyRequisiteId;
				$bankDetailIdLinked = $companyBankDetailId;
			}
			else if ($arFields['CONTACT_ID'] > 0 && $contactRequisiteId > 0)
			{
				$requisiteIdLinked = $contactRequisiteId;
				$bankDetailIdLinked = $contactBankDetailId;
			}
			else
			{
				$requisiteIdLinked = 0;
				$bankDetailIdLinked = 0;
			}

			$processProductRows = array_key_exists($productDataFieldName, $_POST);
			$arProd = array();
			if($processProductRows)
			{
				$arProd = $arResult['PRODUCT_ROWS'] = isset($_POST[$productDataFieldName]) ? ($_POST[$productDataFieldName]) : array();

				if(count($arProd) > 0)
				{
					// SYNC OPPORTUNITY WITH PRODUCT ROW SUM TOTAL
					$result = CCrmProductRow::CalculateTotalInfo('D', 0, false, $arFields, $arProd);
					$arFields['OPPORTUNITY'] = isset($result['OPPORTUNITY']) ? $result['OPPORTUNITY'] : 1.0;
					$arFields['TAX_VALUE'] = isset($result['TAX_VALUE']) ? $result['TAX_VALUE'] : 0.0;
				}
			}

			// Product row settings
			$productRowSettings = array();
			$productRowSettingsFieldName = $productDataFieldName.'_SETTINGS';
			if(array_key_exists($productRowSettingsFieldName, $_POST))
			{
				$settingsJson = isset($_POST[$productRowSettingsFieldName]) ? strval($_POST[$productRowSettingsFieldName]) : '';
				$arSettings = strlen($settingsJson) > 0 ? CUtil::JsObjectToPhp($settingsJson) : array();
				if(is_array($arSettings))
				{
					$productRowSettings['ENABLE_DISCOUNT'] = isset($arSettings['ENABLE_DISCOUNT']) ? $arSettings['ENABLE_DISCOUNT'] === 'Y' : false;
					$productRowSettings['ENABLE_TAX'] = isset($arSettings['ENABLE_TAX']) ? $arSettings['ENABLE_TAX'] === 'Y' : false;
				}
			}
			unset($productRowSettingsFieldName, $settingsJson, $arSettings);

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmDeal::$sUFEntityID, $arFields, array('FORM' => $_POST));
			if($conversionWizard !== null)
			{
				$conversionWizard->prepareDataForSave(CCrmOwnerType::Deal, $arFields);
			}
			elseif($isCopyMode)
			{
				$CCrmUserType->CopyFileFields($arFields);
			}

			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmDeal->CheckFields($arFields, $isEditMode ? $arResult['ELEMENT']['ID'] : false, array('DISABLE_USER_FIELD_CHECK' => true)))
			{
				if (!empty($CCrmDeal->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmDeal->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($isEditMode ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $isEditMode ? $arEntityAttr : null)) === false)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$bSuccess = false;
				if ($isEditMode)
				{
					$bSuccess = $CCrmDeal->Update($arResult['ELEMENT']['ID'], $arFields, true, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
				}
				else
				{
					if ($originatorId > 0 && $originId > 0)
					{
						$arFields['ORIGINATOR_ID'] = $originatorId;
						$arFields['ORIGIN_ID'] = $originId;
					}

					//region Process Creation on base of lead and quote. We need to set parent entity ID for bizproc
					if(isset($arResult['LEAD_ID']) && $arResult['LEAD_ID'] > 0)
					{
						$arFields['LEAD_ID'] = $arResult['LEAD_ID'];
					}

					if(isset($arResult['QUOTE_ID']) && $arResult['QUOTE_ID'] > 0)
					{
						$arFields['QUOTE_ID'] = $arResult['QUOTE_ID'];
					}
					//endregion

					$ID = $CCrmDeal->Add($arFields, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
					$bSuccess = $ID !== false;
					if($bSuccess)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if ($bSuccess)
				{
					if ($requisiteIdLinked > 0)
					{
						\Bitrix\Crm\Requisite\EntityLink::register(
							CCrmOwnerType::Deal, $arResult['ELEMENT']['ID'], $requisiteIdLinked, $bankDetailIdLinked
						);
					}
					else
					{
						\Bitrix\Crm\Requisite\EntityLink::unregister(CCrmOwnerType::Deal, $arResult['ELEMENT']['ID']);
					}
				}

				if ($bSuccess)
				{
					// Save settings
					if(is_array($productRowSettings) && count($productRowSettings) > 0)
					{
						$arSettings = CCrmProductRow::LoadSettings('D', $arResult['ELEMENT']['ID']);
						foreach ($productRowSettings as $k => $v)
							$arSettings[$k] = $v;
						CCrmProductRow::SaveSettings('D', $arResult['ELEMENT']['ID'], $arSettings);
					}
					unset($arSettings);
				}

				if($bSuccess
					&& !$isExternal // Product rows of external deal are read only
					&& $processProductRows
					&& ($isEditMode || !empty($arProd)))
				{
					// Suppress owner synchronization
					$bSuccess = $CCrmDeal::SaveProductRows($arResult['ELEMENT']['ID'], $arProd, true, true, false);
					if(!$bSuccess)
					{
						$arResult['ERROR_MESSAGE'] = GetMessage('PRODUCT_ROWS_SAVING_ERROR');
					}
				}

				if($bSuccess)
				{
					if($arFields['CONTACT_ID'] > 0
						&& $arFields['COMPANY_ID'] > 0
						&& isset($_POST['NEW_CONTACT_ID'])
						&& $arFields['CONTACT_ID'] == $_POST['NEW_CONTACT_ID'])
					{
						$CrmContact = new CCrmContact();
						$arContactFields = array(
							'COMPANY_ID' => $arFields['COMPANY_ID']
						);

						$bSuccess = $CrmContact->Update(
							$arFields['CONTACT_ID'],
							$arContactFields,
							false,
							true,
							array('DISABLE_USER_FIELD_CHECK' => true)
						);

						if(!$bSuccess)
						{
							$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
						}
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

			if ($originId > 0)
			{
				$import = new CCrmExternalSaleImport($originatorId);
				if ($import->IsInitialized())
				{
					$import->AddParam('DEFAULT_DEAL_TITLE', GetMessage('CRM_DEAL_EDIT_DEFAULT_TITLE'));
					$importCode = $import->GetOrderData($originId, true);
					if ($importCode == CCrmExternalSaleImport::SyncStatusError)
					{
						$arErrors = $import->GetErrors();
						foreach ($arErrors as $err)
						{
							$arResult['ERROR_MESSAGE'] .= $err[1].'<br />';
						}
					}
				}
			}

			if (empty($arResult['ERROR_MESSAGE']))
			{
				if (!$CCrmBizProc->StartWorkflow($arResult['ELEMENT']['ID'], $arBizProcParametersValues))
					$arResult['ERROR_MESSAGE'] = $CCrmBizProc->LAST_ERROR;
			}

			//Region automation
			if (class_exists('\Bitrix\Crm\Automation\Factory'))
			{
				if (!$isEditMode)
				{
					\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $arResult['ELEMENT']['ID']);
				}
				elseif (isset($arFields['STAGE_ID']) && $arSrcElement['STAGE_ID'] != $arFields['STAGE_ID'])
				{
					\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Deal, $arResult['ELEMENT']['ID']);
				}
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

			if (isset($_POST['continue']) && $conversionWizard !== null)
			{
				$conversionWizard->execute(array(CCrmOwnerType::DealName => $ID));
				$url = $conversionWizard->getRedirectUrl();
				if($url !== '')
				{
					$arJsonData["url"] = $url;
				}
			}

			$APPLICATION->RestartBuffer();
			echo \Bitrix\Main\Web\Json::encode($arJsonData);
			CMain::FinalActions();
			die();
		}
	}
}

$arResult['BACK_URL'] = $conversionWizard !== null && $conversionWizard->hasOriginUrl()
	? $conversionWizard->getOriginUrl() : $arParams['PATH_TO_DEAL_LIST'];

$arResult['STAGE_LIST'] = array();
$arResult['~STAGE_LIST'] = Bitrix\Crm\Category\DealCategory::getStageList($arResult['CATEGORY_ID']);
foreach ($arResult['~STAGE_LIST'] as $statusID => $statusTitle)
{
	$permissionType = $isEditMode
		? CCrmDeal::GetStageUpdatePermissionType($statusID, $userPermissions, $arResult['CATEGORY_ID'])
		: CCrmDeal::GetStageCreatePermissionType($statusID, $userPermissions, $arResult['CATEGORY_ID']);

	if ($permissionType > BX_CRM_PERM_NONE)
	{
		$arResult['STAGE_LIST'][$statusID] = $statusTitle;
	}
}

$arResult['STATE_LIST'] = CCrmStatus::GetStatusList('DEAL_STATE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('DEAL_TYPE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

$arResult['EVENT_LIST'] = CCrmStatus::GetStatusList('EVENT_TYPE');
$arResult['EDIT'] = $isEditMode;

$arResult['DEAL_VIEW_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['DEAL_VIEW_URL_TEMPLATE'],
	array('deal_id' => $entityID)
);
$arResult['DEAL_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['DEAL_EDIT_URL_TEMPLATE'],
	array('deal_id' => $entityID)
);
/*============= fields for main.interface.form =========*/
$arResult['FIELDS'] = array();

$arResult['FIELDS'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label'
);

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['STAGE_ID']) ? $arResult['ELEMENT']['STAGE_ID'] : '');
else
	$value = (isset($arResult['ELEMENT']['STAGE_ID']) ? $arResult['STAGE_LIST'][$arResult['ELEMENT']['STAGE_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'STAGE_ID',
	'name' => GetMessage('CRM_FIELD_STAGE_ID'),
	'items' => $arResult['STAGE_LIST'],
	'params' => array('sale_order_marker' => 'Y'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'value' => $value
);

if ($bTaxMode && !$arParams["RESTRICTED_MODE"])
{
	// PAYER LOCATION
	$sLocationHtml = '';

	CModule::IncludeModule('sale');
	$locValue = isset($arResult['ELEMENT']['PR_LOCATION']) ? $arResult['ELEMENT']['PR_LOCATION'] : '';

	ob_start();

	CSaleLocation::proxySaleAjaxLocationsComponent(
		array(
			'AJAX_CALL' => 'N',
			'COUNTRY_INPUT_NAME' => 'LOC_COUNTRY',
			'REGION_INPUT_NAME' => 'LOC_REGION',
			'CITY_INPUT_NAME' => 'LOC_CITY',
			'CITY_OUT_LOCATION' => 'Y',
			'LOCATION_VALUE' => $locValue,
			'ORDER_PROPS_ID' => $arInvoiceProperties['FIELDS']['ID'],
			'ONCITYCHANGE' => 'BX.onCustomEvent(\'CrmProductRowSetLocation\', [\'LOC_CITY\']);',
			'SHOW_QUICK_CHOOSE' => 'N'/*,
			'SIZE1' => $arProperties['SIZE1']*/
		),
		array(
			"CODE" => "",
			"ID" => $locValue,
			"PROVIDE_LINK_BY" => "id",
			"JS_CALLBACK" => 'CrmProductRowSetLocation'
		),
		'popup',
		true,
		'locationpro-selector-wrapper'
	);

	$sLocationHtml = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS'][] = array(
		'id' => 'LOCATION_ID',
		'name' => GetMessage('CRM_FIELD_LOCATION'),
		'type' => 'custom',
		'value' => '<div style="padding: 6px 0 7px;">'.$sLocationHtml.'</div>',
		'required' => true
	);
}

$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

$currencyFld = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID')
);
$isExternal = false;
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
if(!$isExternal && $arResult["IS_EDIT_PERMITTED"])
{
	$currencyFld['type'] = 'select';
	$currencyFld['params'] = array('sale_order_marker' => 'Y');
	$currencyFld['items'] = $arResult['CURRENCY_LIST'];
	$currencyFld['value'] = $currencyID;
}
else
{
	$currencyFld['type'] = 'label';
	$currencyFld['params'] = array('size' => 50);
	$currencyFld['value'] = isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID;
}

$arResult['FIELDS'][] = &$currencyFld;

$opportunityFld = array(
	'id' => 'OPPORTUNITY',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'params' => array('size' => 21, 'sale_order_marker' => 'Y'),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : ''
);
/*if(!$isExternal)
{
	$opportunityFld['type'] = 'text';
}
else
{
	$opportunityFld['type'] = 'label';
}*/
$arResult['FIELDS'][] = &$opportunityFld;

$arResult['FIELDS'][] = array(
	'id' => 'PROBABILITY',
	'name' => GetMessage('CRM_FIELD_PROBABILITY'),
	'params' => array('size' => 3, 'maxlength' => '3'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'value' => isset($arResult['ELEMENT']['PROBABILITY']) ? (string)(double)$arResult['ELEMENT']['PROBABILITY'] : ''
);
$arResult['FIELDS'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user',
	'canDrop' => false,
	"item" => CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()),
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);
$beginDate = isset($arResult['ELEMENT']['BEGINDATE']) ? $arResult['ELEMENT']['BEGINDATE'] : '';
$closeDate = isset($arResult['ELEMENT']['CLOSEDATE']) ? $arResult['ELEMENT']['CLOSEDATE'] : $beginDate;

$arResult['FIELDS'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_FIELD_BEGINDATE'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'canDrop' => false,
	'value' => $beginDate !== '' ? ConvertTimeStamp(MakeTimeStamp($beginDate), 'SHORT', SITE_ID) : ''
);
$arResult['FIELDS'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_FIELD_CLOSEDATE2'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'canDrop' => false,
	'value' => $closeDate !== '' ? ConvertTimeStamp(MakeTimeStamp($closeDate), 'SHORT', SITE_ID) : ''
);

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['ELEMENT']['TYPE_ID'] : '');
else
	$value = (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['TYPE_LIST'][$arResult['ELEMENT']['TYPE_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['TYPE_LIST'],
	'value' => $value
);
$arResult['FIELDS'][] = array(
	'id' => 'OPENED',
	'type' => 'checkbox',
	"items" => array(
		"Y" => GetMessage('CRM_FIELD_OPENED')
	),
	'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : \Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N'
);

if (CCrmContact::CheckReadPermission($arResult['ELEMENT']['CONTACT_ID'], $userPermissions))
{
	$arResult['ELEMENT_CONTACT'] = "";
	if ($arResult['ELEMENT']['CONTACT_ID'])
	{
		$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_SHOW_URL_TEMPLATE'],
			array('contact_id' => $arResult['ELEMENT']['CONTACT_ID'])
		);

		if (!$arResult['ELEMENT']["CONTACT_FULL_NAME"])
		{
			$dbContact = CCrmContact::GetListEx(array(), array("ID" => $arResult['ELEMENT']['CONTACT_ID']), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO'));
			if ($arContact = $dbContact->Fetch())
			{
				$arResult['ELEMENT']["CONTACT_FULL_NAME"] = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => $arContact['HONORIFIC'],
						'NAME' => $arContact['NAME'],
						'LAST_NAME' => $arContact['LAST_NAME'],
						'SECOND_NAME' => $arContact['SECOND_NAME']
					)
				);

				$arResult['ELEMENT']["CONTACT_POST"] = $arContact["POST"];
				$arResult['ELEMENT']["CONTACT_PHOTO"] = $arContact["PHOTO"];
			}
		}

		$photoD = isset($arResult['ELEMENT']["CONTACT_PHOTO"]) ? $arResult['ELEMENT']["CONTACT_PHOTO"] : 0;
		if($photoD > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$photoD, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
			$arResult['ELEMENT']["CONTACT_PHOTO"] = $listImageInfo["src"];
		}
		$arResult['ELEMENT']["CONTACT_MULTI_FIELDS"] = CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['CONTACT_ID'], CCrmOwnerType::ContactName);

		$arResult['ELEMENT_CONTACT'] = array(
			"id" => $arResult['ELEMENT']["CONTACT_ID"],
			"name" => $arResult['ELEMENT']["CONTACT_FULL_NAME"],
			"image" => $arResult['ELEMENT']["CONTACT_PHOTO"],
			"url" => $contactShowUrl,
			"entityType" => "contact",
			"addTitle" => $arResult['ELEMENT']['CONTACT_POST'],
			"multi" => is_array($arResult['ELEMENT']["CONTACT_MULTI_FIELDS"]) ? $arResult['ELEMENT']["CONTACT_MULTI_FIELDS"] : array()
		);
	}

	$arResult["ON_SELECT_CONTACT_EVENT_NAME"] = "onCrmContactSelectForDeal_".$arParams['ELEMENT_ID'];

	$contactPath = CHTTP::urlAddParams($arParams['CONTACT_SELECTOR_URL_TEMPLATE'], array(
		"event" => $arResult["ON_SELECT_CONTACT_EVENT_NAME"]
	));

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['CONTACT_ID'])
	{
		$arResult['FIELDS'][] = array(
			'id' => 'CONTACT_ID',
			'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-deal-edit-contact" data-role="mobile-crm-deal-edit-contact">'.
							//Contact's html is generated on javascript, object BX.Mobile.Crm.ClientEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($contactPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}
if (CCrmCompany::CheckReadPermission($arResult['ELEMENT']['COMPANY_ID'], $userPermissions))
{
	$arResult['ELEMENT_COMPANY'] = "";
	if ($arResult['ELEMENT']['COMPANY_ID'])
	{
		$companyShowUrl = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_SHOW_URL_TEMPLATE'],
			array('company_id' => $arResult['ELEMENT']['COMPANY_ID'])
		);

		if (!$arResult['ELEMENT']["COMPANY_TITLE"])
		{
			$dbCompany = CCrmCompany::GetListEx(array(), array("ID" => $arResult['ELEMENT']['COMPANY_ID']), false, false, array('TITLE', 'COMPANY_TYPE', 'LOGO'));
			if ($arCompany = $dbCompany->Fetch())
			{
				$arResult['ELEMENT']["COMPANY_TITLE"] = $arCompany['TITLE'];
				$arResult['ELEMENT']["COMPANY_TYPE"] = $arCompany["COMPANY_TYPE"];
				$arResult['ELEMENT']["COMPANY_LOGO"] = $arCompany["LOGO"];
			}
		}

		$photoD = isset($arResult['ELEMENT']["COMPANY_LOGO"]) ? $arResult['ELEMENT']["COMPANY_LOGO"] : 0;
		if($photoD > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$photoD, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
			$arResult['ELEMENT']["COMPANY_LOGO"] = $listImageInfo["src"];
		}
		$arResult['ELEMENT']["COMPANY_MULTI_FIELDS"] = CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['COMPANY_ID'], CCrmOwnerType::CompanyName);

		$arResult['ELEMENT_COMPANY'] = array(
			"id" => $arResult['ELEMENT']["COMPANY_ID"],
			"name" => $arResult['ELEMENT']["COMPANY_TITLE"],
			"image" => $arResult['ELEMENT']["COMPANY_LOGO"],
			"entityType" => "company",
			"addTitle" => $arResult['COMPANY_TYPE_LIST'][$arResult['ELEMENT']["COMPANY_TYPE"]],
			"url" => $companyShowUrl,
			"multi" => is_array($arResult['ELEMENT']["COMPANY_MULTI_FIELDS"]) ? $arResult['ELEMENT']["COMPANY_MULTI_FIELDS"] : array()
		);
	}

	$arResult["ON_SELECT_COMPANY_EVENT_NAME"] = "onCrmCompanySelectForDeal_".$arParams['ELEMENT_ID'];
	$arResult["ON_DELETE_COMPANY_EVENT_NAME"] = "onCrmCompanyDeleteForDeal_".$arParams['ELEMENT_ID'];

	$companyPath = CHTTP::urlAddParams($arParams['COMPANY_SELECTOR_URL_TEMPLATE'], array(
		"event" => $arResult["ON_SELECT_COMPANY_EVENT_NAME"]
	));

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['COMPANY_ID'])
	{
		$arResult['FIELDS'][] = array(
			'id' => 'COMPANY_ID',
			'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
			'params' => array('size' => 50),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-deal-edit-company" data-role="mobile-crm-deal-edit-company">'.
							//Company's html is generated on javascript, object BX.Mobile.Crm.ClientEditor
							'</div>'. ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($companyPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}

if ($arResult["IS_EDIT_PERMITTED"])
	$fieldType = $arParams['RESTRICTED_MODE'] ? 'custom' : 'textarea';
else
	$fieldType = 'label';

$arResult['FIELDS'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => $fieldType,
	'value' => htmlspecialcharsback($arResult['ELEMENT']['~COMMENTS'])
);

// Product rows
$arResult["PAGEID_PRODUCT_SELECTOR_BACK"] = "crmDealEditPage";
$arResult["ON_PRODUCT_SELECT_EVENT_NAME"] = "onCrmSelectProductForDeal_".$arParams['ELEMENT_ID'];
$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] = CHTTP::urlAddParams($arParams['PRODUCT_SELECTOR_URL_TEMPLATE'], array(
	"event" => $arResult["ON_PRODUCT_SELECT_EVENT_NAME"],
	"pageIdProductSelectorBack" => $arResult["PAGEID_PRODUCT_SELECTOR_BACK"]
));
$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'deal_'.strval($arParams['ELEMENT_ID']) : 'new_deal').'_product_editor';

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
	'OWNER_TYPE' => 'D',
	'PERMISSION_TYPE' => $isExternal || $arParams['RESTRICTED_MODE'] ? 'READ' : 'WRITE',
	'INIT_EDITABLE' => $isExternal ? 'N' : 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => ($bTaxMode && isset($arResult['ELEMENT']['LOCATION_ID'])) ? $arResult['ELEMENT']['LOCATION_ID'] : '',
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
if (isset($arParams['ENABLE_DISCOUNT']))
	$componentSettings['ENABLE_DISCOUNT'] = ($arParams['ENABLE_DISCOUNT'] === 'Y') ? 'Y' : 'N';
if (isset($arParams['ENABLE_TAX']))
	$componentSettings['ENABLE_TAX'] = ($arParams['ENABLE_TAX'] === 'Y') ? 'Y' : 'N';
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
$CCrmUserType->PrepareUserFields(
	$arResult['FIELDS'],
	CCrmDeal::$sUFEntityID,
	$arResult['ELEMENT']['ID']
);

if ($arParams['RESTRICTED_MODE'])
{
	$arResult['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Deal, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';

	$arResult['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['EVENT_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Deal, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';
}

$this->IncludeComponentTemplate();
