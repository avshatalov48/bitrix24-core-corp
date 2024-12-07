<?php

use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Settings\QuoteSettings;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');
$CCrmQuote = new CCrmQuote();
if ($CCrmQuote->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'WRITE')
	&& $CCrmQuote->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath('PATH_TO_QUOTE_LIST', $arParams['PATH_TO_QUOTE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['PREFIX'] = isset($arParams['~PREFIX']) ? $arParams['~PREFIX'] : 'crm_quote_edit';

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
/** @var \Bitrix\Crm\Conversion\EntityConversionWizard */
$conversionWizard = null;
if (isset($_REQUEST['conv_deal_id']) && $_REQUEST['conv_deal_id'] > 0)
{
	$srcDealId = intval($_REQUEST['conv_deal_id']);
	if ($srcDealId > 0)
	{
		$conversionWizard = \Bitrix\Crm\Conversion\DealConversionWizard::load($srcDealId);
		if ($conversionWizard !== null)
		{
			$arResult['DEAL_ID'] = $srcDealId;
		}
	}
}

//region external context ID
$arResult['EXTERNAL_CONTEXT'] = isset($_REQUEST['external_context']) ? $_REQUEST['external_context'] : '';
//endregion

global $USER_FIELD_MANAGER, $DB, $USER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmQuote::$sUFEntityID);
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$isNew = $arParams['ELEMENT_ID'] <= 0;
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

$arPersonTypes = $arResult['PERSON_TYPE_IDS'] = CCrmPaySystem::getPersonTypeIDs();

$bTaxMode = CCrmTax::isTaxMode();
$arResult['TAX_MODE'] = $bTaxMode ? 'Y' : 'N';

if ($bEdit)
{
	CCrmQuote::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $CCrmQuote->cPerms);
	if ($arResult['CAN_CONVERT'])
	{
		$config = \Bitrix\Crm\Conversion\QuoteConversionConfig::load();
		if ($config === null)
		{
			$config = \Bitrix\Crm\Conversion\QuoteConversionConfig::getDefault();
		}

		$arResult['CONVERSION_CONFIG'] = $config;
	}
}

$requisiteIdLinked = 0;
$bankDetailIdLinked = 0;
$mcRequisiteIdLinked = 0;
$mcBankDetailIdLinked = 0;

$arFields = null;
if ($conversionWizard !== null)
{
	$arFields = array('ID' => 0);
	if ($_SERVER['REQUEST_METHOD'] === 'GET')
	{
		$conversionWizard->prepareDataForEdit(CCrmOwnerType::Quote, $arFields, true);
		if (isset($arFields['PRODUCT_ROWS']))
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
	$obFields = CCrmQuote::GetList(array(), $arFilter);
	$arFields = $obFields->GetNext();
	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}
	else
		$arEntityAttr = $CCrmQuote->cPerms->GetEntityAttr('QUOTE', array($arParams['ELEMENT_ID']));
	if ($bCopy)
	{
		if (isset($arFields['QUOTE_NUMBER']))
			unset($arFields['QUOTE_NUMBER']);

		if (isset($arFields['~QUOTE_NUMBER']))
			unset($arFields['~QUOTE_NUMBER']);

		if (isset($arFields['LEAD_ID']))
		{
			unset($arFields['LEAD_ID']);
		}

		if (isset($arFields['~LEAD_ID']))
		{
			unset($arFields['~LEAD_ID']);
		}

		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'QUOTE', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}

		// read product row settings
		$productRowSettings = array();
		$arQuoteProductRowSettings = CCrmProductRow::LoadSettings(CCrmQuote::OWNER_TYPE, $arParams['ELEMENT_ID']);
		if (is_array($arQuoteProductRowSettings))
		{
			$productRowSettings['ENABLE_DISCOUNT'] = isset($arQuoteProductRowSettings['ENABLE_DISCOUNT']) ? $arQuoteProductRowSettings['ENABLE_DISCOUNT'] : false;
			$productRowSettings['ENABLE_TAX'] = isset($arQuoteProductRowSettings['ENABLE_TAX']) ? $arQuoteProductRowSettings['ENABLE_TAX'] : false;
		}
		unset($arQuoteProductRowSettings);
	}

	if (is_array($arFields))
	{
		//HACK: MSSQL returns '.00' for zero value
		if (isset($arFields['~OPPORTUNITY']))
		{
			$arFields['~OPPORTUNITY'] = $arFields['OPPORTUNITY'] = floatval($arFields['~OPPORTUNITY']);
		}
		if (isset($arFields['~OPPORTUNITY_ACCOUNT']))
		{
			$arFields['~OPPORTUNITY_ACCOUNT'] = $arFields['OPPORTUNITY_ACCOUNT'] = floatval($arFields['~OPPORTUNITY_ACCOUNT']);
		}
		if (isset($arFields['~TAX_VALUE']))
		{
			$arFields['~TAX_VALUE'] = $arFields['TAX_VALUE'] = floatval($arFields['~TAX_VALUE']);
		}
		if (isset($arFields['~TAX_VALUE_ACCOUNT']))
		{
			$arFields['~TAX_VALUE_ACCOUNT'] = $arFields['TAX_VALUE_ACCOUNT'] = floatval($arFields['~TAX_VALUE_ACCOUNT']);
		}
	}
}
else
{
	$arFields = array(
		'ID' => 0
	);

	$beginDate = time() + CTimeZone::GetOffset();
	$time = localtime($beginDate, true);
	$beginDate -= $time['tm_sec'];

	$arFields['BEGINDATE'] = ConvertTimeStamp($beginDate, 'FULL', SITE_ID);
	$arFields['CLOSEDATE'] = ConvertTimeStamp($beginDate + 7 * 86400, 'FULL', SITE_ID);

	/*$extVals =  isset($arParams['~VALUES']) && is_array($arParams['~VALUES']) ? $arParams['~VALUES'] : array();
	if (count($extVals) > 0)
	{
		if (isset($extVals['PRODUCT_ROWS']) && is_array($extVals['PRODUCT_ROWS']))
		{
			$arResult['PRODUCT_ROWS'] = $extVals['PRODUCT_ROWS'];
			unset($extVals['PRODUCT_ROWS']);
		}

		$arFields = array_merge($arFields, $extVals);
		$arFields = CCrmComponentHelper::PrepareEntityFields(
			$arFields,
			CCrmQuote::GetFields()
		);
		// hack for UF
		$_REQUEST = $_REQUEST + $extVals;
	}*/

	$bCreateFromContact = $bCreateFromCompany = $bCreateFromDeal = false;
	if (isset($_GET['contact_id']))
	{
		$arFields['CONTACT_ID'] = intval($_GET['contact_id']);
		if ($arFields['CONTACT_ID'] > 0)
			$bCreateFromContact = true;
	}
	if (isset($_GET['company_id']))
	{
		$arFields['COMPANY_ID'] = intval($_GET['company_id']);
		if ($arFields['COMPANY_ID'] > 0)
			$bCreateFromCompany = true;
	}
	if (isset($_GET['lead_id']))
	{
		$arFields['LEAD_ID'] = intval($_GET['lead_id']);
		if ($arFields['LEAD_ID'] > 0)
			$bCreateFromLead = true;
	}
	if (isset($_GET['deal_id']))
	{
		$arFields['DEAL_ID'] = intval($_GET['deal_id']);
		if ($arFields['DEAL_ID'] > 0)
			$bCreateFromDeal = true;
	}
	if (isset($_GET['title']))
	{
		$arFields['~TITLE'] = $_GET['title'];
		$arFields['TITLE'] = htmlspecialcharsbx($arFields['~TITLE']);
	}

	$bCreateFrom = ($bCreateFromLead || $bCreateFromDeal || $bCreateFromCompany || $bCreateFromContact);

	$leadId = isset($arFields['LEAD_ID']) ? intval($arFields['LEAD_ID']) : 0;
	$dealId = isset($arFields['DEAL_ID']) ? intval($arFields['DEAL_ID']) : 0;
	$contactId = isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0;
	$companyId = isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : 0;

	// create from contact
	if ($contactId > 0)
	{
		$dbContact = CCrmContact::GetListEx(array('ID' => 'DESC'), array('ID' => $contactId), false,
			array('nTopCount' => 1), array('ID', 'LEAD_ID'));
		if ($arContact = $dbContact->Fetch())
		{
			if (isset($arContact['LEAD_ID']) && intval($arContact['LEAD_ID']) > 0)
				$arFields['~LEAD_ID'] = $arFields['LEAD_ID'] = intval($arContact['LEAD_ID']);
		}
		unset($dbContact, $arContact);
	}

	// create from company
	if ($companyId > 0)
	{
		$dbCompany = CCrmCompany::GetListEx(array('ID' => 'DESC'), array('ID' => $companyId), false,
			array('nTopCount' => 1), array('ID', 'LEAD_ID'));
		if ($arCompany = $dbCompany->Fetch())
		{
			if (isset($arCompany['LEAD_ID']) && intval($arCompany['LEAD_ID']) > 0)
				$arFields['~LEAD_ID'] = $arFields['LEAD_ID'] = intval($arCompany['LEAD_ID']);
		}
		unset($dbCompany, $arCompany);
	}

	// create from lead
	if ($leadId > 0)
	{
		$dbDeal = CCrmDeal::GetListEx(array('ID' => 'DESC'), array('LEAD_ID' => $leadId), false,
			array('nTopCount' => 1), array('ID'));
		if ($arDeal = $dbDeal->Fetch())
		{
			if (isset($arDeal['ID']) && intval($arDeal['ID']) > 0)
				$arFields['~DEAL_ID'] = $arFields['DEAL_ID'] = intval($arDeal['ID']);
		}
		unset($dbDeal, $arDeal);

		if (($arLead = CCrmLead::GetByID($leadId)) && is_array($arLead))
		{
			// get contact and/or company from lead
			if ($companyId <= 0 || $contactId <=0)
			{
				if (isset($arLead['CONTACT_ID']) && intval($arLead['CONTACT_ID']) > 0)
					$arFields['~CONTACT_ID'] = $arFields['CONTACT_ID'] = intval($arLead['CONTACT_ID']);
				if (isset($arLead['COMPANY_ID']) && intval($arLead['COMPANY_ID']) > 0)
					$arFields['~COMPANY_ID'] = $arFields['COMPANY_ID'] = intval($arLead['COMPANY_ID']);
			}
			if (isset($arLead['TITLE']) && !isset($arFields['~TITLE']))
			{
				$arFields['~TITLE'] = $arLead['TITLE'];
				$arFields['TITLE'] = htmlspecialcharsbx($arLead['TITLE']);
			}
			if (isset($arLead['ASSIGNED_BY_ID']) && intval($arLead['ASSIGNED_BY_ID']) > 0)
				$arFields['~ASSIGNED_BY_ID'] = $arFields['ASSIGNED_BY_ID'] = intval($arLead['ASSIGNED_BY_ID']);
			if (isset($arLead['OPENED']))
				$arFields['~OPENED'] = $arFields['OPENED'] = ($arLead['OPENED'] === 'Y' ? 'Y' : 'N');
			if (isset($arLead['OPPORTUNITY']))
				$arFields['~OPPORTUNITY'] = $arFields['OPPORTUNITY'] = doubleval($arLead['OPPORTUNITY']);
			if (isset($arLead['OPPORTUNITY_ACCOUNT']))
				$arFields['~OPPORTUNITY_ACCOUNT'] = $arFields['OPPORTUNITY_ACCOUNT'] = doubleval($arLead['OPPORTUNITY_ACCOUNT']);
			if (isset($arLead['TAX_VALUE']))
				$arFields['~TAX_VALUE'] = $arFields['TAX_VALUE'] = doubleval($arLead['TAX_VALUE']);
			if (isset($arLead['TAX_VALUE_ACCOUNT']))
				$arFields['~TAX_VALUE_ACCOUNT'] = $arFields['TAX_VALUE_ACCOUNT'] = doubleval($arLead['TAX_VALUE_ACCOUNT']);
			if (isset($arLead['EXCH_RATE']))
				$arFields['~EXCH_RATE'] = $arFields['EXCH_RATE'] = doubleval($arLead['EXCH_RATE']);
			if (isset($arLead['CURRENCY_ID']))
			{
				$arFields['~CURRENCY_ID'] = $arLead['CURRENCY_ID'];
				$arFields['CURRENCY_ID'] = htmlspecialcharsbx($arFields['~CURRENCY_ID']);
			}
			if (isset($arLead['ACCOUNT_CURRENCY_ID']))
			{
				$arFields['~ACCOUNT_CURRENCY_ID'] = $arLead['ACCOUNT_CURRENCY_ID'];
				$arFields['ACCOUNT_CURRENCY_ID'] = htmlspecialcharsbx($arFields['~ACCOUNT_CURRENCY_ID']);
			}
			if (isset($arLead['COMMENTS']))
			{
				$arFields['~COMMENTS'] = $arLead['COMMENTS'];
				$arFields['COMMENTS'] = htmlspecialcharsbx($arFields['~COMMENTS']);
			}
			$arLeadProducts = CCrmLead::LoadProductRows($leadId);
			if (is_array($arLeadProducts) && count($arLeadProducts) > 0)
			{
				foreach ($arLeadProducts as $leadProduct)
					$leadProduct['ID'] = 0;
				unset($leadProduct);
				$arFields['PRODUCT_ROWS'] = $arResult['PRODUCT_ROWS'] = $arLeadProducts;
			}
			unset($arLeadProducts);

			// read product row settings
			$productRowSettings = array();
			$arLeadProductRowSettings = CCrmProductRow::LoadSettings('L', $leadId);
			if (is_array($arLeadProductRowSettings))
			{
				$productRowSettings['ENABLE_DISCOUNT'] = isset($arLeadProductRowSettings['ENABLE_DISCOUNT']) ? $arLeadProductRowSettings['ENABLE_DISCOUNT'] : false;
				$productRowSettings['ENABLE_TAX'] = isset($arLeadProductRowSettings['ENABLE_TAX']) ? $arLeadProductRowSettings['ENABLE_TAX'] : false;
			}
			unset($arLeadProductRowSettings);
		}
		unset($arLead);
	}

	// create from deal
	if ($dealId > 0)
	{
		if (($arDeal = CCrmDeal::GetByID($dealId)) && is_array($arDeal))
		{
			// get lead, contact, company from deal
			if ($companyId <= 0 || $contactId <=0)
			{
				if (isset($arDeal['LEAD_ID']) && intval($arDeal['LEAD_ID']) > 0)
					$arFields['~LEAD_ID'] = $arFields['LEAD_ID'] = intval($arDeal['LEAD_ID']);
				if (isset($arDeal['CONTACT_ID']) && intval($arDeal['CONTACT_ID']) > 0)
					$arFields['~CONTACT_ID'] = $arFields['CONTACT_ID'] = intval($arDeal['CONTACT_ID']);
				if (isset($arDeal['COMPANY_ID']) && intval($arDeal['COMPANY_ID']) > 0)
					$arFields['~COMPANY_ID'] = $arFields['COMPANY_ID'] = intval($arDeal['COMPANY_ID']);
			}
			if (isset($arDeal['TITLE']) && !isset($arFields['~TITLE']))
			{
				$arFields['~TITLE'] = $arDeal['TITLE'];
				$arFields['TITLE'] = htmlspecialcharsbx($arDeal['TITLE']);
			}
			if (isset($arDeal['ASSIGNED_BY_ID']) && intval($arDeal['ASSIGNED_BY_ID']) > 0)
				$arFields['~ASSIGNED_BY_ID'] = $arFields['ASSIGNED_BY_ID'] = intval($arDeal['ASSIGNED_BY_ID']);
			if (isset($arDeal['OPENED']))
				$arFields['~OPENED'] = $arFields['OPENED'] = ($arDeal['OPENED'] === 'Y' ? 'Y' : 'N');

			if ($bTaxMode)
			{
				if (isset($arDeal['LOCATION_ID']))
				{
					$arFields['~LOCATION_ID'] = $arDeal['LOCATION_ID'];
					$arFields['LOCATION_ID'] = htmlspecialcharsbx($arDeal['LOCATION_ID']);
				}
			}

			if (isset($arDeal['OPPORTUNITY']))
				$arFields['~OPPORTUNITY'] = $arFields['OPPORTUNITY'] = doubleval($arDeal['OPPORTUNITY']);
			if (isset($arDeal['OPPORTUNITY_ACCOUNT']))
				$arFields['~OPPORTUNITY_ACCOUNT'] = $arFields['OPPORTUNITY_ACCOUNT'] = doubleval($arDeal['OPPORTUNITY_ACCOUNT']);
			if (isset($arDeal['TAX_VALUE']))
				$arFields['~TAX_VALUE'] = $arFields['TAX_VALUE'] = doubleval($arDeal['TAX_VALUE']);
			if (isset($arDeal['TAX_VALUE_ACCOUNT']))
				$arFields['~TAX_VALUE_ACCOUNT'] = $arFields['TAX_VALUE_ACCOUNT'] = doubleval($arDeal['TAX_VALUE_ACCOUNT']);
			if (isset($arDeal['EXCH_RATE']))
				$arFields['~EXCH_RATE'] = $arFields['EXCH_RATE'] = doubleval($arDeal['EXCH_RATE']);
			if (isset($arDeal['CURRENCY_ID']))
			{
				$arFields['~CURRENCY_ID'] = $arDeal['CURRENCY_ID'];
				$arFields['CURRENCY_ID'] = htmlspecialcharsbx($arFields['~CURRENCY_ID']);
			}
			if (isset($arDeal['ACCOUNT_CURRENCY_ID']))
			{
				$arFields['~ACCOUNT_CURRENCY_ID'] = $arDeal['ACCOUNT_CURRENCY_ID'];
				$arFields['ACCOUNT_CURRENCY_ID'] = htmlspecialcharsbx($arFields['~ACCOUNT_CURRENCY_ID']);
			}
			if (isset($arDeal['COMMENTS']))
			{
				$arFields['~COMMENTS'] = $arDeal['COMMENTS'];
				$arFields['COMMENTS'] = htmlspecialcharsbx($arFields['~COMMENTS']);
			}
			$arDealProducts = CCrmDeal::LoadProductRows($dealId);
			if (is_array($arDealProducts) && count($arDealProducts) > 0)
			{
				foreach ($arDealProducts as $dealProduct)
					$dealProduct['ID'] = 0;
				unset($dealProduct);
				$arFields['PRODUCT_ROWS'] = $arResult['PRODUCT_ROWS'] = $arDealProducts;
			}
			unset($arDealProducts);

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
		unset($arDeal);
	}

	unset($leadId, $dealId, $contactId, $companyId);
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
				array('ENTITY_TYPE_ID' => CCrmOwnerType::Quote, 'ENTITY_ID' => $arParams['ELEMENT_ID']);
		}
	}
	else if ($conversionWizard !== null)
	{
		if (isset($arFields['DEAL_ID']) && $arFields['DEAL_ID'] > 0)
		{
			$mcRequisiteEntityList[] = $requisiteEntityList[] =
				array('ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $arFields['DEAL_ID']);
		}
	}
	if (isset($arFields['COMPANY_ID']) && $arFields['COMPANY_ID'] > 0)
		$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $arFields['COMPANY_ID']);
	if (isset($arFields['CONTACT_ID']) && $arFields['CONTACT_ID'] > 0)
		$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $arFields['CONTACT_ID']);
	if (isset($arFields['MYCOMPANY_ID']) && $arFields['MYCOMPANY_ID'] > 0)
		$mcRequisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $arFields['MYCOMPANY_ID']);
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
	if (!isset($arFields['MYCOMPANY_ID']) || $arFields['MYCOMPANY_ID'] <= 0)
	{
		$defLink = Bitrix\Crm\Requisite\EntityLink::getDefaultMyCompanyRequisiteLink();
		if (is_array($defLink))
		{
			$arFields['MYCOMPANY_ID'] = isset($defLink['MYCOMPANY_ID']) ? (int)$defLink['MYCOMPANY_ID'] : 0;
			$mcRequisiteIdLinked = isset($defLink['MC_REQUISITE_ID']) ? (int)$defLink['MC_REQUISITE_ID'] : 0;
			$mcBankDetailIdLinked = isset($defLink['MC_BANK_DETAIL_ID']) ? (int)$defLink['MC_BANK_DETAIL_ID'] : 0;
		}
		unset($defLink);
	}
}

// storage type
$storageTypeId = isset($arFields['STORAGE_TYPE_ID'])
	? (int)$arFields['STORAGE_TYPE_ID'] : CCrmQuoteStorageType::Undefined;
if ($storageTypeId === CCrmQuoteStorageType::Undefined
	|| !CCrmQuoteStorageType::IsDefined($storageTypeId))
{
	$storageTypeId = CCrmQuote::GetDefaultStorageTypeID();
}
$arFields['STORAGE_TYPE_ID'] = $arFields['~STORAGE_TYPE_ID'] = $storageTypeId;
$arResult['ENABLE_DISK'] = $storageTypeId === StorageType::Disk;
$arResult['ENABLE_WEBDAV'] = $storageTypeId === StorageType::WebDav;

// storage elements
CCrmQuote::PrepareStorageElementIDs($arFields);

if (($bEdit && !$CCrmQuote->cPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]) ||
	(!$bEdit && $CCrmQuote->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD'))))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

// Determine person type
$personTypeId = is_array($arFields) ? CCrmQuote::ResolvePersonType($arFields, $arPersonTypes) : 0;
$arResult['ELEMENT'] = is_array($arFields) ? $arFields : null;
unset($arFields);

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_QUOTE_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_QUOTE_LIST_V12';
$arResult['FILES_FIELD_CONTAINER_ID'] = $arResult['FORM_ID'].'_FILES_CONTAINER';
$arResult['FORM_CUSTOM_HTML'] = '';


$productDataFieldName = 'QUOTE_PRODUCT_DATA';

$bPostChecked = ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid());

$arResult['QUOTE_REFERER'] = '';
if ($bPostChecked && !empty($_POST['QUOTE_REFERER']))
{
	$arResult['QUOTE_REFERER'] = strval($_POST['QUOTE_REFERER']);
}
else if ($bCreateFrom && !empty($GLOBALS['_SERVER']['HTTP_REFERER']))
{
	$arResult['QUOTE_REFERER'] = strval($_SERVER['HTTP_REFERER']);
}
if ($bCreateFrom && !empty($arResult['QUOTE_REFERER']))
{
	$arResult['FORM_CUSTOM_HTML'] =
		'<input type="hidden" name="QUOTE_REFERER" value="'.htmlspecialcharsbx($arResult['QUOTE_REFERER']).'" />'.
		PHP_EOL.$arResult['FORM_CUSTOM_HTML'];
}

if ($bPostChecked)
{
	$bVarsFromForm = true;
	if (isset($_POST['cancel']))
	{
		if (isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
		{
			$arResult['EXTERNAL_EVENT'] = array(
				'NAME' => 'onCrmEntityCreate',
				'IS_CANCELED' => true,
				'PARAMS' => array(
					'isCanceled' => true,
					'context' => $arResult['EXTERNAL_CONTEXT'],
					'entityTypeName' => CCrmOwnerType::QuoteName
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
					: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_LIST'], array())
			);
		}
	}
	elseif (isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']) || isset($_POST['continue']))
	{
		$srcElement = ($bEdit || $bCopy) ? $arResult['ELEMENT'] : [];
		$arFields = [];

		foreach (['CONTENT', 'TERMS', 'COMMENTS'] as $fieldName)
		{
			if (isset($_POST[$fieldName]))
			{
				$value = isset($_POST[$fieldName]) ? trim($_POST[$fieldName]) : '';
				if ($value !== '' && mb_strpos($value, '<') !== false)
				{
					$value = TextHelper::sanitizeHtml($value);
				}
				$arFields[$fieldName] = $value;
				$arFields[$fieldName.'_TYPE'] = CCrmContentType::Html;
				unset($value);
			}
		}
		unset($fieldName);

		$title = isset($_POST['TITLE']) ? trim($_POST['TITLE']) : '';
		if ($title !== '')
		{
			$arFields['TITLE'] = $title;
		}
		elseif (isset($srcElement['~TITLE']))
		{
			$arFields['TITLE'] = $srcElement['~TITLE'];
		}

		if (isset($_POST['STATUS_ID']))
		{
			$arFields['STATUS_ID'] = trim($_POST['STATUS_ID']);
		}
		elseif (isset($srcElement['~STATUS_ID']))
		{
			$arFields['STATUS_ID'] = $srcElement['~STATUS_ID'];
		}

		if (isset($_POST['ASSIGNED_BY_ID']))
		{
			$arFields['ASSIGNED_BY_ID'] = (int)(
				is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']
			);
		}
		elseif (isset($srcElement['~ASSIGNED_BY_ID']))
		{
			$arFields['ASSIGNED_BY_ID'] = $srcElement['~ASSIGNED_BY_ID'];
		}

		if (isset($_POST['LOC_CITY']))
		{
			$arFields['LOCATION_ID'] = $_POST['LOC_CITY'];
		}
		elseif (isset($srcElement['~LOCATION_ID']))
		{
			$arFields['LOCATION_ID'] = $srcElement['~LOCATION_ID'];
		}

		if ($bEdit)
		{
			if (isset($_POST['QUOTE_NUMBER']))
			{
				$arFields['QUOTE_NUMBER'] = trim($_POST['QUOTE_NUMBER']);
			}
			elseif (isset($srcElement['~QUOTE_NUMBER']))
			{
				$arFields['QUOTE_NUMBER'] = $srcElement['~QUOTE_NUMBER'];
			}
		}

		if (isset($_POST['OPENED']))
		{
			$arFields['OPENED'] = mb_strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
		}
		elseif (isset($srcElement['~OPENED']))
		{
			$arFields['OPENED'] = $srcElement['~OPENED'];
		}
		elseif (!$bEdit && !$bCopy)
		{
			$arFields['OPENED'] = QuoteSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}

		if (isset($_POST['BEGINDATE']))
		{
			$arFields['BEGINDATE'] = trim($_POST['BEGINDATE']);
		}
		elseif (isset($srcElement['~BEGINDATE']))
		{
			$arFields['BEGINDATE'] = $srcElement['~BEGINDATE'];
		}

		if (isset($_POST['CLOSEDATE']))
		{
			$arFields['CLOSEDATE'] = trim($_POST['CLOSEDATE']);
		}
		elseif (isset($srcElement['~CLOSEDATE']))
		{
			$arFields['CLOSEDATE'] = $srcElement['~CLOSEDATE'];
		}

		if (isset($_POST['CLOSED']))
		{
			$arFields['CLOSED'] = $_POST['CLOSED'] == 'Y' ? 'Y' : 'N';
		}
		elseif (isset($srcElement['~CLOSED']))
		{
			$arFields['CLOSED'] = $srcElement['~CLOSED'];
		}

		if (isset($_POST['OPPORTUNITY']))
		{
			$arFields['OPPORTUNITY'] = trim($_POST['OPPORTUNITY']);
		}
		elseif (isset($srcElement['~OPPORTUNITY']))
		{
			$arFields['OPPORTUNITY'] = $srcElement['~OPPORTUNITY'];
		}

		if (isset($_POST['TAX_VALUE']))
		{
			$arFields['TAX_VALUE'] = trim($_POST['TAX_VALUE']);
		}
		elseif (isset($srcElement['~TAX_VALUE']))
		{
			$arFields['TAX_VALUE'] = $srcElement['~TAX_VALUE'];
		}

		if (isset($_POST['CURRENCY_ID']))
		{
			$arFields['CURRENCY_ID'] = $_POST['CURRENCY_ID'];
		}
		elseif (isset($srcElement['~CURRENCY_ID']))
		{
			$arFields['CURRENCY_ID'] = $srcElement['~CURRENCY_ID'];
		}

		$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
		if (!($currencyID !== '' && CCrmCurrency::IsExists($currencyID)))
		{
			$currencyID = $arFields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		}
		$arFields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($currencyID);

		if (isset($_POST['PRIMARY_ENTITY_TYPE']) && isset($_POST['PRIMARY_ENTITY_ID']))
		{
			$primaryEntityTypeName = isset($_POST['PRIMARY_ENTITY_TYPE']) ? $_POST['PRIMARY_ENTITY_TYPE'] : '';
			$primaryEntityTypeID = CCrmOwnerType::ResolveID($primaryEntityTypeName);
			$primaryEntityID = isset($_POST['PRIMARY_ENTITY_ID']) ? (int)$_POST['PRIMARY_ENTITY_ID'] : 0;

			if ($primaryEntityTypeID === CCrmOwnerType::Company)
			{
				if ($primaryEntityID <= 0)
				{
					$arFields['COMPANY_ID'] = 0;
				}
				elseif (CCrmCompany::Exists($primaryEntityID))
				{
					$arFields['COMPANY_ID'] = $primaryEntityID;
				}
				elseif (isset($srcElement['~COMPANY_ID']))
				{
					$arFields['COMPANY_ID'] = $srcElement['~COMPANY_ID'];
				}
			}
			else
			{
				$arFields['COMPANY_ID'] = 0;
			}

			if (isset($_POST['SECONDARY_ENTITY_IDS']))
			{
				$contactIDs = explode(',', $_POST['SECONDARY_ENTITY_IDS']);

				$effectiveContactIDs = array();
				foreach($contactIDs as $contactID)
				{
					$contactID = (int)$contactID;
					if ($contactID > 0 && CCrmContact::Exists($contactID))
					{
						$effectiveContactIDs[] = $contactID;
					}
				}

				$arFields['CONTACT_BINDINGS'] = EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Contact,
					$effectiveContactIDs
				);

				if ($primaryEntityTypeID === CCrmOwnerType::Contact && $primaryEntityID > 0)
				{
					EntityBinding::markAsPrimary(
						$arFields['CONTACT_BINDINGS'],
						CCrmOwnerType::Contact,
						$primaryEntityID
					);
					//For backward compatibility
					$arFields['CONTACT_ID'] = $primaryEntityID;
				}
				else
				{
					EntityBinding::markFirstAsPrimary($arFields['CONTACT_BINDINGS']);
					//For backward compatibility
					$arFields['CONTACT_ID'] = EntityBinding::getPrimaryEntityID(
						CCrmOwnerType::Contact,
						$arFields['CONTACT_BINDINGS']
					);
				}
			}
		}
		elseif(!$bEdit)
		{
			if(isset($_REQUEST['company_id']))
			{
				$companyID = (int)$_REQUEST['company_id'];
				$arFields['COMPANY_ID'] = $companyID > 0 && CCrmCompany::CheckReadPermission($companyID, $userPermissions)
					? $companyID : 0;
			}

			if(isset($_REQUEST['contact_id']))
			{
				$contactIDs = is_array($_REQUEST['contact_id']) ? $_REQUEST['contact_id'] : explode(',', $_REQUEST['contact_id']);
				$effectiveContactIDs = array();
				foreach($contactIDs as $contactID)
				{
					$contactID = (int)$contactID;
					if($contactID > 0 && CCrmContact::CheckReadPermission($contactID, $userPermissions))
					{
						$effectiveContactIDs[] = $contactID;
					}
				}

				$arFields['CONTACT_BINDINGS'] = EntityBinding::prepareEntityBindings(
					CCrmOwnerType::Contact,
					$effectiveContactIDs
				);
				EntityBinding::markFirstAsPrimary($arFields['CONTACT_BINDINGS']);
			}
		}

		if (isset($_POST['MYCOMPANY_ID']))
		{
			$myCompanyId = (int)$_POST['MYCOMPANY_ID'];
			if ($myCompanyId > 0 && CCrmCompany::CheckReadPermission($myCompanyId))
			{
				$arFields['MYCOMPANY_ID'] = $myCompanyId;
			}
			else
				$arFields['MYCOMPANY_ID'] = 0;
		}
		elseif (isset($srcElement['~MYCOMPANY_ID']))
		{
			$arFields['MYCOMPANY_ID'] = $srcElement['~MYCOMPANY_ID'];
		}

		$personTypeId = is_array($arFields) ? CCrmQuote::ResolvePersonType($arFields, $arPersonTypes) : 0;
		$requisiteIdLinked = isset($_POST['REQUISITE_ID']) ? max((int)$_POST['REQUISITE_ID'], 0) : 0;
		$bankDetailIdLinked = isset($_POST['BANK_DETAIL_ID']) ? max((int)$_POST['BANK_DETAIL_ID'], 0) : 0;

		$mcRequisiteIdLinked = isset($_POST['MC_REQUISITE_ID']) ? max((int)$_POST['MC_REQUISITE_ID'], 0) : 0;
		$mcBankDetailIdLinked = isset($_POST['MC_BANK_DETAIL_ID']) ? max((int)$_POST['MC_BANK_DETAIL_ID'], 0) : 0;

		$leadID = 0;
		if (isset($_POST['LEAD_ID']))
		{
			$leadID = (int)$_POST['LEAD_ID'];
		}
		elseif (isset($arResult['ELEMENT']['LEAD_ID']))
		{
			$leadID = (int)$arResult['ELEMENT']['LEAD_ID'];
		}
		if ($leadID <= 0 || !CCrmLead::CheckReadPermission($leadID))
		{
			$leadID = 0;
		}
		$arFields['LEAD_ID'] = $leadID;
		unset($leadID);

		$dealID = 0;
		if (isset($_POST['DEAL_ID']))
		{
			$dealID = (int)$_POST['DEAL_ID'];
		}
		elseif (isset($arResult['ELEMENT']['DEAL_ID']))
		{
			$dealID = (int)$arResult['ELEMENT']['DEAL_ID'];
		}
		if ($dealID <= 0 || !CCrmDeal::CheckReadPermission($dealID))
		{
			$dealID = 0;
		}
		$arFields['DEAL_ID'] = $dealID;
		unset($dealID);
		
		// storage type
		$storageTypeId = isset($_POST['storageTypeId']) ? intval($_POST['storageTypeId']) : CCrmQuoteStorageType::Undefined;
		if ($storageTypeId === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($storageTypeId))
		{
			if (!$bEdit)
			{
				$storageTypeId = CCrmQuote::GetDefaultStorageTypeID();
			}
			else
			{
				$storageTypeId = isset($srcElement['~STORAGE_TYPE_ID'])
					? (int)$srcElement['~STORAGE_TYPE_ID'] : CCrmQuoteStorageType::Undefined;
				if ($storageTypeId === CCrmQuoteStorageType::Undefined
					|| !CCrmQuoteStorageType::IsDefined($storageTypeId))
				{
					$storageTypeId = CCrmQuote::GetDefaultStorageTypeID();
				}
			}
		}
		$arFields['STORAGE_TYPE_ID'] = $arFields['~STORAGE_TYPE_ID'] = $storageTypeId;

		// files
		$arPermittedElements = array();
		if ($storageTypeId === CCrmQuoteStorageType::File)
		{
			$arPermittedFiles = array();
			$arUserFiles = isset($_POST['files']) && is_array($_POST['files']) ? $_POST['files'] : array();
			if (!empty($arUserFiles) || $bEdit)
			{
				$arPreviousFiles = array();
				if ($bEdit)
				{
					CCrmQuote::PrepareStorageElementIDs($srcElement);
					$arPreviousFiles = $srcElement['~STORAGE_ELEMENT_IDS'];
					if (is_array($arPreviousFiles) && !empty($arPreviousFiles))
					{
						$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
					}
				}

				$uploadControlCID = isset($_POST['uploadControlCID']) ? strval($_POST['uploadControlCID']) : '';
				if ($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
				{
					$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
					if (!empty($uploadedFiles))
					{
						$arPermittedFiles = array_merge(
							array_intersect($arUserFiles, $uploadedFiles),
							$arPermittedFiles
						);
					}
					unset($uploadedFiles);
				}

				$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
				unset($arPreviousFiles);
			}
			unset($arPermittedFiles, $arUserFiles);
		}
		else
		{
			$fileKey = $storageTypeId === CCrmQuoteStorageType::Disk ? 'diskfiles' : 'webdavelements';
			$arFileIds = isset($_POST[$fileKey]) && is_array($_POST[$fileKey]) ? $_POST[$fileKey] : array();
			if (!empty($arFileIds) || $bEdit)
			{
				$prevStorageElementIDs = array();
				if (($bEdit || $bCopy) && is_array($arResult['ELEMENT']['STORAGE_ELEMENT_IDS']))
				{
					$prevStorageElementIDs = $arResult['ELEMENT']['STORAGE_ELEMENT_IDS'];
				}

				$persistentStorageElementIDs = array_intersect($prevStorageElementIDs, $arFileIds);
				$addedStorageElementIDs = StorageManager::filterFiles(
					array_diff($arFileIds, $prevStorageElementIDs),
					$storageTypeId
				);
				$arFields['STORAGE_ELEMENT_IDS'] = array_merge($persistentStorageElementIDs, $addedStorageElementIDs);
			}
			unset($arFileIds);
		}
		unset($srcElement);

		// person type
		$arFields['PERSON_TYPE_ID'] = 0;
		if (isset($arPersonTypes['CONTACT']) && (!isset($arFields['COMPANY_ID']) || intval($arFields['COMPANY_ID']) <= 0))
			$arFields['PERSON_TYPE_ID'] = (int)$arPersonTypes['CONTACT'];
		else if (isset($arPersonTypes['COMPANY']) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
			$arFields['PERSON_TYPE_ID'] = (int)$arPersonTypes['COMPANY'];

		$processProductRows = array_key_exists($productDataFieldName, $_POST);
		$arProd = array();
		$taxList = array();
		if ($processProductRows)
		{
			$prodJson = isset($_POST[$productDataFieldName]) ? strval($_POST[$productDataFieldName]) : '';
			$arProd = $arResult['PRODUCT_ROWS'] = $prodJson <> '' ? CUtil::JsObjectToPhp($prodJson) : array();

			if (count($arProd) > 0)
			{
				if ($bCopy || $bCreateFrom)
				{
					for($rowInd = 0, $rowQty = count($arProd); $rowInd < $rowQty; $rowInd++)
					{
						unset($arProd[$rowInd]['ID']);
					}
				}

				// SYNC OPPORTUNITY WITH PRODUCT ROW SUM TOTAL
				//$arFields['OPPORTUNITY'] = CCrmProductRow::GetTotalSum($arProd);
				$enableSaleDiscount = false;
				$calcOptions = array();
				if ($bTaxMode)
					$calcOptions['LOCATION_ID'] = $arFields['LOCATION_ID'];
				$result = CCrmSaleHelper::Calculate($arProd, $currencyID, $personTypeId, $enableSaleDiscount, SITE_ID, $calcOptions);

				$arFields['OPPORTUNITY'] = isset($result['PRICE']) ? round(doubleval($result['PRICE']), 2) : 1.0;
				$arFields['TAX_VALUE'] = isset($result['TAX_VALUE']) ? round(doubleval($result['TAX_VALUE']), 2) : 0.0;
			}
		}

		// Product row settings
		$productRowSettings = array();
		$productRowSettingsFieldName = $productDataFieldName.'_SETTINGS';
		if (array_key_exists($productRowSettingsFieldName, $_POST))
		{
			$settingsJson = isset($_POST[$productRowSettingsFieldName]) ? strval($_POST[$productRowSettingsFieldName]) : '';
			$arSettings = $settingsJson <> '' ? CUtil::JsObjectToPhp($settingsJson) : array();
			if (is_array($arSettings))
			{
				$productRowSettings['ENABLE_DISCOUNT'] = isset($arSettings['ENABLE_DISCOUNT']) ? $arSettings['ENABLE_DISCOUNT'] === 'Y' : false;
				$productRowSettings['ENABLE_TAX'] = isset($arSettings['ENABLE_TAX']) ? $arSettings['ENABLE_TAX'] === 'Y' : false;
			}
		}
		unset($productRowSettingsFieldName, $settingsJson, $arSettings);

		$USER_FIELD_MANAGER->EditFormAddFields(CCrmQuote::$sUFEntityID, $arFields);
		if ($conversionWizard !== null)
		{
			$conversionWizard->prepareDataForSave(CCrmOwnerType::Quote, $arFields);
		}

		CCrmQuote::RewriteClientFields($arFields, false);
		CCrmQuote::rewriteClientFieldsFromRequisite($arFields, $requisiteIdLinked, false);

		$arResult['ERROR_MESSAGE'] = '';

		if (!$CCrmQuote->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false))
		{
			if (!empty($CCrmQuote->LAST_ERROR))
				$arResult['ERROR_MESSAGE'] .= $CCrmQuote->LAST_ERROR;
			else
				$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
		}

		if (empty($arResult['ERROR_MESSAGE']))
		{
			$DB->StartTransaction();

			$bSuccess = false;
			if ($bEdit)
			{
				$bSuccess = $CCrmQuote->Update($arResult['ELEMENT']['ID'], $arFields, true, true, array('REGISTER_SONET_EVENT' => true));
			}
			else
			{
				$ID = $CCrmQuote->Add($arFields, true, array('REGISTER_SONET_EVENT' => false));
				$bSuccess = $ID !== false;
				if ($bSuccess)
				{
					$arResult['ELEMENT']['ID'] = $ID;
				}
			}

			if ($bSuccess)
			{
				if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
				{
					\Bitrix\Crm\Requisite\EntityLink::register(
						CCrmOwnerType::Quote, $arResult['ELEMENT']['ID'],
						$requisiteIdLinked, $bankDetailIdLinked,
						$mcRequisiteIdLinked, $mcBankDetailIdLinked
					);
				}
				else
				{
					\Bitrix\Crm\Requisite\EntityLink::unregister(CCrmOwnerType::Quote, $arResult['ELEMENT']['ID']);
				}
			}

			if ($bSuccess)
			{
				// Save settings
				if (is_array($productRowSettings) && count($productRowSettings) > 0)
				{
					$arSettings = CCrmProductRow::LoadSettings(CCrmQuote::OWNER_TYPE, $arResult['ELEMENT']['ID']);
					foreach ($productRowSettings as $k => $v)
						$arSettings[$k] = $v;
					CCrmProductRow::SaveSettings(CCrmQuote::OWNER_TYPE, $arResult['ELEMENT']['ID'], $arSettings);
				}
				unset($arSettings);
			}

			if ($bSuccess
				//&& !$isExternal // Product rows of external quote are read only
				&& $processProductRows
				&& ($bEdit || !empty($arProd)))
			{
				// Suppress owner synchronization
				$bSuccess = $CCrmQuote::SaveProductRows($arResult['ELEMENT']['ID'], $arProd, true, true, false);
				if (!$bSuccess)
				{
					$arResult['ERROR_MESSAGE'] = GetMessage('PRODUCT_ROWS_SAVING_ERROR');
				}
			}

			if ($bSuccess)
			{
				$DB->Commit();
			}
			else
			{
				$DB->Rollback();
				$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
			}
		}

		$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

		if (!empty($arResult['ERROR_MESSAGE']))
		{
			ShowError($arResult['ERROR_MESSAGE']);
			$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
				array_merge(array('ID' => $ID), $arFields),
				CCrmQuote::GetFields()
			);
		}
		else
		{
			if (isset($_POST['apply']))
			{
				if (CCrmQuote::CheckUpdatePermission($ID))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_QUOTE_EDIT'],
							array('quote_id' => $ID)
						)
					);
				}
			}
			elseif (isset($_POST['saveAndAdd']))
			{
				LocalRedirect(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_QUOTE_EDIT'],
						array('quote_id' => 0)
					)
				);
			}
			elseif (isset($_POST['saveAndView']))
			{
				if (CCrmQuote::CheckReadPermission($ID))
				{
					LocalRedirect(
						empty($arResult['QUOTE_REFERER']) ?
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_QUOTE_SHOW'],
								array('quote_id' => $ID)
							)
							:
							$arResult['QUOTE_REFERER']
					);
				}
			}
			elseif (isset($_POST['continue']) && $conversionWizard !== null)
			{
				$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::QuoteName, $ID);
				$url = $conversionWizard->getRedirectUrl();
				if ($url !== '')
				{
					LocalRedirect($url);
				}
			}

			// save
			if (isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
			{
				$info = $arResult['INFO'] = CCrmEntitySelectorHelper::PrepareEntityInfo(
					CCrmOwnerType::QuoteName,
					$ID,
					array(
						'ENTITY_EDITOR_FORMAT' => true,
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
						'entityTypeName' => CCrmOwnerType::QuoteName,
						'entityInfo' => $info
					)
				);
				$this->IncludeComponentTemplate('event');
				return;
			}

			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_LIST'], array()));
		}
	}
}
elseif (isset($_GET['delete']) && check_bitrix_sessid())
{
	if ($bEdit)
	{
		$arResult['ERROR_MESSAGE'] = '';
		if (!$CCrmQuote->cPerms->CheckEnityAccess('QUOTE', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]))
			$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';

		if (empty($arResult['ERROR_MESSAGE']) && !$CCrmQuote->Delete($arResult['ELEMENT']['ID']))
		{
			/** @var CApplicationException $ex */
			$ex = $APPLICATION->GetException();
			$arResult['ERROR_MESSAGE'] = ($ex instanceof CApplicationException)
				? $ex->GetString() : GetMessage('CRM_DELETE_ERROR');
		}

		if (empty($arResult['ERROR_MESSAGE']))
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_LIST']));
		else
			ShowError($arResult['ERROR_MESSAGE']);
		return;
	}
	else
	{
		ShowError(GetMessage('CRM_DELETE_ERROR'));
		return;
	}
}

if ($conversionWizard !== null && $conversionWizard->hasOriginUrl())
{
	$arResult['BACK_URL'] = $conversionWizard->getOriginUrl();
}
else
{
	$arResult['BACK_URL'] = !empty($arResult['INVOICE_REFERER']) ? $arResult['INVOICE_REFERER'] : $arParams['PATH_TO_QUOTE_LIST'];
}

$arResult['STATUS_LIST'] = array();
$arResult['~STATUS_LIST'] = CCrmStatus::GetStatusList('QUOTE_STATUS');
foreach ($arResult['~STATUS_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmQuote->cPerms->GetPermType('QUOTE', $bEdit ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EDIT'] = $bEdit;

$arResult['FIELDS'] = array();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_quote_info',
	'name' => GetMessage('CRM_SECTION_QUOTE_INFO'),
	'type' => 'section'
);

$quoteNumberField = array(
	'id' => 'QUOTE_NUMBER',
	'name' => GetMessage('CRM_QUOTE_FIELD_QUOTE_NUMBER'),
	'params' => array('size' => 100),
	'value' => isset($arResult['ELEMENT']['~QUOTE_NUMBER']) ? $arResult['ELEMENT']['~QUOTE_NUMBER'] : '',
	'type' => 'text',
	'visible' => $bEdit
);
$arResult['FIELDS']['tab_1'][] = $quoteNumberField;

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_QUOTE_FIELD_TITLE_QUOTE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'text'/*,
	'required' => true*/
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_STATUS_ID'),
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
			)
);

$currencyID = CCrmCurrency::GetBaseCurrencyID();
if (isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_CURRENCY_ID'),
	'type' => 'list',
	'params' => array('sale_order_marker' => 'Y'),
	'items' => $arResult['CURRENCY_LIST'],
	'value' => $currencyID
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_QUOTE_FIELD_OPPORTUNITY'),
	'params' => array('size' => 21, 'sale_order_marker' => 'Y'),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : '',
	'type' => 'text'
);

$arResult['RESPONSIBLE_SELECTOR_PARAMS'] = array(
	'NAME' => 'crm_quote_edit_resonsible',
	'INPUT_NAME' => 'ASSIGNED_BY_ID',
	'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
	'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => $arResult['RESPONSIBLE_SELECTOR_PARAMS'],
	'name' => GetMessage('CRM_QUOTE_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

//Fix for issue #36848
$beginDate = isset($arResult['ELEMENT']['BEGINDATE']) ? $arResult['ELEMENT']['BEGINDATE'] : '';
$closeDate = isset($arResult['ELEMENT']['CLOSEDATE']) ? $arResult['ELEMENT']['CLOSEDATE'] : '';

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_QUOTE_FIELD_BEGINDATE'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'date_link',
	'value' => $beginDate !== '' ? ConvertTimeStamp(MakeTimeStamp($beginDate), 'SHORT', SITE_ID) : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_QUOTE_FIELD_CLOSEDATE'),
	'type' => 'date_short',
	'value' => $closeDate !== '' ? ConvertTimeStamp(MakeTimeStamp($closeDate), 'SHORT', SITE_ID) : '',
	'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date')
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LEAD_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_LEAD_ID'),
	'type' => 'crm_entity_selector',
	'componentParams' => array(
		'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "QUOTE_{$arParams['ELEMENT_ID']}" : 'NEWQUOTE',
		'ENTITY_TYPE' => 'LEAD',
		'INPUT_NAME' => 'LEAD_ID',
		'NEW_INPUT_NAME' => '',
		'INPUT_VALUE' => isset($arResult['ELEMENT']['LEAD_ID']) ? $arResult['ELEMENT']['LEAD_ID'] : '',
		'FORM_NAME' => $arResult['FORM_ID'],
		'MULTIPLE' => 'N',
		'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
	)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DEAL_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_DEAL_ID'),
	'type' => 'crm_entity_selector',
	'componentParams' => array(
		'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "QUOTE_{$arParams['ELEMENT_ID']}" : 'NEWQUOTE',
		'ENTITY_TYPE' => 'DEAL',
		'INPUT_NAME' => 'DEAL_ID',
		'NEW_INPUT_NAME' => '',
		'INPUT_VALUE' => isset($arResult['ELEMENT']['DEAL_ID']) ? $arResult['ELEMENT']['DEAL_ID'] : '',
		'FORM_NAME' => $arResult['FORM_ID'],
		'MULTIPLE' => 'N',
		'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
	)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_QUOTE_FIELD_OPENED'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : (QuoteSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N'),
	'title' => GetMessage('CRM_QUOTE_FIELD_OPENED_TITLE')
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CLIENT_INFO'),
	'type' => 'section'
);

$companyID = isset($arResult['ELEMENT']['COMPANY_ID']) ? (int)$arResult['ELEMENT']['COMPANY_ID'] : 0;
if (isset($arResult['ELEMENT']['CONTACT_BINDINGS']))
{
	$contactBindings = $arResult['ELEMENT']['CONTACT_BINDINGS'];
}
elseif ($arParams['ELEMENT_ID'] > 0)
{
	$contactBindings = \Bitrix\Crm\Binding\QuoteContactTable::getQuoteBindings($arParams['ELEMENT_ID']);
}
elseif (isset($arResult['ELEMENT']['CONTACT_ID']))
{
	//For backward compatibility
	$contactBindings = EntityBinding::prepareEntityBindings(
		CCrmOwnerType::Contact,
		array($arResult['ELEMENT']['CONTACT_ID'])
	);
}
else
{
	$contactBindings = array();
}

if ($companyID > 0 || empty($contactBindings))
{
	$primaryEntityTypeName = CCrmOwnerType::CompanyName;
	$primaryEntityID = $companyID;
}
else
{
	$primaryEntityTypeName = CCrmOwnerType::ContactName;
	$primaryBinding = EntityBinding::findPrimaryBinding($contactBindings);
	if ($primaryBinding === null)
	{
		$primaryBinding = $contactBindings[0];
	}
	$primaryEntityID = $primaryBinding['CONTACT_ID'];
}

$arResult['CLIENT_SELECTOR_ID'] = 'CLIENT';
$arResult['FIELDS']['tab_1'][] = array(
	'id' => $arResult['CLIENT_SELECTOR_ID'],
	'name' => GetMessage('CRM_QUOTE_EDIT_FIELD_CLIENT'),
	'type' => 'crm_composite_client_selector',
	'componentParams' => array(
		'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "QUOTE_{$arParams['ELEMENT_ID']}" : 'NEWQUOTE',
		'OWNER_TYPE' => CCrmOwnerType::QuoteName,
		'OWNER_ID' => $arParams['ELEMENT_ID'],
		'PRIMARY_ENTITY_TYPE' => $primaryEntityTypeName,
		'PRIMARY_ENTITY_ID' => $primaryEntityID,
		'SECONDARY_ENTITY_TYPE' => CCrmOwnerType::ContactName,
		'SECONDARY_ENTITY_IDS' => EntityBinding::prepareEntityIDs(CCrmOwnerType::Contact, $contactBindings),
		'CUSTOM_MESSAGES' => array(
			'SECONDARY_ENTITY_HEADER' => GetMessage('CRM_QUOTE_EDIT_CONTACT_SELECTOR_HEADER'),
			'SECONDARY_ENTITY_MARKING_TITLE' => GetMessage('CRM_QUOTE_EDIT_CONTACT_MARKING_TITLE'),
		),
		'PRIMARY_ENTITY_TYPE_INPUT_NAME' => 'PRIMARY_ENTITY_TYPE',
		'PRIMARY_ENTITY_INPUT_NAME' => 'PRIMARY_ENTITY_ID',
		'SECONDARY_ENTITIES_INPUT_NAME' => 'SECONDARY_ENTITY_IDS',
		'REQUISITE_INPUT_NAME' => 'REQUISITE_ID',
		'REQUISITE_ID' => $requisiteIdLinked,
		'BANK_DETAIL_INPUT_NAME' => 'BANK_DETAIL_ID',
		'BANK_DETAIL_ID' => $bankDetailIdLinked,
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.edit/ajax.php?'.bitrix_sessid_get(),
		'REQUISITE_SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.edit/settings.php?'.bitrix_sessid_get(),
		'FORM_NAME' => $arResult['FORM_ID'],
		'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
		'ENTITY_SELECTOR_SEARCH_OPTIONS' => array(
			'NOT_MY_COMPANIES' => 'Y'
		)
	)
);

if ($bTaxMode)
{
	// CLIENT LOCATION
	$sLocationHtml = '';

	$locValue = isset($arResult['ELEMENT']['LOCATION_ID']) ? $arResult['ELEMENT']['LOCATION_ID'] : '';

	ob_start();

	CSaleLocation::proxySaleAjaxLocationsComponent(
		array(
			'AJAX_CALL' => 'N',
			'COUNTRY_INPUT_NAME' => 'LOC_COUNTRY',
			'REGION_INPUT_NAME' => 'LOC_REGION',
			'CITY_INPUT_NAME' => 'LOC_CITY',
			'CITY_OUT_LOCATION' => 'Y',
			'LOCATION_VALUE' => $locValue,
			'ORDER_PROPS_ID' => 'QUOTE_'.$arResult['ELEMENT']['ID'],
			'ONCITYCHANGE' => 'CrmProductRowSetLocation',
			'SHOW_QUICK_CHOOSE' => 'N'/*,
			'SIZE1' => $arProperties['SIZE1']*/
		),
		array(
			"CODE" => $locValue,
			"ID" => "",
			"PROVIDE_LINK_BY" => "code",
			"JS_CALLBACK" => 'CrmProductRowSetLocation'
		),
		'popup'
	);

	$sLocationHtml = ob_get_contents();
	ob_end_clean();
	$locationField = array(
		'id' => 'LOCATION_ID',
		'name' => GetMessage('CRM_QUOTE_FIELD_LOCATION_ID'),
		'type' => 'custom',
		'value' =>  $sLocationHtml.
			'<div>'.
			'<span class="bx-crm-edit-content-location-description">'.
			GetMessage('CRM_QUOTE_FIELD_LOCATION_ID_DESCRIPTION').
			'</span>'.
			'</div>',
		'persistent' => true
	);
	$arResult['FIELDS']['tab_1'][] = $locationField;
	$arResult['FORM_FIELDS_TO_ADD']['LOCATION_ID'] = $locationField;
	unset($locationField);
}

// person type
$arResult['ELEMENT']['PERSON_TYPE_ID'] = 0;
$arResult['PERSON_TYPE'] = 'CONTACT';
if (isset($arPersonTypes['CONTACT']) && (!isset($arResult['ELEMENT']['COMPANY_ID']) || intval($arResult['ELEMENT']['COMPANY_ID']) <= 0))
{
	$arResult['ELEMENT']['PERSON_TYPE_ID'] = intval($arPersonTypes['CONTACT']);
}
else if (isset($arPersonTypes['COMPANY']) && isset($arResult['ELEMENT']['COMPANY_ID']) && intval($arResult['ELEMENT']['COMPANY_ID']) > 0)
{
	$arResult['ELEMENT']['PERSON_TYPE_ID'] = intval($arPersonTypes['COMPANY']);
	$arResult['PERSON_TYPE'] = 'COMPANY';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_quote_seller',
	'name' => GetMessage('CRM_SECTION_QUOTE_SELLER'),
	'type' => 'section'
);

// my company details
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'MYCOMPANY_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_MYCOMPANY_ID1'),
	'type' => 'crm_single_client_selector',
	'componentParams' => array(
		'CONTEXT' => $bEdit ? "QUOTE_{$arResult['ELEMENT']['ID']}" : 'NEWQUOTE',
		'OWNER_TYPE' => CCrmOwnerType::QuoteName,
		'OWNER_ID' => $bEdit ? $arResult['ELEMENT']['ID'] : 0,
		'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
		'ENTITY_ID' => isset($arResult['ELEMENT']['MYCOMPANY_ID']) ? (int)$arResult['ELEMENT']['MYCOMPANY_ID'] : 0,
		'ENTITY_INPUT_NAME' => 'MYCOMPANY_ID',
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

// FILES
if ($arResult['ENABLE_WEBDAV'] || $arResult['ENABLE_DISK'])
{
	$sVal = '<div id="'.$arResult['FILES_FIELD_CONTAINER_ID'].'" class="bx-crm-dialog-activity-webdav-container"></div>';
}
else
{
	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:main.file.input',
		'',
		array(
			'MODULE_ID' => 'crm',
			'MAX_FILE_SIZE' => 20971520,
			'ALLOW_UPLOAD' => 'A',
			'CONTROL_ID' => $arResult['PREFIX'].'_uploader',
			'INPUT_NAME' => $arResult['PREFIX'].'_saved_file',
			'INPUT_NAME_UNSAVED' => $arResult['PREFIX'].'_new_file',
			'INPUT_VALUE' => $arResult['ELEMENT']['STORAGE_ELEMENT_IDS']
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$sVal = '<div id="'.$arResult['FILES_FIELD_CONTAINER_ID'].'" class="bx-crm-dialog-activity-webdav-container">'.
		'<div id="'.$arResult['PREFIX'].'_upload_container"'.
		($arResult['ENABLE_WEBDAV'] ? ' style="display:none;"' : '').'>'.$sVal.'</div></div>';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'FILES',
	'name' => GetMessage('CRM_QUOTE_FIELD_FILES'),
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTENT',
	'name' => GetMessage('CRM_QUOTE_FIELD_CONTENT'),
	'params' => array(),
	'type' => 'lhe',
	'componentParams' => array(
		'inputName' => 'CONTENT',
		'inputId' => 'CONTENT',
		'height' => '360',
		'content' => isset($arResult['ELEMENT']['~CONTENT']) ? $arResult['ELEMENT']['~CONTENT'] : '',
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
	)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TERMS',
	'name' => GetMessage('CRM_QUOTE_FIELD_TERMS'),
	'params' => array(),
	'type' => 'lhe',
	'componentParams' => array(
		'inputName' => 'TERMS',
		'inputId' => 'TERMS',
		'height' => '90',
		'content' => isset($arResult['ELEMENT']['~TERMS']) ? $arResult['ELEMENT']['~TERMS'] : '',
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
	)
);

// PRODUCT_ROWS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);
$sProductsHtml = '';

$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'quote_'.strval($arParams['ELEMENT_ID']) : 'new_quote').'_product_editor';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => CCrmQuote::OWNER_TYPE,
	'PERMISSION_TYPE' => /*$isExternal ? 'READ' : */'WRITE',
	'INIT_EDITABLE' => 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['LOCATION_ID'] : '',
	'CLIENT_SELECTOR_ID' => $arResult['CLIENT_SELECTOR_ID'],
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
$sProductsHtml .= ob_get_contents();
ob_end_clean();
unset($componentSettings);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_QUOTE_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'vertical_container',
	'value' => $sProductsHtml
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_QUOTE_FIELD_COMMENTS'),
	'params' => array(),
	'type' => 'lhe',
	'componentParams' => array(
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
	)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$icnt = count($arResult['FIELDS']['tab_1']);

if ($conversionWizard !== null)
{
	$useUserFieldsFromForm = true;
	$fileViewer = new \Bitrix\Crm\Conversion\EntityConversionFileViewer(
		CCrmOwnerType::Quote,
		CCrmOwnerType::Deal,
		$arResult['DEAL_ID']
	);
}
else
{
	$useUserFieldsFromForm = $bVarsFromForm;
	$fileViewer = new \Bitrix\Crm\UserField\FileViewer(CCrmOwnerType::Quote, $arResult['ELEMENT']['ID']);
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

if (!$arResult['ENABLE_WEBDAV'])
{
	$arResult['WEBDAV_SELECT_URL'] = $arResult['WEBDAV_UPLOAD_URL'] = $arResult['WEBDAV_SHOW_URL'] = '';
}
else
{
	$webDavPaths = CCrmWebDavHelper::GetPaths();
	$arResult['WEBDAV_SELECT_URL'] = isset($webDavPaths['PATH_TO_FILES'])
		? $webDavPaths['PATH_TO_FILES'] : '';
	$arResult['WEBDAV_UPLOAD_URL'] = isset($webDavPaths['ELEMENT_UPLOAD_URL'])
		? $webDavPaths['ELEMENT_UPLOAD_URL'] : '';
	$arResult['WEBDAV_SHOW_URL'] = isset($webDavPaths['ELEMENT_SHOW_INLINE_URL'])
		? $webDavPaths['ELEMENT_SHOW_INLINE_URL'] : '';
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.quote/include/nav.php');
?>
