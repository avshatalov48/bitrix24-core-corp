<?php
use Bitrix\Crm\Recurring;

/** @global CMain $APPLICATION */
global $APPLICATION;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
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
if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE')
	&& $CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_INVOICE_LIST'] = CrmCheckPath('PATH_TO_INVOICE_LIST', $arParams['PATH_TO_INVOICE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_INVOICE_SHOW'] = CrmCheckPath('PATH_TO_INVOICE_SHOW', $arParams['PATH_TO_INVOICE_SHOW'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&show');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
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
$arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];
if (!empty($arParams['ELEMENT_ID']))
	$bEdit = true;
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}

if ($arParams['IS_RECURRING'] !== 'Y' && $_REQUEST['RECUR_PARAM']['RECURRING_SWITCHER'] == 'Y')
	$bEdit = false;

$bCreateFromQuote = $bCreateFromDeal = $bCreateFromCompany = $bCreateFromContact = false;
$quoteId = $dealId = $companyId = $contactId = 0;
$arQuoteClientFields = array();

if (isset($_REQUEST['quote']) && $_REQUEST['quote'] > 0)
{
	$bCreateFromQuote = true;
	$quoteId = (int)$_REQUEST['quote'];
}
elseif (isset($_REQUEST['quote_id']) && $_REQUEST['quote_id'] > 0)
{
	$bCreateFromQuote = true;
	$quoteId = (int)$_REQUEST['quote_id'];
}

if (isset($_REQUEST['deal']) && $_REQUEST['deal'] > 0)
{
	$bCreateFromDeal = true;
	$dealId = (int)$_REQUEST['deal'];
}
elseif (isset($_REQUEST['deal_id']) && $_REQUEST['deal_id'] > 0)
{
	$bCreateFromDeal = true;
	$dealId = (int)$_REQUEST['deal_id'];
}

if (isset($_REQUEST['company']) && $_REQUEST['company'] > 0)
{
	$bCreateFromCompany = true;
	$companyId = (int)($_REQUEST['company']);
}
elseif (isset($_REQUEST['company_id']) && $_REQUEST['company_id'] > 0)
{
	$bCreateFromCompany = true;
	$companyId = (int)($_REQUEST['company_id']);
}

if (isset($_REQUEST['contact']) && $_REQUEST['contact'] > 0)
{
	$bCreateFromContact = true;
	$contactId = (int)$_REQUEST['contact'];
}
elseif (isset($_REQUEST['contact_id']) && $_REQUEST['contact_id'] > 0)
{
	$bCreateFromContact = true;
	$contactId = (int)$_REQUEST['contact_id'];
}

$arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];
$arResult['CALL_LIST_ELEMENT'] = (int)$_REQUEST['call_list_element'];
/** @var \Bitrix\Crm\Conversion\EntityConversionWizard */
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
	$arFields = array('ID' => 0);
	if($_SERVER['REQUEST_METHOD'] === 'GET')
	{
		$conversionWizard->prepareDataForEdit(CCrmOwnerType::Invoice, $arFields, true);
		if(isset($arFields['PRODUCT_ROWS']))
		{
			$arResult['PRODUCT_ROWS'] = $arFields['PRODUCT_ROWS'];
		}
	}
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend();
}
elseif ($bEdit || $bCopy)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmInvoice::GetList(array(), $arFilter);
	$arFields = $obFields->GetNext();
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

	if ($arParams['IS_RECURRING'] === "Y")
	{
		if ($arFields['IS_RECURRING'] !== "Y")
		{
			LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_INVOICE_RECUR'], array()));
		}
		elseif ($_REQUEST['expose'] === 'Y')
		{
			$recurringInstance = Bitrix\Crm\Recurring\Entity\Invoice::getInstance();
			$result = $recurringInstance->expose(["=INVOICE_ID" => $arParams['ELEMENT_ID']], 1, false);
			if ($result->isSuccess())
			{
				$exposeData = $result->getData();
				LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_INVOICE_SHOW'], array('invoice_id' => $exposeData['ID'][0])));
			}
			else
			{
				LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_INVOICE_RECUR'], array()));
			}
		}

		$recurData = Bitrix\Crm\InvoiceRecurTable::getList(
			array(
				"filter" => array("=INVOICE_ID" => $arParams['ELEMENT_ID'])
			)
		);
		$arFields['RECURRING_DATA'] = $recurData->fetch();
	}
	elseif ($arFields['IS_RECURRING'] === "Y")
	{
		LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_INVOICE_LIST'], array()));
	}
}
else
{
	$arFields = array(
		'ID' => 0
	);

	$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
	if ($request->get("redirect") === "y" && !\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
	{
		$url = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
			\CCrmOwnerType::SmartInvoice, 0
		);
		if ($url)
		{
			$params = [];
			if ($request->get("contact"))
			{
				$params['contact_id'] = $request->get("contact");
			}
			if ($request->get('company'))
			{
				$params['company_id'] = $request->get('company');
			}
			if ($request->get('external_context'))
			{
				$params['external_context'] = $request->get('external_context');
			}
			if ($request->get('call_list_id'))
			{
				$params['call_list_id'] = $request->get('call_list_id');
			}
			if ($request->get('call_list_element'))
			{
				$params['call_list_element'] = $request->get('call_list_element');
			}
			$url->addParams($params);
			LocalRedirect($url->getLocator());
			return;
		}
	}

	if ($bCreateFromQuote)
	{
		$arFields['UF_QUOTE_ID'] = $quoteId;
		$arQuote = CCrmQuote::GetByID($quoteId);
		$arQuoteProducts = CCrmQuote::LoadProductRows($quoteId);
		if (is_array($arQuote) && count($arQuote) > 0)
		{
			if ($bTaxMode && isset($arQuote['LOCATION_ID']))
			{
				$locationValue = CSaleLocation::getLocationIDbyCODE($arQuote['LOCATION_ID']);
				$arFields['~PR_LOCATION'] = $locationValue;
				$arFields['PR_LOCATION'] = htmlspecialcharsbx($locationValue);
				unset($locationValue);
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
			if ($bTaxMode && isset($arDeal['LOCATION_ID']))
			{
				$locationValue = CSaleLocation::getLocationIDbyCODE($arDeal['LOCATION_ID']);
				$arFields['~PR_LOCATION'] = $locationValue;
				$arFields['PR_LOCATION'] = htmlspecialcharsbx($locationValue);
				unset($locationValue);
			}
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

if (!$bEdit && !$bCopy)
{
	if (!isset($arFields['UF_MYCOMPANY_ID']) || $arFields['UF_MYCOMPANY_ID'] <= 0)
	{
		$defLink = Bitrix\Crm\Requisite\EntityLink::getDefaultMyCompanyRequisiteLink();
		if (is_array($defLink))
		{
			$arFields['UF_MYCOMPANY_ID'] = isset($defLink['MYCOMPANY_ID']) ? (int)$defLink['MYCOMPANY_ID'] : 0;
			$mcRequisiteIdLinked = isset($defLink['MC_REQUISITE_ID']) ? (int)$defLink['MC_REQUISITE_ID'] : 0;
			$mcBankDetailIdLinked = isset($defLink['MC_BANK_DETAIL_ID']) ? (int)$defLink['MC_BANK_DETAIL_ID'] : 0;
		}
		unset($defLink);
	}
}

if (($bEdit && !$CCrmInvoice->cPerms->CheckEnityAccess('INVOICE', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]) ||
	(!$bEdit && $CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD'))))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$isExternal = $bEdit && isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']) && intval($arFields['ORIGINATOR_ID']) > 0 && intval($arFields['ORIGIN_ID']) > 0;

$bProcessPost = $_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid();

//region external context ID
$arResult['EXTERNAL_CONTEXT'] = isset($_REQUEST['external_context']) ? $_REQUEST['external_context'] : '';
//endregion


if ($bProcessPost)
{
	$bAjaxSubmit = (isset($_POST['invoiceSubmitAjax']) && $_POST['invoiceSubmitAjax'] === 'Y') ? true : false;
	$isPostSaveAction = (isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd'])
		|| isset($_POST['apply']) || isset($_POST['continue']) || $bAjaxSubmit);
}

// Determine company, contact and person type
$clientPostInfo = null;
$companyId = 0;
$contactId = 0;
$personTypeId = 0;
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if ($bProcessPost && $isPostSaveAction)
	{
		$clientPostInfo = $CCrmInvoice::__GetCompanyAndContactFromPost($_POST);
		$prevCompanyID = isset($arResult['ELEMENT']['UF_COMPANY_ID']) ?
			(int)$arResult['ELEMENT']['UF_COMPANY_ID'] : 0;
		if($clientPostInfo['COMPANY_ISSET'])
		{
			$companyId = $clientPostInfo['COMPANY'];
			if ($companyId > 0 && $companyId !== $prevCompanyID
				&& !CCrmCompany::CheckReadPermission($companyId))
			{
				$companyId = $prevCompanyID;
			}
		}
		else if (!$clientPostInfo['CONTACT_ISSET'])
		{
			$companyId = $prevCompanyID;
		}
		if ($companyId < 0)
		{
			$companyId = 0;
		}

		$prevContactID = isset($arResult['ELEMENT']['UF_CONTACT_ID']) ?
			(int)$arResult['ELEMENT']['UF_CONTACT_ID'] : 0;
		if($clientPostInfo['CONTACT_ISSET'])
		{
			$contactId = $clientPostInfo['CONTACT'];
			if ($contactId > 0 && $contactId !== $prevContactID
				&& !CCrmContact::CheckReadPermission($contactId))
			{
				$contactId = ($clientPostInfo['COMPANY_ISSET'] && $companyId !== $prevCompanyID) ?
					0 : $prevContactID;
			}
		}
		else
		{
			$contactId = ($clientPostInfo['COMPANY_ISSET'] && $companyId !== $prevCompanyID) ?
				0 : $prevContactID;
		}
		if ($contactId < 0)
		{
			$contactId = 0;
		}

		unset($clientPostInfo, $prevContactID, $prevCompanyID);
	}
	else
	{
		$companyId = (isset($arFields['UF_COMPANY_ID']) && $arFields['UF_COMPANY_ID'] > 0) ?
			$arFields['UF_COMPANY_ID'] : 0;
		$contactId = (isset($arFields['UF_CONTACT_ID']) && $arFields['UF_CONTACT_ID'] > 0) ?
			$arFields['UF_CONTACT_ID'] : 0;
	}

	if ($companyId > 0)
	{
		$personTypeId = $arPersonTypes['COMPANY'];
	}
	elseif ($contactId > 0)
	{
		$personTypeId = $arPersonTypes['CONTACT'];
	}

	if($personTypeId === 0)
	{
		$personTypeId = $arPersonTypes['CONTACT'];
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

$productDataFieldName = 'INVOICE_PRODUCT_DATA';

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
						'entityTypeName' => CCrmOwnerType::InvoiceName
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
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array())
				);
			}
		}
		elseif ($isPostSaveAction)
		{
			//Check entities access -->
			$prevQuoteID = isset($arResult['ELEMENT']['UF_QUOTE_ID']) ? (int)$arResult['ELEMENT']['UF_QUOTE_ID'] : 0;
			$quoteID = 0;
			if(isset($_POST['UF_QUOTE_ID']))
			{
				$quoteID = (int)$_POST['UF_QUOTE_ID'];
				if ($quoteID > 0 && $quoteID !== $prevQuoteID && !CCrmQuote::CheckReadPermission($quoteID))
				{
					$quoteID = $prevQuoteID;
				}
			}
			else
			{
				$quoteID = $prevQuoteID;
			}
			if ($quoteID < 0)
			{
				$quoteID = 0;
			}
			unset($prevQuoteID);

			$prevDealID = isset($arResult['ELEMENT']['UF_DEAL_ID']) ? (int)$arResult['ELEMENT']['UF_DEAL_ID'] : 0;
			$dealID = 0;
			if(isset($_POST['UF_DEAL_ID']))
			{
				$dealID = (int)$_POST['UF_DEAL_ID'];
				if ($dealID > 0 && $dealID !== $prevDealID && !CCrmDeal::CheckReadPermission($dealID))
				{
					$dealID = $prevDealID;
				}
			}
			else
			{
				$dealID = $prevDealID;
			}
			if ($dealID < 0)
			{
				$dealID = 0;
			}
			unset($prevDealID);
			//<-- Check entities access

			$clientRequisiteId = isset($_POST['REQUISITE_ID']) ? (int)$_POST['REQUISITE_ID'] : 0;
			if ($clientRequisiteId < 0)
				$clientRequisiteId = 0;
			$clientBankDetailId = isset($_POST['BANK_DETAIL_ID']) ? (int)$_POST['BANK_DETAIL_ID'] : 0;
			if ($clientBankDetailId < 0)
				$clientBankDetailId = 0;
			if (($companyId > 0 || $contactId > 0) && $clientRequisiteId > 0)
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
			$bSanitizeComments = ($comments !== '' && mb_strpos($comments, '<') !== false);
			$userDescription = trim($_POST['USER_DESCRIPTION']);
			$bSanitizeUserDescription = ($userDescription !== '' && mb_strpos($userDescription, '<') !== false);
			if($bSanitizeComments || $bSanitizeUserDescription)
			{
				$sanitizer = new CBXSanitizer();
				$sanitizer->ApplyDoubleEncode(false);
				$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
				//Crutch for for Chrome line break behaviour in HTML editor.
				$sanitizer->AddTags(array('div' => array()));
				$sanitizer->AddTags(array('a' => array('href', 'title', 'name', 'style', 'alt', 'target')));
				$sanitizer->AddTags(array('p' => array()));
				$sanitizer->AddTags(array('span' => array('style')));
				if ($bSanitizeComments)
					$comments = $sanitizer->SanitizeHtml($comments);
				if ($bSanitizeUserDescription)
					$userDescription = $sanitizer->SanitizeHtml($userDescription);
				unset($sanitizer);
			}
			unset($bSanitizeComments, $bSanitizeUserDescription);

			$statusId = trim($_POST['STATUS_ID']);
			if ($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] === 'Y' || $arParams['IS_RECURRING'] === 'Y')
			{
				$statusId = \Bitrix\Crm\Invoice\InvoiceStatus::getInitialStatus();
			}

			$arFields = array(
				'ORDER_TOPIC' => trim($_POST['ORDER_TOPIC']),
				'STATUS_ID' => $statusId,
				'DATE_BILL' => isset($_POST['DATE_BILL']) ? trim($_POST['DATE_BILL']) : null,
				'PAY_VOUCHER_DATE' => isset($_POST['PAY_VOUCHER_DATE']) ? trim($_POST['PAY_VOUCHER_DATE']) : null,
				'DATE_PAY_BEFORE' => trim($_POST['DATE_PAY_BEFORE']),
				'RESPONSIBLE_ID' => intval($_POST['RESPONSIBLE_ID']),
				'COMMENTS' => $comments,
				'USER_DESCRIPTION' => $userDescription,
				'UF_QUOTE_ID' => $quoteID,
				'UF_DEAL_ID' => $dealID,
				'UF_COMPANY_ID' => $companyId,
				'UF_CONTACT_ID' => $contactId,
				'UF_MYCOMPANY_ID' => $myCompanyId
			);

			if ($bEdit && isset($arResult['ELEMENT']['DATE_INSERT']))
				$arFields['DATE_INSERT'] = $arResult['ELEMENT']['DATE_INSERT'];

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
				$prodJson = isset($_POST[$productDataFieldName]) ? strval($_POST[$productDataFieldName]) : '';
				$arProduct = $prodJson <> '' ? CUtil::JsObjectToPhp($prodJson) : array();
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

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmInvoice::GetUserFieldEntityID(), $arFields);
			if($conversionWizard !== null)
			{
				$conversionWizard->prepareDataForSave(CCrmOwnerType::Invoice, $arFields);
			}

			// <editor-fold defaultstate="collapsed" desc="Process invoice properties ...">
			CCrmInvoice::__RewritePayerInfo($companyId, $contactId, $arInvoiceProperties);
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

			if ($bAjaxSubmit)
			{
				// recalculate Invoice
				$arFields['ID'] = $bEdit ? $arResult['ELEMENT']['ID'] : 0;
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
					'TOTAL_DISCOUNT' => $totalDiscount,
					'TOTAL_SUM_FORMATTED' => CCrmCurrency::MoneyToString($totalSum, $currencyID),
					'TOTAL_SUM_FORMATTED_SHORT' => CCrmCurrency::MoneyToString($totalSum, $currencyID, '#'),
					'TOTAL_TAX_FORMATTED' => CCrmCurrency::MoneyToString($totalTax, $currencyID),
					'TOTAL_BEFORE_TAX_FORMATTED' => CCrmCurrency::MoneyToString($totalBeforeTax, $currencyID),
					'TOTAL_DISCOUNT_FORMATTED' => CCrmCurrency::MoneyToString($totalDiscount, $currencyID),
					'TOTAL_BEFORE_DISCOUNT_FORMATTED' => CCrmCurrency::MoneyToString($totalBeforeDiscount, $currencyID)
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
									(/*$vat <= 0 &&*/ $taxInfo["IS_PERCENT"] == "Y")
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
							'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'/*($bVatMode) ? 'CRM_PRODUCT_VAT_VALUE' : 'CRM_PRODUCT_TAX_VALUE'*/),
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
					if (!array_key_exists($paySystemValue, $arPaySystemsListItems))
					{
						$paySystem = \Bitrix\Sale\PaySystem\Manager::getById($paySystemValue);
						if ($paySystem)
							$arPaySystemsListItems[$paySystem['ID']] = $paySystem['NAME'];
					}

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

				$GLOBALS['APPLICATION']->RestartBuffer();
				?>
				<script>
					var response = null;
					response = <?=CUtil::PhpToJSObject($arResponse)?>;
					top.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response')?> = response;
				</script>
				<?php
				CMain::FinalActions();
				exit;
			}

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$bSuccess = false;
				if ($bEdit)
				{
					$bSuccess = $CCrmInvoice->Update($arResult['ELEMENT']['ID'], $arFields, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));

					if (
							($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] === 'Y' || $arParams['IS_RECURRING'] === 'Y')
							&& Recurring\Manager::isAllowedExpose(Recurring\Manager::INVOICE)
					)
					{
						if ($_POST['RECUR_PARAM']['START_DATE'] <> '')
							$recurringList['START_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['START_DATE']);

						if ($_POST['RECUR_PARAM']['END_DATE'] <> '')
						{
							$recurringList['LIMIT_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['END_DATE']);
						}
						else
						{
							$recurringList['LIMIT_DATE'] = null;
						}

						if ((int)($_POST['RECUR_PARAM']['LIMIT_REPEAT']) > 0)
						{
							$recurringList['LIMIT_REPEAT'] = (int)($_POST['RECUR_PARAM']['LIMIT_REPEAT']);
						}
						else
						{
							$recurringList['LIMIT_REPEAT'] = null;
						}

						if (
							($_POST['RECUR_PARAM']['REPEAT_TILL'] === 'times' || $_POST['RECUR_PARAM']['REPEAT_TILL'] === Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_TIMES)
							&& (int)$recurringList['LIMIT_REPEAT'] > 0)
						{
							$recurringList['IS_LIMIT'] = Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_TIMES;
						}
						elseif(
							$_POST['RECUR_PARAM']['REPEAT_TILL'] === 'date'
							|| $_POST['RECUR_PARAM']['REPEAT_TILL'] === Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_DATE)
						{
							$recurringList['IS_LIMIT'] = Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_DATE;
						}
						else
						{
							$recurringList['IS_LIMIT'] = Bitrix\Crm\Recurring\Entity\Invoice::NO_LIMITED;
						}

						$recurringList['SEND_BILL'] = ($_POST['RECUR_PARAM']['RECURRING_EMAIL_SEND'] === 'Y') ? 'Y' : 'N';
						$recurringList['EMAIL_ID'] = ((int)$_POST['RECUR_PARAM']['RECURRING_EMAIL_ID'] > 0) ? (int)$_POST['RECUR_PARAM']['RECURRING_EMAIL_ID'] : null;

						$recurringList['PARAMS'] = $_POST['RECUR_PARAM'];
						$recur = \Bitrix\Crm\InvoiceRecurTable::getList(
							array(
								"filter" => array("=INVOICE_ID" => $arResult['ELEMENT']['ID'])
							)
						)->fetch();

						$res = Recurring\Manager::updateRecurring($recur['ID'], $recurringList);

						if ($res->isSuccess())
						{
							Recurring\Manager::exposeTodayInvoices();
						}
					}
				}
				else
				{
					if (
						($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] === 'Y' || $arParams['IS_RECURRING'] === 'Y')
						&& Recurring\Manager::isAllowedExpose(Recurring\Manager::INVOICE)
					)
					{
						if (is_array($arFields['PRODUCT_ROWS']))
						{
							foreach ($arFields['PRODUCT_ROWS'] as &$productRow)
							{
								$productRow['ID'] = 0;
							}
						}

						if ($_POST['RECUR_PARAM']['START_DATE'] <> '')
							$recurringList['START_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['START_DATE']);

						if (
							$_POST['RECUR_PARAM']['END_DATE'] <> ''
							&& ($_POST['RECUR_PARAM']['REPEAT_TILL'] === 'date'|| $_POST['RECUR_PARAM']['REPEAT_TILL'] === Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_DATE)
						)
						{
							$recurringList['LIMIT_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['END_DATE']);
							$recurringList['IS_LIMIT'] = Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_DATE;
						}

						if (
							(int)($_POST['RECUR_PARAM']['LIMIT_REPEAT']) > 0
							&& ($_POST['RECUR_PARAM']['REPEAT_TILL'] === 'times'|| $_POST['RECUR_PARAM']['REPEAT_TILL'] === Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_TIMES)
						)
						{
							$recurringList['LIMIT_REPEAT'] =(int)($_POST['RECUR_PARAM']['LIMIT_REPEAT']);
							$recurringList['IS_LIMIT'] = Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_TIMES;
						}

						if (empty($recurringList['IS_LIMIT']))
						{
							$recurringList['IS_LIMIT'] = Bitrix\Crm\Recurring\Entity\Invoice::NO_LIMITED;
						}

						$recurringList['PARAMS'] = is_array($_POST['RECUR_PARAM']) ? $_POST['RECUR_PARAM'] : array();
						$recurringList['LIMIT_REPEAT'] = (int)$_POST['RECUR_PARAM']['LIMIT_REPEAT'] ? (int)$_POST['RECUR_PARAM']['LIMIT_REPEAT'] : null;
						$recurringList['SEND_BILL'] = ($_POST['RECUR_PARAM']['RECURRING_EMAIL_SEND'] === 'Y') ? 'Y' : 'N';
						$recurringList['EMAIL_ID'] = ((int)$_POST['RECUR_PARAM']['RECURRING_EMAIL_ID'] > 0) ? (int)$_POST['RECUR_PARAM']['RECURRING_EMAIL_ID'] : null;

						$result = Recurring\Manager::createInvoice($arFields, $recurringList);
						$resultData = $result->getData();
						$ID = $resultData['INVOICE_ID'];
					}
					else
					{
						$recalculate = false;
						$ID = $CCrmInvoice->Add($arFields, $recalculate, SITE_ID, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));
					}

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
							$CrmContact->Update(
								$arFields['UF_CONTACT_ID'],
								$arContactFields,
								false,
								true,
								array('DISABLE_USER_FIELD_CHECK' => true)
							);
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
			if($arResult['CALL_LIST_ID'] > 0 && $arResult['CALL_LIST_ELEMENT'] > 0)
			{
				$callList = \Bitrix\Crm\CallList\CallList::createWithId($arResult['CALL_LIST_ID']);
				if($callList && $ID > 0)
				{
					$callList->addCreatedEntity($arResult['CALL_LIST_ELEMENT'], CCrmOwnerType::InvoiceName, $ID);
				}
			}

			if (!empty($arResult['ERROR_MESSAGE']))
			{
				ShowError($arResult['ERROR_MESSAGE']);
				$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
					array_merge(array('ID' => $ID), $arFields),
					array(
						'ORDER_TOPIC' => array('TYPE' => 'string'),
						'STATUS_ID' => array('TYPE' => 'int'),
						'DATE_INSERT' => array('TYPE' => 'datetime'),
						'DATE_BILL' => array('TYPE' => 'date'),
						'DATE_PAY_BEFORE' => array('TYPE' => 'date'),
						'RESPONSIBLE_ID' => array('TYPE' => 'int'),
						'COMMENTS' => array('TYPE' => 'string'),
						'USER_DESCRIPTION' => array('TYPE' => 'string'),
						'ACCOUNT_NUMBER' => array('TYPE' => 'string'),
						'UF_QUOTE_ID' => array('TYPE' => 'int'),
						'UF_DEAL_ID' => array('TYPE' => 'int'),
						'UF_COMPANY_ID' => array('TYPE' => 'int'),
						'UF_CONTACT_ID' => array('TYPE' => 'int'),
						'PAY_VOUCHER_NUM' => array('TYPE' => 'string'),
						'PAY_VOUCHER_DATE' => array('TYPE' => 'datetime'),
						'REASON_MARKED' => array('TYPE' => 'string'),
						'DATE_MARKED' => array('TYPE' => 'datetime')
					)
				);
				$arResult['ELEMENT']['RECURRING_DATA']['PARAMS'] = $_POST['RECUR_PARAM'];
			}
			else
			{
				if ($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] === 'Y' || $arParams['IS_RECURRING'] === 'Y')
				{
					$pathEdit = $arParams['PATH_TO_INVOICE_RECUR_EDIT'];
					$pathShow = $arParams['PATH_TO_INVOICE_RECUR_SHOW'];
				}
				else
				{
					$pathEdit = $arParams['PATH_TO_INVOICE_EDIT'];
					$pathShow = $arParams['PATH_TO_INVOICE_SHOW'];
				}

				if (isset($_POST['apply']))
				{
					if (CCrmInvoice::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::makePathFromTemplate(
								$pathEdit,
								array('invoice_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::makePathFromTemplate(
							$pathEdit,
							array('invoice_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmInvoice::CheckReadPermission($ID))
					{
						LocalRedirect(
							empty($arResult['INVOICE_REFERER']) ?
								CComponentEngine::makePathFromTemplate(
									$pathShow,
									array('invoice_id' => $ID)
								)
								:
								$arResult['INVOICE_REFERER']
						);
					}
				}
				elseif (isset($_POST['continue']) && $conversionWizard !== null)
				{
					$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::InvoiceName, $ID);
					$url = $conversionWizard->getRedirectUrl();
					if($url !== '')
					{
						LocalRedirect($url);
					}
				}

				//save
				if(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
				{
					$info = $arResult['INFO'] = CCrmEntitySelectorHelper::PrepareEntityInfo(
						CCrmOwnerType::InvoiceName,
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
							'entityTypeName' => CCrmOwnerType::InvoiceName,
							'entityInfo' => $info
						)
					);
					if(CModule::IncludeModule('pull'))
					{
						\Bitrix\Pull\Event::add($USER->GetID(), array(
							'module_id' => 'crm',
							'command' => 'external_event',
							'params' => $arResult['EXTERNAL_EVENT']
						));
					}
					$this->IncludeComponentTemplate('event');
					return;
				}
				else
				{
					LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_LIST'], array()));
				}
			}
		}
	}
	elseif (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($bEdit)
		{
			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmInvoice->cPerms->CheckEnityAccess('INVOICE', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]))
			{
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			}
			if (empty($arResult['ERROR_MESSAGE']) && !$CCrmInvoice->Delete($arResult['ELEMENT']['ID']))
			{
				$errMsg = trim(strval($CCrmInvoice->LAST_ERROR));
				if ($errMsg === '' && $ex = $APPLICATION->GetException())
				{
					$errMsg = trim(strval($ex->GetString()));
				}
				if ($errMsg === '')
				{
					$errMsg = GetMessage('CRM_DELETE_ERROR');
				}
				$arResult['ERROR_MESSAGE'] = $errMsg;
				unset($errMsg);
			}
			if (empty($arResult['ERROR_MESSAGE']))
			{
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_LIST']));
			}
			else
			{
				ShowError($arResult['ERROR_MESSAGE']);
			}

			return;
		}
		else
		{
			ShowError(GetMessage('CRM_DELETE_ERROR'));
			return;
		}
	}
}

//$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($arResult['ELEMENT']['STATUS_ID']);
//$bStatusFailed = CCrmStatusInvoice::isStatusFailed($arResult['ELEMENT']['STATUS_ID']);

if($conversionWizard !== null && $conversionWizard->hasOriginUrl())
{
	$arResult['BACK_URL'] = $conversionWizard->getOriginUrl();
}
else
{
	$arResult['BACK_URL'] = !empty($arResult['INVOICE_REFERER']) ? $arResult['INVOICE_REFERER'] : $arParams['PATH_TO_INVOICE_LIST'];
}

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

$arResult['FIELDS'] = array();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_info',
	'name' => GetMessage('CRM_SECTION_INVOICE_INFO'),
	'type' => 'section'
);


$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACCOUNT_NUMBER',
	'name' => GetMessage('CRM_FIELD_ACCOUNT_NUMBER'),
	'params' => array('size' => 100),
	'value' => isset($arResult['ELEMENT']['~ACCOUNT_NUMBER']) ? $arResult['ELEMENT']['~ACCOUNT_NUMBER'] : '',
	'type' => 'text',
	'required' => $bEdit,
	'visible' => $bEdit
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ORDER_TOPIC',
	'name' => GetMessage('CRM_FIELD_ORDER_TOPIC'),
	'params' => array('size' => 255),
	'value' => isset($arResult['ELEMENT']['~ORDER_TOPIC']) ? $arResult['ELEMENT']['~ORDER_TOPIC'] : '',
	'type' => 'text',
	'required' => true
);
if ($arParams['IS_RECURRING'] !== "Y")
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'STATUS_ID',
		'name' => GetMessage('CRM_FIELD_STATUS_ID'),
		'items' => $arResult['STATUS_LIST'],
		'params' => array('sale_order_marker' => 'Y'),
		'type' => 'list',
		'value' => (
						isset($arResult['ELEMENT']['STATUS_ID'])
						? $arResult['ELEMENT']['STATUS_ID']
						:
						(
							isset($_REQUEST['status_id'])
							? $_REQUEST['status_id']
							: ''
						)
					),
		'required' => true
	);

	// status dependent fields
	// <editor-fold defaultstate="collapsed" desc="status dependent fields ...">
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'PAY_VOUCHER_DATE',
		'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_DATE'),
		'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
		'type' => 'date_short',
		'value' => !empty($arResult['ELEMENT']['PAY_VOUCHER_DATE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['PAY_VOUCHER_DATE']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'PAY_VOUCHER_NUM',
		'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_NUM'),
		'params' => array('size' => 20),
		'value' => isset($arResult['ELEMENT']['~PAY_VOUCHER_NUM']) ? $arResult['ELEMENT']['~PAY_VOUCHER_NUM'] : '',
		'type' => 'text'
	);

	$arResult['ELEMENT']['REASON_MARKED_SUCCESS'] = $arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] = '';
	if ($arResult['ELEMENT']['~STATUS_ID'] != '' && CCrmStatusInvoice::isStatusSuccess($arResult['ELEMENT']['~STATUS_ID']))
	{
		$arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] = $arResult['ELEMENT']['~REASON_MARKED'];
		$arResult['ELEMENT']['REASON_MARKED_SUCCESS'] = htmlspecialcharsbx($arResult['ELEMENT']['~REASON_MARKED']);
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'REASON_MARKED_SUCCESS',
		'name' => GetMessage('CRM_FIELD_REASON_MARKED_SUCCESS'),
		'value' => isset($arResult['ELEMENT']['~REASON_MARKED_SUCCESS']) ? $arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] : '',
		'type' => 'textarea'
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_MARKED',
		'name' => GetMessage('CRM_FIELD_DATE_MARKED'),
		'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
		'type' => 'date_short',
		'value' => !empty($arResult['ELEMENT']['DATE_MARKED']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_MARKED']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
	);

	if ($arResult['ELEMENT']['~STATUS_ID'] != '' && !CCrmStatusInvoice::isStatusFailed($arResult['ELEMENT']['~STATUS_ID']))
		$arResult['ELEMENT']['REASON_MARKED'] = $arResult['ELEMENT']['~REASON_MARKED'] = '';
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'REASON_MARKED',
		'name' => GetMessage('CRM_FIELD_REASON_MARKED'),
		'value' => isset($arResult['ELEMENT']['~REASON_MARKED']) ? $arResult['ELEMENT']['~REASON_MARKED'] : '',
		'type' => 'textarea'
	);
}
// </editor-fold>

//	if ($bEdit)
//	{
//		$arResult['FIELDS']['tab_1'][] = array(
//			'id' => 'PAYED',
//			'name' => GetMessage('CRM_FIELD_PAYED'),
//			'type' => 'checkbox',
//			'value' => ((isset($arResult['ELEMENT']['PAYED']) && $arResult['ELEMENT']['PAYED'] === 'Y') ? 'Y' : 'N')
//		);
//	}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_BILL',
	'name' => GetMessage('CRM_FIELD_DATE_BILL'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'date_link',
	'value' => !empty($arResult['ELEMENT']['DATE_BILL']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_BILL']), 'SHORT', SITE_ID) : ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT', SITE_ID)
);

if ($arResult['ELEMENT']['IS_RECURRING'] !== 'Y')
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_PAY_BEFORE',
		'name' => GetMessage('CRM_FIELD_DATE_PAY_BEFORE'),
		'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
		'type' => 'date_short',
		'value' => !empty($arResult['ELEMENT']['DATE_PAY_BEFORE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_PAY_BEFORE']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
	);
}


$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'RESPONSIBLE_ID',
	'componentParams' => array(
		'NAME' => 'crm_invoice_edit_resonsible',
		'INPUT_NAME' => 'RESPONSIBLE_ID',
		'SEARCH_INPUT_NAME' => 'RESPONSIBLE_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_RESPONSIBLE_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['RESPONSIBLE_ID']) ? $arResult['ELEMENT']['RESPONSIBLE_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => htmlspecialcharsbx(isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID)
);

// DEAL LINK
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'UF_DEAL_ID',
	'name' => GetMessage('CRM_FIELD_UF_DEAL_ID'),
	'type' => 'crm_entity_selector',
	'componentParams' => array(
		'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "INVOICE_{$arParams['ELEMENT_ID']}" : 'NEWINVOICE',
		'ENTITY_TYPE' => 'DEAL',
		'INPUT_NAME' => 'UF_DEAL_ID',
		'NEW_INPUT_NAME' => '',
		'INPUT_VALUE' => isset($arResult['ELEMENT']['UF_DEAL_ID']) ? $arResult['ELEMENT']['UF_DEAL_ID'] : '',
		'FORM_NAME' => $arResult['FORM_ID'],
		'MULTIPLE' => 'N',
		'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
	)
);

// QUOTE LINK
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'UF_QUOTE_ID',
	'name' => GetMessage('CRM_FIELD_UF_QUOTE_ID'),
	'type' => 'crm_entity_selector',
	'componentParams' => array(
		'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "INVOICE_{$arParams['ELEMENT_ID']}" : 'NEWINVOICE',
		'ENTITY_TYPE' => 'QUOTE',
		'INPUT_NAME' => 'UF_QUOTE_ID',
		'NEW_INPUT_NAME' => '',
		'INPUT_VALUE' => isset($arResult['ELEMENT']['UF_QUOTE_ID']) ? $arResult['ELEMENT']['UF_QUOTE_ID'] : '',
		'FORM_NAME' => $arResult['FORM_ID'],
		'MULTIPLE' => 'N',
		'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
	)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_payer',
	'name' => GetMessage('CRM_SECTION_INVOICE_PAYER'),
	'type' => 'section'
);

// CLIENT
$companyId = isset($arResult['ELEMENT']['UF_COMPANY_ID']) ? (int)$arResult['ELEMENT']['UF_COMPANY_ID'] : 0;
$contactId = isset($arResult['ELEMENT']['UF_CONTACT_ID']) ? (int)$arResult['ELEMENT']['UF_CONTACT_ID'] : 0;

if($companyId > 0 || $contactId <= 0)
{
	$primaryEntityTypeName = CCrmOwnerType::CompanyName;
	$primaryEntityID = $companyId;
}
else
{
	$primaryEntityTypeName = CCrmOwnerType::ContactName;
	$primaryEntityID = $contactId;
}

$secondaryIDs = array();
if($contactId > 0)
{
	$secondaryIDs[] = $contactId;
}
$arResult['CLIENT_SELECTOR_ID'] = 'CLIENT';
$arResult['FIELDS']['tab_1'][] = array(
	'id' => $arResult['CLIENT_SELECTOR_ID'],
	'name' => GetMessage('CRM_FIELD_CLIENT_ID'),
	'type' => 'crm_composite_client_selector',
	'componentParams' => array(
		'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "INVOICE_{$arParams['ELEMENT_ID']}" : 'NEWINVOICE',
		'OWNER_TYPE' => CCrmOwnerType::InvoiceName,
		'OWNER_ID' => $arParams['ELEMENT_ID'],
		'PRIMARY_ENTITY_TYPE' => $primaryEntityTypeName,
		'PRIMARY_ENTITY_ID' => $primaryEntityID,
		'SECONDARY_ENTITY_TYPE' => CCrmOwnerType::ContactName,
		'SECONDARY_ENTITY_IDS' => $secondaryIDs,
		'ENABLE_MULTIPLICITY' => false,
		'CUSTOM_MESSAGES' => array(
			'SECONDARY_ENTITY_HEADER' => GetMessage('CRM_INVOICE_EDIT_CONTACT_SELECTOR_HEADER')
		),
		'PRIMARY_ENTITY_TYPE_INPUT_NAME' => 'PRIMARY_ENTITY_TYPE',
		'PRIMARY_ENTITY_INPUT_NAME' => 'PRIMARY_ENTITY_ID',
		'SECONDARY_ENTITIES_INPUT_NAME' => 'SECONDARY_ENTITY_IDS',
		'REQUISITE_INPUT_NAME' => 'REQUISITE_ID',
		'REQUISITE_ID' => $requisiteIdLinked,
		'BANK_DETAIL_INPUT_NAME' => 'BANK_DETAIL_ID',
		'BANK_DETAIL_ID' => $bankDetailIdLinked,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.invoice.edit/ajax.php?'.bitrix_sessid_get(),
		'REQUISITE_SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.edit/settings.php?'.bitrix_sessid_get(),
		'FORM_NAME' => $arResult['FORM_ID'],
		'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
		'ENTITY_SELECTOR_SEARCH_OPTIONS' => array(
			'NOT_MY_COMPANIES' => 'Y'
		)
	),
	'required' => true
);

if ($bTaxMode)
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
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'LOCATION_ID',
		'name' => GetMessage('CRM_FIELD_LOCATION'),
		'type' => 'custom',
		'value' =>  $sLocationHtml.
			'<div>'.
				'<span class="bx-crm-edit-content-location-description">'.
				GetMessage('CRM_FIELD_LOCATION_DESCRIPTION').
				'</span>'.
			'</div>',
		'required' => true
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_pay_system',
	'name' => GetMessage('CRM_SECTION_PAY_SYSTEM'),
	'type' => 'section'
);

// pay system
$arResult['PAY_SYSTEM_ID_TITLE'] = GetMessage('CRM_FIELD_PAY_SYSTEM_ID_TITLE');

$paySystemList = CCrmPaySystem::GetPaySystemsListItems($personTypeId);
if (!array_key_exists($arResult['ELEMENT']['PAY_SYSTEM_ID'], $paySystemList))
{
	$paySystem = \Bitrix\Sale\PaySystem\Manager::getById($arResult['ELEMENT']['PAY_SYSTEM_ID']);
	if ($paySystem)
		$paySystemList[$paySystem['ID']] = $paySystem['NAME'];
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => $paySystemFieldId,
	'name' => GetMessage('CRM_FIELD_PAY_SYSTEM_ID'),
	'params' => array('id' => 'PAY_SYSTEM_SELECT'),
	'items' => $paySystemList,
	'type' => 'list',
	'value' => (isset($arResult['ELEMENT']['PAY_SYSTEM_ID']) ? $arResult['ELEMENT']['PAY_SYSTEM_ID'] : ''),
	'required' => true
);

// my company details
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'UF_MYCOMPANY_ID',
	'name' => GetMessage('CRM_INVOICE_FIELD_UF_MYCOMPANY_ID1'),
	'type' => 'crm_single_client_selector',
	'componentParams' => array(
		'CONTEXT' => $bEdit ? "INVOICE_{$arResult['ELEMENT']['ID']}" : 'NEWINVOICE',
		'OWNER_TYPE' => CCrmOwnerType::InvoiceName,
		'OWNER_ID' => $bEdit ? $arResult['ELEMENT']['ID'] : 0,
		'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
		'ENTITY_ID' => isset($arResult['ELEMENT']['UF_MYCOMPANY_ID']) ?
			(int)$arResult['ELEMENT']['UF_MYCOMPANY_ID'] : 0,
		'ENTITY_INPUT_NAME' => 'UF_MYCOMPANY_ID',
		'REQUISITE_INPUT_NAME' => 'MC_REQUISITE_ID',
		'REQUISITE_ID' => $mcRequisiteIdLinked,
		'BANK_DETAIL_INPUT_NAME' => 'MC_BANK_DETAIL_ID',
		'BANK_DETAIL_ID' => $mcBankDetailIdLinked,
		'ENABLE_REQUISITES'=> true,
		'REQUISITE_SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.edit/settings.php?'.bitrix_sessid_get(),
		'ENTITY_SELECTOR_SEARCH_OPTIONS' => array(
			'ONLY_MY_COMPANIES' => 'Y'
		),
		'ENABLE_ENTITY_CREATION'=> $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'),
		'ENTITY_CREATE_URL'=> CCrmOwnerType::GetEditUrl(CCrmOwnerType::Company, 0, false),
		'READ_ONLY' => false,
		'FORM_NAME' => $arResult['FORM_ID'],
		'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
	)
);

ob_start();
$data = !empty($arResult['ELEMENT']['RECURRING_DATA']['PARAMS']) ? $arResult['ELEMENT']['RECURRING_DATA']['PARAMS'] : array();
$data['CONTEXT'] = $arParams['ELEMENT_ID'] > 0 ? "INVOICE_{$arParams['ELEMENT_ID']}" : 'NEWINVOICE';
$data['CLIENT_PRIMARY_ENTITY_TYPE_NAME'] = $primaryEntityTypeName;
$data['CLIENT_PRIMARY_ENTITY_ID'] = $primaryEntityID;
$data['CLIENT_SECONDARY_ENTITY_IDS'] = $secondaryIDs;
$data['START_DATE'] = $arResult['ELEMENT']['RECURRING_DATA']['START_DATE'];
$data['RECURRING_EMAIL_SEND'] = $arResult['ELEMENT']['RECURRING_DATA']['SEND_BILL'];
$data['RECURRING_EMAIL_ID'] = $arResult['ELEMENT']['RECURRING_DATA']['EMAIL_ID'];
$data['LAST_EXECUTION'] = $arResult['ELEMENT']['RECURRING_DATA']['LAST_EXECUTION'];
$data['UF_MYCOMPANY_ID'] = (int)$arResult['ELEMENT']['UF_MYCOMPANY_ID'] > 0 ? $arResult['ELEMENT']['UF_MYCOMPANY_ID'] : null;
$APPLICATION->IncludeComponent('bitrix:crm.interface.form.recurring',
	'edit',
	array(
		'DATA' => $data,
		'ID' => $arResult['ELEMENT']['ID'],
		'IS_RECURRING' => $arResult['ELEMENT']['IS_RECURRING']
	),
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);

$recurringHtml = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_recurring',
	'name' => GetMessage('CRM_SECTION_RECURRING_ROWS'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_recurring_rows',
	'name' => GetMessage('CRM_SECTION_RECURRING_ROWS'),
	'params' =>
		array (
			'class' => 'bx-crm-dialog-input bx-crm-dialog-input-date',
			'sale_order_marker' => 'Y',
		),
	'type' => 'recurring_params',
	'colspan' => true,
	'value' => $recurringHtml
);


// COMMENTS
// <editor-fold defaultstate="collapsed" desc="COMMENTS ...">
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_comments',
	'name' => GetMessage('CRM_SECTION_COMMENTS'),
	'type' => 'section'
);

//	$arResult['FIELDS']['tab_1'][] = array(
//		'id' => 'COMMENTS',
//		'name' => GetMessage('CRM_FIELD_COMMENTS'),
//		'params' => array('size' => 2000),
//		'value' => isset($arResult['ELEMENT']['COMMENTS']) ? $arResult['ELEMENT']['COMMENTS'] : '',
//		'type' => 'textarea'
//	);

ob_start();
$ar = array(
	'inputName' => 'COMMENTS',
	'inputId' => 'COMMENTS',
	'height' => '80',
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
	'required' => false,
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);

//	$arResult['FIELDS']['tab_1'][] = array(
//		'id' => 'USER_DESCRIPTION',
//		'name' => GetMessage('CRM_FIELD_USER_DESCRIPTION'),
//		'params' => array('size' => 250),
//		'value' => isset($arResult['ELEMENT']['USER_DESCRIPTION']) ? $arResult['ELEMENT']['USER_DESCRIPTION'] : '',
//		'type' => 'textarea'
//	);

ob_start();
$ar = array(
	'inputName' => 'USER_DESCRIPTION',
	'inputId' => 'USER_DESCRIPTION',
	'height' => '80',
	'content' => isset($arResult['ELEMENT']['~USER_DESCRIPTION']) ? $arResult['ELEMENT']['~USER_DESCRIPTION'] : '',
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
	'id' => 'USER_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_USER_DESCRIPTION'),
	'required' => false,
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);
// </editor-fold>

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_spec',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section',
	'required' => true
);

// Product rows
$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'invoice_'.strval($arParams['ELEMENT_ID']) : 'new_invoice').'_product_editor';
$sProductsHtml = '';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => 'I',
	'PERMISSION_TYPE' => $isExternal ? 'READ' : 'WRITE',
	'INIT_EDITABLE' => 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['PR_LOCATION'] : '',
	'CLIENT_SELECTOR_ID' => $arResult['CLIENT_SELECTOR_ID'],
	'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
	'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName,
	'TOTAL_SUM' => isset($arResult['ELEMENT']['~PRICE']) ? $arResult['ELEMENT']['~PRICE'] : null,
	'TOTAL_TAX' => isset($arResult['ELEMENT']['~TAX_VALUE']) ? $arResult['ELEMENT']['~TAX_VALUE'] : null,
	'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],
	'COPY_FLAG' => ($bCopy || $bCreateFromQuote || $bCreateFromDeal || $conversionWizard !== null) ? 'Y' : 'N',
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
$sProductsHtml .= ob_get_contents();
ob_end_clean();
unset($componentSettings);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'INVOICE_PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$icnt = count($arResult['FIELDS']['tab_1']);

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

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$useUserFieldsFromForm,
	false,
	false,
	array('FILE_VIEWER' => $fileViewer)
);

if (count($arResult['FIELDS']['tab_1']) == $icnt)
	unset($arResult['FIELDS']['tab_1'][$icnt - 1]);

if ($bCopy)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice/include/nav.php');
?>
