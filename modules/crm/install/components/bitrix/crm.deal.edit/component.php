<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc') && !CModule::IncludeModule('bizproc'))
{
	ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
	return;
}

global $USER_FIELD_MANAGER, $DB, $USER;

use \Bitrix\Crm\Binding\EntityBinding;

$CCrmDeal = new CCrmDeal();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('DEAL');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY', $arParams['PATH_TO_DEAL_CATEGORY'], $APPLICATION->GetCurPage().'?category_id=#category_id#');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;

if ($arParams['IS_RECURRING'] === 'Y')
{
	$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath('PATH_TO_DEAL_RECUR_CATEGORY', $arParams['PATH_TO_DEAL_RECUR_CATEGORY'], $APPLICATION->GetCurPage().'?category_id=#category_id#');
	$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_RECUR', $arParams['PATH_TO_DEAL_RECUR'], $APPLICATION->GetCurPage());
}

$isNew = $arParams['ELEMENT_ID'] <= 0;
$isEditMode = false;
$isCopyMode = false;
$bVarsFromForm = false;

if (!empty($arParams['ELEMENT_ID']))
	$isEditMode = true;
if (!empty($_REQUEST['copy']))
{
	$isCopyMode = true;
	$isEditMode = false;
}

if ($arParams['IS_RECURRING'] !== 'Y' && $_REQUEST['RECUR_PARAM']['RECURRING_SWITCHER'] == 'Y')
	$isEditMode = false;

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

$arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];
$arResult['CALL_LIST_ELEMENT'] = (int)$_REQUEST['call_list_element'];
$arResult['PERMISSION_ENTITY_TYPE'] = Bitrix\Crm\Category\DealCategory::convertToPermissionEntityType($arResult['CATEGORY_ID']);

$CCrmBizProc->AddParam('DealCategoryId', $arResult['CATEGORY_ID']);
$isConverting = isset($arParams['CONVERT']) && $arParams['CONVERT'];
//New Conversion Scheme
/** @var \Bitrix\Crm\Conversion\EntityConversionWizard */
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

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? CCrmDeal::GetPermissionAttributes(array($arParams['ELEMENT_ID']), $arResult['CATEGORY_ID'])
	: array();

//region external context ID
$arResult['EXTERNAL_CONTEXT'] = isset($_REQUEST['external_context']) ? $_REQUEST['external_context'] : '';
//endregion

if($isEditMode)
{
	$isPermitted = CCrmDeal::CheckUpdatePermission(
		$arParams['ELEMENT_ID'],
		$userPermissions,
		$arResult['CATEGORY_ID'],
		array('ENTITY_ATTRS' => $arEntityAttr)
	);
}
elseif($isCopyMode)
{
	$isPermitted = CCrmDeal::CheckReadPermission(
		$arParams['ELEMENT_ID'],
		$userPermissions,
		$arResult['CATEGORY_ID'],
		array('ENTITY_ATTRS' => $arEntityAttr)
	);
}
else
{
	$isPermitted = CCrmDeal::CheckCreatePermission(
		$userPermissions,
		$arResult['CATEGORY_ID']
	);
}

if(!$isPermitted)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

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
	$arFields = array('ID' => 0);
	if($_SERVER['REQUEST_METHOD'] === 'GET')
	{
		$conversionWizard->prepareDataForEdit(CCrmOwnerType::Deal, $arFields, true);
		if(isset($arFields['PRODUCT_ROWS']))
		{
			$arResult['PRODUCT_ROWS'] = $arFields['PRODUCT_ROWS'];
		}

		if(isset($arFields['CATEGORY_ID'])
			&& Bitrix\Crm\Category\DealCategory::isEnabled($arFields['CATEGORY_ID'])
			&& CCrmDeal::CheckCreatePermission($userPermissions, $arFields['CATEGORY_ID']))
		{
			$arResult['CATEGORY_ID'] = $arFields['CATEGORY_ID'];
		}
	}
	$arResult['CONVERSION_LEGEND'] = $conversionWizard->getEditFormLegend();
}
elseif ($isEditMode || $isCopyMode)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmDeal::GetListEx(array(), $arFilter);
	$arFields = $obFields->GetNext();

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
		$arResult['CATEGORY_ID'] = isset($arFields['CATEGORY_ID']) ? (int)$arFields['CATEGORY_ID'] : 0;
		$arFields['CONTACT_BINDINGS'] = \Bitrix\Crm\Binding\DealContactTable::getDealBindings($arParams['ELEMENT_ID']);
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

	if ($arParams['IS_RECURRING'] === "Y")
	{
		if ($arFields['IS_RECURRING'] !== "Y")
		{
			LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_RECUR'], array()));
		}
		elseif ($_REQUEST['expose'] === 'Y')
		{
			$exposeSelectionLimit = 1;
			$recurringInstance = Bitrix\Crm\Recurring\Entity\Deal::getInstance();
			$result = $recurringInstance->expose(
				[
					"=DEAL_ID" => $arParams['ELEMENT_ID']
				],
				$exposeSelectionLimit,
				false
			);
			if ($result->isSuccess())
			{
				$exposeData = $result->getData();
				LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $exposeData['ID'][0])));
			}
			else
			{
				LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_RECUR'], array()));
			}
		}

		$recurData = Bitrix\Crm\DealRecurTable::getList(
			array(
				"filter" => array("=DEAL_ID" => $arParams['ELEMENT_ID'])
			)
		);
		$arFields['RECURRING_DATA'] = $recurData->fetch();
	}
	elseif ($arFields['IS_RECURRING'] === "Y")
	{
		LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
	}
}
else
{
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
		$contactIDs = is_array($_GET['contact_id']) ? $_GET['contact_id'] : explode(',', $_GET['contact_id']);
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
	}

	if (isset($_GET['company_id']) && $_GET['company_id'] > 0)
	{
		$arFields['COMPANY_ID'] = (int)$_GET['company_id'];
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

	if (isset($arFields['CONTACT_BINDINGS']) && !empty($arFields['CONTACT_BINDINGS']))
	{
		$primaryBoundEntityID = EntityBinding::getPrimaryEntityID(CCrmOwnerType::Contact, $arFields['CONTACT_BINDINGS']);
		if($primaryBoundEntityID > 0)
		{
			$requisiteEntityList[] = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'ENTITY_ID' => $primaryBoundEntityID
			);
		}
	}

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

$isExternal = $isEditMode && isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']) && $arFields['ORIGINATOR_ID'] > 0 && $arFields['ORIGIN_ID'] > 0;
if($isExternal)
{
	$dbSalesList = CCrmExternalSale::GetList(
		array(
			'NAME' => 'ASC',
			'SERVER' => 'ASC'
		),
		array('ID' => $arFields['ORIGINATOR_ID'])
	);

	$arExternalSale = $dbSalesList->Fetch();
	if(is_array($arExternalSale))
	{
		$arResult['EXTERNAL_SALE_INFO'] = array(
			'ID' => $arFields['ORIGINATOR_ID'],
			'NAME' => $arExternalSale['NAME'],
			'SERVER' => $arExternalSale['SERVER'],
		);
	}
}

$arResult['ELEMENT'] = is_array($arFields) ? $arFields : null;
unset($arFields);

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

$arResult['FORM_ID'] = Bitrix\Crm\Category\DealCategory::prepareFormID(
	$arResult['CATEGORY_ID'],
	!empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_DEAL_EDIT_V12'
);

$arResult['GRID_ID'] = 'CRM_DEAL_LIST_V12';

$productDataFieldName = 'DEAL_PRODUCT_DATA';

if($isConverting)
{
	$bVarsFromForm = true;
}
else
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
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
						'entityTypeName' => CCrmOwnerType::DealName
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
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array())
				);
			}
		}
		elseif(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']) || isset($_POST['continue']))
		{
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
				if($comments !== '' && mb_strpos($comments, '<') !== false)
				{
					$comments = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($comments);
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
				$arFields['OPENED'] = mb_strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
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

			if(isset($_POST['PRIMARY_ENTITY_TYPE']) && isset($_POST['PRIMARY_ENTITY_ID']))
			{
				$primaryEntityTypeName = isset($_POST['PRIMARY_ENTITY_TYPE']) ? $_POST['PRIMARY_ENTITY_TYPE'] : '';
				$primaryEntityTypeID = CCrmOwnerType::ResolveID($primaryEntityTypeName);
				$primaryEntityID = isset($_POST['PRIMARY_ENTITY_ID']) ? (int)$_POST['PRIMARY_ENTITY_ID'] : 0;

				if($primaryEntityTypeID === CCrmOwnerType::Company)
				{
					if($primaryEntityID <= 0)
					{
						$arFields['COMPANY_ID'] = 0;
					}
					elseif(CCrmCompany::Exists($primaryEntityID))
					{
						$arFields['COMPANY_ID'] = $primaryEntityID;
					}
					elseif(isset($arSrcElement['COMPANY_ID']))
					{
						$arFields['COMPANY_ID'] = $arSrcElement['COMPANY_ID'];
					}
				}
				else
				{
					$arFields['COMPANY_ID'] = 0;
				}

				if(isset($_POST['SECONDARY_ENTITY_IDS']))
				{
					$contactIDs = explode(',', $_POST['SECONDARY_ENTITY_IDS']);

					$effectiveContactIDs = array();
					foreach($contactIDs as $contactID)
					{
						$contactID = (int)$contactID;
						if($contactID > 0 && CCrmContact::Exists($contactID))
						{
							$effectiveContactIDs[] = $contactID;
						}
					}

					$arFields['CONTACT_BINDINGS'] = EntityBinding::prepareEntityBindings(
						CCrmOwnerType::Contact,
						$effectiveContactIDs
					);

					if($primaryEntityTypeID === CCrmOwnerType::Contact && $primaryEntityID > 0)
					{
						EntityBinding::markAsPrimary(
							$arFields['CONTACT_BINDINGS'],
							CCrmOwnerType::Contact,
							$primaryEntityID
						);
					}
					else
					{
						EntityBinding::markFirstAsPrimary($arFields['CONTACT_BINDINGS']);
					}
				}
			}
			elseif(!$isEditMode)
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

			$requisiteIdLinked = isset($_POST['REQUISITE_ID']) ? max((int)$_POST['REQUISITE_ID'], 0) : 0;
			$bankDetailIdLinked = isset($_POST['BANK_DETAIL_ID']) ? max((int)$_POST['BANK_DETAIL_ID'], 0) : 0;

			$processProductRows = array_key_exists($productDataFieldName, $_POST);
			$arProd = array();
			if($processProductRows)
			{
				$prodJson = isset($_POST[$productDataFieldName]) ? strval($_POST[$productDataFieldName]) : '';
				$arProd = $arResult['PRODUCT_ROWS'] = $prodJson <> '' ? CUtil::JsObjectToPhp($prodJson) : array();

				if(count($arProd) > 0)
				{
					if($isCopyMode)
					{
						for($rowInd = 0, $rowQty = count($arProd); $rowInd < $rowQty; $rowInd++)
						{
							unset($arProd[$rowInd]['ID']);
						}
					}
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
				$arSettings = $settingsJson <> '' ? CUtil::JsObjectToPhp($settingsJson) : array();
				if(is_array($arSettings))
				{
					$productRowSettings['ENABLE_DISCOUNT'] = isset($arSettings['ENABLE_DISCOUNT']) ? $arSettings['ENABLE_DISCOUNT'] === 'Y' : false;
					$productRowSettings['ENABLE_TAX'] = isset($arSettings['ENABLE_TAX']) ? $arSettings['ENABLE_TAX'] === 'Y' : false;
				}
			}
			unset($productRowSettingsFieldName, $settingsJson, $arSettings);

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmDeal::$sUFEntityID, $arFields);
			if($conversionWizard !== null)
			{
				$conversionWizard->prepareDataForSave(CCrmOwnerType::Deal, $arFields);
			}
			elseif($isCopyMode)
			{
				$CCrmUserType->CopyFileFields($arFields);
			}

			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmDeal->CheckFields($arFields, $isEditMode ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmDeal->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmDeal->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			$arBizProcParametersValues = $CCrmBizProc->CheckFields(
				$isEditMode ? $arResult['ELEMENT']['ID'] : false,
				false,
				$arResult['ELEMENT']['ASSIGNED_BY'],
				$isEditMode ? $arEntityAttr : null
			);

			if ($arBizProcParametersValues === false)
			{
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;
			}

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$bSuccess = false;
				if ($isEditMode)
				{
					$bSuccess = $CCrmDeal->Update($arResult['ELEMENT']['ID'], $arFields, true, true, array('REGISTER_SONET_EVENT' => true));

					if ($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] === 'Y' && Bitrix\Crm\Recurring\Manager::isAllowedExpose(Bitrix\Crm\Recurring\Manager::DEAL))
					{
						if ($_POST['RECUR_PARAM']['START_DATE'] <> '')
							$recurringList['START_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['START_DATE']);

						if ($_POST['RECUR_PARAM']['DEAL_DATEPICKER_BEFORE'] <> '')
						{
							$recurringList['START_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['DEAL_DATEPICKER_BEFORE']);
						}

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
							$_POST['RECUR_PARAM']['REPEAT_TILL'] === \Bitrix\Crm\Recurring\Entity\Deal::LIMITED_BY_TIMES
							&& (int)$recurringList['LIMIT_REPEAT'] > 0
						)
						{
							$recurringList['IS_LIMIT'] = \Bitrix\Crm\Recurring\Entity\Deal::LIMITED_BY_TIMES;
						}
						elseif($_POST['RECUR_PARAM']['REPEAT_TILL'] === \Bitrix\Crm\Recurring\Entity\Deal::LIMITED_BY_DATE)
						{
							$recurringList['IS_LIMIT'] = \Bitrix\Crm\Recurring\Entity\Deal::LIMITED_BY_DATE;
						}
						else
						{
							$recurringList['IS_LIMIT'] = \Bitrix\Crm\Recurring\Entity\Deal::NO_LIMITED;
						}

						$recurringList['PARAMS'] = $_POST['RECUR_PARAM'];
						$recur = \Bitrix\Crm\DealRecurTable::getList(
							array(
								"filter" => array("=DEAL_ID" => $arResult['ELEMENT']['ID'])
							)
						)->fetch();

						$res = \Bitrix\Crm\Recurring\Manager::update(
							$recur['ID'],
							$recurringList,
							\Bitrix\Crm\Recurring\Manager::DEAL
						);

						if ($res->isSuccess())
						{
							\Bitrix\Crm\Recurring\Manager::exposeToday(null, Bitrix\Crm\Recurring\Manager::DEAL);
						}
					}
				}
				else
				{
					if ($originatorId > 0 && $originId > 0)
					{
						$arFields['ORIGINATOR_ID'] = $originatorId;
						$arFields['ORIGIN_ID'] = $originId;
					}

					if(isset($arResult['CATEGORY_ID']) && $arResult['CATEGORY_ID'] > 0)
					{
						$arFields['CATEGORY_ID'] = $arResult['CATEGORY_ID'];
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

					if ($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] === 'Y' && Bitrix\Crm\Recurring\Manager::isAllowedExpose(Bitrix\Crm\Recurring\Manager::DEAL))
					{
						if ($_POST['RECUR_PARAM']['START_DATE'] <> '')
							$recurringList['START_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['START_DATE']);

						if ($_POST['RECUR_PARAM']['DEAL_DATEPICKER_BEFORE'] <> '')
						{
							$recurringList['START_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['DEAL_DATEPICKER_BEFORE']);
						}

						if ($_POST['RECUR_PARAM']['END_DATE'] <> ''
							&& $_POST['RECUR_PARAM']['REPEAT_TILL'] === 'date')
						{
							$recurringList['LIMIT_DATE'] = new \Bitrix\Main\Type\Date($_POST['RECUR_PARAM']['END_DATE']);
							$recurringList['IS_LIMIT'] = 'D';
						}

						if ((int)($_POST['RECUR_PARAM']['LIMIT_REPEAT']) > 0
							&& $_POST['RECUR_PARAM']['REPEAT_TILL'] === 'times')
						{
							$recurringList['LIMIT_REPEAT'] =(int)($_POST['RECUR_PARAM']['LIMIT_REPEAT']);
							$recurringList['IS_LIMIT'] = 'T';
						}

						if (empty($recurringList['IS_LIMIT']))
							$recurringList['IS_LIMIT'] = 'N';

						if (isset($_POST['ENTITY_CATEGORY_ID']))
							$recurringList['CATEGORY_ID'] = (int)$_POST['ENTITY_CATEGORY_ID'];

						$recurringList['PARAMS'] = is_array($_POST['RECUR_PARAM']) ? $_POST['RECUR_PARAM'] : array();
						$recurringList['LIMIT_REPEAT'] = (int)$_POST['RECUR_PARAM']['LIMIT_REPEAT'] ? (int)$_POST['RECUR_PARAM']['LIMIT_REPEAT'] : null;

						$result = \Bitrix\Crm\Recurring\Manager::createEntity(
							$arFields,
							$recurringList,
							\Bitrix\Crm\Recurring\Manager::DEAL
						);
						$resultData = $result->getData();
						$ID = $resultData['DEAL_ID'];

						if ($ID)
						{
							if ((int)($arParams['ELEMENT_ID']) > 0)
							{
								$oldProducts = \CCrmProductRow::LoadRows('D', (int)($arParams['ELEMENT_ID']), true);
								foreach ($oldProducts as &$product)
								{
									unset($product['ID'], $product['OWNER_ID']);
								}
							}

							if (is_array($arProd) && !empty($arProd))
							{
								foreach ($arProd as &$product)
								{
									$oldProduct = $oldProducts[$product['ID']];
									unset($product['ID'], $product['OWNER_ID']);

									if (empty($oldProduct))
										continue;

									$product = array_merge($oldProduct, $product);
								}
							}
							elseif (!empty($oldProducts))
							{
								$arProd = $oldProducts;
							}
						}
					}
					else
					{
						$ID = $CCrmDeal->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
					}

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

			if ($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] !== 'Y')
			{
				//Region automation
				$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $arResult['ELEMENT']['ID']);
				$starter->setUserIdFromCurrent();
				if (!$isEditMode)
				{
					$starter->runOnAdd();
				}
				else
				{
					$starter->runOnUpdate($arFields, $arSrcElement);
				}
				//end automation
			}


			$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
			if($arResult['CALL_LIST_ID'] > 0 && $arResult['CALL_LIST_ELEMENT'] > 0)
			{
				$callList = \Bitrix\Crm\CallList\CallList::createWithId($arResult['CALL_LIST_ID']);
				if($callList && $ID > 0)
				{
					$callList->addCreatedEntity($arResult['CALL_LIST_ELEMENT'], CCrmOwnerType::DealName, $ID);
				}
			}

			if (!empty($arResult['ERROR_MESSAGE']))
			{
				ShowError($arResult['ERROR_MESSAGE']);
				$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
					array_merge(array('ID' => $ID), $arFields),
					CCrmDeal::GetFields()
				);

				$arResult['ELEMENT']['RECURRING_DATA']['PARAMS'] = $_POST['RECUR_PARAM'];
			}
			else
			{
				if ($_POST['RECUR_PARAM']['RECURRING_SWITCHER'] === 'Y')
				{
					$pathEdit = $arParams['PATH_TO_DEAL_RECUR_EDIT'];
					$pathShow = $arParams['PATH_TO_DEAL_RECUR_SHOW'];
				}
				else
				{
					$pathEdit = $arParams['PATH_TO_DEAL_EDIT'];
					$pathShow = $arParams['PATH_TO_DEAL_SHOW'];
				}

				if ($originId > 0)
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$pathShow,
							array('deal_id' => $ID)
						)
					);
				}

				if (isset($_POST['apply']))
				{
					if (CCrmDeal::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$pathEdit,
								array('deal_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$pathEdit,
							array('deal_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmDeal::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$pathShow,
								array('deal_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['continue']) && $conversionWizard !== null)
				{
					$conversionWizard->attachNewlyCreatedEntity(\CCrmOwnerType::DealName, $ID);
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
						CCrmOwnerType::DealName,
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
							'entityTypeName' => CCrmOwnerType::DealName,
							'entityInfo' => $info
						)
					);
					if(CModule::IncludeModule('pull'))
					{
						\Bitrix\Pull\Event::add($USER->GetID(), array(
							'module_id' => 'crm',
							'command' => 'external_event',
							'params' =>  $arResult['EXTERNAL_EVENT']
						));
					}

					$this->IncludeComponentTemplate('event');
					return;
				}
				else
				{
					LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
				}
			}
		}
	}
	elseif (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($isEditMode)
		{
			$entityID = $arParams['ELEMENT_ID'];
			$arResult['ERROR_MESSAGE'] = '';

			if (!CCrmDeal::CheckDeletePermission($entityID, $userPermissions, $arResult['CATEGORY_ID'], array('ENTITY_ATTRS' => $arEntityAttr)))
			{
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			}
			elseif (!$CCrmBizProc->Delete($entityID, $arEntityAttr, array('DealCategoryId' => $arResult['CATEGORY_ID'])))
			{
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;
			}

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmDeal->Delete($arResult['ELEMENT']['ID'], array('PROCESS_BIZPROC' => false)))
			{
				/** @var CApplicationException $ex */
				$ex = $APPLICATION->GetException();
				$arResult['ERROR_MESSAGE'] = ($ex instanceof CApplicationException)
					? $ex->GetString() : GetMessage('CRM_DELETE_ERROR');
			}

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST']));
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
}

if($conversionWizard !== null && $conversionWizard->hasOriginUrl())
{
	$arResult['BACK_URL'] = $conversionWizard->getOriginUrl();
}
else
{
	$arResult['BACK_URL'] = $arResult['CATEGORY_ID'] >= 0
		? CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_DEAL_CATEGORY'],
			array('category_id' => $arResult['CATEGORY_ID'])
		) : $arParams['PATH_TO_DEAL_LIST'];
}

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

//region Reset stage in copy mode
if($isCopyMode)
{
	if(!empty($arResult['STAGE_LIST']))
	{
		reset($arResult['STAGE_LIST']);
		$arResult['ELEMENT']['STAGE_ID'] = key($arResult['STAGE_LIST']);
	}
	else
	{
		unset($arResult['ELEMENT']['STAGE_ID']);
	}
}
//endregion

$arResult['EDIT'] = $isEditMode;
$arResult['FIELDS'] = array();

$APPLICATION->AddHeadScript($this->GetPath().'/sale.js');

if (!$isEditMode)
{
	$dbSalesList = CCrmExternalSale::GetList(
		array("NAME" => "ASC", "SERVER" => "ASC"),
		array("ACTIVE" => "Y")
	);
	$arSalesList = array();
	while ($arSale = $dbSalesList->GetNext())
		$arSalesList[$arSale["ID"]] = ($arSale["NAME"] != "" ? $arSale["NAME"] : $arSale["SERVER"]);

	$salesListCount = count($arSalesList);
	if ($salesListCount > 0)
	{
		$strCreateOrderHtml  = '<script type="text/javascript">var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>'.
			'<input type="hidden" name="SYNC_ORDER_ID" id="ID_SYNC_ORDER_ID" value="" />'.
			'<input type="hidden" name="SYNC_ORDER_FORM_NAME" id="ID_SYNC_ORDER_FORM_NAME" value="form_'.htmlspecialcharsbx($arResult['FORM_ID']).'" />';
		$strCreateOrderHtml .= '<script type="text/javascript">'.
			'function DoChangeExternalSaleId(val)'.
			'{'.
			'	var frm = document.forms[document.getElementById("ID_SYNC_ORDER_FORM_NAME").value];'.
			'	if (frm)'.
			'	{'.
			'		var l = frm.getElementsByTagName(\'*\');'.
			'		for (var i in l)'.
			'		{'.
			'			var el = l[i];'.
			'			if (el && el.type && (el.getAttribute("sale_order_marker") != null || el.type == "submit"))'.
			'				el.disabled = val;'.
			'		}'.
			'	}'.
			'	var contactSelectorId = "'.CUtil::JSEscape($arResult['FORM_ID']).'_CONTACT_ID";'.
			'	var companySelectorId = "'.CUtil::JSEscape($arResult['FORM_ID']).'_COMPANY_ID";'.
			'	if(typeof(BX.CrmEntityEditor.items[contactSelectorId]) !== "undefined")'.
			'		BX.CrmEntityEditor.items[contactSelectorId].setReadOnly(val);'.
			'	if(typeof(BX.CrmEntityEditor.items[companySelectorId]) !== "undefined")'.
			'		BX.CrmEntityEditor.items[companySelectorId].setReadOnly(val);'.
			'	var b = document.getElementById("ID_EXTERNAL_SALE_CREATE_BTN1");'.
			'	if (b)'.
			'		b.style.display = (val ? "" : "none");'.
			'	BX.CrmProductEditor.getDefault().setReadOnly(val);'.
			'}'.
			'</script>';
		$strCreateOrderHtml .= '<input type="checkbox" name="DO_USE_EXTERNAL_SALE" id="ID_DO_USE_EXTERNAL_SALE" value="Y" onclick="DoChangeExternalSaleId(this.checked)">';

		$strCreateOrderHtmlSelect = '';
		$strCreateOrderHtmlAction = '';

		if ($salesListCount == 1)
		{
			$arKeys = array_keys($arSalesList);
			$strCreateOrderHtmlSelect .= '<input type="hidden" name="EXTERNAL_SALE_ID" id="ID_EXTERNAL_SALE_ID" value="'.$arKeys[0].'" />';
			$strCreateOrderHtmlAction .= "document.getElementById('ID_EXTERNAL_SALE_ID').value";
		}
		elseif ($salesListCount > 1)
		{
			$strCreateOrderHtmlSelect .= '<select name="EXTERNAL_SALE_ID" id="ID_EXTERNAL_SALE_ID">';
			foreach ($arSalesList as $key => $val)
				$strCreateOrderHtmlSelect .= '<option value="'.$key.'">'.$val.'</option>';
			$strCreateOrderHtmlSelect .= '</select> ';
			$strCreateOrderHtmlAction .= "document.getElementById('ID_EXTERNAL_SALE_ID').options[document.getElementById('ID_EXTERNAL_SALE_ID').selectedIndex].value";
		}

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'SALE_ORDER',
			'name' => GetMessage('CRM_FIELD_SALE_ORDER'),
			'type' => 'custom',
			'value' => $strCreateOrderHtml,
			'persistent' => true
		);
	}
}
else
{
	if ($isExternal)
	{
		$strEditOrderHtml = '<script type="text/javascript">var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>'.
			'<input type="hidden" name="SYNC_ORDER_ID" id="ID_SYNC_ORDER_ID" value="" />'.
			'<input type="hidden" name="SYNC_ORDER_FORM_NAME" id="ID_SYNC_ORDER_FORM_NAME" value="form_'.htmlspecialcharsbx($arResult['FORM_ID']).'" />';

		$dbSalesList = CCrmExternalSale::GetList(
			array("NAME" => "ASC", "SERVER" => "ASC"),
			array("ID" => $arResult['ELEMENT']['ORIGINATOR_ID'])
		);
		if ($arSale = $dbSalesList->GetNext())
			$strEditOrderHtml .= ($arSale["NAME"] != "" ? $arSale["NAME"] : $arSale["SERVER"]);

		if(isset($arResult['EXTERNAL_SALE_INFO']))
		{
			$strEditOrderHtml .= $arResult['EXTERNAL_SALE_INFO']['NAME'] != ''
				? htmlspecialcharsbx($arResult['EXTERNAL_SALE_INFO']['NAME'])
				: htmlspecialcharsbx($arResult['EXTERNAL_SALE_INFO']['SERVER']);
		}
		else
		{
			$strEditOrderHtml .= GetMessage("CRM_EXTERNAL_SALE_NOT_FOUND");
		}

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'SALE_ORDER',
			'name' => GetMessage('CRM_FIELD_SALE_ORDER1'),
			'type' => 'custom',
			'value' => $strEditOrderHtml,
			'persistent' => true
		);
	}
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_deal_info',
	'name' => GetMessage('CRM_SECTION_DEAL_INFO'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STAGE_ID',
	'name' => GetMessage('CRM_FIELD_STAGE_ID'),
	'items' => $arResult['STAGE_LIST'],
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'list',
	'value' => (isset($arResult['ELEMENT']['STAGE_ID']) ? $arResult['ELEMENT']['STAGE_ID'] : '')
);

$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

$currencyFld = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID')
);
if(!$isExternal)
{
	$currencyFld['type'] = 'list';
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
$arResult['FIELDS']['tab_1'][] = &$currencyFld;

$opportunityFld = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'params' => array('size' => 21, 'sale_order_marker' => 'Y'),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : ''
);
if(!$isExternal)
{
	$opportunityFld['type'] = 'text';
}
else
{
	$opportunityFld['type'] = 'label';
}
$arResult['FIELDS']['tab_1'][] = &$opportunityFld;

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PROBABILITY',
	'name' => GetMessage('CRM_FIELD_PROBABILITY'),
	'params' => array('size' => 3, 'maxlength' => '3'),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['PROBABILITY']) ? (string)(double)$arResult['ELEMENT']['PROBABILITY'] : ''
);
$arResult['RESPONSIBLE_SELECTOR_PARAMS'] = array(
	'NAME' => 'crm_deal_edit_resonsible',
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

//Fix for issue #36848
$beginDate = isset($arResult['ELEMENT']['BEGINDATE']) ? $arResult['ELEMENT']['BEGINDATE'] : '';
$closeDate = isset($arResult['ELEMENT']['CLOSEDATE']) ? $arResult['ELEMENT']['CLOSEDATE'] : $beginDate;

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_FIELD_BEGINDATE'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'date_link',
	'value' => $beginDate !== '' ? ConvertTimeStamp(MakeTimeStamp($beginDate), 'SHORT', SITE_ID) : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_FIELD_CLOSEDATE2'),
	'type' => 'date_link',
	'value' => $closeDate !== '' ? ConvertTimeStamp(MakeTimeStamp($closeDate), 'SHORT', SITE_ID) : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'list',
	'items' => $arResult['TYPE_LIST'],
	'value' => (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['ELEMENT']['TYPE_ID'] : '')
);

/* Field 'CLOSED' was removed from user editable fields
 * $arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSED',
	'name' => GetMessage('CRM_FIELD_CLOSED'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'checkbox',
	'value' => (isset($arResult['ELEMENT']['CLOSED']) ? $arResult['ELEMENT']['CLOSED'] : 'N')
);*/

/*if ($arResult['ELEMENT']['IS_RECURRING'] !== 'Y')
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_PAY_BEFORE',
		'name' => GetMessage('CRM_FIELD_DATE_PAY_BEFORE'),
		'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
		'type' => 'date_short',
		'value' => !empty($arResult['ELEMENT']['DATE_PAY_BEFORE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_PAY_BEFORE']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
	);
}	*/

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['OPENED'])
		? $arResult['ELEMENT']['OPENED'] : (\Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N'),
	'title' => GetMessage('CRM_FIELD_OPENED_TITLE')
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CLIENT_INFO'),
	'type' => 'section'
);

if(CCrmCompany::CheckReadPermission(0, $userPermissions) || CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$companyID = isset($arResult['ELEMENT']['COMPANY_ID']) ? (int)$arResult['ELEMENT']['COMPANY_ID'] : 0;
	if(isset($arResult['ELEMENT']['CONTACT_BINDINGS']))
	{
		$contactBindings = $arResult['ELEMENT']['CONTACT_BINDINGS'];
	}
	elseif($arParams['ELEMENT_ID'] > 0)
	{
		$contactBindings = \Bitrix\Crm\Binding\DealContactTable::getDealBindings($arParams['ELEMENT_ID']);
	}
	elseif(isset($arResult['ELEMENT']['CONTACT_ID']))
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

	if($companyID > 0 || empty($contactBindings))
	{
		$primaryEntityTypeName = CCrmOwnerType::CompanyName;
		$primaryEntityID = $companyID;
	}
	else
	{
		$primaryEntityTypeName = CCrmOwnerType::ContactName;
		$primaryEntityID = EntityBinding::getPrimaryEntityID(CCrmOwnerType::Contact, $contactBindings);
	}

	$arResult['CLIENT_SELECTOR_ID'] = 'CLIENT';
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => $arResult['CLIENT_SELECTOR_ID'],
		'name' => GetMessage('CRM_DEAL_EDIT_FIELD_CLIENT'),
		'type' => 'crm_composite_client_selector',
		'componentParams' => array(
			'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "DEAL_{$arParams['ELEMENT_ID']}" : 'NEWDEAL',
			'OWNER_TYPE' => CCrmOwnerType::DealName,
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'PRIMARY_ENTITY_TYPE' => $primaryEntityTypeName,
			'PRIMARY_ENTITY_ID' => $primaryEntityID,
			'SECONDARY_ENTITY_TYPE' => CCrmOwnerType::ContactName,
			'SECONDARY_ENTITY_IDS' => EntityBinding::prepareEntityIDs(CCrmOwnerType::Contact, $contactBindings),
			'CUSTOM_MESSAGES' => array(
				'SECONDARY_ENTITY_HEADER' => GetMessage('CRM_DEAL_EDIT_CONTACT_SELECTOR_HEADER'),
				'SECONDARY_ENTITY_MARKING_TITLE' => GetMessage('CRM_DEAL_EDIT_CONTACT_MARKING_TITLE'),
			),
			'PRIMARY_ENTITY_TYPE_INPUT_NAME' => 'PRIMARY_ENTITY_TYPE',
			'PRIMARY_ENTITY_INPUT_NAME' => 'PRIMARY_ENTITY_ID',
			'SECONDARY_ENTITIES_INPUT_NAME' => 'SECONDARY_ENTITY_IDS',
			'REQUISITE_INPUT_NAME' => 'REQUISITE_ID',
			'REQUISITE_ID' => $requisiteIdLinked,
			'BANK_DETAIL_INPUT_NAME' => 'BANK_DETAIL_ID',
			'BANK_DETAIL_ID' => $bankDetailIdLinked,
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.edit/ajax.php?'.bitrix_sessid_get(),
			'REQUISITE_SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.edit/settings.php?'.bitrix_sessid_get(),
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			'ENTITY_SELECTOR_SEARCH_OPTIONS' => array(
				'NOT_MY_COMPANIES' => 'Y'
			)
		)
	);
}

if ($bTaxMode)
{
	// CLIENT LOCATION
	$sLocationHtml = '';
	ob_start();

	$locValue = isset($arResult['ELEMENT']['LOCATION_ID']) ? $arResult['ELEMENT']['LOCATION_ID'] : '';
	CSaleLocation::proxySaleAjaxLocationsComponent(
		array(
			'AJAX_CALL' => 'N',
			'COUNTRY_INPUT_NAME' => 'LOC_COUNTRY',
			'REGION_INPUT_NAME' => 'LOC_REGION',
			'CITY_INPUT_NAME' => 'LOC_CITY',
			'CITY_OUT_LOCATION' => 'Y',
			'LOCATION_VALUE' => $locValue,
			'ORDER_PROPS_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
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
		'name' => GetMessage('CRM_DEAL_FIELD_LOCATION_ID'),
		'type' => 'custom',
		'value' =>  $sLocationHtml.
			'<div>
				<span class="bx-crm-edit-content-block-element-name">&nbsp;</span>'.
			'<span class="bx-crm-edit-content-location-description">'.
			GetMessage('CRM_DEAL_FIELD_LOCATION_ID_DESCRIPTION').
			'</span>'.
			'</div>',
		'persistent' => true
	);
	$arResult['FIELDS']['tab_1'][] = $locationField;
	$arResult['FORM_FIELDS_TO_ADD']['LOCATION_ID'] = $locationField;
	unset($locationField);
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

// PRODUCT_ROWS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);

$sProductsHtml = '';

if ($isEditMode)
{
	if ($isExternal)
	{
		if(isset($arResult['EXTERNAL_SALE_INFO']))
		{
			$sProductsHtml .= '<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'EDIT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_EDIT").'</span>'.
				'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'VIEW\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_VIEW").'</span>'.
				'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'PRINT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_PRINT").'</span><br/><br/>';
		}
		else
		{
			$sProductsHtml .= GetMessage("CRM_EXTERNAL_SALE_NOT_FOUND");
		}
	}
}
else
{
	if ($salesListCount > 0)
		$sProductsHtml .= '<div id="ID_EXTERNAL_SALE_CREATE_BTN1" style="display:none;">'.$strCreateOrderHtmlSelect.'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$strCreateOrderHtmlAction.', \'CREATE\')">'.GetMessage("CRM_EXT_SALE_CD_CREATE1").'</span></div>';
}

// Determine person type
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
$personTypeId = 0;
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if ($arResult['ELEMENT']['COMPANY_ID'] > 0)
	{
		$personTypeId = $arPersonTypes['COMPANY'];
	}
	if (isset($arResult['ELEMENT']['CONTACT_BINDINGS'])
		&& EntityBinding::getPrimaryEntityID(CCrmOwnerType::Contact, $arResult['ELEMENT']['CONTACT_BINDINGS']) > 0)
	{
		$personTypeId = $arPersonTypes['CONTACT'];
	}
}

ob_start();
$data = !empty($arResult['ELEMENT']['RECURRING_DATA']['PARAMS']) ? $arResult['ELEMENT']['RECURRING_DATA']['PARAMS'] : array();
$data['CONTEXT'] = $arParams['ELEMENT_ID'] > 0 ? "INVOICE_{$arParams['ELEMENT_ID']}" : 'NEWINVOICE';
$data['CLIENT_PRIMARY_ENTITY_TYPE_NAME'] = $primaryEntityTypeName;
$data['CLIENT_PRIMARY_ENTITY_ID'] = $primaryEntityID;
$data['CLIENT_SECONDARY_ENTITY_IDS'] = $secondaryIDs;
$data['LAST_EXECUTION'] = $arResult['ELEMENT']['RECURRING_DATA']['LAST_EXECUTION'];
$data['CATEGORY_ID'] = $arResult['ELEMENT']['RECURRING_DATA']['CATEGORY_ID'];
$data['UF_MYCOMPANY_ID'] = (int)$arResult['ELEMENT']['UF_MYCOMPANY_ID'] > 0 ? $arResult['ELEMENT']['UF_MYCOMPANY_ID'] : null;
$APPLICATION->IncludeComponent('bitrix:crm.interface.form.recurring',
	'edit',
	array(
		'DATA' => $data,
		'ID' => $arResult['ELEMENT']['ID'],
		'ENTITY_TYPE' => Bitrix\Crm\Recurring\Manager::DEAL,
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

$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'deal_'.strval($arParams['ELEMENT_ID']) : 'new_deal').'_product_editor';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => 'D',
	'PERMISSION_TYPE' => $isExternal ? 'READ' : 'WRITE',
	'PERMISSION_ENTITY_TYPE' => $arResult['PERMISSION_ENTITY_TYPE'],
	'INIT_EDITABLE' => $isExternal ? 'N' : 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => ($bTaxMode && isset($arResult['ELEMENT']['LOCATION_ID'])) ? $arResult['ELEMENT']['LOCATION_ID'] : '',
	'CLIENT_SELECTOR_ID' => $arResult['CLIENT_SELECTOR_ID'],
	'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
	'TOTAL_SUM' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : null,
	'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
	'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName,
	'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW']
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
		CCrmOwnerType::Deal,
		CCrmOwnerType::Lead,
		$arResult['LEAD_ID']
	);
}
else
{
	$useUserFieldsFromForm = $isConverting ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $bVarsFromForm;
	$fileViewer = new \Bitrix\Crm\UserField\FileViewer(CCrmOwnerType::Deal, $arResult['ELEMENT']['ID']);
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

if (IsModuleInstalled('bizproc') && CBPRuntime::isFeatureEnabled() && $arParams['IS_RECURRING'] !== 'Y')
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'DEAL');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentDeal', 'DEAL'),
			$isEditMode ? array('crm', 'CCrmDocumentDeal', 'DEAL_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentDeal', $isEditMode ? 'DEAL_'.$arResult['ELEMENT']['ID'] : 'DEAL_0'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowId' => $arDocumentState['ID'] > 0 ? $arDocumentState['ID'] : $arDocumentState['TEMPLATE_ID'],
				'CreatedBy' => $arResult['ELEMENT']['ASSIGNED_BY'],
				'UserIsAdmin' => $USER->IsAdmin(),
				'DealCategoryId' => $arResult['CATEGORY_ID']
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
				'id' => 'BP_EVENTS_'.$bizProcIndex,
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

if ($isCopyMode)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');
?>
