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

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

$CCrmInvoice = new CCrmInvoice();

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;

global $DB, $USER, $USER_FIELD_MANAGER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmInvoice::$sUFEntityID);
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$bEdit = false;
$bCopy = false;
$bVarsFromForm = false;

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID < 0)
{
	$entityID = 0;
}
if($entityID === 0 && isset($_REQUEST['invoice_id']))
{
	$entityID = $arParams['ELEMENT_ID'] = intval($_REQUEST['invoice_id']);
}
$arResult['ELEMENT_ID'] = $entityID;

if (!empty($arParams['ELEMENT_ID']))
	$bEdit = true;
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}

$arResult["IS_EDIT_PERMITTED"] = false;
$arResult["IS_VIEW_PERMITTED"] = false;
$arResult["IS_DELETE_PERMITTED"] = CCrmInvoice::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if($bEdit)
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmInvoice::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"])
	{
		$arResult["IS_VIEW_PERMITTED"] = CCrmInvoice::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
}
elseif($bCopy)
{
	$arResult["IS_VIEW_PERMITTED"] = CCrmInvoice::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmInvoice::CheckCreatePermission($userPermissions);
}

if(!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"])
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$bCreateFromQuote = $bCreateFromDeal = $bCreateFromCompany = $bCreateFromContact = false;
$quoteId = $dealId = $companyId = $contactId = 0;
$arQuoteClientFields = array();
if (isset($_REQUEST['quote']) && $_REQUEST['quote'] > 0)
{
	$bCreateFromQuote = true;
	$quoteId = intval($_REQUEST['quote']);
}
elseif (isset($_REQUEST['deal']) && $_REQUEST['deal'] > 0)
{
	$bCreateFromDeal = true;
	$dealId = intval($_REQUEST['deal']);
}
elseif (isset($_REQUEST['company']) && $_REQUEST['company'] > 0)
{
	$bCreateFromCompany = true;
	$companyId = intval($_REQUEST['company']);
}
if (isset($_REQUEST['contact']) && $_REQUEST['contact'] > 0)
{
	$bCreateFromContact = true;
	$contactId = intval($_REQUEST['contact']);
}

$conversionWizard = null;
if (isset($_REQUEST['conv_deal_id']) && $_REQUEST['conv_deal_id'] > 0)
{
	$srcDealId = (int)$_REQUEST['conv_deal_id'];
	if($srcDealId > 0)
	{
		$conversionWizard = \Bitrix\Crm\Conversion\DealConversionWizard::load($srcDealId);
		if($conversionWizard !== null)
		{
			$arResult['DEAL_ID'] = $srcDealId;
		}
	}
}
elseif (isset($_REQUEST['conv_quote_id']) && $_REQUEST['conv_quote_id'] > 0)
{
	$srcQuoteId = (int)$_REQUEST['conv_quote_id'];
	if($srcQuoteId > 0)
	{
		$conversionWizard = \Bitrix\Crm\Conversion\QuoteConversionWizard::load($srcQuoteId);
		if($conversionWizard !== null)
		{
			$arResult['QUOTE_ID'] = $srcQuoteId;
		}
	}
}

$bCreateFrom = ($bCreateFromQuote || $bCreateFromDeal || $bCreateFromCompany || $bCreateFromContact);

$bConvert = isset($arParams['CONVERT']) && $arParams['CONVERT'];

$bTaxMode = CCrmTax::isTaxMode();

if (($bEdit || $bCopy) && !empty($arResult['ELEMENT']['CURRENCY']))
	$currencyID = $arResult['ELEMENT']['CURRENCY'];
else
	$currencyID = CCrmInvoice::GetCurrencyID();

$requisiteIdLinked = 0;
$bankDetailIdLinked = 0;
$mcRequisiteIdLinked = 0;
$mcBankDetailIdLinked = 0;

if ($conversionWizard !== null)
{
	$arResult['MODE'] = 'CONVERT';

	$arFields = array('ID' => 0);
	$conversionWizard->prepareDataForEdit(CCrmOwnerType::Invoice, $arFields, true);
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend(CCrmOwnerType::Invoice);

	if(isset($arFields['PRODUCT_ROWS']))
	{
		$arResult['PRODUCT_ROWS'] = $arFields['PRODUCT_ROWS'];
	}
}
elseif ($bEdit || $bCopy) //edit/view
{
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => $arParams["RESTRICTED_MODE"] ? 'READ' : 'WRITE'
	);
	$obFields = CCrmInvoice::GetList(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if(!is_array($arFields))
	{
		ShowError(GetMessage('CRM_INVOICE_EDIT_NOT_FOUND', array("#ID#" => $arParams['ELEMENT_ID'])));
		return;
	}

	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}
	else
		$arEntityAttr = $CCrmInvoice->cPerms->GetEntityAttr('INVOICE', array($arParams['ELEMENT_ID']));

	//HACK: MSSQL returns '.00' for zero value
	if(isset($arFields['~PRICE']))
	{
		$arFields['~PRICE'] = $arFields['PRICE'] = floatval($arFields['~PRICE']);
	}
}
else //create
{
	$arResult['MODE'] = 'CREATE';

	$arFields = array(
		'ID' => 0,
		'DATE_INSERT' => ConvertTimeStamp(time(), 'FULL', SITE_ID)
	);

	if ($bCreateFromQuote)
	{
		$arFields['UF_QUOTE_ID'] = $quoteId;
		$arQuote = CCrmQuote::GetByID($quoteId);
		$arQuoteProducts = CCrmQuote::LoadProductRows($quoteId);
		if (is_array($arQuote) && count($arQuote) > 0)
		{
			if ($bTaxMode && isset($arQuote['LOCATION_ID']))
			{
				$arFields['~PR_LOCATION'] = $arQuote['LOCATION_ID'];
				$arFields['PR_LOCATION'] = htmlspecialcharsbx($arQuote['LOCATION_ID']);
			}
			if (isset($arQuote['TITLE']))
			{
				$arFields['~ORDER_TOPIC'] = $arQuote['TITLE'];
				$arFields['ORDER_TOPIC'] = htmlspecialcharsbx($arQuote['TITLE']);
			}
			if (isset($arQuote['COMPANY_ID']))
			{
				$arFields['~UF_COMPANY_ID'] = $arQuote['COMPANY_ID'];
				$arFields['UF_COMPANY_ID'] = htmlspecialcharsbx($arQuote['COMPANY_ID']);
			}
			if (isset($arQuote['CONTACT_ID']))
			{
				$arFields['~UF_CONTACT_ID'] = $arQuote['CONTACT_ID'];
				$arFields['UF_CONTACT_ID'] = htmlspecialcharsbx($arQuote['CONTACT_ID']);
			}
			if (isset($arQuote['DEAL_ID']))
			{
				$arFields['~UF_DEAL_ID'] = $arQuote['DEAL_ID'];
				$arFields['UF_DEAL_ID'] = htmlspecialcharsbx($arQuote['DEAL_ID']);
			}
			if (isset($arQuote['ASSIGNED_BY_ID']))
			{
				$arFields['~RESPONSIBLE_ID'] = $arQuote['ASSIGNED_BY_ID'];
				$arFields['RESPONSIBLE_ID'] = htmlspecialcharsbx($arQuote['ASSIGNED_BY_ID']);
			}
			if (isset($arQuote['COMMENTS']))
			{
				$arFields['~COMMENTS'] = $arQuote['COMMENTS'];
				$arFields['COMMENTS'] = htmlspecialcharsbx($arQuote['COMMENTS']);
			}
			foreach (CCrmQuote::GetClientFields() as $k)
				$arQuoteClientFields[$k] = isset($arQuote[$k]) ? $arQuote[$k] : '';
			unset($k);
			if (is_array($arQuoteProducts) && count($arQuoteProducts) > 0)
			{
				$quoteCurrencyID =
					(empty($arQuote['CURRENCY_ID']) || !CCrmCurrency::IsExists($arQuote['CURRENCY_ID'])) ?
						CCrmCurrency::GetBaseCurrencyID() :
						$arQuote['CURRENCY_ID'];
				$freshRows = CCrmInvoice::ProductRows2BasketItems($arQuoteProducts, $quoteCurrencyID, $currencyID);
				if (count($freshRows) > 0)
				{
					$arFields['PRODUCT_ROWS']= $arResult['PRODUCT_ROWS'] = $freshRows;
				}
				unset($freshRows);
			}
			unset($arQuoteProducts);
		}
		unset($arQuote, $arQuoteProducts);

		// read product row settings
		$productRowSettings = array();
		$arQuoteProductRowSettings = CCrmProductRow::LoadSettings(CCrmQuote::OWNER_TYPE, $quoteId);
		if (is_array($arQuoteProductRowSettings))
		{
			$productRowSettings['ENABLE_DISCOUNT'] = isset($arQuoteProductRowSettings['ENABLE_DISCOUNT']) ? $arQuoteProductRowSettings['ENABLE_DISCOUNT'] : false;
			$productRowSettings['ENABLE_TAX'] = isset($arQuoteProductRowSettings['ENABLE_TAX']) ? $arQuoteProductRowSettings['ENABLE_TAX'] : false;
		}
		unset($arQuoteProductRowSettings);
	}
	elseif ($bCreateFromDeal)
	{
		$arFields['UF_DEAL_ID'] = $dealId;
		$arDeal = CCrmDeal::GetByID($dealId);
		$arDealProducts = CCrmDeal::LoadProductRows($dealId);
		if (is_array($arDeal) && count($arDeal) > 0)
		{
			if (isset($arDeal['TITLE']))
			{
				$arFields['~ORDER_TOPIC'] = $arDeal['TITLE'];
				$arFields['ORDER_TOPIC'] = htmlspecialcharsbx($arDeal['TITLE']);
			}
			if (isset($arDeal['COMPANY_ID']))
			{
				$arFields['~UF_COMPANY_ID'] = $arDeal['COMPANY_ID'];
				$arFields['UF_COMPANY_ID'] = htmlspecialcharsbx($arDeal['COMPANY_ID']);
			}
			if (isset($arDeal['CONTACT_ID']))
			{
				$arFields['~UF_CONTACT_ID'] = $arDeal['CONTACT_ID'];
				$arFields['UF_CONTACT_ID'] = htmlspecialcharsbx($arDeal['CONTACT_ID']);
			}
			if (isset($arDeal['ASSIGNED_BY_ID']))
			{
				$arFields['~RESPONSIBLE_ID'] = $arDeal['ASSIGNED_BY_ID'];
				$arFields['RESPONSIBLE_ID'] = htmlspecialcharsbx($arDeal['ASSIGNED_BY_ID']);
			}
			if (isset($arDeal['COMMENTS']))
			{
				$arFields['~COMMENTS'] = $arDeal['COMMENTS'];
				$arFields['COMMENTS'] = htmlspecialcharsbx($arDeal['COMMENTS']);
			}
			if (is_array($arDealProducts) && count($arDealProducts) > 0)
			{
				$dealCurrencyID =
					(empty($arDeal['CURRENCY_ID']) || !CCrmCurrency::IsExists($arDeal['CURRENCY_ID'])) ?
						CCrmCurrency::GetBaseCurrencyID() :
						$arDeal['CURRENCY_ID'];
				$freshRows = CCrmInvoice::ProductRows2BasketItems($arDealProducts, $dealCurrencyID, $currencyID);
				if (count($freshRows) > 0)
				{
					$arFields['PRODUCT_ROWS']= $arResult['PRODUCT_ROWS'] = $freshRows;
				}
				unset($freshRows);
			}
			unset($arDealProducts);
		}
		unset($arDeal, $arDealProducts);

		// read product row settings
		$productRowSettings = array();
		$arDealProductRowSettings = CCrmProductRow::LoadSettings('D', $dealId);
		if (is_array($arDealProductRowSettings))
		{
			$productRowSettings['ENABLE_DISCOUNT'] = isset($arDealProductRowSettings['ENABLE_DISCOUNT']) ? $arDealProductRowSettings['ENABLE_DISCOUNT'] : false;
			$productRowSettings['ENABLE_TAX'] = isset($arDealProductRowSettings['ENABLE_TAX']) ? $arDealProductRowSettings['ENABLE_TAX'] : false;
		}
		unset($arDealProductRowSettings);
	}
	elseif ($bCreateFromCompany)
		$arFields['UF_COMPANY_ID'] = $companyId;
	elseif ($bCreateFromContact)
		$arFields['UF_CONTACT_ID'] = $contactId;
}

// requisite link
if ($conversionWizard !== null || $bEdit || $bCopy)
{
	$requisiteEntityList = array();
	$mcRequisiteEntityList = array();
	$requisite = new \Bitrix\Crm\EntityRequisite();
	if ($bEdit || $bCopy)
	{
		if ($arParams['ELEMENT_ID'] > 0)
		{
			$mcRequisiteEntityList[] = $requisiteEntityList[] =
				array('ENTITY_TYPE_ID' => CCrmOwnerType::Invoice, 'ENTITY_ID' => $arParams['ELEMENT_ID']);
		}
	}
	else if ($conversionWizard !== null)
	{
		if ($bCreateFromDeal || $conversionWizard instanceof \Bitrix\Crm\Conversion\DealConversionWizard)
		{
			if (isset($arFields['UF_DEAL_ID']) && $arFields['UF_DEAL_ID'] > 0)
			{
				$mcRequisiteEntityList[] = $requisiteEntityList[] =
					array('ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $arFields['UF_DEAL_ID']);
			}
			if (isset($arFields['UF_QUOTE_ID']) && $arFields['UF_QUOTE_ID'] > 0)
			{
				$mcRequisiteEntityList[] = $requisiteEntityList[] =
					array('ENTITY_TYPE_ID' => CCrmOwnerType::Quote, 'ENTITY_ID' => $arFields['UF_QUOTE_ID']);
			}
		}
		else
		{
			if (isset($arFields['UF_QUOTE_ID']) && $arFields['UF_QUOTE_ID'] > 0)
			{
				$mcRequisiteEntityList[] = $requisiteEntityList[] =
					array('ENTITY_TYPE_ID' => CCrmOwnerType::Quote, 'ENTITY_ID' => $arFields['UF_QUOTE_ID']);
			}
			if (isset($arFields['UF_DEAL_ID']) && $arFields['UF_DEAL_ID'] > 0)
			{
				$mcRequisiteEntityList[] = $requisiteEntityList[] =
					array('ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $arFields['UF_DEAL_ID']);
			}
		}
	}
	if (isset($arFields['UF_COMPANY_ID']) && $arFields['UF_COMPANY_ID'] > 0)
		$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $arFields['UF_COMPANY_ID']);
	if (isset($arFields['UF_CONTACT_ID']) && $arFields['UF_CONTACT_ID'] > 0)
		$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $arFields['UF_CONTACT_ID']);
	if (isset($arFields['UF_MYCOMPANY_ID']) && $arFields['UF_MYCOMPANY_ID'] > 0)
		$mcRequisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $arFields['UF_MYCOMPANY_ID']);
	$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
	if (is_array($requisiteInfoLinked))
	{
		if (isset($requisiteInfoLinked['REQUISITE_ID']))
			$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
		if (isset($requisiteInfoLinked['BANK_DETAIL_ID']))
			$bankDetailIdLinked = (int)$requisiteInfoLinked['BANK_DETAIL_ID'];
	}
	$mcRequisiteInfoLinked = $requisite->getDefaultMyCompanyRequisiteInfoLinked($mcRequisiteEntityList);
	if (is_array($mcRequisiteInfoLinked))
	{
		if (isset($mcRequisiteInfoLinked['MC_REQUISITE_ID']))
			$mcRequisiteIdLinked = (int)$mcRequisiteInfoLinked['MC_REQUISITE_ID'];
		if (isset($mcRequisiteInfoLinked['MC_BANK_DETAIL_ID']))
			$mcBankDetailIdLinked = (int)$mcRequisiteInfoLinked['MC_BANK_DETAIL_ID'];
	}
	unset($requisite, $requisiteEntityList, $mcRequisiteEntityList, $requisiteInfoLinked, $mcRequisiteInfoLinked);
}

$isExternal = $bEdit && isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']) && intval($arFields['ORIGINATOR_ID']) > 0 && intval($arFields['ORIGIN_ID']) > 0;

$bProcessPost = $_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid();
if ($bProcessPost)
{
	$bAjaxSubmit = (isset($_POST['invoiceSubmitAjax']) && $_POST['invoiceSubmitAjax'] === 'Y') ? true : false;
}

// Determine person type
$personTypeId = 0;
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if ($bProcessPost)
	{
		$info = $CCrmInvoice::__GetCompanyAndContactFromPost($_POST);

		if ($info['COMPANY'] > 0)
			$personTypeId = $arPersonTypes['COMPANY'];
		elseif ($info['CONTACT'] > 0)
			$personTypeId = $arPersonTypes['CONTACT'];
		unset($info);
	}
	else
	{
		if (intval($arFields['UF_COMPANY_ID']) > 0) $personTypeId = $arPersonTypes['COMPANY'];
		elseif (intval($arFields['UF_CONTACT_ID']) > 0) $personTypeId = $arPersonTypes['CONTACT'];
	}
}

// Get invoice properties
$arInvoiceProperties = array();
if ($bEdit || $bCopy || $bProcessPost || $bCreateFromQuote || $bCreateFromDeal || $bCreateFromCompany || $bCreateFromContact || $conversionWizard !== null)
{
	$tmpArProps = $CCrmInvoice->GetProperties($arParams['ELEMENT_ID'], $personTypeId);
	if ($tmpArProps !== false)
	{
		$arInvoiceProperties = $tmpArProps;
		if ($bTaxMode && !isset($arFields['PR_LOCATION']) && isset($arInvoiceProperties['PR_LOCATION']))
			$arFields['PR_LOCATION'] = $arInvoiceProperties['PR_LOCATION']['VALUE'];
	}
	unset($tmpArProps);
}

$bVatMode = CCrmTax::isVatMode();

if (isset($arFields['~COMMENTS']))
{
	$arFields['COMMENTS'] = htmlspecialcharsbx($arFields['~COMMENTS']);
}
if (isset($arFields['~USER_DESCRIPTION']))
{
	$arFields['USER_DESCRIPTION'] = htmlspecialcharsbx($arFields['~USER_DESCRIPTION']);
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_INVOICE_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_INVOICE_LIST_V12';
$arResult['AJAX_SUBMIT_FUNCTION'] = ((isset($arResult['FORM_ID']) && !empty($arResult['FORM_ID'])) ? $arResult['FORM_ID'] : 'crm_invoice_form').'_ajax_submit';
$arResult['FORM_CUSTOM_HTML'] = '';

// status sort array for js
$arResult['STATUS_SORT'] = array();
$arInvoiceStatuses = CCrmStatusInvoice::GetStatus('INVOICE_STATUS');
foreach ($arInvoiceStatuses as $statusId => $statusInfo)
{
	$arResult['STATUS_SORT'][$statusId] = $statusInfo['SORT'];
}
unset($arInvoiceStatuses);

$arResult['PAY_SYSTEMS_LIST_ID'] = $paySystemFieldId = 'PAY_SYSTEM_ID';

$productDataFieldName = $arResult["productDataFieldName"] = 'INVOICE_PRODUCT_DATA';

$arResult['INVOICE_REFERER'] = '';
if ($bProcessPost && !$bAjaxSubmit && !empty($_POST['INVOICE_REFERER']))
{
	$arResult['INVOICE_REFERER'] = strval($_POST['INVOICE_REFERER']);
}
else if ($bCreateFrom && !empty($GLOBALS['_SERVER']['HTTP_REFERER']))
{
	$arResult['INVOICE_REFERER'] = strval($_SERVER['HTTP_REFERER']);
}
if ($bCreateFrom && !empty($arResult['INVOICE_REFERER']))
{
	$arResult['FORM_CUSTOM_HTML'] =
		'<input type="hidden" name="INVOICE_REFERER" value="'.htmlspecialcharsbx($arResult['INVOICE_REFERER']).'" />'.
		PHP_EOL.$arResult['FORM_CUSTOM_HTML'];
}

if($bConvert)
{
	$bVarsFromForm = true;
}
else
{
	if ($bProcessPost)
	{
		$bVarsFromForm = true;
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && (isset($_POST['save']) || isset($_POST['continue']) || $bAjaxSubmit) && $arResult["IS_EDIT_PERMITTED"])
		{
			//Check entities access -->
			$quoteID = isset($_POST['UF_QUOTE_ID']) ? intval($_POST['UF_QUOTE_ID']) : null;
			if ($quoteID > 0 && !CCrmQuote::CheckReadPermission($quoteID))
			{
				$quoteID = null;
			}

			$dealID = isset($_POST['UF_DEAL_ID']) ? intval($_POST['UF_DEAL_ID']) : null;
			if ($dealID > 0 && !CCrmDeal::CheckReadPermission($dealID))
			{
				$dealID = null;
			}

			$info = CCrmInvoice::__GetCompanyAndContactFromPost($_POST);
			$companyID = $info['COMPANY'];
			if($companyID > 0 && !CCrmCompany::CheckReadPermission($companyID))
			{
				$companyID = 0;
			}

			$contactID = $info['CONTACT'];
			if($contactID > 0 && !CCrmContact::CheckReadPermission($contactID))
			{
				$contactID = 0;
			}
			unset($info);
			//<-- Check entities access

			$clientRequisiteId = isset($_POST['REQUISITE_ID']) ? (int)$_POST['REQUISITE_ID'] : 0;
			if ($clientRequisiteId < 0)
				$clientRequisiteId = 0;
			$clientBankDetailId = isset($_POST['BANK_DETAIL_ID']) ? (int)$_POST['BANK_DETAIL_ID'] : 0;
			if ($clientBankDetailId < 0)
				$clientBankDetailId = 0;
			if (($companyID > 0 || $contactID > 0) && $clientRequisiteId > 0)
			{
				$requisiteIdLinked = $clientRequisiteId;
				$bankDetailIdLinked = $clientBankDetailId;
			}
			else
			{
				$requisiteIdLinked = 0;
				$bankDetailIdLinked = 0;
			}

			$myCompanyId = 0;
			if(isset($_POST['UF_MYCOMPANY_ID']))
			{
				$myCompanyId = (int)$_POST['UF_MYCOMPANY_ID'];
				if ($myCompanyId < 0)
					$myCompanyId = 0;
				if($myCompanyId > 0 && CCrmCompany::CheckReadPermission($myCompanyId))
				{
					$mcRequisiteIdLinked = isset($_POST['MC_REQUISITE_ID']) ? max((int)$_POST['MC_REQUISITE_ID'], 0) : 0;
					$mcBankDetailIdLinked = isset($_POST['MC_BANK_DETAIL_ID']) ? max((int)$_POST['MC_BANK_DETAIL_ID'], 0) : 0;
				}
				else
				{
					$mcRequisiteIdLinked = 0;
					$mcBankDetailIdLinked = 0;
				}
			}
			elseif(isset($arResult['ELEMENT']['UF_MYCOMPANY_ID']))
			{
				$myCompanyId = (int)$arResult['ELEMENT']['UF_MYCOMPANY_ID'];
			}

			$comments = trim($_POST['COMMENTS']);
			$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);
			$userDescription = trim($_POST['USER_DESCRIPTION']);
			$userDescription = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($userDescription);

			$dateInsert = ConvertTimeStamp(time(), 'FULL', SITE_ID);
			if ($bEdit && isset($arResult['ELEMENT']['DATE_INSERT']))
				$dateInsert = $arResult['ELEMENT']['DATE_INSERT'];

			$arFields = array(
				'ORDER_TOPIC' => trim($_POST['ORDER_TOPIC']),
				'STATUS_ID' => trim($_POST['STATUS_ID']),
				'DATE_INSERT' => $dateInsert,
				'DATE_BILL' => isset($_POST['DATE_BILL']) ? trim($_POST['DATE_BILL']) : null,
				'PAY_VOUCHER_DATE' => isset($_POST['PAY_VOUCHER_DATE']) ? trim($_POST['PAY_VOUCHER_DATE']) : null,
				'DATE_PAY_BEFORE' => trim($_POST['DATE_PAY_BEFORE']),
				'RESPONSIBLE_ID' => intval($_POST['RESPONSIBLE_ID']),
				'COMMENTS' => $comments,
				'USER_DESCRIPTION' => $userDescription,
				'UF_COMPANY_ID' => $companyID,
				'UF_CONTACT_ID' => $contactID,
				'UF_MYCOMPANY_ID' => $myCompanyId
			);

			if ($quoteID !== null)
			{
				$arFields['UF_QUOTE_ID'] = $quoteID;
			}

			if ($dealID !== null)
			{
				$arFields['UF_DEAL_ID'] = $dealID;
			}

			unset($dateInsert);

			if ($bTaxMode)
			{
				$arFields['PR_LOCATION'] = $_POST['LOC_CITY'];
			}
			if ($bEdit)
				$arFields['ACCOUNT_NUMBER'] = trim($_POST['ACCOUNT_NUMBER']);
			$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($arFields['STATUS_ID']);
			if ($bStatusSuccess)
				$bStatusFailed = false;
			else
				$bStatusFailed = CCrmStatusInvoice::isStatusFailed($arFields['STATUS_ID']);
			if ($bStatusSuccess)
			{
				$arFields['PAY_VOUCHER_NUM'] = isset($_POST['PAY_VOUCHER_NUM'])? mb_substr(trim($_POST['PAY_VOUCHER_NUM']), 0, 20) : '';
				$arFields['DATE_MARKED'] = $statusParams['PAY_VOUCHER_DATE'] = isset($_POST['PAY_VOUCHER_DATE']) ? trim($_POST['PAY_VOUCHER_DATE']) : null;
				$arFields['REASON_MARKED'] = isset($_POST['REASON_MARKED_SUCCESS'])? mb_substr(trim($_POST['REASON_MARKED_SUCCESS']), 0, 255) : '';
			}
			elseif ($bStatusFailed)
			{
				$arFields['DATE_MARKED'] = isset($_REQUEST['DATE_MARKED']) ? trim($_POST['DATE_MARKED']) : null;
				$arFields['REASON_MARKED'] = isset($_REQUEST['REASON_MARKED'])? mb_substr(trim($_REQUEST['REASON_MARKED']), 0, 255) : '';
			}

			$processProductRows = array_key_exists($productDataFieldName, $_POST);
			$arProduct = array();
			if($processProductRows)
			{
				$arProduct = isset($_POST[$productDataFieldName]) ? ($_POST[$productDataFieldName]) : array();
			}

			// sort product rows
			/*$arSort = array();
			foreach ($arProduct as $row)
				$arSort[] = isset($row['SORT']) ? intval($row['SORT']) : 0;
			unset($row);
			array_multisort($arSort, SORT_ASC, SORT_NUMERIC, $arProduct);
			unset($arSort);*/

			$arProduct = CCrmInvoice::ProductRows2BasketItems($arProduct);
			$arResult['PRODUCT_ROWS'] = $arFields['PRODUCT_ROWS'] = $arProduct;

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

			// set person type field
			$arFields['PERSON_TYPE_ID'] = $personTypeId;

			// set pay system field
			$arFields['PAY_SYSTEM_ID'] = intval($_POST['PAY_SYSTEM_ID']);

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmInvoice::GetUserFieldEntityID(), $arFields, array('FORM' => $_POST));
			if($conversionWizard !== null)
			{
				$conversionWizard->prepareDataForSave(CCrmOwnerType::Invoice, $arFields);
			}

			// <editor-fold defaultstate="collapsed" desc="Process invoice properties ...">
			CCrmInvoice::__RewritePayerInfo($companyID, $contactID, $arInvoiceProperties);
			unset($companyId, $contactId);
			CCrmInvoice::rewritePropsFromRequisite($personTypeId, $requisiteIdLinked, $arInvoiceProperties);
			$formProps = array();
			if (isset($_POST['LOC_CITY']))
				$formProps['LOC_CITY'] = $_POST['LOC_CITY'];
			$tmpArInvoicePropertiesValues = $CCrmInvoice->ParsePropertiesValuesFromPost($personTypeId, $formProps, $arInvoiceProperties);
			if (isset($tmpArInvoicePropertiesValues['PROPS_VALUES']) && isset($tmpArInvoicePropertiesValues['PROPS_INDEXES']))
			{
				$arFields['INVOICE_PROPERTIES'] = $tmpArInvoicePropertiesValues['PROPS_VALUES'];
				foreach ($tmpArInvoicePropertiesValues['PROPS_INDEXES'] as $propertyName => $propertyIndex)
					if (!isset($arFields[$propertyName]))
						$arFields[$propertyName] = $tmpArInvoicePropertiesValues['PROPS_VALUES'][$propertyIndex];
			}
			unset($tmpArInvoicePropertiesValues);
			// </editor-fold>

			if (!$CCrmInvoice->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false, $bStatusSuccess, $bStatusFailed))
			{
				if (!empty($CCrmInvoice->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmInvoice->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

		/*	if ($bAjaxSubmit)
			{
				// recalculate Invoice
				$arFields['ID'] = $arResult['ELEMENT']['ID'];
				$arRecalculated = $CCrmInvoice->Recalculate($arFields);

				// product rows to remove
				$arRemoveItems = array();
				if (is_array($arProduct) && count($arProduct) > 0)
				{
					$arRemoveItems = array_keys($arProduct);
					if (is_array($arRecalculated['BASKET_ITEMS']))
					{
						$arKeptItems = array();
						foreach ($arRecalculated['BASKET_ITEMS'] as $row)
							$arKeptItems[] = intval($row['TABLE_ROW_ID']);
						$arRemoveItems = array_values(array_diff($arRemoveItems, $arKeptItems));
						unset($arKeptItems, $row);
					}
				}

				// response
				$arResponse = array(
					'REMOVE_ITEMS' => $arRemoveItems,
					'TAX_VALUE' => isset($arRecalculated['TAX_VALUE']) ? $arRecalculated['TAX_VALUE'] : 0.00,
					'PRICE' => isset($arRecalculated['PRICE']) ? $arRecalculated['PRICE'] : 0.00,
				);
				$totalDiscount = 0.0;
				foreach($arProduct as $row)
				{
					if (isset($row['DISCOUNT_PRICE']))
						$totalDiscount += $row['DISCOUNT_PRICE'] * $row['QUANTITY'];
				}
				unset($row);
				$totalSum = isset($arRecalculated['PRICE']) ? round(doubleval($arRecalculated['PRICE']), 2) : 1.0;
				$totalTax = isset($arRecalculated['TAX_VALUE']) ? round(doubleval($arRecalculated['TAX_VALUE']), 2) : 0.0;
				$totalBeforeTax = round($totalSum - $totalTax, 2);
				$totalBeforeDiscount = round($totalBeforeTax + $totalDiscount, 2);
				$arResponse['TOTALS'] = array(
					'TOTAL_SUM' => $totalSum,
					'TOTAL_TAX' => $totalTax,
					'TOTAL_BEFORE_TAX' => $totalBeforeTax,
					'TOTAL_BEFORE_DISCOUNT' => $totalBeforeDiscount,
					'TOTAL_DISCOUNT' => $totalDiscount
				);
				unset($arRemoveItems, $totalSum, $totalTax, $totalBeforeTax, $totalBeforeDiscount, $totalDiscount);

				$arResponseTaxList = array();
				if ($bVatMode)
				{
					// gather vat rates
					$arVatRates = array();
					if (is_array($arRecalculated['BASKET_ITEMS']))
					{
						$basketItems = &$arRecalculated['BASKET_ITEMS'];
						foreach ($basketItems as $row)
							$arVatRates[$row['TABLE_ROW_ID']] = $row['VAT_RATE'];
						unset($basketItems, $row);
					}
					if (count($arVatRates) > 0)
						$arResponse['VAT_RATES'] = $arVatRates;
					unset($arVatRates);

					// tax list
					$arResponseTaxList[] = array(
						'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'),
						'TAX_VALUE' => CCrmCurrency::MoneyToString(
							isset($arRecalculated['TAX_VALUE']) ? $arRecalculated['TAX_VALUE'] : 0.00, $currencyID
						)
					);
				}
				else
				{
					// gather tax values
					$arTaxList = array();
					if (is_array($arRecalculated['TAX_LIST']))
					{
						$arTaxes = &$arRecalculated['TAX_LIST'];
						foreach ($arTaxes as $row)
							$arTaxList[] = array(
								'IS_IN_PRICE' => $row['~IS_IN_PRICE'],
								'TAX_NAME' => $row['~NAME'],
								'IS_PERCENT' => $row['~IS_PERCENT'],
								'VALUE' => $row['~VALUE'],
								'VALUE_MONEY' => $row['VALUE_MONEY']
							);
						unset($arTaxes, $row);
					}
					if (count($arTaxList) > 0)
					{
						$arResponse['TAX_VALUE'] = 0.00;
						foreach ($arTaxList as $taxInfo)
						{
							$arResponseTaxList[] = array(
								'TAX_NAME' => sprintf(
									"%s%s%s",
									($taxInfo["IS_IN_PRICE"] == "Y") ? GetMessage('CRM_PRODUCT_TAX_INCLUDING')." " : "",
									$taxInfo["TAX_NAME"],
									($taxInfo["IS_PERCENT"] == "Y")
										? sprintf(' (%s%%)', roundEx($taxInfo["VALUE"], SALE_VALUE_PRECISION))
										: ""
								),
								'TAX_VALUE' => CCrmCurrency::MoneyToString(
									$taxInfo['VALUE_MONEY'], $currencyID
								)
							);
							$arResponse['TAX_VALUE'] += round(doubleval($taxInfo['VALUE_MONEY']), 2);
						}
					}
					else
					{
						$arResponseTaxList[] = array(
							'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'),
							'TAX_VALUE' => CCrmCurrency::MoneyToString(0.0, $currencyID)
						);
					}
					unset($arTaxList);
				}
				$arResponse['TAX_LIST'] = $arResponseTaxList;
				$arResponse['VAT_MODE'] = $bVatMode;
				unset($arResponseTaxList);

				if ($personTypeId > 0)
				{
					// pay system
					$paySystemValue = intval($arFields['PAY_SYSTEM_ID']);
					$arPaySystemsListItems = CCrmPaySystem::GetPaySystemsListItems($personTypeId);
					$arPaySystemValues = array_keys($arPaySystemsListItems);
					if (!in_array($paySystemValue, $arPaySystemValues))
					{
						if (count($arPaySystemValues) === 0)
							$paySystemValue = 0;
						else
							$paySystemValue = $arPaySystemValues[0];
					}
					$arPaySystemsListData = array();
					foreach ($arPaySystemsListItems as $k => $v)
						$arPaySystemsListData[] = array('value' => $k, 'text' => $v);
					$arResponse['PAY_SYSTEMS_LIST'] = array(
						'items' => $arPaySystemsListData,
						'value' => $paySystemValue
					);
					unset($paySystemValue, $arPaySystemValues, $arPaySystemsListData, $arPaySystemsListItems);
				}

				$APPLICATION->RestartBuffer();
				echo \Bitrix\Main\Web\Json::encode($arResponse);
				CMain::FinalActions();
				die();
			}*/

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$bSuccess = false;
				if ($bEdit)
				{
					$bSuccess = $CCrmInvoice->Update($arResult['ELEMENT']['ID'], $arFields, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));
				}
				else
				{
					$recalculate = false;
					$ID = $CCrmInvoice->Add($arFields, $recalculate, SITE_ID, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));
					$bSuccess = (intval($ID) > 0) ? true : false;
					if($bSuccess)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if ($bSuccess)
				{
					if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
					{
						\Bitrix\Crm\Requisite\EntityLink::register(
							CCrmOwnerType::Invoice, $arResult['ELEMENT']['ID'],
							$requisiteIdLinked, $bankDetailIdLinked,
							$mcRequisiteIdLinked, $mcBankDetailIdLinked
						);
					}
					else
					{
						\Bitrix\Crm\Requisite\EntityLink::unregister(CCrmOwnerType::Invoice, $arResult['ELEMENT']['ID']);
					}
				}

				if ($bSuccess)
				{
					// Save settings
					if(is_array($productRowSettings) && count($productRowSettings) > 0)
					{
						$arSettings = CCrmProductRow::LoadSettings('I', $arResult['ELEMENT']['ID']);
						foreach ($productRowSettings as $k => $v)
							$arSettings[$k] = $v;
						CCrmProductRow::SaveSettings('I', $arResult['ELEMENT']['ID'], $arSettings);
					}
					unset($arSettings);
				}

				// link contact to company
				if($bSuccess)
				{
					if($arFields['UF_CONTACT_ID'] > 0 && $arFields['UF_COMPANY_ID'] > 0)
					{
						$CrmContact = new CCrmContact();
						$dbRes = CCrmContact::GetListEx(
							array(),
							array('ID' => $arFields['UF_CONTACT_ID']),
							false,
							false,
							array('COMPANY_ID')
						);
						$arContactInfo = $dbRes->Fetch();
						if ($arContactInfo && intval($arContactInfo['COMPANY_ID']) <= 0)
						{
							$arContactFields = array(
								'COMPANY_ID' => $arFields['UF_COMPANY_ID']
							);

							$bSuccess = $CrmContact->Update(
								$arFields['UF_CONTACT_ID'],
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
						unset($arContactInfo, $dbRes, $CrmContact);
					}
				}

				if($bSuccess)
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();

					$errCode = 0;
					$errMsg = '';
					$ex = $APPLICATION->GetException();
					if ($ex)
					{
						$errCode = $ex->GetID();
						$APPLICATION->ResetException();
						if (!empty($errCode))
							$errMsg = GetMessage('CRM_ERR_SAVE_INVOICE_'.$errCode);
						if ($errMsg == '')
							$errMsg = $ex->GetString();
					}
					$arResult['ERROR_MESSAGE'] = (!empty($errMsg) ? $errMsg : GetMessage('UNKNOWN_ERROR'))."<br />\n";
					unset($errCode, $errMsg);
				}
			}

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
				$conversionWizard->execute(array(CCrmOwnerType::InvoiceName => $ID));
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

$bStatusSuccess = false;
$bStatusFailed = false;
if (isset($arResult['ELEMENT']['STATUS_ID']) && $arResult['ELEMENT']['STATUS_ID'] !== '')
{
	$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($arResult['ELEMENT']['STATUS_ID']);
	if ($bStatusSuccess)
		$bStatusFailed = false;
	else
		$bStatusFailed = CCrmStatusInvoice::isStatusFailed($arResult['ELEMENT']['STATUS_ID']);
}

if($conversionWizard !== null && $conversionWizard->hasOriginUrl())
{
	$arResult['BACK_URL'] = $conversionWizard->getOriginUrl();
}
else
{
	$arResult['BACK_URL'] = !empty($arResult['INVOICE_REFERER']) ? $arResult['INVOICE_REFERER'] : $arParams['PATH_TO_INVOICE_LIST'];
}

$arResult['INVOICE_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['INVOICE_EDIT_URL_TEMPLATE'],
	array('invoice_id' => $entityID)
);

$arResult['STATUS_LIST'] = array();
$arResult['~STATUS_LIST'] = CCrmStatus::GetStatusList('INVOICE_STATUS');
foreach ($arResult['~STATUS_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmInvoice->cPerms->GetPermType('INVOICE', $bEdit ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

//$arResult['EVENT_LIST'] = CCrmStatus::GetStatusList('EVENT_TYPE');
$arResult['EDIT'] = $bEdit;

$arResult["pageId"] = "pageId_".($arResult['ELEMENT']["ID"] ? $arResult['ELEMENT']["ID"] : 0);

/*============= fields for main.interface.form =========*/
$arResult['FIELDS'] = array();

$arResult['FIELDS'][] = array(
	'id' => 'ACCOUNT_NUMBER',
	'name' => GetMessage('CRM_FIELD_ACCOUNT_NUMBER'),
	'params' => array('size' => 100),
	'value' => isset($arResult['ELEMENT']['~ACCOUNT_NUMBER']) ? $arResult['ELEMENT']['~ACCOUNT_NUMBER'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'required' => $bEdit,
	'visible' => $bEdit
);

$arResult['FIELDS'][] = array(
	'id' => 'ORDER_TOPIC',
	'name' => GetMessage('CRM_FIELD_ORDER_TOPIC'),
	'params' => array('size' => 255),
	'value' => isset($arResult['ELEMENT']['~ORDER_TOPIC']) ? $arResult['ELEMENT']['~ORDER_TOPIC'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
	'required' => true
);

$CCrmInvoice = new CCrmInvoice();
$arResult['STATUS_LIST'] = array();
$arResult['~STATUS_LIST'] = CCrmStatus::GetStatusList('INVOICE_STATUS');
foreach ($arResult['~STATUS_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmInvoice->cPerms->GetPermType('INVOICE', $bEdit ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}

// status sort array for js
$arResult['STATUS_SORT'] = array();
$arInvoiceStatuses = CCrmStatusInvoice::GetStatus('INVOICE_STATUS');
foreach ($arInvoiceStatuses as $statusId => $statusInfo)
{
	$arResult['STATUS_SORT'][$statusId] = $statusInfo['SORT'];
}
unset($arInvoiceStatuses);

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['STATUS_ID']) ? $arResult['ELEMENT']['STATUS_ID'] : '');
else
	$value = (isset($arResult['ELEMENT']['STATUS_ID']) ? $arResult['STATUS_LIST'][$arResult['ELEMENT']['STATUS_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_FIELD_STATUS_ID'),
	'items' => $arResult['STATUS_LIST'],
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'value' => $value,
	'required' => true
);

// status dependent fields
$arResult['FIELDS'][] = array(
	'id' => 'PAY_VOUCHER_DATE',
	'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_DATE'),
	'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'value' => !empty($arResult['ELEMENT']['PAY_VOUCHER_DATE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['PAY_VOUCHER_DATE']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
);

$arResult['FIELDS'][] = array(
	'id' => 'PAY_VOUCHER_NUM',
	'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_NUM'),
	'params' => array('size' => 20),
	'value' => isset($arResult['ELEMENT']['~PAY_VOUCHER_NUM']) ? $arResult['ELEMENT']['~PAY_VOUCHER_NUM'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label'
);

$arResult['ELEMENT']['REASON_MARKED_SUCCESS'] = $arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] = '';
if ($arResult['ELEMENT']['~STATUS_ID'] != '' && CCrmStatusInvoice::isStatusSuccess($arResult['ELEMENT']['~STATUS_ID']))
{
	$arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] = $arResult['ELEMENT']['~REASON_MARKED'];
	$arResult['ELEMENT']['REASON_MARKED_SUCCESS'] = htmlspecialcharsbx($arResult['ELEMENT']['~REASON_MARKED']);
}
$arResult['FIELDS'][] = array(
	'id' => 'REASON_MARKED_SUCCESS',
	'name' => GetMessage('CRM_FIELD_REASON_MARKED_SUCCESS'),
	'value' => isset($arResult['ELEMENT']['~REASON_MARKED_SUCCESS']) ? $arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'textarea' : 'label'
);

$arResult['FIELDS'][] = array(
	'id' => 'DATE_MARKED',
	'name' => GetMessage('CRM_FIELD_DATE_MARKED'),
	'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
	'type' => 'date',
	'value' => !empty($arResult['ELEMENT']['DATE_MARKED']) && $bStatusFailed ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_MARKED']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
);

if ($arResult['ELEMENT']['~STATUS_ID'] != '' && !CCrmStatusInvoice::isStatusFailed($arResult['ELEMENT']['~STATUS_ID']))
	$arResult['ELEMENT']['REASON_MARKED'] = $arResult['ELEMENT']['~REASON_MARKED'] = '';
$arResult['FIELDS'][] = array(
	'id' => 'REASON_MARKED',
	'name' => GetMessage('CRM_FIELD_REASON_MARKED'),
	'value' => isset($arResult['ELEMENT']['~REASON_MARKED']) ? $arResult['ELEMENT']['~REASON_MARKED'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'textarea' : 'label'
);

$arResult['FIELDS'][] = array(
	'id' => 'DATE_BILL',
	'name' => GetMessage('CRM_FIELD_DATE_BILL'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'canDrop' => false,
	'value' => !empty($arResult['ELEMENT']['DATE_BILL']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_BILL']), 'SHORT', SITE_ID) : ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT', SITE_ID)
);

$arResult['FIELDS'][] = array(
	'id' => 'DATE_PAY_BEFORE',
	'name' => GetMessage('CRM_FIELD_DATE_PAY_BEFORE'),
	'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'value' => !empty($arResult['ELEMENT']['DATE_PAY_BEFORE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_PAY_BEFORE']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
);

$arResult['FIELDS'][] = array(
	'id' => 'RESPONSIBLE_ID',
	'name' => GetMessage('CRM_FIELD_RESPONSIBLE_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user',
	'canDrop' => false,
	"item" => CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['RESPONSIBLE_ID']) ? $arResult['ELEMENT']['RESPONSIBLE_ID'] : $USER->GetID()),
	'value' => isset($arResult['ELEMENT']['RESPONSIBLE_ID']) ? $arResult['ELEMENT']['RESPONSIBLE_ID'] : $USER->GetID()
);

$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['FIELDS'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => htmlspecialcharsbx(isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID)
);

// DEAL LINK
if (CCrmDeal::CheckReadPermission())
{
	$arResult['ELEMENT_DEAL'] = "";
	if ($arResult['ELEMENT']['UF_DEAL_ID'])
	{
		$dealShowUrl = CComponentEngine::MakePathFromTemplate($arParams['DEAL_SHOW_URL_TEMPLATE'],
			array('deal_id' => $arResult['ELEMENT']['UF_DEAL_ID'])
		);

		$obRes = CCrmDeal::GetListEx(
			array(),
			array('=ID'=> $arResult['ELEMENT']['UF_DEAL_ID']),
			false,
			false,
			array('TITLE')
		);
		if($arDeal = $obRes->Fetch())
		{
			$arResult['ELEMENT_DEAL'] = array(
				"id" => $arResult['ELEMENT']['UF_DEAL_ID'],
				"name" => htmlspecialcharsbx($arDeal["TITLE"]),
				"image" => false,
				"entityType" => "deal",
				"url" => $dealShowUrl
			);
		}
	}

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['UF_DEAL_ID'])
	{
		$arResult["ON_SELECT_DEAL_EVENT_NAME"] = "onCrmDealSelectForInvoice_".$arParams['ELEMENT_ID'];

		$dealPath = CHTTP::urlAddParams($arParams['DEAL_SELECTOR_URL_TEMPLATE'], array(
			"event" => $arResult["ON_SELECT_DEAL_EVENT_NAME"]
		));

		$arResult['FIELDS'][] = array(
			'id' => 'UF_DEAL_ID',
			'name' => GetMessage('CRM_FIELD_UF_DEAL_ID'),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-invoice-edit-deal" data-role="mobile-crm-invoice-edit-deal">'.
							//Deal's html is generated on javascript, object BX.Mobile.Crm.EntityEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($dealPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}

// QUOTE LINK
if (CCrmQuote::CheckReadPermission())
{
	$arResult['ELEMENT_QUOTE'] = "";
	if ($arResult['ELEMENT']['UF_QUOTE_ID'])
	{
		$quoteShowUrl = CComponentEngine::MakePathFromTemplate($arParams['QUOTE_SHOW_URL_TEMPLATE'],
			array('quote_id' => $arResult['ELEMENT']['UF_QUOTE_ID'])
		);

		$obRes = CCrmQuote::GetList(
			array(), array('=ID'=> $arResult['ELEMENT']['UF_QUOTE_ID']), false, false,
			array('TITLE')
		);
		if($arQuote = $obRes->Fetch())
		{
			$arResult['ELEMENT_QUOTE'] = array(
				"id" => $arResult['ELEMENT']['UF_QUOTE_ID'],
				"name" => htmlspecialcharsbx($arQuote["TITLE"]),
				"image" => false,
				"entityType" => "quote",
				"url" => $quoteShowUrl
			);
		}
	}

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['UF_QUOTE_ID'])
	{
		$arResult["ON_SELECT_QUOTE_EVENT_NAME"] = "onCrmQuoteSelectForInvoice_".$arParams['ELEMENT_ID'];

		$quotePath = CHTTP::urlAddParams($arParams['QUOTE_SELECTOR_URL_TEMPLATE'], array(
			"event" => $arResult["ON_SELECT_QUOTE_EVENT_NAME"]
		));

		$arResult['FIELDS'][] = array(
			'id' => 'UF_QUOTE_ID',
			'name' => GetMessage('CRM_FIELD_UF_QUOTE_ID'),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-invoice-edit-quote" data-role="mobile-crm-invoice-edit-quote">'.
							//Quote's html is generated on javascript, object BX.Mobile.Crm.EntityEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($quotePath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}

// CLIENT
if (CCrmContact::CheckReadPermission() && CCrmCompany::CheckReadPermission())
{
	$arResult["ON_DELETE_CLIENT_EVENT_NAME"] = "onCrmDeleteClientForInvoice_".$arParams['ELEMENT_ID'];

	$arResult['ELEMENT_CLIENT'] = "";
	$arResult['ELEMENT_CLIENT_PREFIX'] = "";
	$arResult['ELEMENT_CLIENT_TYPE'] = "";

	if (intval($arResult['ELEMENT']['UF_COMPANY_ID']) > 0)
	{
		$companyShowUrl = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_SHOW_URL_TEMPLATE'],
			array('company_id' => $arResult['ELEMENT']['UF_COMPANY_ID'])
		);

		$obRes = CCrmCompany::GetList(
			array(), array('=ID'=> $arResult['ELEMENT']['UF_COMPANY_ID']), array('TITLE', 'LOGO')
		);
		if($arCompany = $obRes->Fetch())
		{
			$photo = isset($arCompany["LOGO"]) ? $arCompany["LOGO"] : false;
			if($photo > 0)
			{
				$listImageInfo = CFile::ResizeImageGet(
					$photo, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
				$photo = $listImageInfo["src"];
			}

			$arResult['ELEMENT_CLIENT'] = array(
				"id" => $arResult['ELEMENT']['UF_COMPANY_ID'],
				"name" => htmlspecialcharsbx($arCompany["TITLE"]),
				"image" => $photo,
				"entityType" => "company",
				"url" => $companyShowUrl
			);

			$arResult['ELEMENT_CLIENT']["multi"] = CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['UF_COMPANY_ID'], CCrmOwnerType::CompanyName);

			$arResult['ELEMENT_CLIENT_PREFIX']  = "CO_";
			$arResult['ELEMENT_CLIENT_TYPE'] = "COMPANY";

			//for send email
			$emailOwnerTypeName = CCrmOwnerType::CompanyName;
			$emailOwnerID = $arResult['ELEMENT']['UF_COMPANY_ID'];
			if($dealID > 0)
			{
				$emailOwnerTypeName = CCrmOwnerType::DealName;
				$emailOwnerID = $dealID;
			}

			$arResult['COMPANY_EMAIL_EDIT_URL'] = CCrmUrlUtil::AddUrlParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
					array('owner_type' => $emailOwnerTypeName, 'owner_id' => $emailOwnerID, 'type_id' => CCrmActivityType::Email)
				),
				array('comm[]' => mb_strtolower(CCrmOwnerType::CompanyName).'_'.$arResult['ELEMENT']['UF_COMPANY_ID'])
			);
		}
	}
	else if (intval($arResult['ELEMENT']['UF_CONTACT_ID']) > 0)
	{
		$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_SHOW_URL_TEMPLATE'],
			array('contact_id' => $arResult['ELEMENT']['UF_CONTACT_ID'])
		);

		$obRes = CCrmContact::GetListEx(
			array(),
			array('=ID'=> $arResult['ELEMENT']['UF_CONTACT_ID']),
			false,
			false,
			array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID')
		);
		if($arContact = $obRes->Fetch())
		{
			$contact["FULL_NAME"] = CUser::FormatName(
				CSite::GetNameFormat(false),
				array(
					'LOGIN' => isset($arContact['LOGIN']) ? $arContact['LOGIN'] : '',
					'NAME' => isset($arContact['NAME']) ? $arContact['NAME'] : '',
					'LAST_NAME' => isset($arContact['LAST_NAME']) ? $arContact['LAST_NAME'] : '',
					'SECOND_NAME' => isset($arContact['SECOND_NAME']) ? $arContact['SECOND_NAME'] : ''
				),
				true, false
			);

			$photo = isset($arContact["PHOTO"]) ? $arContact["PHOTO"] : false;
			if($photo > 0)
			{
				$listImageInfo = CFile::ResizeImageGet(
					$photo, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
				$photo = $listImageInfo["src"];
			}

			$arResult['ELEMENT_CLIENT'] = array(
				"id" => $arResult['ELEMENT']['UF_CONTACT_ID'],
				"name" => htmlspecialcharsbx($contact["FULL_NAME"]),
				"image" => $photo,
				"entityType" => "contact",
				"url" => $contactShowUrl
			);

			$arResult['ELEMENT_CLIENT']["multi"] = CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['UF_CONTACT_ID'], CCrmOwnerType::ContactName);

			$arResult['ELEMENT_CLIENT_PREFIX']  = "C_";
			$arResult['ELEMENT_CLIENT_TYPE'] = "CONTACT";

			//for send email
			$emailOwnerTypeName = CCrmOwnerType::ContactName;
			$emailOwnerID = $arResult['ELEMENT']['UF_CONTACT_ID'];
			if($dealID > 0)
			{
				$emailOwnerTypeName = CCrmOwnerType::DealName;
				$emailOwnerID = $dealID;
			}

			$arResult['CONTACT_EMAIL_EDIT_URL'] = CCrmUrlUtil::AddUrlParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
					array('owner_type' => $emailOwnerTypeName, 'owner_id' => $emailOwnerID, 'type_id' => CCrmActivityType::Email)
				),
				array('comm[]' => mb_strtolower(CCrmOwnerType::ContactName).'_'.$arResult['ELEMENT']['UF_CONTACT_ID'])
			);
		}
	}

	$arResult["ON_SELECT_CLIENT_EVENT_NAME"] = "onCrmClientSelectForInvoice_".$arParams['ELEMENT_ID'];

	$clientContactPath = CHTTP::urlAddParams($arParams['CLIENT_CONTACT_SELECTOR_URL_TEMPLATE'], array(
		"event" => $arResult["ON_SELECT_CLIENT_EVENT_NAME"]
	));
	$clientCompanyPath = CHTTP::urlAddParams($arParams['CLIENT_COMPANY_SELECTOR_URL_TEMPLATE'], array(
		"event" => $arResult["ON_SELECT_CLIENT_EVENT_NAME"]
	));

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['UF_COMPANY_ID'] || $arResult['ELEMENT']['UF_CONTACT_ID'])
	{
		$arResult['FIELDS'][] = array(
			'id' => 'CLIENT_ID',
			'name' => GetMessage('CRM_FIELD_CLIENT_ID'),
			'type' => 'custom',
			'required' => true,
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-invoice-edit-client" data-role="mobile-crm-invoice-edit-client">'.
							//client's html is generated on javascript, object BX.Mobile.Crm.EntityEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' :
							'<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($clientContactPath).'\')">'.GetMessage("CRM_BUTTON_SELECT_CONTACT").'</a>'
							.'<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($clientCompanyPath).'\')">'.GetMessage("CRM_BUTTON_SELECT_COMPANY").'</a>'
							)
						.'</div>'
						. ($arParams["RESTRICTED_MODE"]
						? '<input type="hidden" name="PRIMARY_ENTITY_TYPE" value="'.$arResult['ELEMENT_CLIENT_TYPE'].'">
							<input type="hidden" name="PRIMARY_ENTITY_ID" value="'.$arResult['ELEMENT_CLIENT']['id'].'">'
						: "")
		);
	}
}

// CONTACT PERSON
if (CCrmContact::CheckReadPermission())
{
	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['UF_CONTACT_ID'] && $arResult['ELEMENT']['UF_COMPANY_ID'])
	{
		$arResult['ELEMENT_CONTACT'] = "";
		if ($arResult['ELEMENT']['UF_CONTACT_ID'])
		{
			$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_SHOW_URL_TEMPLATE'],
				array('contact_id' => $arResult['ELEMENT']['UF_CONTACT_ID'])
			);

			$obRes = CCrmContact::GetListEx(
				array(),
				array('=ID' => $arResult['ELEMENT']['UF_CONTACT_ID']),
				false,
				false,
				array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID')
			);
			if ($arContact = $obRes->Fetch())
			{
				$contact["FULL_NAME"] = CUser::FormatName(
					CSite::GetNameFormat(false),
					array(
						'LOGIN' => isset($arContact['LOGIN']) ? $arContact['LOGIN'] : '',
						'NAME' => isset($arContact['NAME']) ? $arContact['NAME'] : '',
						'LAST_NAME' => isset($arContact['LAST_NAME']) ? $arContact['LAST_NAME'] : '',
						'SECOND_NAME' => isset($arContact['SECOND_NAME']) ? $arContact['SECOND_NAME'] : ''
					),
					true, false
				);

				$photo = isset($arContact["PHOTO"]) ? $arContact["PHOTO"] : false;
				if ($photo > 0)
				{
					$listImageInfo = CFile::ResizeImageGet(
						$photo, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL);
					$photo = $listImageInfo["src"];
				}

				$arResult['ELEMENT_CONTACT'] = array(
					"id" => $arResult['ELEMENT']['UF_CONTACT_ID'],
					"name" => htmlspecialcharsbx($contact["FULL_NAME"]),
					"image" => $photo,
					"entityType" => "contact",
					"url" => $contactShowUrl,
					"multi" => CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['UF_CONTACT_ID'], CCrmOwnerType::ContactName)
				);
			}
		}

		if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['UF_CONTACT_ID'])
		{
			$arResult["ON_SELECT_CONTACT_EVENT_NAME"] = "onCrmContactSelectForInvoice_" . $arParams['ELEMENT_ID'];

			$contactPath = CHTTP::urlAddParams($arParams['CONTACT_SELECTOR_URL_TEMPLATE'], array(
				"event" => $arResult["ON_SELECT_CONTACT_EVENT_NAME"]
			));

			$arResult['FIELDS'][] = array(
				'id' => 'UF_CONTACT_ID',
				'name' => GetMessage('CRM_FIELD_CONTACT_PERSON_ID'),
				'type' => 'custom',
				'value' => '<div class="mobile-grid-field-select-user">
								<div id="mobile-crm-invoice-edit-contact" data-role="mobile-crm-invoice-edit-contact">' .
								//contact's html is generated on javascript, object BX.Mobile.Crm.EntityEditor
								'</div>'
								. ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\'' . CUtil::JSEscape($contactPath) . '\')">' . GetMessage("CRM_BUTTON_SELECT") . '</a>') .
							'</div>'
			);
		}
	}
}

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
			'ONCITYCHANGE' => 'CrmProductRowSetLocation',
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

// pay system
$arResult['PAY_SYSTEM_ID_TITLE'] = GetMessage('CRM_FIELD_PAY_SYSTEM_ID_TITLE');
$paySystemFieldId = 'PAY_SYSTEM_ID';

// Determine person type
$personTypeId = 0;
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if (intval($arResult['ELEMENT']['UF_COMPANY_ID']) > 0)
		$personTypeId = $arPersonTypes['COMPANY'];
	elseif (intval($arResult['ELEMENT']['UF_CONTACT_ID']) > 0)
		$personTypeId = $arPersonTypes['CONTACT'];
}

$paySystems = CCrmPaySystem::GetPaySystemsListItems($personTypeId);
if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['PAY_SYSTEM_ID']) ? $arResult['ELEMENT']['PAY_SYSTEM_ID'] : '');
else
	$value = (isset($arResult['ELEMENT']['PAY_SYSTEM_ID']) ? $paySystems[$arResult['ELEMENT']['PAY_SYSTEM_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => $paySystemFieldId,
	'name' => GetMessage('CRM_FIELD_PAY_SYSTEM_ID'),
	'items' => $paySystems,
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'value' => $value,
	'required' => true
);

// COMMENTS
if ($arResult["IS_EDIT_PERMITTED"])
	$fieldType = $arParams['RESTRICTED_MODE'] ? 'custom' : 'textarea';
else
	$fieldType = 'label';


$value = "";
if (isset($arResult['ELEMENT']['~COMMENTS']))
{
	$value = ($fieldType == "textarea")
		? htmlspecialcharsback($arResult['ELEMENT']['~COMMENTS'])
		: htmlspecialcharsbx($arResult['ELEMENT']['~COMMENTS'], ENT_COMPAT, false);
}

$arResult['FIELDS'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'required' => false,
	'params' => array(),
	'type' => $fieldType,
	'value' => $value
);

$value = "";
if (isset($arResult['ELEMENT']['~USER_DESCRIPTION']))
{
	$value = ($fieldType == "textarea")
		? htmlspecialcharsback($arResult['ELEMENT']['~USER_DESCRIPTION'])
		: htmlspecialcharsbx($arResult['ELEMENT']['~USER_DESCRIPTION'], ENT_COMPAT, false);
}
$arResult['FIELDS'][] = array(
	'id' => 'USER_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_USER_DESCRIPTION'),
	'required' => false,
	'params' => array(),
	'type' => $fieldType,
	'value' => $value
);

// Product rows
$arResult["PAGEID_PRODUCT_SELECTOR_BACK"] = "crmInvoiceEditPage";
$arResult["ON_PRODUCT_SELECT_EVENT_NAME"] = "onCrmSelectProductForInvoice_".$arParams['ELEMENT_ID'];
$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] = CHTTP::urlAddParams($arParams['PRODUCT_SELECTOR_URL_TEMPLATE'], array(
	"event" => $arResult["ON_PRODUCT_SELECT_EVENT_NAME"],
	"pageIdProductSelectorBack" => $arResult["PAGEID_PRODUCT_SELECTOR_BACK"]
));

$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'invoice_'.strval($arParams['ELEMENT_ID']) : 'new_invoice').'_product_editor';
$sProductsHtml = '';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => 'I',
	'PERMISSION_TYPE' => $isExternal || $arParams['RESTRICTED_MODE'] ? 'READ' : 'WRITE',
	'INIT_EDITABLE' => 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['PR_LOCATION'] : '',
	'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
	'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName,
	'TOTAL_SUM' => isset($arResult['ELEMENT']['~PRICE']) ? $arResult['ELEMENT']['~PRICE'] : null,
	'TOTAL_TAX' => isset($arResult['ELEMENT']['~TAX_VALUE']) ? $arResult['ELEMENT']['~TAX_VALUE'] : null,
	'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],
	'COPY_FLAG' => ($bCopy || $bCreateFromQuote || $bCreateFromDeal || $conversionWizard !== null) ? 'Y' : 'N',
	'SEND_PRODUCTS_IN_RESTRICTED_MODE' => $arParams['RESTRICTED_MODE'] ? true : false,

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
		'value' => $sProductsHtml,
		'required' => true
	);
}

if($conversionWizard !== null)
{
	$useUserFieldsFromForm = true;
	$fileViewer = new \Bitrix\Crm\Conversion\EntityConversionFileViewer(
		CCrmOwnerType::Invoice,
		$conversionWizard->getEntityTypeID(),
		$conversionWizard->getEntityID()
	);
}
else
{
	$useUserFieldsFromForm = $bConvert ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $bVarsFromForm;
	$fileViewer = new \Bitrix\Crm\UserField\FileViewer(CCrmOwnerType::Invoice, $arResult['ELEMENT']['ID']);
}

//user fields
$CCrmUserType = new CCrmMobileHelper();
$CCrmUserType->prepareUserFields(
	$arResult['FIELDS'],
	CCrmInvoice::$sUFEntityID,
	$arResult['ELEMENT']['ID'],
	($conversionWizard !== null),
	'invoice_details'
);

if ($bCopy)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

if ($arParams['RESTRICTED_MODE'])
{
	$accountNumber = isset($arResult['ELEMENT']['ACCOUNT_NUMBER']) ? $arResult['ELEMENT']['ACCOUNT_NUMBER'] : '';
	if($accountNumber === '')
	{
		$accountNumber = $arResult["ELEMENT_ID"];
	}

	$arResult['EMAIL_SUBJECT'] = GetMessage(
		'CRM_INVOICE_VIEW_EMAIL_SUBJECT', array('#NUMBER#' => $accountNumber)
	);

	$arResult['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Invoice, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';

	$arResult['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['EVENT_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Invoice, 'entity_id' => $arResult['ELEMENT_ID'])
		) : '';
}

$this->IncludeComponentTemplate();
?>
