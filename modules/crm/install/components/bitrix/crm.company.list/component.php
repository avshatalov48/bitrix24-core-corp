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

$isBizProcInstalled = IsModuleInstalled('bizproc');
if (!$isErrorOccured && $isBizProcInstalled)
{
	if (!CModule::IncludeModule('bizproc'))
	{
		$errorMessage = GetMessage('BIZPROC_MODULE_NOT_INSTALLED');
		$isErrorOccured = true;
	}
	elseif (!CBPRuntime::isFeatureEnabled())
	{
		$isBizProcInstalled = false;
	}
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$isErrorOccured && !CCrmCompany::CheckReadPermission(0, $userPermissions))
{
	$errorMessage = GetMessage('CRM_PERMISSION_DENIED');
	$isErrorOccured = true;
}

//region Export params
$sExportType = !empty($arParams['EXPORT_TYPE']) ?
	strval($arParams['EXPORT_TYPE']) : (!empty($_REQUEST['type']) ? strval($_REQUEST['type']) : '');
$isStExport = false;    // Step-by-step export mode
if (!empty($sExportType))
{
	$sExportType = mb_strtolower(trim($sExportType));
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

$isStExportRequisiteMultiline = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['REQUISITE_MULTILINE'])
	&& $arParams['STEXPORT_INITIAL_OPTIONS']['REQUISITE_MULTILINE'] === 'Y');
$arResult['STEXPORT_REQUISITE_MULTILINE'] = ($isStExport && $isStExportRequisiteMultiline) ? 'Y' : 'N';

$arResult['STEXPORT_MODE'] = $isStExport ? 'Y' : 'N';
$arResult['STEXPORT_TOTAL_ITEMS'] = isset($arParams['STEXPORT_TOTAL_ITEMS']) ?
	(int)$arParams['STEXPORT_TOTAL_ITEMS'] : 0;
//endregion


$CCrmCompany = new CCrmCompany();
if (!$isErrorOccured && !empty($sExportType) && $CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'EXPORT'))
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

use Bitrix\Crm;
use Bitrix\Crm\Agent\Duplicate\Background\CompanyIndexRebuild;
use Bitrix\Crm\Agent\Duplicate\Background\CompanyMerge;
use Bitrix\Crm\Agent\Requisite\CompanyAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\CompanyUfAddressConvertAgent;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

$CCrmBizProc = new CCrmBizProc('COMPANY');

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();
$enableOutmodedFields = $arResult['ENABLE_OUTMODED_FIELDS'] = CompanySettings::getCurrent()->areOutmodedRequisitesEnabled();

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_DETAILS'] = CrmCheckPath('PATH_TO_COMPANY_DETAILS', $arParams['PATH_TO_COMPANY_DETAILS'], $APPLICATION->GetCurPage().'?company_id=#company_id#&details');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_COMPANY_MERGE'] = CrmCheckPath('PATH_TO_COMPANY_MERGE', $arParams['PATH_TO_COMPANY_MERGE'], '/company/merge/');
$arParams['PATH_TO_DEAL_DETAILS'] = CrmCheckPath('PATH_TO_DEAL_DETAILS', $arParams['PATH_TO_DEAL_DETAILS'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&details');
$arParams['PATH_TO_DEAL_EDIT']    = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_CONTACT_DETAILS'] = CrmCheckPath('PATH_TO_CONTACT_DETAILS', $arParams['PATH_TO_CONTACT_DETAILS'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&details');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_COMPANY_WIDGET'] = CrmCheckPath('PATH_TO_COMPANY_WIDGET', $arParams['PATH_TO_COMPANY_WIDGET'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_PORTRAIT'] = CrmCheckPath('PATH_TO_COMPANY_PORTRAIT', $arParams['PATH_TO_COMPANY_PORTRAIT'], $APPLICATION->GetCurPage());
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
$arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
$arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];
if($arResult['CALL_LIST_UPDATE_MODE'])
{
	AddEventHandler('crm', 'onCrmCompanyListItemBuildMenu', array('\Bitrix\Crm\CallList\CallList', 'handleOnCrmCompanyListItemBuildMenu'));
}


if(!isset($arParams['INTERNAL_CONTEXT']))
{
	$arParams['INTERNAL_CONTEXT'] = array();
}

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
$arResult['SESSION_ID'] = bitrix_sessid();
$arResult['NAVIGATION_CONTEXT_ID'] = isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

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
if (isset($arParams['GADGET_ID']) && $arParams['GADGET_ID'] <> '')
{
	$arResult['GADGET'] = 'Y';
	$arResult['GADGET_ID'] = $arParams['GADGET_ID'];
}
$isInGadgetMode = $arResult['GADGET'] === 'Y';

$arFilter = $arSort = array();

$arResult['MYCOMPANY_MODE'] = (isset($arParams['MYCOMPANY_MODE']) && $arParams['MYCOMPANY_MODE'] === 'Y') ? 'Y' : 'N';
$isMyCompanyMode = ($arResult['MYCOMPANY_MODE'] === 'Y');
if ($isMyCompanyMode)
	$arFilter['=IS_MY_COMPANY'] = 'Y';
else
	$arFilter['=IS_MY_COMPANY'] = 'N';

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
		$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null? mb_strtoupper($this->GetParent()->GetName()) : '';
	}
	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
{
	$arSort = $arParams['INTERNAL_SORT'];
}

$enableWidgetFilter = false;
$widgetFilter = null;
if (isset($arParams['WIDGET_DATA_FILTER']) && isset($arParams['WIDGET_DATA_FILTER']['WG']) && $arParams['WIDGET_DATA_FILTER']['WG'] === 'Y')
{
	$enableWidgetFilter = true;
	$widgetFilter = $arParams['WIDGET_DATA_FILTER'];
}
elseif (!$bInternal && isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y')
{
	$enableWidgetFilter = true;
	$widgetFilter = $_REQUEST;
}
if($enableWidgetFilter)
{
	$dataSourceFilter = null;

	$dataSourceName = isset($widgetFilter['DS']) ? $widgetFilter['DS'] : '';
	if($dataSourceName !== '')
	{
		$dataSource = null;
		try
		{
			$dataSource = Bitrix\Crm\Widget\Data\DataSourceFactory::create(array('name' => $dataSourceName), $userID, true);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}

		try
		{
			$dataSourceFilter = $dataSource ? $dataSource->prepareEntityListFilter($widgetFilter) : null;
		}
		catch(Bitrix\Main\ArgumentException $e)
		{
		}
		catch(Bitrix\Main\InvalidOperationException $e)
		{
		}
	}

	if(is_array($dataSourceFilter) && !empty($dataSourceFilter))
	{
		$arFilter = $dataSourceFilter;
	}
	else
	{
		$enableWidgetFilter = false;
	}
}

$enableCounterFilter = false;
if(!$bInternal && isset($_REQUEST['counter']))
{
	$counterTypeID = Bitrix\Crm\Counter\EntityCounterType::resolveID($_REQUEST['counter']);
	$counter = null;
	if(Bitrix\Crm\Counter\EntityCounterType::isDefined($counterTypeID))
	{
		try
		{
			$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
				CCrmOwnerType::Company,
				$counterTypeID,
				$userID,
				Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST)
			);

			$arFilter = $counter->prepareEntityListFilter(
				array(
					'MASTER_ALIAS' => CCrmCompany::TABLE_ALIAS,
					'MASTER_IDENTITY' => 'ID'
				)
			);
			$enableCounterFilter = !empty($arFilter);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}
		catch(Bitrix\Main\ArgumentException $e)
		{
		}
	}
}

$request = Main\Application::getInstance()->getContext()->getRequest();
$fromAnalytics = $request->getQuery('from_analytics') === 'Y';
$enableReportFilter = false;
if($fromAnalytics)
{
	$reportId = Bitrix\Main\Context::getCurrent()->getRequest()['report_id'];
	if($reportId != '')
	{
		$reportHandler = Crm\Integration\Report\ReportHandlerFactory::createWithReportId($reportId);
		$reportFilter = $reportHandler ? $reportHandler->prepareEntityListFilter(Bitrix\Main\Context::getCurrent()->getRequest()) : null;

		if(is_array($reportFilter) && !empty($reportFilter))
		{
			$arFilter = $reportFilter;
			$enableReportFilter = true;
		}
	}
}

$arResult['IS_EXTERNAL_FILTER'] = ($enableWidgetFilter || $enableCounterFilter || $enableReportFilter);

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
$CCrmFieldMulti = new CCrmFieldMulti();

$gridId = $isMyCompanyMode ? 'CRM_MYCOMPANY_LIST_V12' : 'CRM_COMPANY_LIST_V12';
$arResult['GRID_ID'] = $gridId.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusListEx('EMPLOYEES');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusListEx('INDUSTRY');
$arResult['WEBFORM_LIST'] = WebFormManager::getListNamesEncoded();
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = [];
$arResult['FILTER_PRESETS'] = array();

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['PRESERVE_HISTORY'] = isset($arParams['PRESERVE_HISTORY']) ? $arParams['PRESERVE_HISTORY'] : false;

$addressLabels = EntityAddress::getShortLabels();
$regAddressLabels = EntityAddress::getShortLabels(EntityAddressType::Registered);
$requisite = new \Bitrix\Crm\EntityRequisite();

//region Filter Presets Initialization
if (!$bInternal)
{
	$currentUserID = $arResult['CURRENT_USER_ID'];
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'disallow_for_all' => true, 'fields' => array('ASSIGNED_BY_ID_name' => $currentUserName, 'ASSIGNED_BY_ID' => $currentUserID)),
		'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CHANGE_MY'), 'disallow_for_all' => true, 'fields' => array('MODIFY_BY_ID_name' => $currentUserName, 'MODIFY_BY_ID' => $currentUserID))
	);
}
//endregion

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);

//region Navigation Params
if ($arParams['COMPANY_COUNT'] <= 0)
{
	$arParams['COMPANY_COUNT'] = 20;
}
$arNavParams = $gridOptions->GetNavParams(array('nPageSize' => $arParams['COMPANY_COUNT']));
$arNavParams['bShowAll'] = false;
if(isset($arNavParams['nPageSize']) && $arNavParams['nPageSize'] > 100)
{
	$arNavParams['nPageSize'] = 100;
}
//endregion

//region Filter initialization
if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = ['TITLE', 'BANKING_DETAILS', 'COMMENTS'];
	$filterFlags = Crm\Filter\CompanySettings::FLAG_NONE;
	if($enableOutmodedFields)
	{
		$filterFlags |= Crm\Filter\CompanySettings::FLAG_ENABLE_ADDRESS;
	}

	$entityFilter = Crm\Filter\Factory::createEntityFilter(
		new Crm\Filter\CompanySettings(
				array(
					'ID' => $arResult['GRID_ID'],
					'flags' => $filterFlags
				)
		)
	);
	$effectiveFilterFieldIDs = $filterOptions->getUsedFields();
	if(empty($effectiveFilterFieldIDs))
	{
		$effectiveFilterFieldIDs = $entityFilter->getDefaultFieldIDs();
	}

	//region HACK: Preload fields for filter of user activities & webforms
	if(!in_array('ASSIGNED_BY_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ASSIGNED_BY_ID';
	}

	if(!in_array('ACTIVITY_COUNTER', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ACTIVITY_COUNTER';
	}

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

//region Headers initialization
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'first_order' => 'desc', 'width' => 60, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
	array('id' => 'COMPANY_SUMMARY', 'name' => GetMessage('CRM_COLUMN_COMPANY'), 'sort' => 'title', 'width' => 200, 'default' => true, 'editable' => false, 'enableDefaultSort' => false)
);

// Don't display activities in INTERNAL mode.
if(!$bInternal && !$isMyCompanyMode)
{
	$arResult['HEADERS'][] = array(
		'id' => 'ACTIVITY_ID',
		'name' => GetMessage('CRM_COLUMN_ACTIVITY'),
		'sort' => 'nearest_activity',
		'default' => true,
		'prevent_default' => false
	);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'LOGO', 'name' => GetMessage('CRM_COLUMN_LOGO'), 'sort' => false, 'editable' => false),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'editable' => true),
		array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TYPE'), 'sort' => 'company_type', 'editable' => array('items' => CCrmStatus::GetStatusList('COMPANY_TYPE')), 'type' => 'list'),
		array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_COLUMN_EMPLOYEES'), 'sort' => 'employees', 'first_order' => 'desc', 'editable' => array('items' => CCrmStatus::GetStatusList('EMPLOYEES')), 'type' => 'list')
	)
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

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS'], ['LINK']);
if($isInExportMode)
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

Crm\Service\Container::getInstance()->getParentFieldManager()->prepareGridHeaders(
	\CCrmOwnerType::Company,
	$arResult['HEADERS']
);

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username')
	)
);

if($enableOutmodedFields)
{
	$arResult['HEADERS'] = array_merge(
		$arResult['HEADERS'],
		array(
			array('id' => 'FULL_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(), 'sort' => false, 'editable' => false),
			array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address', 'editable' => false),
			array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2', 'editable' => false),
			array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city', 'editable' => false),
			array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region', 'editable' => false),
			array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province', 'editable' => false),
			array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code', 'editable' => false),
			array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country', 'editable' => false),

			array('id' => 'FULL_REG_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(EntityAddressType::Registered), 'sort' => false, 'editable' => false),
			//REG_ADDRESS = ADDRESS_LEGAL
			array('id' => 'ADDRESS_LEGAL', 'name' => $regAddressLabels['ADDRESS'], 'sort' => 'registered_address', 'editable' => false),
			array('id' => 'REG_ADDRESS_2', 'name' => $regAddressLabels['ADDRESS_2'], 'sort' => 'registered_address_2', 'editable' => false),
			array('id' => 'REG_ADDRESS_CITY', 'name' => $regAddressLabels['CITY'], 'sort' => 'registered_address_city', 'editable' => false),
			array('id' => 'REG_ADDRESS_REGION', 'name' => $regAddressLabels['REGION'], 'sort' => 'registered_address_region', 'editable' => false),
			array('id' => 'REG_ADDRESS_PROVINCE', 'name' => $regAddressLabels['PROVINCE'], 'sort' => 'registered_address_province', 'editable' => false),
			array('id' => 'REG_ADDRESS_POSTAL_CODE', 'name' => $regAddressLabels['POSTAL_CODE'], 'sort' => 'registered_address_postal_code', 'editable' => false),
			array('id' => 'REG_ADDRESS_COUNTRY', 'name' => $regAddressLabels['COUNTRY'], 'sort' => 'registered_address_country', 'editable' => false)
		)
	);
}

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_COLUMN_BANKING_DETAILS'), 'sort' => false, 'editable' => false),
	array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_COLUMN_INDUSTRY'), 'sort' => 'industry', 'editable' => array('items' => CCrmStatus::GetStatusList('INDUSTRY')), 'type' => 'list'),
	array('id' => 'REVENUE', 'name' => GetMessage('CRM_COLUMN_REVENUE'), 'sort' => 'revenue', 'editable' => true),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false, 'editable' => false),
	/*array('id' => 'IS_MY_COMPANY', 'name' => GetMessage('CRM_COLUMN_IS_MY_COMPANY'), 'sort' => 'is_my_company', 'editable' => true, 'type' => 'checkbox'),*/
));
if ($isMyCompanyMode)
{
	$arResult['HEADERS'][] = array(
		'id' => 'IS_DEF_MYCOMPANY', 'name' => GetMessage('CRM_COLUMN_IS_DEF_MYCOMPANY'), 'sort' => false, 'editable' => false, 'type' => 'checkbox'
	);
}
$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'editable' => false, 'class' => 'username'),
	array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'first_order' => 'desc', 'default' => true, 'editable' => false, 'class' => 'date'),
	array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'editable' => false, 'class' => 'username'),
	array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'first_order' => 'desc', 'editable' => false, 'class' => 'date'),
	array('id' => 'WEBFORM_ID', 'name' => GetMessage('CRM_COLUMN_WEBFORM'), 'sort' => 'webform_id', 'type' => 'list')
));

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);

$arResult['HEADERS_SECTIONS'] = [
	[
		'id' => 'COMPANY',
		'name' => Loc::getMessage('CRM_COLUMN_COMPANY'),
		'default' => true,
		'selected' => true,
	],
];

$arBPData = array();
if ($isBizProcInstalled)
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentCompany', 'COMPANY'), false);
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentCompany', 'COMPANY'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::ViewWorkflow,
			$userID,
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowTemplateId' => $arBP['ID'],
				'UserIsAdmin' => $isAdmin
			)
		))
		{
			continue;
		}
		$arResult['HEADERS'][] = array('id' => 'BIZPROC_'.$arBP['ID'], 'name' => $arBP['NAME'], 'sort' => false, 'editable' => false);
	}

	if ($arBPData)
	{
		CJSCore::Init('bp_starter');
	}
}

// list all filds for export
$exportAllFieldsList = array();
if ($isInExportMode && $isStExportAllFields)
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		$exportAllFieldsList[$arHeader['id']] = true;
	}
}
unset($arHeader);

// requisite entity fields
if (!($isInExportMode && $isStExport && $isStExportRequisiteMultiline))
{
	$requisite->prepareEntityListHeaderFields($arResult['HEADERS']);
}
//endregion Headers initialization


// Try to extract user action data -->
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
				$actionData['OPENED'] = mb_strtoupper($_POST['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
				unset($_POST['ACTION_OPENED'], $_REQUEST['ACTION_OPENED']);
			}
			else
			{
				$actionData['OPENED'] = mb_strtoupper($controls['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
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

if(!$arResult['IS_EXTERNAL_FILTER'])
{
	$arFilter += $filterOptions->getFilter($arResult['FILTER']);
}

$CCrmUserType->PrepareListFilterValues($arResult['FILTER'], $arFilter, $arResult['GRID_ID']);
$USER_FIELD_MANAGER->AdminListAddFilter(CCrmCompany::$sUFEntityID, $arFilter);

//region Apply Search Restrictions
$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
if(!$searchRestriction->isExceeded(CCrmOwnerType::Company))
{
	$searchRestriction->notifyIfLimitAlmostExceed(CCrmOwnerType::Company);

	Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Company, $arFilter);
}
else
{
	$arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
		array('ENTITY_TYPE_ID' => CCrmOwnerType::Company)
	);
}
//endregion

//region Activity Counter Filter
if(isset($arFilter['ACTIVITY_COUNTER']))
{
	if(is_array($arFilter['ACTIVITY_COUNTER']))
	{
		$counterTypeID = Bitrix\Crm\Counter\EntityCounterType::joinType(
			array_filter($arFilter['ACTIVITY_COUNTER'], 'is_numeric')
		);
	}
	else
	{
		$counterTypeID = (int)$arFilter['ACTIVITY_COUNTER'];
	}

	$counter = null;
	if($counterTypeID > 0)
	{
		$counterUserIDs = array();
		if(isset($arFilter['ASSIGNED_BY_ID']))
		{
			if(is_array($arFilter['ASSIGNED_BY_ID']))
			{
				$counterUserIDs = array_filter($arFilter['ASSIGNED_BY_ID'], 'is_numeric');
			}
			elseif($arFilter['ASSIGNED_BY_ID'] > 0)
			{
				$counterUserIDs[] = $arFilter['ASSIGNED_BY_ID'];
			}
		}

		try
		{
			$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
				CCrmOwnerType::Company,
				$counterTypeID,
				0,
				Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST)
			);

			$arFilter += $counter->prepareEntityListFilter(
				array(
					'MASTER_ALIAS' => CCrmCompany::TABLE_ALIAS,
					'MASTER_IDENTITY' => 'ID',
					'USER_IDS' => $counterUserIDs
				)
			);
			unset($arFilter['ASSIGNED_BY_ID']);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}
		catch(Bitrix\Main\ArgumentException $e)
		{
		}
	}
}
//endregion

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter, array(), '=%', false);
$requisite->prepareEntityListFilter($arFilter);

$arImmutableFilters = array(
	'FM', 'ID', 'CURRENCY_ID', 'ASSOCIATED_CONTACT_ID',
	'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
	'COMPANY_TYPE', 'INDUSTRY', 'EMPLOYEES', 'WEBFORM_ID',
	'HAS_PHONE', 'HAS_EMAIL', 'IS_MY_COMPANY', '!IS_MY_COMPANY', 'RQ',
	'SEARCH_CONTENT', 'TRACKING_SOURCE_ID', 'TRACKING_CHANNEL_CODE',
	'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
);

foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	if (Crm\Service\ParentFieldManager::isParentFieldName($k))
	{
		$arFilter[$k] = Crm\Service\ParentFieldManager::transformEncodedFilterValueIntoInteger($k, $v);
		continue;
	}

	$arMatch = array();

	if($k === 'ORIGINATOR_ID')
	{
		// HACK: build filter by internal entities
		$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
		unset($arFilter[$k]);
	}
	elseif($k === 'ADDRESS'
		|| $k === 'ADDRESS_2'
		|| $k === 'ADDRESS_CITY'
		|| $k === 'ADDRESS_REGION'
		|| $k === 'ADDRESS_PROVINCE'
		|| $k === 'ADDRESS_POSTAL_CODE'
		|| $k === 'ADDRESS_COUNTRY'
		|| $k === 'ADDRESS_LEGAL'
		|| $k === 'REG_ADDRESS_2'
		|| $k === 'REG_ADDRESS_CITY'
		|| $k === 'REG_ADDRESS_REGION'
		|| $k === 'REG_ADDRESS_PROVINCE'
		|| $k === 'REG_ADDRESS_POSTAL_CODE'
		|| $k === 'REG_ADDRESS_COUNTRY')
	{
		$v = trim($v);
		if($v === '')
		{
			continue;
		}

		if(!isset($arFilter['ADDRESSES']))
		{
			$arFilter['ADDRESSES'] = array();
		}

		$addressAliases = array('ADDRESS_LEGAL' => 'REG_ADDRESS');
		$addressTypeID = CompanyAddress::resolveEntityFieldTypeID($k, $addressAliases);

		if(!isset($arFilter['ADDRESSES'][$addressTypeID]))
		{
			$arFilter['ADDRESSES'][$addressTypeID] = array();
		}

		$n = CompanyAddress::mapEntityField($k, $addressTypeID, $addressAliases);
		$arFilter['ADDRESSES'][$addressTypeID][$n] = "{$v}%";

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
	elseif (in_array($k, $arResult['FILTER2LOGIC']) && $v !== false)
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif($k === 'COMMUNICATION_TYPE')
	{
		if(!is_array($v))
		{
			$v = array($v);
		}
		foreach($v as $commTypeID)
		{
			if($commTypeID === CCrmFieldMulti::PHONE)
			{
				$arFilter['=HAS_PHONE'] = 'Y';
			}
			elseif($commTypeID === CCrmFieldMulti::EMAIL)
			{
				$arFilter['=HAS_EMAIL'] = 'Y';
			}
		}
		unset($arFilter['COMMUNICATION_TYPE']);
	}
	elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && mb_strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1 && $v !== false)
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

// POST & GET actions processing -->
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

				$obRes = CCrmCompany::GetListEx(array(), $arFilterDel, false, false, array('ID'));
				while($arCompany = $obRes->Fetch())
				{
					$ID = $arCompany['ID'];
					$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
					if (!$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr)
						&& $CCrmCompany->Delete($ID, array('PROCESS_BIZPROC' => false)))
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
					$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
					if (!$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$ID]))
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

						if($CCrmCompany->Update($ID, $arUpdateData))
						{
							$DB->Commit();

							if (!$isMyCompanyMode)
							{
								$arErrors = [];
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Company,
								$ID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);
						}
						}
						else
						{
							$arResult['ERRORS'][] = [
								'TITLE' => Main\Text\HtmlFilter::encode($arUpdateData['TITLE'] ?? $ID),
								'TEXT' => Main\Text\HtmlFilter::encode(strip_tags($CCrmCompany->LAST_ERROR)),
							];
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
					$arTaskID[] = 'CO_'.$ID;
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
						'back_url' => urlencode($arParams['PATH_TO_COMPANY_LIST'])
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
		elseif ($actionData['NAME'] == 'assign_to')
		{
			if(isset($actionData['ASSIGNED_BY_ID']))
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.
					$dbRes = CCrmCompany::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arCompany = $dbRes->Fetch())
					{
						$arIDs[] = $arCompany['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $userPermissions->GetEntityAttr('COMPANY', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmCompany->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						if (!$isMyCompanyMode)
						{
							$arErrors = [];
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Company,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
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
				$isOpened = mb_strtoupper($actionData['OPENED']) === 'Y' ? 'Y' : 'N';
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.

					$dbRes = CCrmCompany::GetListEx(
						array(),
						$arActionFilter,
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arCompany = $dbRes->Fetch())
					{
						if(isset($arCompany['OPENED']) && $arCompany['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arCompany['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$dbRes = CCrmCompany::GetListEx(
						array(),
						array(
							'@ID'=> $actionData['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arCompany = $dbRes->Fetch())
					{
						if(isset($arCompany['OPENED']) && $arCompany['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arCompany['ID'];
					}
				}

				$arEntityAttr = $userPermissions->GetEntityAttr('COMPANY', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();
					$arUpdateData = array('OPENED' => $isOpened);
					if($CCrmCompany->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						if (!$isMyCompanyMode)
						{
							$arErrors = [];
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Company,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
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
			LocalRedirect($arParams['PATH_TO_COMPANY_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);
			$arEntityAttr = $userPermissions->GetEntityAttr('COMPANY', array($ID));
			if(CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::CompanyName, $ID, $userPermissions, $arEntityAttr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $arEntityAttr)
					&& $CCrmCompany->Delete($ID, array('PROCESS_BIZPROC' => false)))
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();
				}
			}
		}
		elseif ($isMyCompanyMode && $actionData['NAME'] === 'set_def_mycompany' && isset($actionData['ID']))
		{
			$defMyCompanyId = (int)$actionData['ID'];
			if ($defMyCompanyId > 0)
				Bitrix\Crm\Requisite\EntityLink::setDefaultMyCompanyId($defMyCompanyId);
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_company' : $arParams['PATH_TO_COMPANY_LIST']);
		}
	}
}
//endregion POST & GET actions processing

$_arSort = $gridOptions->GetSorting(array(
	'sort' => array('title' => 'asc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arSelect = $gridOptions->GetVisibleColumns();

// Remove column for deleted RQ & UF
if ($requisite->normalizeEntityListFields($arSelect, $arResult['HEADERS'])
	|| $CCrmUserType->NormalizeFields($arSelect))
{
	$gridOptions->SetVisibleColumns($arSelect);
}

$rqSelect = $requisite->separateEntityListRqFields($arSelect);

$arSelectMap = array_fill_keys($arSelect, true);

$arResult['ENABLE_BIZPROC'] = $arResult['IS_BIZPROC_AVAILABLE'] = $isBizProcInstalled;
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
			'back_url' => urlencode($arParams['PATH_TO_COMPANY_LIST'])
		)
	);
}

// Export all fields
if ($isInExportMode && $isStExport && $isStExportAllFields)
{
	$arSelectMap = $exportAllFieldsList;
	$requisite->normalizeEntityListFields($arSelectMap, $arResult['HEADERS']);
}

// Fill in default values if empty
if (empty($arSelectMap))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arSelectMap[$arHeader['id']] = true;
		}
	}

	//Disable bizproc fields processing
	$arResult['ENABLE_BIZPROC'] = false;
}
else
{
	if($arResult['ENABLE_BIZPROC'])
	{
		//Check if bizproc fields selected
		$hasBizprocFields = false;
		foreach($arSelectMap as $k => $v)
		{
			if(strncmp($k, 'BIZPROC_', 8) === 0)
			{
				$hasBizprocFields = true;
				break;
			}
		}
		$arResult['ENABLE_BIZPROC'] = $hasBizprocFields;
	}
	unset($fieldName);
}

// separate entity requisite fields
if ($isInExportMode && $isStExport && $isStExportRequisiteMultiline)
{
	$arSelectedHeaders = array_keys($arSelectMap);
}
else
{
	$arSelectedHeaders = array_merge(array_keys($arSelectMap), $rqSelect);
}

if ($isInGadgetMode)
{
	$arSelectMap['DATE_CREATE'] =
	$arSelectMap['TITLE'] =
	$arSelectMap['COMPANY_TYPE'] = true;
}
else
{
	if(isset($arSelectMap['COMPANY_SUMMARY']))
	{
		$arSelectMap['LOGO'] =
		$arSelectMap['TITLE'] =
		$arSelectMap['COMPANY_TYPE'] = true;
	}

	if($arSelectMap['ASSIGNED_BY'])
	{
		$arSelectMap['ASSIGNED_BY_LOGIN'] =
		$arSelectMap['ASSIGNED_BY_NAME'] =
		$arSelectMap['ASSIGNED_BY_LAST_NAME'] =
		$arSelectMap['ASSIGNED_BY_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['ACTIVITY_ID']))
	{
		$arSelectMap['ACTIVITY_TIME'] =
		$arSelectMap['ACTIVITY_SUBJECT'] =
		$arSelectMap['C_ACTIVITY_ID'] =
		$arSelectMap['C_ACTIVITY_TIME'] =
		$arSelectMap['C_ACTIVITY_SUBJECT'] =
		$arSelectMap['C_ACTIVITY_RESP_ID'] =
		$arSelectMap['C_ACTIVITY_RESP_LOGIN'] =
		$arSelectMap['C_ACTIVITY_RESP_NAME'] =
		$arSelectMap['C_ACTIVITY_RESP_LAST_NAME'] =
		$arSelectMap['C_ACTIVITY_RESP_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['CREATED_BY']))
	{
		$arSelectMap['CREATED_BY_LOGIN'] =
		$arSelectMap['CREATED_BY_NAME'] =
		$arSelectMap['CREATED_BY_LAST_NAME'] =
		$arSelectMap['CREATED_BY_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['MODIFY_BY']))
	{
		$arSelectMap['MODIFY_BY_LOGIN'] =
		$arSelectMap['MODIFY_BY_NAME'] =
		$arSelectMap['MODIFY_BY_LAST_NAME'] =
		$arSelectMap['MODIFY_BY_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		$arSelectMap['ADDRESS'] =
		$arSelectMap['ADDRESS_2'] =
		$arSelectMap['ADDRESS_CITY'] =
		$arSelectMap['ADDRESS_POSTAL_CODE'] =
		$arSelectMap['ADDRESS_POSTAL_CODE'] =
		$arSelectMap['ADDRESS_REGION'] =
		$arSelectMap['ADDRESS_PROVINCE'] =
		$arSelectMap['ADDRESS_COUNTRY'] = true;
	}

	if(isset($arSelectMap['FULL_REG_ADDRESS']))
	{
		$arSelectMap['REG_ADDRESS'] =
		$arSelectMap['REG_ADDRESS_2'] =
		$arSelectMap['REG_ADDRESS_CITY'] =
		$arSelectMap['REG_ADDRESS_POSTAL_CODE'] =
		$arSelectMap['REG_ADDRESS_POSTAL_CODE'] =
		$arSelectMap['REG_ADDRESS_REGION'] =
		$arSelectMap['REG_ADDRESS_PROVINCE'] =
		$arSelectMap['REG_ADDRESS_COUNTRY'] = true;
	}

	// ID must present in select
	if(!isset($arSelectMap['ID']))
	{
		$arSelectMap['ID'] = true;
	}
}

if ($isInExportMode)
{
	CCrmComponentHelper::PrepareExportFieldsList(
		$arSelectedHeaders,
		array(
			'COMPANY_SUMMARY' => array(
				'LOGO',
				'TITLE',
				'COMPANY_TYPE'
			),
			'ACTIVITY_ID' => array()
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
	$nTopCount = $arParams['COMPANY_COUNT'];
}

if($nTopCount > 0 && !isset($arFilter['ID']))
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
	$arFilter['PERMISSION'] = 'EXPORT';

// HACK: Make custom sort for ASSIGNED_BY
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

if($arSort['date_create'])
{
	$arSort['id'] = $arSort['date_create'];
	unset($arSort['date_create']);
}

if(!empty($arSort) && !isset($arSort['id']))
{
	$arSort['id'] = reset($arSort);
}

$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(isset($arSelectMap['ACTIVITY_ID']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}

if(isset($arParams['IS_EXTERNAL_CONTEXT']))
{
	$arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

$selectIsDefMyCompany = false;
if ($isMyCompanyMode)
{
	if (isset($arSelectMap['IS_DEF_MYCOMPANY']))
	{
		$selectIsDefMyCompany = true;
		unset($arSelectMap['IS_DEF_MYCOMPANY']);
	}
}


$arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);

$arResult['COMPANY'] = array();
$arResult['COMPANY_ID'] = array();
$arResult['COMPANY_UF'] = array();

$defaultMyCompanyId = 0;
if ($isMyCompanyMode)
	$defaultMyCompanyId = \Bitrix\Crm\Requisite\EntityLink::getDefaultMyCompanyId();

//region Navigation data initialization
$pageNum = 0;
if ($isInExportMode && $isStExport)
{
	$pageSize = !empty($arParams['STEXPORT_PAGE_SIZE']) ? $arParams['STEXPORT_PAGE_SIZE'] : $arParams['COMPANY_COUNT'];
}
else
{
	$pageSize = !$isInExportMode
		? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['COMPANY_COUNT']) : 0;
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
		$total = CCrmCompany::GetListEx(array(), $arFilter, array());
		$pageNum = (int)(ceil($total / $pageSize)) - $offset;
		if($pageNum <= 0)
		{
			$pageNum = 1;
		}
	}
}

if (!($isInExportMode && $isStExport))
{
	if($pageNum > 0)
	{
		if(!isset($_SESSION['CRM_PAGINATION_DATA']))
		{
			$_SESSION['CRM_PAGINATION_DATA'] = array();
		}
		$_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum, 'PAGE_SIZE' => $pageSize);
	}
	else
	{
		if(!$bInternal
			&& !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
			&& isset($_SESSION['CRM_PAGINATION_DATA'])
			&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']]))
		{
			$paginationData = $_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']];
			if(isset($paginationData['PAGE_NUM'])
				&& isset($paginationData['PAGE_SIZE'])
				&& $paginationData['PAGE_SIZE'] == $pageSize)
			{
				$pageNum = (int)$paginationData['PAGE_NUM'];
			}
		}

		if($pageNum <= 0)
		{
			$pageNum = 1;
		}
	}
}
//endregion

if ($isInExportMode && $isStExport && $pageNum === 1)
{
	$total = \CCrmCompany::GetListEx(array(), $arFilter, array());
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
	{
		$limit = $total - $processed;
	}
	unset($total, $processed);
}

if(isset($arSort['nearest_activity']))
{
	$navListOptions = ($isInExportMode && !$isStExport)
		? array()
		: array_merge(
			$arOptions,
			array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
		);

	$navDbResult = CCrmActivity::GetEntityList(
		CCrmOwnerType::Company,
		$userID,
		$arSort['nearest_activity'],
		$arFilter,
		false,
		$navListOptions
	);

	$qty = 0;
	while($arCompany = $navDbResult->Fetch())
	{
		if($pageSize > 0 && ++$qty > $pageSize)
		{
			$enableNextPage = true;
			break;
		}

		$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
		$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
		$arResult['COMPANY_UF'][$arCompany['ID']] = array();
	}

	//region Navigation data storing
	$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
	$arResult['DB_FILTER'] = $arFilter;
	if(!isset($_SESSION['CRM_GRID_DATA']))
	{
		$_SESSION['CRM_GRID_DATA'] = array();
	}
	$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
	//endregion

	$entityIDs = array_keys($arResult['COMPANY']);
	if(!empty($entityIDs))
	{
		if ($isInExportMode && $isStExport)
		{
			if (!is_array($arSort))
			{
				$arSort = array();
			}

			if (!isset($arSort['ID']))
			{
				$order = mb_strtoupper($arSort['nearest_activity']);
				if ($order === 'ASC' || $order === 'DESC')
				{
					$arSort['ID'] = $arSort['nearest_activity'];
				}
				else
				{
					$arSort['ID'] = 'asc';
				}
				unset($order);
			}
		}

		//Permissions are already checked.
		$dbResult = CCrmCompany::GetListEx(
			$arSort,
			array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			$arSelect,
			$arOptions
		);
		while($arCompany = $dbResult->GetNext())
		{
			$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
		}
	}
}
else
{
	$addressSort = array();
	$addressTypeID = EntityAddressType::Primary;
	foreach($arSort as $k => $v)
	{
		if(strncmp($k, 'address', 7) === 0)
		{
			$addressSort[mb_strtoupper($k)] = $v;
		}
	}

	if(empty($addressSort))
	{
		$addressTypeID = EntityAddressType::Registered;
		foreach($arSort as $k => $v)
		{
			if(strncmp($k, 'registered_address', 18) === 0)
			{
				$addressSort[mb_strtoupper($k)] = $v;
			}
		}
	}

	if(!empty($addressSort))
	{
		$navListOptions = ($isInExportMode && !$isStExport)
			? array()
			: array_merge(
				$arOptions,
				array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
			);

		$navDbResult = \Bitrix\Crm\CompanyAddress::getEntityList(
			$addressTypeID,
			$addressSort,
			$arFilter,
			false,
			$navListOptions
		);

		$qty = 0;
		while($arCompany = $navDbResult->Fetch())
		{
			if($pageSize > 0 && ++$qty > $pageSize)
			{
				$enableNextPage = true;
				break;
			}

			$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
			$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
			$arResult['COMPANY_UF'][$arCompany['ID']] = array();
		}

		//region Navigation data storing
		$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
		$arResult['DB_FILTER'] = $arFilter;
		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}
		$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
		//endregion

		$entityIDs = array_keys($arResult['COMPANY']);
		if(!empty($entityIDs))
		{
			$arSort['ID'] = array_shift(array_slice($addressSort, 0, 1));
			//Permissions are already checked.
			$dbResult = CCrmCompany::GetListEx(
				$arSort,
				array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				$arSelect,
				$arOptions
			);
			while($arCompany = $dbResult->GetNext())
			{
				$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
			}
		}
	}
	else
	{
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
					array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
				);
		}

		if ($isInExportMode && $isStExport)
		{
			if (!is_array($arSort))
			{
				$arSort = array();
			}

			if (!isset($arSort['ID']))
			{
				if (!empty($arSort))
				{
					$arSort['ID'] = array_shift(array_slice($arSort, 0, 1));
				}
				else
				{
					$arSort['ID'] = 'asc';
				}
			}
		}

		$dbResult = CCrmCompany::GetListEx(
			$arSort,
			$arFilter,
			false,
			false,
			$arSelect,
			$navListOptions
		);

		$qty = 0;
		while($arCompany = $dbResult->GetNext())
		{
			if($pageSize > 0 && ++$qty > $pageSize)
			{
				$enableNextPage = true;
				break;
			}

			$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
			$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
			$arResult['COMPANY_UF'][$arCompany['ID']] = array();
		}

		//region Navigation data storing
		$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
		$arResult['DB_FILTER'] = $arFilter;

		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}
		$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
		//endregion
	}
}

$arResult['STEXPORT_IS_FIRST_PAGE'] = $pageNum === 1 ? 'Y' : 'N';
$arResult['STEXPORT_IS_LAST_PAGE'] = $enableNextPage ? 'N' : 'Y';

$arResult['PAGINATION']['URL'] = $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'));
$arResult['PERMS']['ADD']    = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'DELETE');

$bDeal = CCrmDeal::CheckCreatePermission($userPermissions);
$arResult['PERM_DEAL'] = $bDeal;
$bQuote = !$CCrmCompany->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_QUOTE'] = $bQuote;
$bInvoice = !$CCrmCompany->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_INVOICE'] = $bInvoice;
$bContact = !$CCrmCompany->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_CONTACT'] = $bContact;

$enableExportEvent = $isInExportMode && HistorySettings::getCurrent()->isExportEventEnabled();

$bizProcTabId = $isMyCompanyMode ? 'CRM_MYCOMPANY_SHOW_V12_active_tab' : 'CRM_COMPANY_SHOW_V12_active_tab';

$now = time() + CTimeZone::GetOffset();

$parentFieldValues = Crm\Service\Container::getInstance()->getParentFieldManager()->loadParentElementsByChildren(
	\CCrmOwnerType::Company,
	$arResult['COMPANY']
);

foreach($arResult['COMPANY'] as &$arCompany)
{
	$entityID =  $arCompany['ID'];
	if($enableExportEvent)
	{
		CCrmEvent::RegisterExportEvent(CCrmOwnerType::Company, $entityID, $userID);
	}

	if (!empty($arCompany['LOGO']))
	{
		if ($isInExportMode)
		{
			if ($arFile = CFile::GetFileArray($arCompany['LOGO']))
			{
				$arCompany['LOGO'] = CHTTP::URN2URI($arFile['SRC']);
			}
		}
		else
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arCompany['LOGO'],
				array('width' => 100, 'height' => 100),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$arCompany['LOGO'] = CFile::ShowImage($arFileTmp['src'], 50, 50, 'border=0');
		}
	}

	$typeID = isset($arCompany['COMPANY_TYPE']) ? $arCompany['COMPANY_TYPE'] : '';
	$arCompany['COMPANY_TYPE_NAME'] = isset($arResult['COMPANY_TYPE_LIST'][$typeID]) ? $arResult['COMPANY_TYPE_LIST'][$typeID] : $typeID;

	if ($isMyCompanyMode)
	{
		if ($selectIsDefMyCompany)
			$arCompany['IS_DEF_MYCOMPANY'] = ($defaultMyCompanyId === (int)$arCompany['ID']) ? 'Y' : 'N';
	}

	$arCompany['PATH_TO_COMPANY_DETAILS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_COMPANY_DETAILS'],
		array('company_id' => $entityID)
	);

	if($arResult['ENABLE_SLIDER'])
	{
		$arCompany['PATH_TO_COMPANY_SHOW'] = $arCompany['PATH_TO_COMPANY_DETAILS'];
		$arCompany['PATH_TO_COMPANY_EDIT'] = CCrmUrlUtil::AddUrlParams(
			$arCompany['PATH_TO_COMPANY_DETAILS'],
			array('init_mode' => 'edit')
		);
	}
	else
	{
		$arCompany['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_COMPANY_SHOW'],
			array('company_id' => $entityID)
		);

		$arCompany['PATH_TO_COMPANY_EDIT'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_COMPANY_EDIT'],
			array('company_id' => $entityID)
		);
	}

	if ($bDeal)
	{
		$addParams = array('company_id' => $entityID);
		if(isset($arParams['INTERNAL_CONTEXT']['CONTACT_ID']))
		{
			$addParams['contact_id'] = $arParams['INTERNAL_CONTEXT']['CONTACT_ID'];
		}

		$arCompany['PATH_TO_DEAL_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arResult['ENABLE_SLIDER'] ? $arParams['PATH_TO_DEAL_DETAILS'] : $arParams['PATH_TO_DEAL_EDIT'],
				array('deal_id' => 0)
			),
			$addParams
		);
	}

	if ($bContact)
	{
		$arCompany['PATH_TO_CONTACT_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arResult['ENABLE_SLIDER'] ? $arParams['PATH_TO_CONTACT_DETAILS'] : $arParams['PATH_TO_CONTACT_EDIT'],
				array('contact_id' => 0)
			),
			array('company_id' => $entityID)
		);
	}

	$arCompany['PATH_TO_COMPANY_COPY'] =  CHTTP::urlAddParams(
		$arCompany['PATH_TO_COMPANY_EDIT'],
		array('copy' => 1)
	);

	$arCompany['PATH_TO_COMPANY_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_COMPANY_LIST'],
		array(
			'action_'.$arResult['GRID_ID'] => 'delete',
			'ID' => $entityID,
			'sessid' => $arResult['SESSION_ID']
		)
	);
	$arCompany['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arCompany['ASSIGNED_BY'])
	);
	$arCompany['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arCompany['CREATED_BY'])
	);

	$arCompany['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arCompany['MODIFY_BY'])
	);

	$arCompany['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['CREATED_BY_LOGIN'],
			'NAME' => $arCompany['CREATED_BY_NAME'],
			'LAST_NAME' => $arCompany['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arCompany['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['MODIFY_BY_LOGIN'],
			'NAME' => $arCompany['MODIFY_BY_NAME'],
			'LAST_NAME' => $arCompany['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);


	if(isset($arCompany['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arCompany['~ACTIVITY_TIME']);
		$arCompany['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arCompany['~ACTIVITY_IS_CURRENT_DAY'] = $arCompany['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}

	if ($arResult['ENABLE_TASK'])
	{
		$arCompany['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array('task_id' => 0, 'user_id' => $userID)
			),
			array(
				'UF_CRM_TASK' => "CO_{$entityID}",
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX').' '),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_COMPANY_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arCompany['PATH_TO_QUOTE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_QUOTE_EDIT'], array('quote_id' => 0)),
				array('company_id' => $entityID)
			);
		$arCompany['PATH_TO_INVOICE_ADD'] =
			CHTTP::urlAddParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_INVOICE_EDIT'],
					array('invoice_id' => 0)
				),
				array('company' => $entityID)
			);
	}

	if ($arResult['ENABLE_BIZPROC'])
	{
		$arCompany['BIZPROC_STATUS'] = '';
		$arCompany['BIZPROC_STATUS_HINT'] = '';
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			array('crm', 'CCrmDocumentCompany', "COMPANY_{$entityID}")
		);

		$arCompany['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_COMPANY_SHOW'],
				array('company_id' => $entityID)
			),
			array($bizProcTabId => 'tab_bizproc')
		);

		$totalTaskQty = 0;
		$docStatesQty = count($arDocumentStates);
		if($docStatesQty === 1)
		{
			$arDocState = $arDocumentStates[array_shift(array_keys($arDocumentStates))];

			$docTemplateID = $arDocState['TEMPLATE_ID'];
			$paramName = "BIZPROC_{$docTemplateID}";
			$docTtl = isset($arDocState['STATE_TITLE']) ? $arDocState['STATE_TITLE'] : '';
			$docName = isset($arDocState['STATE_NAME']) ? $arDocState['STATE_NAME'] : '';
			$docTemplateName = isset($arDocState['TEMPLATE_NAME']) ? $arDocState['TEMPLATE_NAME'] : '';

			if($isInExportMode)
			{
				$arCompany[$paramName] = $docTtl;
			}
			else
			{
				$arCompany[$paramName] = '<a href="'.htmlspecialcharsbx($arCompany['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

				$docID = $arDocState['ID'];
				$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
				if($taskQty > 0)
				{
					$totalTaskQty += $taskQty;
				}

				$arCompany['BIZPROC_STATUS'] = $taskQty > 0 ? 'attention' : 'inprogress';
				$arCompany['BIZPROC_STATUS_HINT'] =
					'<div class=\'bizproc-item-title\'>'.
					htmlspecialcharsbx($docTemplateName !== '' ? $docTemplateName : GetMessage('CRM_BPLIST')).
					': <span class=\'bizproc-item-title bizproc-state-title\'><a href=\''.$arCompany['PATH_TO_BIZPROC_LIST'].'\'>'.
					htmlspecialcharsbx($docTtl !== '' ? $docTtl : $docName).'</a></span></div>';
			}
		}
		elseif($docStatesQty > 1)
		{
			foreach ($arDocumentStates as &$arDocState)
			{
				$docTemplateID = $arDocState['TEMPLATE_ID'];
				$paramName = "BIZPROC_{$docTemplateID}";
				$docTtl = isset($arDocState['STATE_TITLE']) ? $arDocState['STATE_TITLE'] : '';

				if($isInExportMode)
				{
					$arCompany[$paramName] = $docTtl;
				}
				else
				{
					$arCompany[$paramName] = '<a href="'.htmlspecialcharsbx($arCompany['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

					$docID = $arDocState['ID'];
					//TODO: wait for bizproc bugs will be fixed and replace serial call of CCrmBizProcHelper::GetUserWorkflowTaskCount on single call
					$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
					if($taskQty === 0)
					{
						continue;
					}

					if ($arCompany['BIZPROC_STATUS'] !== 'attention')
					{
						$arCompany['BIZPROC_STATUS'] = 'attention';
					}

					$totalTaskQty += $taskQty;
					if($totalTaskQty > 5)
					{
						break;
					}
				}
			}
			unset($arDocState);

			if(!$isInExportMode)
			{
				$arCompany['BIZPROC_STATUS_HINT'] =
					'<span class=\'bizproc-item-title\'>'.GetMessage('CRM_BP_R_P').': <a href=\''.$arCompany['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.$docStatesQty.'</a></span>'.
					($totalTaskQty === 0
						? ''
						: '<br /><span class=\'bizproc-item-title\'>'.GetMessage('CRM_TASKS').': <a href=\''.$arCompany['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$totalTaskQty.($totalTaskQty > 5 ? '+' : '').'</a></span>');
			}
		}
	}

	$arCompany['ASSIGNED_BY_ID'] = $arCompany['~ASSIGNED_BY_ID'] = isset($arCompany['~ASSIGNED_BY']) ? (int)$arCompany['~ASSIGNED_BY'] : 0;
	$arCompany['~ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => isset($arCompany['~ASSIGNED_BY_LOGIN']) ? $arCompany['~ASSIGNED_BY_LOGIN'] : '',
			'NAME' => isset($arCompany['~ASSIGNED_BY_NAME']) ? $arCompany['~ASSIGNED_BY_NAME'] : '',
			'LAST_NAME' => isset($arCompany['~ASSIGNED_BY_LAST_NAME']) ? $arCompany['~ASSIGNED_BY_LAST_NAME'] : '',
			'SECOND_NAME' => isset($arCompany['~ASSIGNED_BY_SECOND_NAME']) ? $arCompany['~ASSIGNED_BY_SECOND_NAME'] : ''
		),
		true, false
	);
	$arCompany['ASSIGNED_BY'] = htmlspecialcharsbx($arCompany['~ASSIGNED_BY']);

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		if ($sExportType === 'csv')
		{
			$arCompany['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatTextComma(
				CompanyAddress::mapEntityFields($arCompany)
			);
		}
		else
		{
			$arCompany['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
				CompanyAddress::mapEntityFields($arCompany)
			);
		}
	}

	if(isset($arSelectMap['FULL_REG_ADDRESS']))
	{
		if ($sExportType === 'csv')
		{
			$arCompany['FULL_REG_ADDRESS'] = AddressFormatter::getSingleInstance()->formatTextComma(
				CompanyAddress::mapEntityFields($arCompany, ['TYPE_ID' => EntityAddressType::Registered])
			);
		}
		else
		{
			$arCompany['FULL_REG_ADDRESS'] = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
				CompanyAddress::mapEntityFields($arCompany, ['TYPE_ID' => EntityAddressType::Registered])
			);
		}
	}

	if (isset($parentFieldValues[$arCompany['ID']]))
	{
		foreach ($parentFieldValues[$arCompany['ID']] as $parentEntityTypeId => $parentEntity)
		{
			if ($isInExportMode)
			{
				$arCompany[$parentEntity['code']] = $parentEntity['title'];
			}
			else
			{
				$arCompany[$parentEntity['code']] = $parentEntity['value'];
			}
		}
	}

	$arResult['COMPANY'][$entityID] = $arCompany;
	$arResult['COMPANY_UF'][$entityID] = array();
	$arResult['COMPANY_ID'][$entityID] = $entityID;
}
unset($arCompany);

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['COMPANY'],
	$arResult['COMPANY_UF'],
	($isInExportMode ? ', ' : '<br />'),
	$isInExportMode,
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.company.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : false;
if($arResult['ENABLE_TOOLBAR'])
{
	$parentEntityTypeId = (int)($arParams['PARENT_ENTITY_TYPE_ID'] ?? 0);
	$parentEntityId = (int)($arParams['PARENT_ENTITY_ID'] ?? 0);
	if (\CCrmOwnerType::IsDefined($parentEntityTypeId) && $parentEntityId > 0)
	{
		$arResult['PATH_TO_COMPANY_ADD'] = Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
			\CCrmOwnerType::Company,
			0,
			null,
			new Crm\ItemIdentifier($parentEntityTypeId, $parentEntityId)
		);
	}
}

// adding crm multi field to result array
if (isset($arResult['COMPANY_ID']) && !empty($arResult['COMPANY_ID']))
{
	$arFmList = array();
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arResult['COMPANY_ID']));
	while($ar = $res->Fetch())
	{
		if (!$isInExportMode)
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		else
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = $ar['VALUE'];
		$arResult['COMPANY'][$ar['ELEMENT_ID']]['~'.$ar['COMPLEX_ID']][] = $ar['VALUE'];
	}
	foreach ($arFmList as $elementId => $arFM)
		foreach ($arFM as $complexId => $arComplexName)
			$arResult['COMPANY'][$elementId][$complexId] = implode(', ', $arComplexName);

	// checkig access for operation
	$arCompanyAttr = CCrmPerms::GetEntityAttr('COMPANY', $arResult['COMPANY_ID']);
	foreach ($arResult['COMPANY_ID'] as $iCompanyId)
	{
		$arResult['COMPANY'][$iCompanyId]['EDIT'] = $CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'WRITE', $arCompanyAttr[$iCompanyId]);
		$arResult['COMPANY'][$iCompanyId]['DELETE'] = $CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $arCompanyAttr[$iCompanyId]);

		$arResult['COMPANY'][$iCompanyId]['BIZPROC_LIST'] = array();

		if ($isBizProcInstalled)
		{
			foreach ($arBPData as $arBP)
			{
				if (!CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::StartWorkflow,
					$userID,
					array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arResult['COMPANY'][$iCompanyId]['ID']),
					array(
						'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
						'DocumentStates' => $arDocumentStates,
						'WorkflowTemplateId' => $arBP['ID'],
						'CreatedBy' => $arResult['COMPANY'][$iCompanyId]['~ASSIGNED_BY_ID'],
						'UserIsAdmin' => $isAdmin,
						'CRMEntityAttr' => $arCompanyAttr
					)
				))
				{
					continue;
				}

				$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
					array(
						'company_id' => $arResult['COMPANY'][$iCompanyId]['ID']
					)),
					array(
						'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => $arResult['SESSION_ID'],
						$bizProcTabId => 'tab_bizproc', 'back_url' => $arParams['PATH_TO_COMPANY_LIST'])
				);

				if (isset($arBP['HAS_PARAMETERS']))
				{
					$params = \Bitrix\Main\Web\Json::encode(array(
						'moduleId' => 'crm',
						'entity' => 'CCrmDocumentCompany',
						'documentType' => 'COMPANY',
						'documentId' => 'COMPANY_'.$arResult['COMPANY'][$iCompanyId]['ID'],
						'templateId' => $arBP['ID'],
						'templateName' => $arBP['NAME'],
						'hasParameters' => $arBP['HAS_PARAMETERS']
					));
					$arBP['ONCLICK'] = 'BX.Bizproc.Starter.singleStart('.$params
						.', function(){BX.Main.gridManager.reload(\''.CUtil::JSEscape($arResult['GRID_ID']).'\');});';
				}

				$arResult['COMPANY'][$iCompanyId]['BIZPROC_LIST'][] = $arBP;
			}
		}
	}
}

if (is_array($arResult['COMPANY_ID']) && !empty($arResult['COMPANY_ID']))
{
	if ($isInExportMode && $isStExport && $isStExportRequisiteMultiline)
	{
		$requisiteExportInfo =
			$requisite->prepareEntityListRequisiteExportData(CCrmOwnerType::Company, $arResult['COMPANY_ID']);
		$arResult['STEXPORT_RQ_HEADERS'] = $requisiteExportInfo['HEADERS'];
		$requisiteExportData = $requisiteExportInfo['EXPORT_DATA'];
		unset($requisiteExportInfo);
		$arResult['STEXPORT_RQ_DATA'] = $requisite->entityListRequisiteExportDataFormatMultiline(
			$requisiteExportData,
			$arResult['STEXPORT_RQ_HEADERS'],
			array('EXPORT_TYPE' => $sExportType)
		);
		unset($requisiteExportData);
	}
	else
	{
		$requisite->prepareEntityListFieldsValues(
			$arResult['COMPANY'],
			CCrmOwnerType::Company,
			$arResult['COMPANY_ID'],
			$rqSelect,
			array('EXPORT_TYPE' => $sExportType)
		);
	}
}

if (!$isInExportMode)
{
	$arResult['ANALYTIC_TRACKER'] = array(
		'lead_enabled' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isEnabled() ? 'Y' : 'N'
	);

	$arResult['NEED_FOR_REBUILD_DUP_INDEX'] =
		$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
		$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] =
		$arResult['NEED_FOR_TRANSFER_REQUISITES'] =
		$arResult['NEED_FOR_BUILD_TIMELINE'] =
		$arResult['NEED_FOR_BUILD_DUPLICATE_INDEX'] =
		$arResult['NEED_TO_CONVERT_ADDRESSES'] =
		$arResult['NEED_TO_CONVERT_UF_ADDRESSES'] =
		$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] = false;

	if(!$bInternal)
	{
		if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_SEARCH_CONTENT', 'N') === 'Y')
		{
			$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
		}

		$arResult['NEED_FOR_BUILD_TIMELINE'] = \Bitrix\Crm\Agent\Timeline\CompanyTimelineBuildAgent::getInstance()->isEnabled();
		$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] = \Bitrix\Crm\Agent\Security\CompanyAttributeRebuildAgent::getInstance()->isEnabled();

		$agent = Bitrix\Crm\Agent\Duplicate\CompanyDuplicateIndexRebuildAgent::getInstance();
		$isAgentEnabled = $agent->isEnabled();
		if ($isAgentEnabled)
		{
			if (!$agent->isActive())
			{
				$agent->enable(false);
				$isAgentEnabled = false;
			}
		}
		$arResult['NEED_FOR_BUILD_DUPLICATE_INDEX'] = $isAgentEnabled;
		unset ($agent, $isAgentEnabled);


		if(CCrmPerms::IsAdmin())
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX', 'N') === 'Y')
			{
				$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = true;
			}
			if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ATTR', 'N') === 'Y')
			{
				$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
				$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] = true;
			}
			if(COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_COMPANY', 'N') === 'Y')
			{
				$arResult['NEED_FOR_TRANSFER_REQUISITES'] = true;
			}
		}

		//region Address conversion
		/** @var CompanyAddressConvertAgent $agent */
		$agent = CompanyAddressConvertAgent::getInstance();
		$isAgentEnabled = $agent->isEnabled();
		if ($isAgentEnabled)
		{
			if (!$agent->isActive())
			{
				$agent->enable(false);
				$isAgentEnabled = false;
			}
		}
		$arResult['NEED_TO_CONVERT_ADDRESSES'] = $isAgentEnabled;
		unset ($agent, $isAgentEnabled);
		//endregion Address conversion

		//region Transfer addresses from user fields
		/** @var CompanyUfAddressConvertAgent $agent */
		$agent = CompanyUfAddressConvertAgent::getInstance();
		$isAgentEnabled = $agent->isEnabled();
		if ($isAgentEnabled)
		{
			if (!$agent->isActive())
			{
				if (CCrmOwnerType::IsDefined($agent->getSourceEntityTypeId()))
				{
					// Disable if was running but is not active
					// Source entity type is known only after start the agent
					$agent->enable(false);
				}
				$isAgentEnabled = false;
			}
		}
		$arResult['NEED_TO_CONVERT_UF_ADDRESSES'] = $isAgentEnabled;
		unset ($agent, $isAgentEnabled);
		//endregion Transfer addresses from user fields

		//region Show the process of indexing duplicates
		$isNeedToShowDupIndexProcess = false;
		$agent = CompanyIndexRebuild::getInstance($userID);
		if ($agent->isActive())
		{
			$state = $agent->state()->getData();
			if (isset($state['STATUS']) && $state['STATUS'] === CompanyIndexRebuild::STATUS_RUNNING)
			{
				$isNeedToShowDupIndexProcess = true;
			}
		}
		$arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS'] = $isNeedToShowDupIndexProcess;
		unset($isNeedToShowDupIndexProcess, $agent);
		//endregion Show the process of indexing duplicates

		//region Show the process of merge duplicates
		$isNeedToShowDupMergeProcess = false;
		$agent = CompanyMerge::getInstance($userID);
		if ($agent->isActive())
		{
			$state = $agent->state()->getData();
			if (isset($state['STATUS']) && $state['STATUS'] === CompanyMerge::STATUS_RUNNING)
			{
				$isNeedToShowDupMergeProcess = true;
			}
		}
		$arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS'] = $isNeedToShowDupMergeProcess;
		unset($isNeedToShowDupMergeProcess, $agent);
		//endregion Show the process of merge duplicates
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');
	return $arResult['ROWS_COUNT'];
}
else
{
	if ($isStExport)
	{
		$this->__templateName = '.default';

		$this->IncludeComponentTemplate($sExportType);

		return array(
			'PROCESSED_ITEMS' => count($arResult['COMPANY']),
			'TOTAL_ITEMS' => $arResult['STEXPORT_TOTAL_ITEMS']
		);
	}
	else
	{
		$APPLICATION->RestartBuffer();
		// hack. any '.default' customized template should contain 'excel' page
		$this->__templateName = '.default';

		if($sExportType === 'carddav')
		{
			Header('Content-Type: text/vcard');
		}
		elseif($sExportType === 'csv')
		{
			Header('Content-Type: text/csv');
			Header('Content-Disposition: attachment;filename=companies.csv');
		}
		elseif($sExportType === 'excel')
		{
			Header('Content-Type: application/vnd.ms-excel');
			Header('Content-Disposition: attachment;filename=companies.xls');
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
