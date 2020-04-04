<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 * @var \CUserTypeManager $USER_FIELD_MANAGER
 * @var \CBitrixComponent $this
 * @var array $arParams
 * @var array $arResult
 */

global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;

$isErrorOccured = false;
$errorMessage = '';

if (!CModule::IncludeModule('crm'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED');
	$isErrorOccured = true;
}

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!$isErrorOccured && $CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	$errorMessage = GetMessage('CRM_PERMISSION_DENIED');
	$isErrorOccured = true;
}

//region Export params
$sExportType = !empty($arParams['EXPORT_TYPE']) ?
	strval($arParams['EXPORT_TYPE']) : (!empty($_REQUEST['type']) ? strval($_REQUEST['type']) : '');
$isInExportMode = false;
$isStExport = false;    // Step-by-step export mode
if (!empty($sExportType))
{
	$sExportType = strtolower(trim($sExportType));
	switch ($sExportType)
	{
		case 'csv':
		case 'excel':
			$isInExportMode = true;
			$isStExport = (isset($arParams['STEXPORT_MODE']) && $arParams['STEXPORT_MODE'] === 'Y');
			break;
		default:
			$sExportType = '';
	}
}

$isStExportAllFields = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'])
						&& $arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'] === 'Y');
$arResult['STEXPORT_EXPORT_ALL_FIELDS'] = ($isStExport && $isStExportAllFields) ? 'Y' : 'N';

$isStExportProductsFields = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_PRODUCT_FIELDS'])
						&& $arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_PRODUCT_FIELDS'] === 'Y');
$arResult['STEXPORT_EXPORT_PRODUCT_FIELDS'] = ($isStExport && $isStExportProductsFields) ? 'Y' : 'N';

$arResult['STEXPORT_MODE'] = $isStExport ? 'Y' : 'N';
$arResult['STEXPORT_TOTAL_ITEMS'] = isset($arParams['STEXPORT_TOTAL_ITEMS']) ?
	(int)$arParams['STEXPORT_TOTAL_ITEMS'] : 0;
//endregion


if (!$isErrorOccured && $isInExportMode && $CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'EXPORT'))
{
	$errorMessage = GetMessage('CRM_PERMISSION_DENIED');
	$isErrorOccured = true;
}

if ($isErrorOccured)
{
	if ($isStExport)
	{
		return array('ERROR' => $errorMessage);
	}
	else
	{
		ShowError($errorMessage);
		return;
	}
}

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Crm\Settings\LayoutSettings;

$CCrmQuote = new CCrmQuote(false);

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath('PATH_TO_QUOTE_LIST', $arParams['PATH_TO_QUOTE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_QUOTE_DETAILS'] = CrmCheckPath('PATH_TO_QUOTE_DETAILS', $arParams['PATH_TO_QUOTE_DETAILS'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&details');
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_QUOTE_KANBAN'] = CrmCheckPath('PATH_TO_QUOTE_KANBAN', $arParams['PATH_TO_QUOTE_KANBAN'], $currentPage);
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['ADD_EVENT_NAME'] = isset($arParams['ADD_EVENT_NAME']) ? $arParams['ADD_EVENT_NAME'] : '';
$arResult['ADD_EVENT_NAME'] = $arParams['ADD_EVENT_NAME'] !== ''
	? preg_replace('/[^a-zA-Z0-9_\.]/', '', $arParams['ADD_EVENT_NAME']) : '';

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
$arResult['NAVIGATION_CONTEXT_ID'] = isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arResult['PRESERVE_HISTORY'] = isset($arParams['PRESERVE_HISTORY']) ? $arParams['PRESERVE_HISTORY'] : false;
//$arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();
$arResult['ENABLE_SLIDER'] = false;
$arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
$arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
$arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];
if($arResult['CALL_LIST_UPDATE_MODE'])
{
	AddEventHandler('crm', 'onCrmQuoteListItemBuildMenu', array('\Bitrix\Crm\CallList\CallList', 'handleOnCrmQuoteListItemBuildMenu'));
}

CCrmQuote::PrepareConversionPermissionFlags(0, $arResult, $CCrmPerms);
if($arResult['CAN_CONVERT'])
{
	$config = \Bitrix\Crm\Conversion\QuoteConversionConfig::load();
	if($config === null)
	{
		$config = \Bitrix\Crm\Conversion\QuoteConversionConfig::getDefault();
	}

	$arResult['CONVERSION_CONFIG'] = $config;
}


if(LayoutSettings::getCurrent()->isSimpleTimeFormatEnabled())
{
	$arResult['TIME_FORMAT'] = array(
		'tommorow' => 'tommorow',
		's' => 'sago',
		'i' => 'iago',
		'H3' => 'Hago',
		'today' => 'today',
		'yesterday' => 'yesterday',
		//'d7' => 'dago',
		'-' => Main\Type\DateTime::convertFormatToPhp(FORMAT_DATE)
	);
}
else
{
	$arResult['TIME_FORMAT'] = preg_replace('/:s$/', '', Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && strlen($arParams['GADGET_ID']) > 0)
{
	$arResult['GADGET'] = 'Y';
	$arResult['GADGET_ID'] = $arParams['GADGET_ID'];
}
$isInGadgetMode = $arResult['GADGET'] === 'Y';

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
if (!empty($arParams['INTERNAL_FILTER']) || $isInGadgetMode)
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	if(empty($arParams['GRID_ID_SUFFIX']))
	{
		$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? strtoupper($this->GetParent()->GetName()) : '';
	}

	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
{
	$arSort = $arParams['INTERNAL_SORT'];
}

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmQuote::$sUFEntityID);

$arResult['GRID_ID'] = 'CRM_QUOTE_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('QUOTE_STATUS');
$arResult['CLOSED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
$arResult['WEBFORM_LIST'] = WebFormManager::getListNames();
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();
$arResult['PERMS']['ADD']    = !$CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'DELETE');

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';

$currentUserID = $arResult['CURRENT_USER_ID'];

//region Filter Presets Initialization
if (!$bInternal)
{
	//region Preparation of work statuses
	$processStatusIDs = array();
	foreach(array_keys(CCrmQuote::GetStatuses()) as $statusID)
	{
		if(CCrmQuote::GetSemanticID($statusID) === Bitrix\Crm\PhaseSemantics::PROCESS)
		{
			$processStatusIDs[] = $statusID;
		}
	}
	//endregion
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_new' => array('name' => GetMessage('CRM_PRESET_NEW'), 'fields' => array('STATUS_ID' => array('selDRAFT' => 'DRAFT'))),
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'fields' => array( 'ASSIGNED_BY_ID_name' => $currentUserName, 'ASSIGNED_BY_ID' => $currentUserID)),
		'filter_my_in_work' => array(
			'name' => GetMessage('CRM_PRESET_MY_IN_WORK'),
			'default' => true,
			'fields' => array(
				'ASSIGNED_BY_ID_name' => $currentUserName,
				'ASSIGNED_BY_ID' => $currentUserID,
				'STATUS_ID' => $processStatusIDs
			)
		)
	);
}
//endregion

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);

//region Navigation Params
if ($arParams['QUOTE_COUNT'] <= 0)
{
	$arParams['QUOTE_COUNT'] = 20;
}
$arNavParams = $gridOptions->GetNavParams(array('nPageSize' => $arParams['QUOTE_COUNT']));
$arNavParams['bShowAll'] = false;
//endregion

//region Filter initialization
if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'COMMENTS');

	$entityFilter = Crm\Filter\Factory::createEntityFilter(
		new Crm\Filter\QuoteSettings(
			array('ID' => $arResult['GRID_ID'])
		)
	);

	$effectiveFilterFieldIDs = $filterOptions->getUsedFields();
	if(empty($effectiveFilterFieldIDs))
	{
		$effectiveFilterFieldIDs = $entityFilter->getDefaultFieldIDs();
	}

	//region HACK: Preload fields for filter of webforms
	if(!in_array('WEBFORM_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'WEBFORM_ID';
	}

	Tracking\UI\Filter::appendEffectiveFields($effectiveFilterFieldIDs);
	//endregion

	foreach($effectiveFilterFieldIDs as $filterFieldID)
	{
		$filterField = $entityFilter->getField($filterFieldID);
		if($filterField)
		{
			$arResult['FILTER'][] = $filterField->toArray();
		}
	}
}
//endregion

$arResult['~STATUS_LIST_WRITE']= CCrmStatus::GetStatusList('QUOTE_STATUS');
$arResult['STATUS_LIST_WRITE'] = array();
foreach ($arResult['~STATUS_LIST_WRITE'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmPerms->GetPermType('QUOTE', 'WRITE', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST_WRITE'][$sStatusId] = $sStatusTitle;
}

//region Headers initialization
$arResult['HEADERS'] = 	array(
	// default fields
	array('id' => 'QUOTE_SUMMARY', 'name' => GetMessage('CRM_COLUMN_QUOTE'), 'sort' => 'quote_summary', 'width' => 200, 'default' => true, 'editable' => false),
	array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_STATUS_ID'), 'sort' => 'status_sort', 'width' => 200, 'default' => true, 'prevent_default' => false, 'editable' => array('items' => $arResult['STATUS_LIST_WRITE']), 'type' => 'list'),
	array('id' => 'SUM', 'name' => GetMessage('CRM_COLUMN_SUM'), 'sort' => 'opportunity_account', 'first_order' => 'desc', 'default' => true, 'editable' => false, 'align' => 'right'),
	array('id' => 'ENTITIES_LINKS', 'name' => GetMessage('CRM_COLUMN_ENTITIES_LINKS'), 'default' => true, 'editable' => false),
	array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_CLOSEDATE'), 'sort' => 'closedate', 'default' => true, 'editable' => true, 'type' => 'date'),
	array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_BEGINDATE'), 'sort' => 'begindate', 'editable' => true, 'type' => 'date'),
	array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false),

	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'first_order' => 'desc', 'width' => 60, 'editable' => false, 'type' => 'int'),
	array('id' => 'QUOTE_NUMBER', 'name' => GetMessage('CRM_COLUMN_QUOTE_NUMBER'), 'sort' => 'quote_number', 'width' => 60, 'editable' => false),
	array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'editable' => true),
	array('id' => 'QUOTE_CLIENT', 'name' => GetMessage('CRM_COLUMN_CLIENT'), 'sort' => 'quote_client', 'editable' => false),
	array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'sort' => 'opportunity', 'first_order' => 'desc', 'editable' => true, 'align' => 'right'),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
	array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_ID'), 'sort' => 'contact_full_name', 'editable' => false),
	array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'company_id', 'editable' => false),
	array('id' => 'LEAD_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_ID'), 'sort' => 'lead_id', 'editable' => false),
	array('id' => 'DEAL_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_ID'), 'sort' => 'deal_id', 'editable' => false),
	array('id' => 'MYCOMPANY_ID', 'name' => GetMessage('CRM_COLUMN_MYCOMPANY_ID1'), 'sort' => 'mycompany_id', 'editable' => false),
	array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_CLOSED'), 'sort' => 'closed', 'align' => 'center', 'editable' => array('items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))), 'type' => 'list'),
	array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'first_order' => 'desc', 'default' => true,),
	array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'editable' => false),
	array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'first_order' => 'desc'),
	array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'editable' => false),
	array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID'), 'sort' => false, 'default' => $isInExportMode, 'editable' => false, 'type' => 'list'),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /*because of MSSQL*/, 'editable' => false),
	array('id' => 'WEBFORM_ID', 'name' => GetMessage('CRM_COLUMN_WEBFORM'), 'sort' => 'webform_id', 'type' => 'list')
);

Tracking\UI\Grid::appendColumns($arResult['HEADERS']);

$utmList = \Bitrix\Crm\UtmTable::getCodeNames();
foreach ($utmList as $utmCode => $utmName)
{
	$arResult['HEADERS'][] = array(
		'id' => $utmCode,
		'name' => $utmName,
		'sort' => false, 'default' => $isInExportMode, 'editable' => false
	);
}

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);

// list all filds for export
$exportAllFieldsList = array();
if ($isInExportMode && $isStExportAllFields)
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		$exportAllFieldsList[] = $arHeader['id'];
	}
}
unset($arHeader);

//endregion Headers initialization

//region Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['GRID_ID'];
	$getAction = 'action_'.$arResult['GRID_ID'];
	//We need to check grid 'controls'
	$controls = isset($_POST['controls']) && is_array($_POST['controls']) ? $_POST['controls'] : array();
	if ($actionData['METHOD'] == 'POST' && (isset($controls[$postAction]) || isset($_POST[$postAction])))
	{
		CUtil::JSPostUnescape();

		$actionData['ACTIVE'] = true;

		if(isset($controls[$postAction]))
		{
			$actionData['NAME'] = $controls[$postAction];
		}
		else
		{
			$actionData['NAME'] = $_POST[$postAction];
			unset($_POST[$postAction], $_REQUEST[$postAction]);
		}

		$allRows = 'action_all_rows_'.$arResult['GRID_ID'];
		$actionData['ALL_ROWS'] = false;
		if(isset($controls[$allRows]))
		{
			$actionData['ALL_ROWS'] = $controls[$allRows] == 'Y';
		}
		elseif(isset($_POST[$allRows]))
		{
			$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
			unset($_POST[$allRows], $_REQUEST[$allRows]);
		}

		if(isset($_POST['rows']) && is_array($_POST['rows']))
		{
			$actionData['ID'] = $_POST['rows'];
		}
		elseif(isset($_POST['ID']))
		{
			$actionData['ID'] = $_POST['ID'];
			unset($_POST['ID'], $_REQUEST['ID']);
		}

		if(isset($_POST['FIELDS']))
		{
			$actionData['FIELDS'] = $_POST['FIELDS'];
			unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
		}

		if(isset($_POST['ACTION_STATUS_ID']) || isset($controls['ACTION_STATUS_ID']))
		{
			if(isset($_POST['ACTION_STATUS_ID']))
			{
				$actionData['STATUS_ID'] = trim($_POST['ACTION_STATUS_ID']);
				unset($_POST['ACTION_STATUS_ID'], $_REQUEST['ACTION_STATUS_ID']);
			}
			else
			{
				$actionData['STATUS_ID'] = trim($controls['ACTION_STATUS_ID']);
			}
		}

		if(isset($_POST['ACTION_ASSIGNED_BY_ID']) || isset($controls['ACTION_ASSIGNED_BY_ID']))
		{
			$assignedByID = 0;
			if(isset($_POST['ACTION_ASSIGNED_BY_ID']))
			{
				if(!is_array($_POST['ACTION_ASSIGNED_BY_ID']))
				{
					$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID']);
				}
				elseif(count($_POST['ACTION_ASSIGNED_BY_ID']) > 0)
				{
					$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID'][0]);
				}
				unset($_POST['ACTION_ASSIGNED_BY_ID'], $_REQUEST['ACTION_ASSIGNED_BY_ID']);
			}
			else
			{
				$assignedByID = (int)$controls['ACTION_ASSIGNED_BY_ID'];
			}

			$actionData['ASSIGNED_BY_ID'] = $assignedByID;
		}

		if(isset($_POST['ACTION_OPENED']) || isset($controls['ACTION_OPENED']))
		{
			if(isset($_POST['ACTION_OPENED']))
			{
				$actionData['OPENED'] = strtoupper($_POST['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
				unset($_POST['ACTION_OPENED'], $_REQUEST['ACTION_OPENED']);
			}
			else
			{
				$actionData['OPENED'] = strtoupper($controls['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
			}
		}

		$actionData['AJAX_CALL'] = $arResult['IS_AJAX_CALL'];
	}
	elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if(isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = $arResult['IS_AJAX_CALL'];
	}
}
//endregion Try to extract user action data

// HACK: for clear filter by CREATED_BY_ID, MODIFY_BY_ID and ASSIGNED_BY_ID
if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	if(isset($_REQUEST['CREATED_BY_ID_name']) && $_REQUEST['CREATED_BY_ID_name'] === '')
	{
		$_REQUEST['CREATED_BY_ID'] = $_GET['CREATED_BY_ID'] = array();
	}

	if(isset($_REQUEST['MODIFY_BY_ID_name']) && $_REQUEST['MODIFY_BY_ID_name'] === '')
	{
		$_REQUEST['MODIFY_BY_ID'] = $_GET['MODIFY_BY_ID'] = array();
	}

	if(isset($_REQUEST['ASSIGNED_BY_ID_name']) && $_REQUEST['ASSIGNED_BY_ID_name'] === '')
	{
		$_REQUEST['ASSIGNED_BY_ID'] = $_GET['ASSIGNED_BY_ID'] = array();
	}
}

$arFilter += $filterOptions->GetFilter($arResult['FILTER']);
$CCrmUserType->PrepareListFilterValues($arResult['FILTER'], $arFilter, $arResult['GRID_ID']);
$USER_FIELD_MANAGER->AdminListAddFilter(CCrmQuote::$sUFEntityID, $arFilter);

//region Apply Search Restrictions
$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
if(!$searchRestriction->isExceeded(CCrmOwnerType::Quote))
{
	if(isset($arFilter['FIND']))
	{
		if(is_string($arFilter['FIND']))
		{
			$find = trim($arFilter['FIND']);
			if($find !== '')
			{
				$arFilter['SEARCH_CONTENT'] = $find;
			}
		}
		unset($arFilter['FIND']);
	}
}
else
{
	$arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
		array('ENTITY_TYPE_ID' => CCrmOwnerType::Quote)
	);
}


CCrmEntityHelper::PrepareMultiFieldFilter($arFilter, array(), '=%', false);
$arImmutableFilters = array(
	'FM', 'ID', 'ASSIGNED_BY_ID', 'CURRENCY_ID',
	'CONTACT_ID', 'CONTACT_ID_value', 'ASSOCIATED_CONTACT_ID',
	'COMPANY_ID', 'COMPANY_ID_value',
	'LEAD_ID', 'LEAD_ID_value',
	'DEAL_ID', 'DEAL_ID_value',
	'MYCOMPANY_ID', 'MYCOMPANY_ID_value',
	'CREATED_BY_ID', 'MODIFY_BY_ID', 'PRODUCT_ROW_PRODUCT_ID',
	'WEBFORM_ID', 'TRACKING_SOURCE_ID', 'TRACKING_CHANNEL_CODE',
	'SEARCH_CONTENT',
	'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
);
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	$arMatch = array();

	if(
		in_array(
			$k, array(
				'PRODUCT_ID', /*'TYPE_ID', */'STATUS_ID',
				'COMPANY_ID', 'LEAD_ID', 'DEAL_ID', 'CONTACT_ID', 'MYCOMPANY_ID'
			)
		))
	{
		// Bugfix #23121 - to suppress comparison by LIKE
		$arFilter['='.$k] = $v;
		unset($arFilter[$k]);
	}
	elseif ($k === 'ENTITIES_LINKS')
	{
		$arEntitiesFilter = [];

		try
		{
			$v = Bitrix\Main\Web\Json::decode($v);
			if(count($v) > 0)
			{
				foreach ($v as $entityType => $entityValues)
				{
					$entityTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::ResolveID($entityType));
					if (!empty($entityTypeName))
					{
						foreach ($entityValues as $value)
						{
							$value = intval($value);
							if ($value > 0)
							{
								$arEntitiesFilter[$entityTypeName][] = $value;
							}
						}
					}
				}
			}
		}
		catch (Main\ArgumentException $e)
		{
			$ownerData = explode('_', $v);
			if(count($ownerData) > 1)
			{
				$ownerTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::ResolveID($ownerData[0]));
				$ownerID = intval($ownerData[1]);
				if(
					!empty($ownerTypeName)
					&& $ownerID > 0
				)
				{
					$arEntitiesFilter[$ownerTypeName.'_ID'] = $ownerID;
				}
			}
		}

		// for internalize
		if (!empty($arEntitiesFilter))
		{
			foreach ($arEntitiesFilter as $key => $val)
			{
				$arFilter[$key.'_ID'] = Bitrix\Main\Web\Json::encode([$key => $val]);
			}
		}

		unset($arEntitiesFilter);
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		\Bitrix\Crm\UI\Filter\Range::prepareFrom($arFilter, $arMatch[1], $v);
	}
	elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if ($v != '' && ($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
		{
			$v = CCrmDateTimeHelper::SetMaxDayTime($v);
		}
		\Bitrix\Crm\UI\Filter\Range::prepareTo($arFilter, $arMatch[1], $v);
	}
	elseif (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (strpos($k, 'UF_') !== 0 && $k != 'LOGIC')
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

//region POST & GET actions processing
if($actionData['ACTIVE'])
{
	if ($actionData['METHOD'] == 'POST')
	{
		if($actionData['NAME'] == 'delete')
		{
			if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
			{
				$arFilterDel = array();
				if (!$actionData['ALL_ROWS'])
				{
					$arFilterDel = array('ID' => $actionData['ID']);
				}
				else
				{
					// Fix for issue #26628
					$arFilterDel += $arFilter;
				}

				$obRes = CCrmQuote::GetList(array(), $arFilterDel, false, false, array('ID'));
				while($arQuote = $obRes->Fetch())
				{
					$ID = $arQuote['ID'];
					$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if (/*---bizproc---$CCrmBizProc->Delete($ID, $arEntityAttr[$ID])
						&& */$CCrmQuote->Delete($ID))
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif($actionData['NAME'] == 'edit')
		{
			if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable']) && (is_array($arHead['editable']) || $arHead['editable'] === true) && isset($arSrcData[$arHead['id']]))
						{
							$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
						}
					}
					if (!empty($arUpdateData))
					{
						$DB->StartTransaction();

						if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
						{
							$DB->Commit();
						}
						else
						{
							$DB->Rollback();
						}
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'tasks')
		{
			if (isset($actionData['ID']) && is_array($actionData['ID']))
			{
				$arTaskID = array();
				foreach($actionData['ID'] as $ID)
				{
					$arTaskID[] = 'D_'.$ID;
				}

				$APPLICATION->RestartBuffer();

				$taskUrl = CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
						array(
							'task_id' => 0,
							'user_id' => $userID
						)
					),
					array(
						'UF_CRM_TASK' => implode(';', $arTaskID),
						'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
						'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
						'back_url' => urlencode($arParams['PATH_TO_QUOTE_LIST'])
					)
				);
				if ($actionData['AJAX_CALL'])
				{
					echo '<script> parent.window.location = "'.CUtil::JSEscape($taskUrl).'";</script>';
					exit();
				}
				else
				{
					LocalRedirect($taskUrl);
				}
			}
		}
		elseif ($actionData['NAME'] == 'set_status')
		{
			if(isset($actionData['STATUS_ID']) && $actionData['STATUS_ID'] != '') // Fix for issue #26628
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.

					$dbRes = CCrmQuote::GetList(array(), $arActionFilter, false, false, array('ID'));
					while($arQuote = $dbRes->Fetch())
					{
						$arIDs[] = $arQuote['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'STATUS_ID' => $actionData['STATUS_ID']
					);

					if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'assign_to')
		{
			if(isset($actionData['ASSIGNED_BY_ID']))
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.
					$dbRes = CCrmQuote::GetList(array(), $arActionFilter, false, false, array('ID'));
					while($arQuote = $dbRes->Fetch())
					{
						$arIDs[] = $arQuote['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', $arIDs);

				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'mark_as_opened')
				{
					if(isset($actionData['OPENED']) && $actionData['OPENED'] != '')
					{
						$isOpened = strtoupper($actionData['OPENED']) === 'Y' ? 'Y' : 'N';
						$arIDs = array();
						if ($actionData['ALL_ROWS'])
						{
							$arActionFilter = $arFilter;
							$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.

							$dbRes = CCrmQuote::GetList(
								array(),
								$arActionFilter,
								false,
								false,
								array('ID', 'OPENED')
							);

							while($arQuote = $dbRes->Fetch())
							{
								if(isset($arQuote['OPENED']) && $arQuote['OPENED'] === $isOpened)
								{
									continue;
								}

								$arIDs[] = $arQuote['ID'];
							}
						}
						elseif (isset($actionData['ID']) && is_array($actionData['ID']))
						{
							$dbRes = CCrmQuote::GetList(
								array(),
								array(
									'@ID'=> $actionData['ID'],
									'CHECK_PERMISSIONS' => 'N'
								),
								false,
								false,
								array('ID', 'OPENED')
							);

							while($arQuote = $dbRes->Fetch())
							{
								if(isset($arQuote['OPENED']) && $arQuote['OPENED'] === $isOpened)
								{
									continue;
								}

								$arIDs[] = $arQuote['ID'];
							}
						}

						$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', $arIDs);
						foreach($arIDs as $ID)
						{
							if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
							{
								continue;
							}

							$DB->StartTransaction();
							$arUpdateData = array('OPENED' => $isOpened);
							if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
							{
								$DB->Commit();
							}
							else
							{
								$DB->Rollback();
							}
						}
					}
				}
		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($arParams['PATH_TO_QUOTE_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmPerms->CheckEnityAccess('QUOTE', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if($CCrmQuote->Delete($ID))
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();
				}
			}
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_quote' : $arParams['PATH_TO_QUOTE_LIST']);
		}
	}
}
//endregion POST & GET actions processing

$_arSort = $gridOptions->GetSorting(
	array(
		'sort' => array('date_create' => 'desc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

// Remove column for deleted UF
$arSelect = $gridOptions->GetVisibleColumns();

if ($CCrmUserType->NormalizeFields($arSelect))
{
	$gridOptions->SetVisibleColumns($arSelect);
}

/*---bizproc---$arResult['ENABLE_BIZPROC'] = IsModuleInstalled('bizproc');*/
$arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
if($arResult['ENABLE_TASK'])
{
	$arResult['TASK_CREATE_URL'] = CHTTP::urlAddParams(
		CComponentEngine::MakePathFromTemplate(
			COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
			array(
				'task_id' => 0,
				'user_id' => $userID
			)
		),
		array(
			'UF_CRM_TASK' => '#ENTITY_KEYS#',
			'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
			'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
			'back_url' => urlencode($arParams['PATH_TO_QUOTE_LIST'])
		)
	);
}

// Export all fields
if ($isInExportMode && $isStExport && $isStExportAllFields)
{
	$arSelect = $exportAllFieldsList;
}

// Fill in default values if empty
if (empty($arSelect))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arSelect[] = $arHeader['id'];
		}
	}
}

$arSelectedHeaders = $arSelect;

if(!in_array('TITLE', $arSelect, true))
{
	//Is required for activities management
	$arSelect[] = 'TITLE';
}

if(in_array('CREATED_BY', $arSelect, true))
{
	$arSelect[] = 'CREATED_BY_LOGIN';
	$arSelect[] = 'CREATED_BY_NAME';
	$arSelect[] = 'CREATED_BY_LAST_NAME';
	$arSelect[] = 'CREATED_BY_SECOND_NAME';
}

if(in_array('MODIFY_BY', $arSelect, true))
{
	$arSelect[] = 'MODIFY_BY_LOGIN';
	$arSelect[] = 'MODIFY_BY_NAME';
	$arSelect[] = 'MODIFY_BY_LAST_NAME';
	$arSelect[] = 'MODIFY_BY_SECOND_NAME';
}

if(in_array('QUOTE_SUMMARY', $arSelect, true))
{
	$arSelect[] = 'QUOTE_NUMBER';
	$arSelect[] = 'TITLE';
}

if(in_array('SUM', $arSelect, true))
{
	$arSelect[] = 'OPPORTUNITY';
	$arSelect[] = 'CURRENCY_ID';
}

if(in_array('MYCOMPANY_ID', $arSelect, true))
{
	$arSelect[] = 'MYCOMPANY_TITLE';
}

if (in_array('ENTITIES_LINKS', $arSelect, true))
{
	$arSelect[] = 'CONTACT_ID';
	$arSelect[] = 'COMPANY_TITLE';
	$arSelect[] = 'COMPANY_ID';
	$arSelect[] = 'CONTACT_HONORIFIC';
	$arSelect[] = 'CONTACT_NAME';
	$arSelect[] = 'CONTACT_SECOND_NAME';
	$arSelect[] = 'CONTACT_LAST_NAME';
	$arSelect[] = 'LEAD_ID';
	$arSelect[] = 'LEAD_TITLE';
	$arSelect[] = 'DEAL_ID';
	$arSelect[] = 'DEAL_TITLE';
}
else if(in_array('QUOTE_CLIENT', $arSelect, true))
{
	$arSelect[] = 'CONTACT_ID';
	$arSelect[] = 'COMPANY_ID';
	$arSelect[] = 'COMPANY_TITLE';
	$arSelect[] = 'CONTACT_HONORIFIC';
	$arSelect[] = 'CONTACT_NAME';
	$arSelect[] = 'CONTACT_SECOND_NAME';
	$arSelect[] = 'CONTACT_LAST_NAME';
}
else
{
	if(in_array('CONTACT_ID', $arSelect, true))
	{
		$arSelect[] = 'CONTACT_HONORIFIC';
		$arSelect[] = 'CONTACT_NAME';
		$arSelect[] = 'CONTACT_SECOND_NAME';
		$arSelect[] = 'CONTACT_LAST_NAME';
	}
	if(in_array('COMPANY_ID', $arSelect, true))
	{
		$arSelect[] = 'COMPANY_TITLE';
	}
	if(in_array('LEAD_ID', $arSelect, true))
	{
		$arSelect[] = 'LEAD_TITLE';
	}
	if(in_array('DEAL_ID', $arSelect, true))
	{
		$arSelect[] = 'DEAL_TITLE';
	}
}

// Always need to remove the menu items
if (!in_array('STATUS_ID', $arSelect))
	$arSelect[] = 'STATUS_ID';

// For bizproc
if (!in_array('ASSIGNED_BY', $arSelect))
	$arSelect[] = 'ASSIGNED_BY';

// For preparing user html
if (!in_array('ASSIGNED_BY_LOGIN', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_LOGIN';

if (!in_array('ASSIGNED_BY_NAME', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_NAME';

if (!in_array('ASSIGNED_BY_LAST_NAME', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_LAST_NAME';

if (!in_array('ASSIGNED_BY_SECOND_NAME', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_SECOND_NAME';

// ID must present in select
if(!in_array('ID', $arSelect))
{
	$arSelect[] = 'ID';
}

if ($isInExportMode)
{
	$productHeaderIndex = array_search('PRODUCT_ID', $arSelectedHeaders, true);
	if($productHeaderIndex <= 0 && $isStExportProductsFields)
	{
		$arSelectedHeaders[] = 'PRODUCT_ID';
	}
	elseif($productHeaderIndex > 0 && !$isStExportProductsFields)
	{
		unset($arSelectedHeaders[$productHeaderIndex]);
		$arSelectedHeaders = array_values($arSelectedHeaders);
	}

	CCrmComponentHelper::PrepareExportFieldsList(
		$arSelectedHeaders,
		array(
			'QUOTE_SUMMARY' => array(
				'QUOTE_NUMBER',
				'TITLE'
			),
			'QUOTE_CLIENT' => array(
				'CONTACT_ID',
				'COMPANY_ID'
			),
			'SUM' => array(
				'OPPORTUNITY',
				'CURRENCY_ID'
			)
		)
	);

	if(!in_array('ID', $arSelectedHeaders))
	{
		$arSelectedHeaders[] = 'ID';
	}

	$arResult['SELECTED_HEADERS'] = $arSelectedHeaders;
}

$nTopCount = false;
if ($isInGadgetMode)
{
	$arSelect = array(
		'DATE_CREATE', 'TITLE', 'STATUS_ID',/* 'TYPE_ID',*/
		'OPPORTUNITY', 'CURRENCY_ID', 'COMMENTS',
		'CONTACT_ID', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME',
		'CONTACT_LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE',
		'LEAD_ID', 'LEAD_TITLE', 'DEAL_ID', 'DEAL_TITLE'
	);
	$nTopCount = $arParams['QUOTE_COUNT'];
}

if($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
{
	$arFilter['PERMISSION'] = 'EXPORT';
}

// HACK: Make custom sort for ASSIGNED_BY field
$arSort = $arResult['SORT'];
if(isset($arSort['assigned_by']))
{
	if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isUserNameSortingEnabled())
	{
		$arSort['assigned_by_last_name'] = $arSort['assigned_by'];
		$arSort['assigned_by_name'] = $arSort['assigned_by'];
	}
	else
	{
		$arSort['assigned_by_id'] = $arSort['assigned_by'];
	}
	unset($arSort['assigned_by']);
}
$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(isset($arSort['status_sort']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STATUS_SORT';
}
if(isset($arSort['closedate']))
{
	$arOptions['NULLS_LAST'] = true;
}
if(isset($arSort['contact_full_name']))
{
	$arSort['contact_last_name'] = $arSort['contact_full_name'];
	$arSort['contact_name'] = $arSort['contact_full_name'];
	unset($arSort['contact_full_name']);
}
if(isset($arSort['quote_client']))
{
	$arSort['contact_last_name'] = $arSort['quote_client'];
	$arSort['contact_name'] = $arSort['quote_client'];
	$arSort['company_title'] = $arSort['quote_client'];
	unset($arSort['quote_client']);
}
if(isset($arSort['quote_summary']))
{
	$arSort['quote_number'] = $arSort['quote_summary'];
	$arSort['title'] = $arSort['quote_summary'];
	unset($arSort['quote_summary']);
}
if($arSort['date_create'])
{
	$arSort['id'] = $arSort['date_create'];
	unset($arSort['date_create']);
}
if(!empty($arSort) && !isset($arSort['id']))
{
	$arSort['id'] = reset($arSort);
}
if(isset($arParams['IS_EXTERNAL_CONTEXT']))
{
	$arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

//region Navigation data initialization
$pageNum = 0;
if ($isInExportMode && $isStExport)
{
	$pageSize = !empty($arParams['STEXPORT_PAGE_SIZE']) ? $arParams['STEXPORT_PAGE_SIZE'] : $arParams['QUOTE_COUNT'];
}
else
{
	$pageSize = !$isInExportMode
		? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['QUOTE_COUNT']) : 0;
}

$enableNextPage = false;
if(isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
{
	$pageNum = 1;
}
elseif($pageSize > 0 && (isset($arParams['PAGE_NUMBER']) || isset($_REQUEST['page'])))
{
	$pageNum = (int)(isset($arParams['PAGE_NUMBER']) ? $arParams['PAGE_NUMBER'] : $_REQUEST['page']);
	if($pageNum < 0)
	{
		//Backward mode
		$offset = -($pageNum + 1);
		$total = CCrmQuote::GetList(array(), $arFilter, array());
		$pageNum = (int)(ceil($total / $pageSize)) - $offset;
		if($pageNum <= 0)
		{
			$pageNum = 1;
		}
	}
}

if (!($isInExportMode && $isStExport))
{
	if ($pageNum > 0)
	{
		if (!isset($_SESSION['CRM_PAGINATION_DATA']))
		{
			$_SESSION['CRM_PAGINATION_DATA'] = array();
		}
		$_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum, 'PAGE_SIZE' => $pageSize);
	}
	else
	{
		if (!$bInternal
			&& !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
			&& isset($_SESSION['CRM_PAGINATION_DATA'])
			&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']])
		)
		{
			$paginationData = $_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']];
			if (isset($paginationData['PAGE_NUM'])
				&& isset($paginationData['PAGE_SIZE'])
				&& $paginationData['PAGE_SIZE'] == $pageSize
			)
			{
				$pageNum = (int)$paginationData['PAGE_NUM'];
			}
		}

		if ($pageNum <= 0)
		{
			$pageNum = 1;
		}
	}
}
//endregion

if ($isInExportMode && $isStExport && $pageNum === 1)
{
	$total = CCrmQuote::GetList(array(), $arFilter, array());
	if (is_numeric($total))
	{
		$arResult['STEXPORT_TOTAL_ITEMS'] = (int)$total;
	}
}

$limit = $pageSize + 1;
if ($isInExportMode && $isStExport)
{
	$total = (int)$arResult['STEXPORT_TOTAL_ITEMS'];
	$processed = ($pageNum - 1) * $pageSize;
	if ($total - $processed <= $pageSize)
		$limit = $total - $processed;
	unset($total, $processed);
}

if ($isInGadgetMode && isset($arNavParams['nTopCount']))
{
	$navListOptions = array_merge($arOptions, array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount'])));
}
else
{
	$navListOptions = ($isInExportMode && !$isStExport)
		? array()
		: array_merge(
			$arOptions,
			array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
		);
}

$arSelect = array_unique($arSelect, SORT_STRING);
$obRes = CCrmQuote::GetList($arSort, $arFilter, false, false, $arSelect, $navListOptions);

$arResult['QUOTE'] = array();
$arResult['QUOTE_ID'] = array();
$arResult['QUOTE_UF'] = array();
$now = time() + CTimeZone::GetOffset();

$qty = 0;
while($arQuote = $obRes->GetNext())
{
	if($pageSize > 0 && ++$qty > $pageSize)
	{
		$enableNextPage = true;
		break;
	}

	$arQuote['CLOSEDATE'] = !empty($arQuote['CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arQuote['CLOSEDATE']), 'SHORT', SITE_ID)) : '';
	$arQuote['BEGINDATE'] = !empty($arQuote['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arQuote['BEGINDATE']), 'SHORT', SITE_ID)) : '';
	$arQuote['~CLOSEDATE'] = $arQuote['CLOSEDATE'];
	$arQuote['~BEGINDATE'] = $arQuote['BEGINDATE'];

	$currencyID =  isset($arQuote['~CURRENCY_ID']) ? $arQuote['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arQuote['~CURRENCY_ID'] = $currencyID;
	$arQuote['CURRENCY_ID'] = htmlspecialcharsbx($currencyID);

	$arQuote['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($arQuote['~OPPORTUNITY'], $arQuote['~CURRENCY_ID']);

	$entityID = $arQuote['ID'];

	$arQuote['PATH_TO_QUOTE_DETAILS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_QUOTE_DETAILS'],
		array('quote_id' => $entityID)
	);

	if($arResult['ENABLE_SLIDER'])
	{
		$arQuote['PATH_TO_QUOTE_SHOW'] = $arQuote['PATH_TO_QUOTE_DETAILS'];
		$arQuote['PATH_TO_QUOTE_EDIT'] = CCrmUrlUtil::AddUrlParams(
			$arQuote['PATH_TO_QUOTE_DETAILS'],
			array('init_mode' => 'edit')
		);
	}
	else
	{
		$arQuote['PATH_TO_QUOTE_SHOW'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_QUOTE_SHOW'],
			array('quote_id' => $entityID)
		);

		$arQuote['PATH_TO_QUOTE_EDIT'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_QUOTE_EDIT'],
			array('quote_id' => $entityID)
		);
	}

	$arQuote['PATH_TO_QUOTE_COPY'] =  CHTTP::urlAddParams(
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_QUOTE_EDIT'],
			array('quote_id' => $entityID)
		),
		array('copy' => 1)
	);
	$arQuote['PATH_TO_QUOTE_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_QUOTE_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => bitrix_sessid())
	);
	//region Contact
	$contactID = isset($arQuote['~CONTACT_ID']) ? (int)$arQuote['~CONTACT_ID'] : 0;
	$arQuote['PATH_TO_CONTACT_SHOW'] = $contactID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $contactID));
	$arQuote['~CONTACT_FORMATTED_NAME'] = $contactID <= 0 ? ''
		: CCrmContact::PrepareFormattedName(
			array(
				'HONORIFIC' => isset($arQuote['~CONTACT_HONORIFIC']) ? $arQuote['~CONTACT_HONORIFIC'] : '',
				'NAME' => isset($arQuote['~CONTACT_NAME']) ? $arQuote['~CONTACT_NAME'] : '',
				'LAST_NAME' => isset($arQuote['~CONTACT_LAST_NAME']) ? $arQuote['~CONTACT_LAST_NAME'] : '',
				'SECOND_NAME' => isset($arQuote['~CONTACT_SECOND_NAME']) ? $arQuote['~CONTACT_SECOND_NAME'] : ''
			)
		);
	$arQuote['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arQuote['~CONTACT_FORMATTED_NAME']);
	$arQuote['~CONTACT_FULL_NAME'] = $contactID <= 0 ? ''
		: CCrmContact::GetFullName(
			array(
				'HONORIFIC' => isset($arQuote['~CONTACT_HONORIFIC']) ? $arQuote['~CONTACT_HONORIFIC'] : '',
				'NAME' => isset($arQuote['CONTACT_NAME']) ? $arQuote['CONTACT_NAME'] : '',
				'LAST_NAME' => isset($arQuote['CONTACT_LAST_NAME']) ? $arQuote['CONTACT_LAST_NAME'] : '',
				'SECOND_NAME' => isset($arQuote['CONTACT_SECOND_NAME']) ? $arQuote['CONTACT_SECOND_NAME'] : ''
			)
		);
	$arQuote['CONTACT_FULL_NAME'] = htmlspecialcharsbx($arQuote['~CONTACT_FULL_NAME']);
	if($contactID > 0)
	{
		$arQuote['CONTACT_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_ID' => $contactID
		);

		if(!CCrmContact::CheckReadPermission($contactID, $CCrmPerms))
		{
			$arQuote['CONTACT_INFO']['IS_HIDDEN'] = true;
			$arQuote['CONTACT_LINK_HTML'] = CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Contact);
		}
		else
		{
			$arQuote['CONTACT_INFO'] =
				array_merge(
					$arQuote['CONTACT_INFO'],
					array(
						'TITLE' => isset($arQuote['CONTACT_FORMATTED_NAME']) ? $arQuote['CONTACT_FORMATTED_NAME'] : ('['.$contactID.']'),
						'PREFIX' => "QUOTE_{$arQuote['~ID']}",
						'DESCRIPTION' => isset($arQuote['~COMPANY_TITLE']) ? $arQuote['~COMPANY_TITLE'] : ''
					)
				);

			$arQuote['CONTACT_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
				array_merge(
					$arQuote['CONTACT_INFO'],
					array('PREFIX' => uniqid("crm_quote_contact_link_"),)
				)
			);
		}
	}
	//endregion
	//region Company
	$companyID = isset($arQuote['~COMPANY_ID']) ? (int)$arQuote['~COMPANY_ID'] : 0;
	$arQuote['PATH_TO_COMPANY_SHOW'] = $companyID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $companyID));
	if($companyID > 0)
	{
		$arQuote['COMPANY_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_ID' => $companyID
		);

		if(!CCrmCompany::CheckReadPermission($companyID, $CCrmPerms))
		{
			$arQuote['COMPANY_INFO']['IS_HIDDEN'] = true;
			$arQuote['COMPANY_LINK_HTML'] = CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Company);
		}
		else
		{
			$arQuote['COMPANY_INFO'] =
				array_merge(
					$arQuote['COMPANY_INFO'],
					array(
						'TITLE' => isset($arQuote['~COMPANY_TITLE']) ? $arQuote['~COMPANY_TITLE'] : ('['.$companyID.']'),
						'PREFIX' => "QUOTE_{$arQuote['~ID']}"
					)
				);

			$arQuote['COMPANY_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
				array_merge(
					$arQuote['COMPANY_INFO'],
					array('PREFIX' => uniqid("crm_quote_company_link_"),)
				)
			);
		}
	}
	//endregion
	//region Lead
	$leadID = isset($arQuote['~LEAD_ID']) ? (int)$arQuote['~LEAD_ID'] : 0;
	$arQuote['PATH_TO_LEAD_SHOW'] = $leadID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'], array('lead_id' => $leadID));
	if($leadID > 0)
	{
		$arQuote['LEAD_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'ENTITY_ID' => $leadID
		);

		if(!CCrmLead::CheckReadPermission($leadID, $CCrmPerms))
		{
			$arQuote['LEAD_INFO']['IS_HIDDEN'] = true;
			$arQuote['LEAD_LINK_HTML'] = CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Lead);
		}
		else
		{
			$arQuote['LEAD_INFO'] =
				array_merge(
					$arQuote['LEAD_INFO'],
					array(
						'TITLE' => isset($arQuote['~LEAD_TITLE']) ? $arQuote['~LEAD_TITLE'] : ('['.$leadID.']'),
						'PREFIX' => "QUOTE_{$arQuote['~ID']}"
					)
				);

			$arQuote['LEAD_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
				array_merge(
					$arQuote['LEAD_INFO'],
					array('PREFIX' => uniqid("crm_quote_lead_link_"),)
				)
			);
		}
	}
	//endregion
	//region Deal
	$dealID = isset($arQuote['~DEAL_ID']) ? (int)$arQuote['~DEAL_ID'] : 0;
	$arQuote['PATH_TO_DEAL_SHOW'] = $dealID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $dealID));
	if($dealID > 0)
	{
		$arQuote['DEAL_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
			'ENTITY_ID' => $dealID
		);

		if(!CCrmDeal::CheckReadPermission($dealID, $CCrmPerms))
		{
			$arQuote['DEAL_INFO']['IS_HIDDEN'] = true;
			$arQuote['DEAL_LINK_HTML'] = CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Deal);
		}
		else
		{
			$arQuote['DEAL_INFO'] =
				array_merge(
					$arQuote['DEAL_INFO'],
					array(
						'TITLE' => isset($arQuote['~DEAL_TITLE']) ? $arQuote['~DEAL_TITLE'] : ('['.$dealID.']'),
						'PREFIX' => "QUOTE_{$arQuote['~ID']}"
					)
				);

			$arQuote['DEAL_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
				array_merge(
					$arQuote['DEAL_INFO'],
					array('PREFIX' => uniqid("crm_quote_deal_link_"),)
				)
			);
		}
	}
	//endregion
	//region My Company
	$myCompanyID = isset($arQuote['~MYCOMPANY_ID']) ? (int)$arQuote['~MYCOMPANY_ID'] : 0;
	$arQuote['PATH_TO_MYCOMPANY_SHOW'] = $myCompanyID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MYCOMPANY_SHOW'], array('company_id' => $myCompanyID));
	if($myCompanyID > 0)
	{
		$arQuote['MY_COMPANY_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_ID' => $myCompanyID,
			'TITLE' => isset($arQuote['~MYCOMPANY_TITLE']) ? $arQuote['~MYCOMPANY_TITLE'] : ('['.$myCompanyID.']'),
			'PREFIX' => "QUOTE_{$arQuote['~ID']}"
		);
	}
	//endregion
	$arQuote['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arQuote['ASSIGNED_BY']
		)
	);
	$arQuote['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_BP'],
		array(
			'user_id' => $userID
		)
	);

	$arQuote['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arQuote['CREATED_BY']
		)
	);

	$arQuote['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arQuote['MODIFY_BY']
		)
	);

	$arQuote['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arQuote['CREATED_BY_LOGIN'],
			'NAME' => $arQuote['CREATED_BY_NAME'],
			'LAST_NAME' => $arQuote['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arQuote['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arQuote['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arQuote['MODIFY_BY_LOGIN'],
			'NAME' => $arQuote['MODIFY_BY_NAME'],
			'LAST_NAME' => $arQuote['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arQuote['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);

	$statusID = isset($arQuote['STATUS_ID']) ? $arQuote['STATUS_ID'] : '';
	$arQuote['QUOTE_STATUS_NAME'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;

	if ($arResult['ENABLE_TASK'])
	{
		$arQuote['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $userID
				)
			),
			array(
				'UF_CRM_TASK' => 'D_'.$entityID,
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX').' '),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_QUOTE_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arQuote['PATH_TO_INVOICE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_INVOICE_EDIT'], array('invoice_id' => 0)),
				array('quote' => $entityID)
			);
	}

	$arQuote['ASSIGNED_BY_ID'] = $arQuote['~ASSIGNED_BY_ID'] = intval($arQuote['ASSIGNED_BY']);
	$arQuote['ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arQuote['ASSIGNED_BY_LOGIN'],
			'NAME' => $arQuote['ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arQuote['ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arQuote['ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	);

	$arQuote['FORMATTED_ENTITIES_LINKS'] =
		'<div class="crm-info-links-wrapper">'.PHP_EOL.
		"\t".'<div class="crm-info-contact-wrapper">'.
			(isset($arQuote['CONTACT_LINK_HTML']) ?
				htmlspecialchars_decode($arQuote['CONTACT_LINK_HTML']) : '').'</div>'.PHP_EOL.
		"\t".'<div class="crm-info-company-wrapper">'.
			(isset($arQuote['COMPANY_LINK_HTML']) ? $arQuote['COMPANY_LINK_HTML'] : '').'</div>'.PHP_EOL.
		"\t".'<div class="crm-info-lead-wrapper">'.
			(isset($arQuote['LEAD_LINK_HTML']) ? $arQuote['LEAD_LINK_HTML'] : '').'</div>'.PHP_EOL.
		"\t".'<div class="crm-info-deal-wrapper">'.
			(isset($arQuote['DEAL_LINK_HTML']) ? $arQuote['DEAL_LINK_HTML'] : '').'</div>'.PHP_EOL.
		'</div>'.PHP_EOL;

	// color coding
	$arQuote['EXPIRED_FLAG'] = false;
	$arQuote['IN_COUNTER_FLAG'] = false;
	if (!empty($arQuote['CLOSEDATE']))
	{
		$tsCloseDate = MakeTimeStamp($arQuote['CLOSEDATE']);
		$tsNow = time() + CTimeZone::GetOffset();
		$tsMax = mktime(0, 0, 0, date('m',$tsNow), date('d',$tsNow), date('Y',$tsNow));

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arQuote
		);
		$bReckoned = CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentQuoteActivies, $counterData);
		if ($bReckoned)
		{
			$arQuote['IN_COUNTER_FLAG'] = true;
			if ($tsCloseDate < $tsMax)
				$arQuote['EXPIRED_FLAG'] = true;
		}
		unset($tsCloseDate, $tsNow, $counterData);
	}

	$arResult['QUOTE'][$entityID] = $arQuote;
	$arResult['QUOTE_UF'][$entityID] = array();
	$arResult['QUOTE_ID'][$entityID] = $entityID;
}

$arResult['STEXPORT_IS_FIRST_PAGE'] = $pageNum === 1 ? 'Y' : 'N';
$arResult['STEXPORT_IS_LAST_PAGE'] = $enableNextPage ? 'N' : 'Y';

//region Navigation data storing
$arResult['PAGINATION'] = array(
	'PAGE_NUM' => $pageNum,
	'ENABLE_NEXT_PAGE' => $enableNextPage,
	'URL' => $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'))
);
$arResult['DB_FILTER'] = $arFilter;

if(!isset($_SESSION['CRM_GRID_DATA']))
{
	$_SESSION['CRM_GRID_DATA'] = array();
}
$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
//endregion

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['QUOTE'],
	$arResult['QUOTE_UF'],
	($isInExportMode ? ', ' : '<br />'),
	$isInExportMode,
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.quote.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : false;
if($arResult['ENABLE_TOOLBAR'])
{
	$arResult['PATH_TO_QUOTE_ADD'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_QUOTE_EDIT'],
		array('quote_id' => 0)
	);

	$addParams = array();

	if($bInternal && isset($arParams['INTERNAL_CONTEXT']) && is_array($arParams['INTERNAL_CONTEXT']))
	{
		$internalContext = $arParams['INTERNAL_CONTEXT'];
		if(isset($internalContext['CONTACT_ID']))
		{
			$addParams['contact_id'] = $internalContext['CONTACT_ID'];
		}
		if(isset($internalContext['COMPANY_ID']))
		{
			$addParams['company_id'] = $internalContext['COMPANY_ID'];
		}
		if(isset($internalContext['LEAD_ID']))
		{
			$addParams['lead_id'] = $internalContext['LEAD_ID'];
		}
		if(isset($internalContext['DEAL_ID']))
		{
			$addParams['deal_id'] = $internalContext['DEAL_ID'];
		}
	}

	if(!empty($addParams))
	{
		$arResult['PATH_TO_QUOTE_ADD'] = CHTTP::urlAddParams(
			$arResult['PATH_TO_QUOTE_ADD'],
			$addParams
		);
	}
}

if (isset($arResult['QUOTE_ID']) && !empty($arResult['QUOTE_ID']))
{
	// try to load product rows
	$arProductRows = CCrmQuote::LoadProductRows(array_keys($arResult['QUOTE_ID']));
	foreach($arProductRows as $arProductRow)
	{
		$ownerID = $arProductRow['OWNER_ID'];
		if(!isset($arResult['QUOTE'][$ownerID]))
		{
			continue;
		}

		$arEntity = &$arResult['QUOTE'][$ownerID];
		if(!isset($arEntity['PRODUCT_ROWS']))
		{
			$arEntity['PRODUCT_ROWS'] = array();
		}
		$arEntity['PRODUCT_ROWS'][] = $arProductRow;
	}

	// checkig access for operation
	$arQuoteAttr = CCrmPerms::GetEntityAttr('QUOTE', $arResult['QUOTE_ID']);
	foreach ($arResult['QUOTE_ID'] as $iQuoteId)
	{
		$arResult['QUOTE'][$iQuoteId]['EDIT'] = $CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arQuoteAttr[$iQuoteId]);
		$arResult['QUOTE'][$iQuoteId]['DELETE'] = $CCrmPerms->CheckEnityAccess('QUOTE', 'DELETE', $arQuoteAttr[$iQuoteId]);
	}
}

if (!$isInExportMode)
{
	$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] =
		$arResult['NEED_FOR_TRANSFER_PS_REQUISITES'] =
		$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = false;

	if(!$bInternal)
	{
		if(COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_SEARCH_CONTENT', 'N') === 'Y')
		{
			$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
		}

		if(CCrmPerms::IsAdmin())
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_ATTR', 'N') === 'Y')
			{
				$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
				$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] = true;
			}
			if(COption::GetOptionString('crm', '~CRM_TRANSFER_PS_PARAMS_TO_REQUISITES', 'N') === 'Y')
			{
				$arResult['NEED_FOR_TRANSFER_PS_REQUISITES'] = true;
			}
		}
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.quote/include/nav.php');
	return $arResult['ROWS_COUNT'];
}
else
{
	if ($isStExport)
	{
		$this->__templateName = '.default';

		$this->IncludeComponentTemplate($sExportType);

		return array(
			'PROCESSED_ITEMS' => count($arResult['QUOTE']),
			'TOTAL_ITEMS' => $arResult['STEXPORT_TOTAL_ITEMS']
		);
	}
	else
	{
		$APPLICATION->RestartBuffer();
		// hack. any '.default' customized template should contain 'excel' page
		$this->__templateName = '.default';

		if ($sExportType === 'carddav')
		{
			Header('Content-Type: text/vcard');
		}
		elseif ($sExportType === 'csv')
		{
			Header('Content-Type: text/csv');
			Header('Content-Disposition: attachment;filename=quotes.csv');
		}
		elseif ($sExportType === 'excel')
		{
			Header('Content-Type: application/vnd.ms-excel');
			Header('Content-Disposition: attachment;filename=quotes.xls');
		}
		Header('Content-Type: application/octet-stream');
		Header('Content-Transfer-Encoding: binary');

		// add UTF-8 BOM marker
		if (defined('BX_UTF') && BX_UTF)
			echo chr(239).chr(187).chr(191);

		$this->IncludeComponentTemplate($sExportType);

		die();
	}
}
?>