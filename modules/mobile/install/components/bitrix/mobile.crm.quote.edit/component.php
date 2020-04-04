<?php
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;

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
$CCrmQuote = new CCrmQuote();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

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

$conversionWizard = null;
if (isset($_REQUEST['conv_deal_id']) && $_REQUEST['conv_deal_id'] > 0)
{
	$srcDealId = intval($_REQUEST['conv_deal_id']);
	if($srcDealId > 0)
	{
		$conversionWizard = \Bitrix\Crm\Conversion\DealConversionWizard::load($srcDealId);
		if($conversionWizard !== null)
		{
			$arResult['DEAL_ID'] = $srcDealId;
		}
	}
}

global $USER_FIELD_MANAGER, $DB, $USER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmQuote::$sUFEntityID);

$bEdit = false;
$bCopy = false;
$bVarsFromForm = false;

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['quote_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_REQUEST['quote_id']);
}
$arResult['ENTITY_ID'] = $arParams['ELEMENT_ID'] = $entityID;

if (!empty($arParams['ELEMENT_ID']))
	$bEdit = true;
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}

$arResult["IS_EDIT_PERMITTED"] = false;
$arResult["IS_VIEW_PERMITTED"] = false;
$arResult["IS_DELETE_PERMITTED"] = CCrmQuote::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if($bEdit)
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmQuote::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"])
	{
		$arResult["IS_VIEW_PERMITTED"] = CCrmQuote::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
}
elseif($bCopy)
{
	$arResult["IS_VIEW_PERMITTED"] = CCrmQuote::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
}
else
{
	$arResult["IS_EDIT_PERMITTED"] = CCrmQuote::CheckCreatePermission($userPermissions);
}

if(!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"])
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arPersonTypes = $arResult['PERSON_TYPE_IDS'] = CCrmPaySystem::getPersonTypeIDs();

$bTaxMode = CCrmTax::isTaxMode();
$arResult['TAX_MODE'] = $bTaxMode ? 'Y' : 'N';

if($bEdit)
{
	CCrmQuote::PrepareConversionPermissionFlags($arParams['ELEMENT_ID'], $arResult, $CCrmQuote->cPerms);
}

$requisiteIdLinked = 0;
$bankDetailIdLinked = 0;

$arFields = null;
if ($conversionWizard !== null)
{
	$arResult['MODE'] = 'CONVERT';

	$arFields = array('ID' => 0);
	$conversionWizard->prepareDataForEdit(CCrmOwnerType::Quote, $arFields, true);
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend(CCrmOwnerType::Quote);

	if(isset($arFields['PRODUCT_ROWS']))
	{
		$arResult['PRODUCT_ROWS'] = $arFields['PRODUCT_ROWS'];
	}
}
elseif ($bEdit || $bCopy)
{
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => $arParams["RESTRICTED_MODE"] ? 'READ' : 'WRITE'
	);
	$obFields = CCrmQuote::GetList(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if(!is_array($arFields))
	{
		ShowError(GetMessage('CRM_QUOTE_EDIT_NOT_FOUND', array("#ID#" => $arParams['ELEMENT_ID'])));
		return;
	}

	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}
	else
		$arEntityAttr = $CCrmQuote->cPerms->GetEntityAttr('QUOTE', array($arParams['ELEMENT_ID']));
	if ($bCopy)
	{
		if(isset($arFields['QUOTE_NUMBER']))
			unset($arFields['QUOTE_NUMBER']);

		if(isset($arFields['~QUOTE_NUMBER']))
			unset($arFields['~QUOTE_NUMBER']);

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
		if(isset($arFields['~TAX_VALUE']))
		{
			$arFields['~TAX_VALUE'] = $arFields['TAX_VALUE'] = floatval($arFields['~TAX_VALUE']);
		}
		if(isset($arFields['~TAX_VALUE_ACCOUNT']))
		{
			$arFields['~TAX_VALUE_ACCOUNT'] = $arFields['TAX_VALUE_ACCOUNT'] = floatval($arFields['~TAX_VALUE_ACCOUNT']);
		}
	}
}
else
{
	$arResult['MODE'] = 'CREATE';

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
		if(isset($extVals['PRODUCT_ROWS']) && is_array($extVals['PRODUCT_ROWS']))
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
		CUtil::decodeURIComponent($arFields['~TITLE']);
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
			$arLeadProductRowSettings = CCrmProductRow::LoadSettings('D', $leadId);
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
	$requisite = new \Bitrix\Crm\EntityRequisite();
	if ($bEdit || $bCopy)
	{
		if ($arParams['ELEMENT_ID'] > 0)
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Quote, 'ENTITY_ID' => $arParams['ELEMENT_ID']);
	}
	else if ($conversionWizard !== null)
	{
		if (isset($arFields['DEAL_ID']) && $arFields['DEAL_ID'] > 0)
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $arFields['DEAL_ID']);
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

// storage type
$storageTypeId = isset($arFields['STORAGE_TYPE_ID'])
	? (int)$arFields['STORAGE_TYPE_ID'] : CCrmQuoteStorageType::Undefined;
if($storageTypeId === CCrmQuoteStorageType::Undefined
	|| !CCrmQuoteStorageType::IsDefined($storageTypeId))
{
	$storageTypeId = CCrmQuote::GetDefaultStorageTypeID();
}
$arFields['STORAGE_TYPE_ID'] = $arFields['~STORAGE_TYPE_ID'] = $storageTypeId;
$arResult['ENABLE_DISK'] = $storageTypeId === StorageType::Disk;
$arResult['ENABLE_WEBDAV'] = $storageTypeId === StorageType::WebDav;

// storage elements
CCrmQuote::PrepareStorageElementIDs($arFields);

// Determine person type
$personTypeId = 0;
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if (intval($arFields['COMPANY_ID']) > 0)
		$personTypeId = $arPersonTypes['COMPANY'];
	elseif (intval($arFields['CONTACT_ID']) > 0)
		$personTypeId = $arPersonTypes['CONTACT'];
}

$arResult['ELEMENT'] = is_array($arFields) ? $arFields : null;
unset($arFields);

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_QUOTE_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_QUOTE_LIST_V12';
$arResult['FILES_FIELD_CONTAINER_ID'] = $arResult['FORM_ID'].'_FILES_CONTAINER';
$arResult['FORM_CUSTOM_HTML'] = '';


$productDataFieldName = $arResult["productDataFieldName"] = 'QUOTE_PRODUCT_DATA';

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
	if(isset($_POST['save']) || isset($_POST['continue']) && $arResult["IS_EDIT_PERMITTED"])
	{
		CUtil::JSPostUnescape();

		$content = isset($_POST['CONTENT']) ? trim($_POST['CONTENT']) : '';
		$terms = isset($_POST['TERMS']) ? trim($_POST['TERMS']) : '';
		$comments = isset($_POST['COMMENTS']) ? trim($_POST['COMMENTS']) : '';
		$bSanContent = ($content !== '' && strpos($content, '<'));
		$bSanTerms = ($terms !== '' && strpos($terms, '<'));
		$bSanComments = ($comments !== '' && strpos($comments, '<'));
		if ($bSanContent || $bSanTerms || $bSanComments)
		{
			$sanitizer = new CBXSanitizer();
			$sanitizer->ApplyDoubleEncode(false);
			$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
			//Crutch for for Chrome line break behaviour in HTML editor.
			$sanitizer->AddTags(array('div' => array(), 'span'=> array('style')));
			$sanitizer->AddTags(array('a' => array('href', 'title', 'name', 'style', 'alt', 'target')));

			if($bSanContent)
				$content = $sanitizer->SanitizeHtml($content);
			if($bSanTerms)
				$terms = $sanitizer->SanitizeHtml($terms);
			if ($bSanComments)
				$comments = $sanitizer->SanitizeHtml($comments);
		}
		unset($bSanContent, $bSanTerms, $bSanComments);

		$arFields = array(
			'TITLE' => trim($_POST['TITLE']),
			'CONTENT' => $content,
			'CONTENT_TYPE' => CCrmContentType::Html,
			'TERMS' => $terms,
			'TERMS_TYPE' => CCrmContentType::Html,
			'COMMENTS' => $comments,
			'COMMENTS_TYPE' => CCrmContentType::Html,
			'STATUS_ID' => trim($_POST['STATUS_ID']),
			'ASSIGNED_BY_ID' => (int)(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID'])
		);

		if ($bTaxMode)
		{
			$arFields['LOCATION_ID'] = $_POST['LOC_CITY'];
		}

		if ($bEdit)
			$arFields['QUOTE_NUMBER'] = trim($_POST['QUOTE_NUMBER']);

		$arSrcElement = ($bEdit || $bCopy) ? $arResult['ELEMENT'] : array();

		if(isset($_POST['OPENED']))
		{
			$arFields['OPENED'] = strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
		}
		elseif(isset($arSrcElement['OPENED']))
		{
			$arFields['OPENED'] = $arSrcElement['OPENED'];
		}
		elseif(!$bEdit && !$bCopy)
		{
			$arFields['OPENED'] = \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
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

		if(isset($_POST['TAX_VALUE']))
		{
			$arFields['TAX_VALUE'] = trim($_POST['TAX_VALUE']);
		}
		elseif(isset($arSrcElement['TAX_VALUE']))
		{
			$arFields['TAX_VALUE'] = $arSrcElement['TAX_VALUE'];
		}

		if(isset($_POST['CURRENCY_ID']))
		{
			$arFields['CURRENCY_ID'] = $_POST['CURRENCY_ID'];
		}
		elseif(isset($arSrcElement['CURRENCY_ID']))
		{
			$arFields['CURRENCY_ID'] = $arSrcElement['CURRENCY_ID'];
		}

		// EXCH_RATE -->
		$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
		if(!($currencyID !== '' && CCrmCurrency::IsExists($currencyID)))
		{
			$currencyID = $arFields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		}
		$arFields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($currencyID);
		// <-- EXCH_RATE

		if(isset($_POST['CONTACT_ID']))
		{
			$contactID = intval($_POST['CONTACT_ID']);
			if($contactID > 0 && CCrmContact::CheckReadPermission($contactID))
			{
				$arFields['CONTACT_ID'] = $contactID;
			}
			else
				$arFields['CONTACT_ID'] = 0;
		}
		elseif(isset($arSrcElement['CONTACT_ID']))
		{
			$arFields['CONTACT_ID'] = $arSrcElement['CONTACT_ID'];
		}

		if(isset($_POST['COMPANY_ID']))
		{
			$companyID = intval($_POST['COMPANY_ID']);
			if($companyID > 0 && CCrmCompany::CheckReadPermission($companyID))
			{
				$arFields['COMPANY_ID'] = $companyID;
			}
			else
				$arFields['COMPANY_ID'] = 0;
		}
		elseif(isset($arSrcElement['COMPANY_ID']))
		{
			$arFields['COMPANY_ID'] = $arSrcElement['COMPANY_ID'];
		}

		if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
		{
			if (intval($arFields['COMPANY_ID']) > 0)
				$personTypeId = $arPersonTypes['COMPANY'];
			elseif (intval($arFields['CONTACT_ID']) > 0)
				$personTypeId = $arPersonTypes['CONTACT'];
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

		if(isset($_POST['LEAD_ID']))
		{
			$leadID = intval($_POST['LEAD_ID']);
			if($leadID > 0 && CCrmLead::CheckReadPermission($leadID))
			{
				$arFields['LEAD_ID'] = $leadID;
			}
		}
		elseif(isset($arSrcElement['LEAD_ID']))
		{
			$arFields['LEAD_ID'] = $arSrcElement['LEAD_ID'];
		}

		if(isset($_POST['DEAL_ID']))
		{
			$dealID = intval($_POST['DEAL_ID']);
			if($dealID > 0 && CCrmDeal::CheckReadPermission($dealID))
			{
				$arFields['DEAL_ID'] = $dealID;
			}
		}
		elseif(isset($arSrcElement['DEAL_ID']))
		{
			$arFields['DEAL_ID'] = $arSrcElement['DEAL_ID'];
		}

		// storage type
		$storageTypeId = isset($_POST['storageTypeId']) ? intval($_POST['storageTypeId']) : CCrmQuoteStorageType::Undefined;
		if($storageTypeId === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($storageTypeId))
		{
			if(!$bEdit)
			{
				$storageTypeId = CCrmQuote::GetDefaultStorageTypeID();
			}
			else
			{
				$storageTypeId = isset($arSrcElement['STORAGE_TYPE_ID'])
					? (int)$arSrcElement['STORAGE_TYPE_ID'] : CCrmQuoteStorageType::Undefined;
				if($storageTypeId === CCrmQuoteStorageType::Undefined
					|| !CCrmQuoteStorageType::IsDefined($storageTypeId))
				{
					$storageTypeId = CCrmQuote::GetDefaultStorageTypeID();
				}
			}
		}
		$arFields['STORAGE_TYPE_ID'] = $arFields['~STORAGE_TYPE_ID'] = $storageTypeId;

		// files
		$arPermittedElements = array();
		if($storageTypeId === CCrmQuoteStorageType::File)
		{
			$arPermittedFiles = array();
			$arUserFiles = isset($_POST['files']) && is_array($_POST['files']) ? $_POST['files'] : array();
			if(!empty($arUserFiles) || $bEdit)
			{
				$arPreviousFiles = array();
				if($bEdit)
				{
					CCrmQuote::PrepareStorageElementIDs($arSrcElement);
					$arPreviousFiles = $arSrcElement['STORAGE_ELEMENT_IDS'];
					if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
					{
						$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
					}
				}

				$uploadControlCID = isset($_POST['uploadControlCID']) ? strval($_POST['uploadControlCID']) : '';
				if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
				{
					$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
					if(!empty($uploadedFiles))
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
			if(!empty($arFileIds) || $bEdit)
			{
				$arFields['STORAGE_ELEMENT_IDS'] = StorageManager::filterFiles($arFileIds, $storageTypeId);
			}
			unset($arFileIds);
		}

		// person type
		$arFields['PERSON_TYPE_ID'] = 0;
		if (isset($arPersonTypes['CONTACT']) && (!isset($arFields['COMPANY_ID']) || intval($arFields['COMPANY_ID']) <= 0))
			$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['CONTACT']);
		else if (isset($arPersonTypes['COMPANY']) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
			$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['COMPANY']);

		$processProductRows = array_key_exists($productDataFieldName, $_POST);
		$arProd = array();
		$taxList = array();
		if($processProductRows)
		{
			$arProd = $arResult['PRODUCT_ROWS'] = isset($_POST[$productDataFieldName]) ? ($_POST[$productDataFieldName]) : array();

			if(count($arProd) > 0)
			{
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

		$USER_FIELD_MANAGER->EditFormAddFields(CCrmQuote::$sUFEntityID, $arFields, array('FORM' => $_POST));
		if($conversionWizard !== null)
		{
			$conversionWizard->prepareDataForSave(CCrmOwnerType::Quote, $arFields);
		}

		CCrmQuote::RewriteClientFields($arFields, false);
		CCrmQuote::rewriteClientFieldsFromRequisite($arFields, $requisiteIdLinked, false);

		$arResult['ERROR_MESSAGE'] = '';

		if (!$CCrmQuote->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false, array('DISABLE_USER_FIELD_CHECK' => true)))
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
				$bSuccess = $CCrmQuote->Update($arResult['ELEMENT']['ID'], $arFields, true, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
			}
			else
			{
				$ID = $CCrmQuote->Add($arFields, true, array('REGISTER_SONET_EVENT' => false, 'DISABLE_USER_FIELD_CHECK' => true));
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
						CCrmOwnerType::Quote, $arResult['ELEMENT']['ID'], $requisiteIdLinked, $bankDetailIdLinked
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
				if(is_array($productRowSettings) && count($productRowSettings) > 0)
				{
					$arSettings = CCrmProductRow::LoadSettings(CCrmQuote::OWNER_TYPE, $arResult['ELEMENT']['ID']);
					foreach ($productRowSettings as $k => $v)
						$arSettings[$k] = $v;
					CCrmProductRow::SaveSettings(CCrmQuote::OWNER_TYPE, $arResult['ELEMENT']['ID'], $arSettings);
				}
				unset($arSettings);
			}

			if($bSuccess
				//&& !$isExternal // Product rows of external quote are read only
				&& $processProductRows
				&& ($bEdit || !empty($arProd)))
			{
				// Suppress owner synchronization
				$bSuccess = $CCrmQuote::SaveProductRows($arResult['ELEMENT']['ID'], $arProd, true, true, false);
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
			$conversionWizard->execute(array(CCrmOwnerType::QuoteName => $ID));
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

if($conversionWizard !== null && $conversionWizard->hasOriginUrl())
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
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('QUOTE_TYPE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EDIT'] = $bEdit;

$arResult['QUOTE_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate($arParams['QUOTE_EDIT_URL_TEMPLATE'],
	array('quote_id' => $entityID)
);
/*============= fields for main.interface.form =========*/
$arResult['FIELDS'] = array();

$arResult['FIELDS'][] = array(
	'id' => 'QUOTE_NUMBER',
	'name' => GetMessage('CRM_QUOTE_FIELD_QUOTE_NUMBER'),
	'value' => isset($arResult['ELEMENT']['~QUOTE_NUMBER']) ? $arResult['ELEMENT']['~QUOTE_NUMBER'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
);

$arResult['FIELDS'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_QUOTE_FIELD_TITLE_QUOTE'),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label'
);

$arResult['STATUS_LIST'] = array();
$arResult['~STATUS_LIST'] = CCrmStatus::GetStatusList('QUOTE_STATUS');
foreach ($arResult['~STATUS_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmQuote->cPerms->GetPermType('QUOTE', $isEditMode ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}

if ($arResult["IS_EDIT_PERMITTED"])
	$value = (isset($arResult['ELEMENT']['STATUS_ID']) ? $arResult['ELEMENT']['STATUS_ID'] : '');
else
	$value = (isset($arResult['ELEMENT']['STATUS_ID']) ? $arResult['STATUS_LIST'][$arResult['ELEMENT']['STATUS_ID']] : '');

$arResult['FIELDS'][] = array(
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_STATUS_ID'),
	'items' => $arResult['STATUS_LIST'],
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'value' => $value
);

$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
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
	'name' => GetMessage('CRM_QUOTE_FIELD_CURRENCY_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'list' : 'label',
	'items' => $arResult['CURRENCY_LIST'],
	'value' => $value
);

$arResult['FIELDS'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_QUOTE_FIELD_OPPORTUNITY'),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : '',
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label'
);

$arResult['FIELDS'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_ASSIGNED_BY_ID'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user',
	'canDrop' => false,
	'item' => CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()),
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$beginDate = isset($arResult['ELEMENT']['BEGINDATE']) ? $arResult['ELEMENT']['BEGINDATE'] : '';
$closeDate = isset($arResult['ELEMENT']['CLOSEDATE']) ? $arResult['ELEMENT']['CLOSEDATE'] : '';

$arResult['FIELDS'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_QUOTE_FIELD_BEGINDATE'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'canDrop' => false,
	'value' => $beginDate !== '' ? ConvertTimeStamp(MakeTimeStamp($beginDate), 'SHORT', SITE_ID) : ''
);
$arResult['FIELDS'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_QUOTE_FIELD_CLOSEDATE'),
	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'date' : 'label',
	'value' => $closeDate !== '' ? ConvertTimeStamp(MakeTimeStamp($closeDate), 'SHORT', SITE_ID) : '',
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
			'ORDER_PROPS_ID' => 'QUOTE_'.$arResult['ELEMENT']['ID'],
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

if (CCrmLead::CheckReadPermission($arResult['ELEMENT']['LEAD_ID'], $userPermissions))
{
	$arResult["ON_SELECT_LEAD_EVENT_NAME"] = "onCrmSelectLeadForQuote_".$arParams['ELEMENT_ID'];

	$arResult['ELEMENT_LEAD'] = "";
	if ($arResult['ELEMENT']['LEAD_ID'])
	{
		$leadShowUrl = CComponentEngine::MakePathFromTemplate($arParams['LEAD_SHOW_URL_TEMPLATE'],
			array('lead_id' => $arResult['ELEMENT']['LEAD_ID'])
		);

		$arResult['ELEMENT']["LEAD_MULTI_FIELDS"] = CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['LEAD_ID'], CCrmOwnerType::LeadName);

		$arResult['ELEMENT_LEAD'] = array(
			"id" => $arResult['ELEMENT']["LEAD_ID"],
			"name" => $arResult['ELEMENT']["LEAD_TITLE"],
			"image" => false,
			"entityType" => "lead",
			"url" => $leadShowUrl,
			"multi" => is_array($arResult['ELEMENT']["LEAD_MULTI_FIELDS"]) ? $arResult['ELEMENT']["LEAD_MULTI_FIELDS"] : array()
		);
	}

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['LEAD_ID'])
	{
		$leadPath = CHTTP::urlAddParams($arParams['LEAD_SELECTOR_URL_TEMPLATE'], array(
			"event" => $arResult["ON_SELECT_LEAD_EVENT_NAME"]
		));

		$arResult['FIELDS'][] = array(
			'id' => 'LEAD_ID',
			'name' => GetMessage('CRM_QUOTE_FIELD_LEAD_ID'),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-quote-edit-lead" data-role="mobile-crm-quote-edit-lead">'.
							//Contact's html is generated on javascript, object BX.Mobile.Crm.ClientEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($leadPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}

if (CCrmDeal::CheckReadPermission($arResult['ELEMENT']['DEAL_ID'], $userPermissions))
{
	$arResult["ON_SELECT_DEAL_EVENT_NAME"] = "onCrmSelectDealForQuote_".$arParams['ELEMENT_ID'];

	$arResult['ELEMENT_DEAL'] = "";
	if ($arResult['ELEMENT']['DEAL_ID'])
	{
		$dealShowUrl = CComponentEngine::MakePathFromTemplate($arParams['DEAL_SHOW_URL_TEMPLATE'],
			array('deal_id' => $arResult['ELEMENT']['DEAL_ID'])
		);

		if (!isset($arResult['ELEMENT']["DEAL_TITLE"]))
		{
			$obRes = CCrmDeal::GetListEx(
				array(),
				array('=ID'=> $arResult['ELEMENT']['DEAL_ID']),
				false,
				false,
				array('TITLE')
			);
			if($arDeal = $obRes->Fetch())
			{
				$arResult['ELEMENT']["DEAL_TITLE"] = $arDeal["TITLE"];
			}
		}

		$arResult['ELEMENT_DEAL'] = array(
			"id" => $arResult['ELEMENT']["DEAL_ID"],
			"name" => $arResult['ELEMENT']["DEAL_TITLE"],
			"image" => false,
			"entityType" => "deal",
			"url" => $dealShowUrl
		);
	}

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['DEAL_ID'])
	{
		$dealPath = CHTTP::urlAddParams($arParams['DEAL_SELECTOR_URL_TEMPLATE'], array(
			"event" => $arResult["ON_SELECT_DEAL_EVENT_NAME"]
		));

		$arResult['FIELDS'][] = array(
			'id' => 'DEAL_ID',
			'name' => GetMessage('CRM_QUOTE_FIELD_DEAL_ID'),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-quote-edit-deal" data-role="mobile-crm-quote-edit-deal">'.
							//Contact's html is generated on javascript, object BX.Mobile.Crm.EntityEditor
						'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($dealPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}

$arResult['FIELDS'][] = array(
	'id' => 'OPENED',
	'type' => 'checkbox',
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->getOpenedFlag(),
	"items" => array(
		"Y" => GetMessage('CRM_QUOTE_FIELD_OPENED')
	),
	'params' => $arResult["IS_EDIT_PERMITTED"] ? array() : array('disabled' => true)
);

if (CCrmContact::CheckReadPermission($arResult['ELEMENT']['CONTACT_ID'], $userPermissions))
{
	$arResult["ON_SELECT_CONTACT_EVENT_NAME"] = "onCrmSelectContactForQuote_".$arParams['ELEMENT_ID'];

	$arResult['ELEMENT_CONTACT'] = "";
	if ($arResult['ELEMENT']['CONTACT_ID'])
	{
		$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_SHOW_URL_TEMPLATE'],
			array('contact_id' => $arResult['ELEMENT']['CONTACT_ID'])
		);

		if (!isset($arResult['ELEMENT']["CONTACT_FULL_NAME"]))
		{
			$obRes = CCrmContact::GetListEx(
				array(),
				array('=ID'=> $arResult['ELEMENT']['CONTACT_ID']),
				false,
				false,
				array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID')
			);
			if($arContact = $obRes->Fetch())
			{
				$arResult['ELEMENT']["CONTACT_FULL_NAME"] = CUser::FormatName(
					CSite::GetNameFormat(false),
					array(
						'LOGIN' => isset($arContact['LOGIN']) ? $arContact['LOGIN'] : '',
						'NAME' => isset($arContact['NAME']) ? $arContact['NAME'] : '',
						'LAST_NAME' => isset($arContact['LAST_NAME']) ? $arContact['LAST_NAME'] : '',
						'SECOND_NAME' => isset($arContact['SECOND_NAME']) ? $arContact['SECOND_NAME'] : ''
					),
					true, false
				);

				$arResult['ELEMENT']["CONTACT_PHOTO"] = isset($arContact["PHOTO"]) ? $arContact["PHOTO"] : false;
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

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['CONTACT_ID'])
	{
		$contactPath = CHTTP::urlAddParams($arParams['CONTACT_SELECTOR_URL_TEMPLATE'], array(
			"event" => $arResult["ON_SELECT_CONTACT_EVENT_NAME"]
		));

		$arResult['FIELDS'][] = array(
			'id' => 'CONTACT_ID',
			'name' => GetMessage('CRM_QUOTE_FIELD_CONTACT_ID'),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-quote-edit-contact" data-role="mobile-crm-quote-edit-contact">'.
							//Contact's html is generated on javascript, object BX.Mobile.Crm.EntityEditor
							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($contactPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}

if (CCrmCompany::CheckReadPermission($arResult['ELEMENT']['COMPANY_ID'], $userPermissions))
{
	$arResult["ON_DELETE_COMPANY_EVENT_NAME"] = "onCrmDeleteCompanyForQuote_".$arParams['ELEMENT_ID'];
	$arResult["ON_SELECT_COMPANY_EVENT_NAME"] = "onCrmSelectCompanyForQuote_".$arParams['ELEMENT_ID'];

	$arResult['ELEMENT_COMPANY'] = "";
	if ($arResult['ELEMENT']['COMPANY_ID'])
	{
		$companyShowUrl = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_SHOW_URL_TEMPLATE'],
			array('company_id' => $arResult['ELEMENT']['COMPANY_ID'])
		);

		if (!isset($arResult['ELEMENT']["COMPANY_TITLE"]))
		{
			$obRes = CCrmCompany::GetList(
				array(), array('=ID'=> $arResult['ELEMENT']['COMPANY_ID']), array('TITLE', 'LOGO')
			);
			if($arCompany = $obRes->Fetch())
			{
				$arResult['ELEMENT']["COMPANY_TITLE"] = $arCompany["TITLE"];
				$arResult['ELEMENT']["COMPANY_LOGO"] = isset($arCompany["LOGO"]) ? $arCompany["LOGO"] : false;
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

	$companyPath = CHTTP::urlAddParams($arParams['COMPANY_SELECTOR_URL_TEMPLATE'], array(
		//	"pageId" => $arResult['ELEMENT']["ID"] ? "pageId_".$arResult['ELEMENT']["ID"] : "pageId_0",
		"event" => $arResult["ON_SELECT_COMPANY_EVENT_NAME"]
	));

	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['COMPANY_ID'])
	{
		$arResult['FIELDS'][] = array(
			'id' => 'COMPANY_ID',
			'name' => GetMessage('CRM_QUOTE_FIELD_COMPANY_ID'),
			'params' => array('size' => 50),
			'type' => 'custom',
			'value' => '<div class="mobile-grid-field-select-user">
							<div id="mobile-crm-quote-edit-company" data-role="mobile-crm-quote-edit-company">'.
							//Company's html is generated on javascript, object BX.Mobile.Crm.EntityEditor
							'</div>'. ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($companyPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
						'</div>'
		);
	}
}

if ($arResult["IS_EDIT_PERMITTED"])
	$fieldType = $arParams['RESTRICTED_MODE'] ? 'custom' : 'textarea';
else
	$fieldType = 'label';

$value = "";
if (isset($arResult['ELEMENT']['~CONTENT']))
	$value = ($fieldType == "textarea") ? htmlspecialcharsback($arResult['ELEMENT']['~CONTENT']) : $arResult['ELEMENT']['~CONTENT'];
$arResult['FIELDS'][] = array(
	'id' => 'CONTENT',
	'name' => GetMessage('CRM_QUOTE_FIELD_CONTENT'),
	'type' => $fieldType,
	'value' => $value,
);

$value = "";
if (isset($arResult['ELEMENT']['~TERMS']))
	$value = ($fieldType == "textarea") ? htmlspecialcharsback($arResult['ELEMENT']['~TERMS']) : $arResult['ELEMENT']['~TERMS'];
$arResult['FIELDS'][] = array(
	'id' => 'TERMS',
	'name' => GetMessage('CRM_QUOTE_FIELD_TERMS'),
	'type' => $fieldType,
	'value' => $value
);

$value = "";
if (isset($arResult['ELEMENT']['~COMMENTS']))
	$value = ($fieldType == "textarea") ? htmlspecialcharsback($arResult['ELEMENT']['~COMMENTS']) : $arResult['ELEMENT']['~COMMENTS'];
$arResult['FIELDS'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_QUOTE_FIELD_COMMENTS'),
	'params' => array(),
	'type' => $fieldType,
	'value' => $value
);

//-- Product rows
$arResult["PAGEID_PRODUCT_SELECTOR_BACK"] = "crmQuoteEditPage";
$arResult["ON_PRODUCT_SELECT_EVENT_NAME"] = "onCrmSelectProductForQuote_".$arParams['ELEMENT_ID'];
$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] = CHTTP::urlAddParams($arParams['PRODUCT_SELECTOR_URL_TEMPLATE'], array(
	"event" => $arResult["ON_PRODUCT_SELECT_EVENT_NAME"],
	"pageIdProductSelectorBack" => $arResult["PAGEID_PRODUCT_SELECTOR_BACK"]
));

$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'quote_'.strval($arParams['ELEMENT_ID']) : 'new_quote').'_product_editor';
$sProductsHtml = '';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => CCrmQuote::OWNER_TYPE,
	'PERMISSION_TYPE' => $arParams['RESTRICTED_MODE'] ? 'READ' : 'WRITE',
	'INIT_EDITABLE' => 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['LOCATION_ID'] : '',
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

$arResult['FIELDS'][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_QUOTE_FIELD_PRODUCT_ROWS'),
	'type' => 'custom',
	'value' => $sProductsHtml
);
//-- product rows

//user fields
$CCrmUserType = new CCrmMobileHelper();
$CCrmUserType->PrepareUserFields(
	$arResult['FIELDS'],
	CCrmQuote::$sUFEntityID,
	$arResult['ELEMENT']['ID']
);

if ($bCopy)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

if ($arParams['RESTRICTED_MODE'])
{
	$arResult['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Quote, 'entity_id' => $arResult['ENTITY_ID'])
		) : '';

	$arResult['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
		? CComponentEngine::MakePathFromTemplate(
			$arParams['EVENT_LIST_URL_TEMPLATE'],
			array('entity_type_id' => CCrmOwnerType::Quote, 'entity_id' => $arResult['ENTITY_ID'])
		) : '';
}

$this->IncludeComponentTemplate();
?>
