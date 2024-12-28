<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global \CMain $APPLICATION
 * @global \CDatabase $DB
 * @var \CUserTypeManager $USER_FIELD_MANAGER
 * @var \CBitrixComponent $this
 * @var array $arParams
 * @var array $arResult
 */

global $USER_FIELD_MANAGER, $APPLICATION, $DB;

use Bitrix\Crm;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Component\EntityList\FieldRestrictionManager;
use Bitrix\Crm\Component\EntityList\FieldRestrictionManagerTypes;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

$isErrorOccurred = false;
$errorMessage = '';

if (!CModule::IncludeModule('crm'))
{
	$errorMessage = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
	$isErrorOccurred = true;
}

$isBizProcInstalled = IsModuleInstalled('bizproc');
if (!$isErrorOccurred && $isBizProcInstalled)
{
	if (!CModule::IncludeModule('bizproc'))
	{
		$errorMessage = Loc::getMessage('BIZPROC_MODULE_NOT_INSTALLED');
		$isErrorOccurred = true;
	}
	elseif (!CBPRuntime::isFeatureEnabled())
	{
		$isBizProcInstalled = false;
	}
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$isErrorOccurred && !CCrmDeal::CheckReadPermission(0, $userPermissions))
{
	$errorMessage = Loc::getMessage('CRM_PERMISSION_DENIED');
	$isErrorOccurred = true;
}

if (!$isErrorOccurred && !CAllCrmInvoice::installExternalEntities())
{
	$isErrorOccurred = true;
}

if (!$isErrorOccurred && !CCrmQuote::LocalComponentCausedUpdater())
{
	$isErrorOccurred = true;
}

if (!$isErrorOccurred && !CModule::IncludeModule('currency'))
{
	$errorMessage = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
	$isErrorOccurred = true;
}

if (!$isErrorOccurred && !CModule::IncludeModule('catalog'))
{
	$errorMessage = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
	$isErrorOccurred = true;
}
if (!$isErrorOccurred && !CModule::IncludeModule('sale'))
{

	$errorMessage = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');
	$isErrorOccurred = true;
}

//region Export params
$sExportType = (string)($arParams['EXPORT_TYPE'] ?? '');
if (empty($sExportType))
{
	$sExportType = (string)($_REQUEST['type'] ?? '');
}

$isInExportMode = false;
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

$needExportClientFields = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_CLIENT_FIELDS'])
	&& $arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_CLIENT_FIELDS'] === 'Y');

$arResult['STEXPORT_EXPORT_ALL_FIELDS'] = ($isStExport && $isStExportAllFields) ? 'Y' : 'N';

$isStExportProductsFields = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_PRODUCT_FIELDS'])
	&& $arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_PRODUCT_FIELDS'] === 'Y');
$arResult['STEXPORT_EXPORT_PRODUCT_FIELDS'] = ($isStExport && $isStExportProductsFields) ? 'Y' : 'N';

$arResult['STEXPORT_MODE'] = $isStExport ? 'Y' : 'N';
$arResult['STEXPORT_TOTAL_ITEMS'] = isset($arParams['STEXPORT_TOTAL_ITEMS'])
	? (int)$arParams['STEXPORT_TOTAL_ITEMS']
	: 0;
//endregion

if (!$isErrorOccurred && $isInExportMode && !CCrmDeal::CheckExportPermission($userPermissions))
{
	$errorMessage = Loc::getMessage('CRM_PERMISSION_DENIED');
	$isErrorOccurred = true;
}

if ($isErrorOccurred)
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

$isInCalendarMode = isset($arParams['CALENDAR_MODE']) && ($arParams['CALENDAR_MODE'] === 'Y');

$CCrmDeal = new CCrmDeal(false);
$CCrmBizProc = new CCrmBizProc('DEAL');
$fieldRestrictionManager = new FieldRestrictionManager(
	FieldRestrictionManager::MODE_GRID,
	[
		FieldRestrictionManagerTypes::CLIENT,
		FieldRestrictionManagerTypes::OBSERVERS,
		FieldRestrictionManagerTypes::ACTIVITY
	],
	\CCrmOwnerType::Deal
);

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();
$curPage = $APPLICATION->GetCurPage();

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arResult['PATH_TO_DEAL_LIST'] = $arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath(
	'PATH_TO_DEAL_LIST',
	$arParams['PATH_TO_DEAL_LIST'] ?? '',
	$curPage
);

$arResult['PATH_TO_DEAL_WIDGET'] = $arParams['PATH_TO_DEAL_WIDGET'] = CrmCheckPath(
	'PATH_TO_DEAL_WIDGET',
	$arParams['PATH_TO_DEAL_WIDGET'] ?? '',
	$curPage
);

$arResult['PATH_TO_DEAL_KANBAN'] = $arParams['PATH_TO_DEAL_KANBAN'] = CrmCheckPath(
	'PATH_TO_DEAL_KANBAN',
	$arParams['PATH_TO_DEAL_KANBAN'] ?? '',
	$curPage
);

$arResult['PATH_TO_DEAL_CALENDAR'] = $arParams['PATH_TO_DEAL_CALENDAR'] = CrmCheckPath(
	'PATH_TO_DEAL_CALENDAR',
	$arParams['PATH_TO_DEAL_CALENDAR'] ?? '',
	$curPage
);

$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_CATEGORY',
	$arParams['PATH_TO_DEAL_CATEGORY'] ?? '',
	$curPage . '?category_id=#category_id#'
);

$arParams['IS_RECURRING'] = isset($arParams['IS_RECURRING']) ? $arParams['IS_RECURRING'] : 'N';

if ($arParams['IS_RECURRING'] == 'Y')
{
	$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath(
		'PATH_TO_DEAL_RECUR_CATEGORY',
		$arParams['PATH_TO_DEAL_RECUR_CATEGORY'] ?? '',
		$curPage . '?category_id=#category_id#'
	);
}

$arParams['PATH_TO_DEAL_WIDGETCATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_WIDGETCATEGORY',
	$arParams['PATH_TO_DEAL_WIDGETCATEGORY'] ?? '',
	$curPage . '?category_id=#category_id#'
);

$arParams['PATH_TO_DEAL_KANBANCATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_KANBANCATEGORY',
	$arParams['PATH_TO_DEAL_KANBANCATEGORY'] ?? '',
	$curPage . '?category_id=#category_id#'
);//!!!

$arParams['PATH_TO_DEAL_CALENDARCATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_CALENDARCATEGORY',
	$arParams['PATH_TO_DEAL_CALENDARCATEGORY'] ?? '',
	$curPage . '?category_id=#category_id#'
);

$arParams['PATH_TO_DEAL_DETAILS'] = CrmCheckPath(
	'PATH_TO_DEAL_DETAILS',
	$arParams['PATH_TO_DEAL_DETAILS'] ?? '',
	$curPage . '?deal_id=#deal_id#&details'
);

$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath(
	'PATH_TO_DEAL_SHOW',
	$arParams['PATH_TO_DEAL_SHOW'] ?? '',
	$curPage . '?deal_id=#deal_id#&show'
);

$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath(
	'PATH_TO_DEAL_EDIT',
	$arParams['PATH_TO_DEAL_EDIT'] ?? '',
	$curPage . '?deal_id=#deal_id#&edit'
);

$arParams['PATH_TO_DEAL_MERGE'] = CrmCheckPath(
	'PATH_TO_DEAL_MERGE',
	$arParams['PATH_TO_DEAL_MERGE'] ?? '',
	'/deal/merge/'
);

$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath(
	'PATH_TO_QUOTE_EDIT',
	$arParams['PATH_TO_QUOTE_EDIT'] ?? '',
	$curPage . '?quote_id=#quote_id#&edit'
);

$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath(
	'PATH_TO_INVOICE_EDIT',
	$arParams['PATH_TO_INVOICE_EDIT'] ?? '',
	$curPage . '?invoice_id=#invoice_id#&edit'
);

$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath(
	'PATH_TO_COMPANY_SHOW',
	$arParams['PATH_TO_COMPANY_SHOW'] ?? '',
	$curPage . '?company_id=#company_id#&show'
);

$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath(
	'PATH_TO_CONTACT_SHOW',
	$arParams['PATH_TO_CONTACT_SHOW'] ?? '',
	$curPage . '?contact_id=#contact_id#&show'
);

$arParams['PATH_TO_USER_BP'] = CrmCheckPath(
	'PATH_TO_USER_BP',
	$arParams['PATH_TO_USER_BP'] ?? '',
	'/company/personal/bizproc/'
);

// $arParams['PATH_TO_USER_PROFILE'] is deprecated and will be ignored

$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath(
	'PATH_TO_USER_PROFILE',
	$arParams['PATH_TO_USER_PROFILE'] ?? '',
	'/company/personal/user/#user_id#/'
);

// $arParams['NAME_TEMPLATE'] is deprecated and will be ignored
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE'])
	? CSite::GetNameFormat(false)
	: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $arParams["NAME_TEMPLATE"] ?? '');

$arResult['PATH_TO_CURRENT_LIST'] = ($arParams['IS_RECURRING'] !== 'Y')
	? $arParams['PATH_TO_DEAL_LIST']
	: $arParams['PATH_TO_DEAL_RECUR'];

$arParams['ADD_EVENT_NAME'] = $arParams['ADD_EVENT_NAME'] ?? '';
$arResult['ADD_EVENT_NAME'] = $arParams['ADD_EVENT_NAME'] !== ''
	? preg_replace('/[^a-zA-Z0-9_]/', '', $arParams['ADD_EVENT_NAME'])
	: '';

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
$arResult['SESSION_ID'] = bitrix_sessid();
$arResult['NAVIGATION_CONTEXT_ID'] = $arParams['NAVIGATION_CONTEXT_ID'] ?? '';
$arResult['DISABLE_NAVIGATION_BAR'] = $arParams['DISABLE_NAVIGATION_BAR'] ?? 'N';
$arResult['PRESERVE_HISTORY'] = $arParams['PRESERVE_HISTORY'] ?? false;

$arResult['HAVE_CUSTOM_CATEGORIES'] = DealCategory::isCustomized();

$arResult['CATEGORY_ACCESS'] = [
	'CREATE' => \CCrmDeal::GetPermittedToCreateCategoryIDs($userPermissions),
	'READ' => \CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions),
	'UPDATE' => \CCrmDeal::GetPermittedToUpdateCategoryIDs($userPermissions)
];
$arResult['CATEGORY_ID'] = isset($arParams['CATEGORY_ID']) ? (int)$arParams['CATEGORY_ID'] : -1;
$arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

$arResult['ENTITY_CREATE_URLS'] = [
	\CCrmOwnerType::DealName =>
		\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Deal, 0),
	\CCrmOwnerType::LeadName =>
		\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Lead, 0),
	\CCrmOwnerType::CompanyName =>
		\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Company, 0),
	\CCrmOwnerType::ContactName =>
		\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Contact, 0),
	\CCrmOwnerType::QuoteName =>
		\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Quote, 0),
	\CCrmOwnerType::InvoiceName =>
		\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Invoice, 0)
];

$arResult['TIME_FORMAT'] = CCrmDateTimeHelper::getDefaultDateTimeFormat();

[$callListId, $callListContext] = \CCrmViewHelper::getCallListIdAndContextFromRequest();
$arResult['CALL_LIST_ID'] = $callListId;
$arResult['CALL_LIST_CONTEXT'] = $callListContext;
unset($callListId, $callListContext);

if (\CCrmViewHelper::isCallListUpdateMode(\CCrmOwnerType::Deal))
{
	AddEventHandler('crm', 'onCrmDealListItemBuildMenu', ['\Bitrix\Crm\CallList\CallList', 'handleOnCrmDealListItemBuildMenu']);
}

if (isset($arResult['CATEGORY_ID']) && $arResult['CATEGORY_ID'] >= 0)
{
	$arResult['PATH_TO_DEAL_CATEGORY'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_CATEGORY'] ?? '',
		['category_id' => $arResult['CATEGORY_ID']]
	);

	$arResult['PATH_TO_DEAL_KANBANCATEGORY'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_KANBANCATEGORY'] ?? '',
		['category_id' => $arResult['CATEGORY_ID']]
	);
	$arResult['PATH_TO_DEAL_CALENDARCATEGORY'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_CALENDARCATEGORY'] ?? '',
		['category_id' => $arResult['CATEGORY_ID']]
	);

	$arResult['PATH_TO_DEAL_WIDGETCATEGORY'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_WIDGETCATEGORY'] ?? '',
		['category_id' => $arResult['CATEGORY_ID']]
	);
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && $arParams['GADGET_ID'] <> '')
{
	$arResult['GADGET'] = 'Y';
	$arResult['GADGET_ID'] = $arParams['GADGET_ID'];
}
$isInGadgetMode = $arResult['GADGET'] === 'Y';

$arFilter = $arSort = [];
$bInternal = false;
$arResult['FORM_ID'] = $arParams['FORM_ID'] ?? '';
$arResult['TAB_ID'] = $arParams['TAB_ID'] ?? '';

if ($arResult['CATEGORY_ID'] > 0)
{
	$arFilter['CATEGORY_ID'] = $arResult['CATEGORY_ID'];
}
if ($arResult['CATEGORY_ID'] == 0)
{
	$arFilter['@CATEGORY_ID'] = $arResult['CATEGORY_ID'];
}

if (!empty($arParams['INTERNAL_FILTER']) || $isInGadgetMode)
{
	$bInternal = true;
}

$arResult['INTERNAL'] = $bInternal;

if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	if (empty($arParams['GRID_ID_SUFFIX']))
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
if (isset($arParams['WIDGET_DATA_FILTER']['WG']) && $arParams['WIDGET_DATA_FILTER']['WG'] === 'Y')
{
	$enableWidgetFilter = true;
	$widgetFilter = $arParams['WIDGET_DATA_FILTER'];
}
elseif (!$bInternal && isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y')
{
	$enableWidgetFilter = true;
	$widgetFilter = $_REQUEST;
}

if ($enableWidgetFilter)
{
	$dataSourceFilter = null;
	$dataSourceName = $widgetFilter['DS'] ?? '';
	if ($dataSourceName !== '')
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

	if (is_array($dataSourceFilter) && !empty($dataSourceFilter))
	{
		$arFilter = $dataSourceFilter;
	}
	else
	{
		$enableWidgetFilter = false;
	}
}

//region Old logic of the counter panel (not used)
$enableCounterFilter = false;
if (!$bInternal && isset($_REQUEST['counter']))
{
	$counterTypeID = Bitrix\Crm\Counter\EntityCounterType::resolveID($_REQUEST['counter']);
	$counter = null;
	if (Bitrix\Crm\Counter\EntityCounterType::isDefined($counterTypeID))
	{
		try
		{
			$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
				CCrmOwnerType::Deal,
				$counterTypeID,
				$userID,
				array_merge(
					Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST),
					array('DEAL_CATEGORY_ID' => $arResult['CATEGORY_ID'])
				)
			);

			$arFilter = $counter->prepareEntityListFilter(
				array(
					'MASTER_ALIAS' => CCrmDeal::TABLE_ALIAS,
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
//endregion

$request = Main\Application::getInstance()->getContext()->getRequest();
$fromAnalytics = $request->getQuery('from_analytics') === 'Y';
$enableReportFilter = false;
if ($fromAnalytics)
{
	$reportId = Context::getCurrent()->getRequest()['report_id'];
	if ($reportId != '')
	{
		$reportHandler = Crm\Integration\Report\ReportHandlerFactory::createWithReportId($reportId);
		$reportFilter = $reportHandler ? $reportHandler->prepareEntityListFilter(Context::getCurrent()->getRequest()) : null;

		if (is_array($reportFilter) && !empty($reportFilter))
		{
			$arFilter = $reportFilter;
			$enableReportFilter = true;
		}
	}
	else
	{
		$boardId = Main\Application::getInstance()->getContext()->getRequest()->getQuery('board_id');
		$boardId = preg_replace('/[^\w\-_]/', '', $boardId);
		$externalFilterId = 'report_board_' . $boardId . '_filter';
	}
}

$arResult['IS_EXTERNAL_FILTER'] = ($enableWidgetFilter || $enableCounterFilter || $enableReportFilter);

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);

$arResult['GRID_ID'] =
	(new Crm\Component\EntityList\GridId(CCrmOwnerType::Deal))
		->getValue(
			(string)$arParams['GRID_ID_SUFFIX'],
			[
				'IS_RECURRING' => ($arParams['IS_RECURRING'] === 'Y'),
			]
		)
;

CCrmDeal::PrepareConversionPermissionFlags(0, $arResult, $userPermissions);
if ($arResult['CAN_CONVERT'])
{
	$config = Crm\Conversion\ConversionManager::getConfig(\CCrmOwnerType::Deal);
	$config->deleteItemByEntityTypeId(\CCrmOwnerType::SmartDocument);
	$arResult['CONVERSION_CONFIG'] = $config;

	$arResult['CONVERTER_ID'] = $arResult['GRID_ID'];
}

$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_TYPE');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
// Please, uncomment if required
//$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
$arResult['CLOSED_LIST'] = array('Y' => Loc::getMessage('MAIN_YES'), 'N' => Loc::getMessage('MAIN_NO'));
$arResult['WEBFORM_LIST'] = WebFormManager::getListNamesEncoded();
$arResult['FILTER'] = [];
$arResult['FILTER2LOGIC'] = [];
$arResult['FILTER_PRESETS'] = [];
$arResult['PERMS']['ADD'] = CCrmDeal::CheckCreatePermission($userPermissions);
$arResult['PERMS']['WRITE'] = CCrmDeal::CheckUpdatePermission(0, $userPermissions);
$arResult['PERMS']['DELETE'] = CCrmDeal::CheckDeletePermission(0, $userPermissions);

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['EXTERNAL_SALES'] = CCrmExternalSaleHelper::PrepareListItems();

//region Setup of group action 'Change stage'
$effectiveCategoryID = -1;
if ($arResult['CATEGORY_ID'] >= 0)
{
	$effectiveCategoryID = $arResult['CATEGORY_ID'];
}
if (!$arResult['HAVE_CUSTOM_CATEGORIES'])
{
	$effectiveCategoryID = 0;
}
elseif(count($arResult['CATEGORY_ACCESS']['READ']) === 1)
{
	$effectiveCategoryID = $arResult['CATEGORY_ACCESS']['READ'][0];
}
elseif($effectiveCategoryID < 0 && isset($arFilter['CATEGORY_ID']))
{
	if (!is_array($arFilter['CATEGORY_ID']))
	{
		$effectiveCategoryID = (int)$arFilter['CATEGORY_ID'];
	}
	elseif(count($arFilter['CATEGORY_ID']) === 1)
	{
		$effectiveCategoryID = (int)($arFilter['CATEGORY_ID'][0]);
	}
}
elseif($effectiveCategoryID < 0 && is_array($arFilter['@CATEGORY_ID'] ?? null) && count($arFilter['@CATEGORY_ID']) == 1)
{
	$effectiveCategoryID = (int)($arFilter['@CATEGORY_ID'][0] ?? -1);
}

if ($effectiveCategoryID >= 0)
{
	$arResult['CATEGORY_STAGE_LIST'] = DealCategory::getStageList($effectiveCategoryID);
}
//endregion

$arResult['CATEGORY_LIST'] = DealCategory::prepareSelectListItems($arResult['CATEGORY_ACCESS']['READ']);

//region Filter Presets Initialization
if (!$bInternal)
{
	$flags = Crm\Filter\DealSettings::FLAG_NONE | Crm\Filter\DealSettings::FLAG_ENABLE_CLIENT_FIELDS;
	if ($arParams['IS_RECURRING'] === 'Y')
	{
		$flags |= Crm\Filter\DealSettings::FLAG_RECURRING;
	}

	$entityFilter = Crm\Filter\Factory::createEntityFilter(
		new Crm\Filter\DealSettings([
			'ID' => $arResult['GRID_ID'],
			'categoryID' => $arResult['CATEGORY_ID'],
			'categoryAccess' => $arResult['CATEGORY_ACCESS'],
			'flags' => $flags,
		])
	);

	if ($arParams['IS_RECURRING'] !== 'Y')
	{
		if (!empty($externalFilterId))
		{
			$fields = $entityFilter->getFields();
			foreach ($fields as $field)
			{
				$arResult['FILTER'][] = $field->toArray();
			}

			$arResult['FILTER_PRESETS'] = [];
		}
		else
		{
			$arResult['FILTER_PRESETS'] = (new Bitrix\Crm\Filter\Preset\Deal())
				->setUserId($arResult['CURRENT_USER_ID'])
				->setUserName(Container::getInstance()->getUserBroker()->getName($arResult['CURRENT_USER_ID']))
				->setDefaultValues($entityFilter->getDefaultFieldIDs())
				->setCategoryId($arResult['CATEGORY_ID'])
				->getDefaultPresets()
			;
		}
	}
}
//endregion

if (!empty($externalFilterId))
{
	Main\Loader::includeModule('report');
	$arResult['GRID_ID'] = 'report_' . $boardId . '_grid';
	$filterOptions = new \Bitrix\Crm\Filter\UiFilterOptions($arResult['GRID_ID'], []);
}
else
{
	$filterOptions = new \Bitrix\Crm\Filter\UiFilterOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
}

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
//region Navigation Params
if ($arParams['DEAL_COUNT'] <= 0)
{
	$arParams['DEAL_COUNT'] = 20;
}
$arNavParams = $gridOptions->GetNavParams(array('nPageSize' => $arParams['DEAL_COUNT']));
$arNavParams['bShowAll'] = false;
if (isset($arNavParams['nPageSize']) && $arNavParams['nPageSize'] > 100)
{
	$arNavParams['nPageSize'] = 100;
}
//endregion

//region Filter initialization

//region Filter fields cleanup
$fieldRestrictionManager->removeRestrictedFields($filterOptions, $gridOptions);
//endregion

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = ['TITLE', 'COMMENTS'];

	$effectiveFilterFieldIDs = $filterOptions->getUsedFields();
	if (empty($effectiveFilterFieldIDs))
	{
		$effectiveFilterFieldIDs = $entityFilter->getDefaultFieldIDs();
	}

	//region HACK: Preload fields for filter of user activities, stage ID & webforms
	if (!in_array('ASSIGNED_BY_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ASSIGNED_BY_ID';
	}

	if (!in_array('ACTIVITY_COUNTER', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ACTIVITY_COUNTER';
	}

	if(!in_array('ACTIVITY_RESPONSIBLE_IDS', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ACTIVITY_RESPONSIBLE_IDS';
	}

	if(!in_array('ACTIVITY_FASTSEARCH_CREATED', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ACTIVITY_FASTSEARCH_CREATED';
	}

	if (!in_array('WEBFORM_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'WEBFORM_ID';
	}

	if (!in_array('CONTACT_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'CONTACT_ID';
	}

	Tracking\UI\Filter::appendEffectiveFields($effectiveFilterFieldIDs);

	//Is necessary for deal funnel filter.
	if ($arResult['CATEGORY_ID'] >= 0 && !in_array('STAGE_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'STAGE_ID';
	}
	//endregion

	if (empty($externalFilterId))
	{
		foreach ($effectiveFilterFieldIDs as $filterFieldID)
		{
			$filterField = $entityFilter->getField($filterFieldID);
			if ($filterField)
			{
				$arResult['FILTER'][] = $filterField->toArray();
			}
		}
	}
}
//endregion

//region Headers initialization
Container::getInstance()->getLocalization()->loadMessages();

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => Loc::getMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'first_order' => 'desc', 'width' => 60, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
	array('id' => 'DEAL_SUMMARY', 'name' => Loc::getMessage('CRM_COLUMN_DEAL'), 'sort' => 'title', 'width' => 200, 'default' => true, 'editable' => true),
	array('id' => 'CATEGORY_ID', 'name' => Loc::getMessage('CRM_COLUMN_CATEGORY_ID'), 'sort' => 'category_id', 'default' => false),
	array('id' => 'IS_RETURN_CUSTOMER', 'name' => Loc::getMessage('CRM_COLUMN_IS_RETURN_CUSTOMER'), 'sort' => 'is_return_customer', 'default' => false),
	array('id' => 'IS_REPEATED_APPROACH', 'name' => Loc::getMessage('CRM_COLUMN_IS_REPEATED_APPROACH'), 'sort' => 'is_repeated_approach', 'default' => false),
);

if ($arParams['IS_RECURRING'] !== 'Y')
{
	if ($arResult['CATEGORY_ID'] >= 0 || !DealCategory::isCustomized())
	{
		$arResult['HEADERS'][] = array(
			'id' => 'STAGE_ID',
			'name' => Loc::getMessage('CRM_COLUMN_STAGE_ID'),
			'sort' => 'stage_sort',
			'width' => 200,
			'default' => true,
			'prevent_default' => false,
			'type' => 'list',
			'editable' => array('items' => $arResult['CATEGORY_STAGE_LIST'])
		);
	}
	else
	{
		$arResult['HEADERS'][] = array(
			'id' => 'STAGE_ID',
			'name' => Loc::getMessage('CRM_COLUMN_STAGE_ID'),
			'sort' => false,
			'width' => 200,
			'default' => true,
			'prevent_default' => false,
			'editable' => false
		);
	}
}

// Don't display activities in INTERNAL mode.
if (!$bInternal && $arParams['IS_RECURRING'] !== 'Y')
{
	$arResult['HEADERS'][] = array(
		'id' => 'ACTIVITY_ID',
		'name' => Loc::getMessage('CRM_COLUMN_ACTIVITY'),
		'sort' => 'nearest_activity',
		'default' => true,
		'prevent_default' => false
	);
}

if ($arParams['IS_RECURRING'] === 'Y')
{
	$arResult['HEADERS'] = array_merge(
		$arResult['HEADERS'],
		[
			[
				'id' => 'DEAL_CLIENT',
				'name' => Loc::getMessage('CRM_COLUMN_CLIENT'),
				'sort' => 'deal_client',
				'default' => true,
				'editable' => false,
			],
			[
				'id' => Crm\Item::FIELD_NAME_OBSERVERS,
				'name' => Loc::getMessage('CRM_COLUMN_OBSERVERS'),
				'sort' => false,
				'editable' => false,
			],
			[
				'id' => 'CRM_DEAL_RECURRING_ACTIVE',
				'name' => Loc::getMessage('CRM_COLUMN_RECURRING_ACTIVE_TITLE'),
				'sort' => 'active',
				'default' => true,
				'editable' => false,
				'type' => 'checkbox',
			],
			[
				'id' => 'CRM_DEAL_RECURRING_COUNTER_REPEAT',
				'name' => Loc::getMessage('CRM_COLUMN_RECURRING_COUNTER_REPEAT'),
				'sort' => 'counter_repeat',
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'CRM_DEAL_RECURRING_NEXT_EXECUTION',
				'name' => Loc::getMessage('CRM_COLUMN_RECURRING_NEXT_EXECUTION'),
				'sort' => 'next_execution',
				'default' => true,
				'editable' => false,
			],

			[
				'id' => 'CRM_DEAL_RECURRING_START_DATE',
				'name' => Loc::getMessage('CRM_COLUMN_START_DATE'),
				'sort' => 'start_date',
				'editable' => false,
			],
			[
				'id' => 'CRM_DEAL_RECURRING_LIMIT_DATE',
				'name' => Loc::getMessage('CRM_COLUMN_LIMIT_DATE'),
				'sort' => 'limit_date',
				'editable' => false,
			],
			[
				'id' => 'CRM_DEAL_RECURRING_LIMIT_REPEAT',
				'name' => Loc::getMessage('CRM_COLUMN_LIMIT_REPEAT'),
				'sort' => 'limit_repeat',
				'editable' => false,
			],

			[
				'id' => 'PROBABILITY',
				'name' => Loc::getMessage('CRM_COLUMN_PROBABILITY'),
				'sort' => 'probability',
				'first_order' => 'desc',
				'editable' => true,
				'align' => 'right',
			],
			[
				'id' => 'SUM',
				'name' => Loc::getMessage('CRM_COLUMN_SUM'),
				'sort' => 'opportunity_account',
				'first_order' => 'desc',
				'default' => true,
				'editable' => false,
				'align' => 'right',
			],
			[
				'id' => 'PAYMENT_STAGE',
				'name' => Loc::getMessage('CRM_COLUMN_PAYMENT_STAGE'),
				'sort' => false,
				'editable' => false,
			],
			[
				'id' => 'DELIVERY_STAGE',
				'name' => Loc::getMessage('CRM_COLUMN_DELIVERY_STAGE'),
				'sort' => false,
				'editable' => false,
			],
			[
				'id' => 'ASSIGNED_BY',
				'name' => Loc::getMessage('CRM_COLUMN_ASSIGNED_BY'),
				'sort' => 'assigned_by',
				'default' => true,
				'editable' => false,
				'class' => 'username',
			],
			[
				'id' => 'ORIGINATOR_ID',
				'name' => Loc::getMessage('CRM_COLUMN_BINDING'),
				'sort' => false,
				'editable' => empty($arResult['EXTERNAL_SALES']) ? false : ['items' => $arResult['EXTERNAL_SALES']],
				'type' => 'list',
			],

			[
				'id' => 'TITLE',
				'name' => Loc::getMessage('CRM_COLUMN_TITLE'),
				'sort' => 'title',
				'editable' => true,
			],
			[
				'id' => 'TYPE_ID',
				'name' => Loc::getMessage('CRM_COLUMN_TYPE_ID'),
				'sort' => 'type_id',
				'editable' => ['items' => CCrmStatus::GetStatusList('DEAL_TYPE')],
				'type' => 'list',
			],
			[
				'id' => 'OPPORTUNITY',
				'name' => Loc::getMessage('CRM_COLUMN_OPPORTUNITY'),
				'sort' => 'opportunity',
				'first_order' => 'desc',
				'editable' => true,
				'align' => 'right',
			],
			[
				'id' => 'CURRENCY_ID',
				'name' => Loc::getMessage('CRM_COLUMN_CURRENCY_ID'),
				'sort' => 'currency_id',
				'editable' => ['items' => CCrmCurrencyHelper::PrepareListItems()],
				'type' => 'list',
			],
			[
				'id' => 'COMPANY_ID',
				'name' => Loc::getMessage('CRM_COLUMN_COMPANY_ID'),
				'sort' => 'company_id',
				'editable' => false,
			],
			[
				'id' => 'CONTACT_ID',
				'name' => Loc::getMessage('CRM_COLUMN_CONTACT_ID'),
				'sort' => 'contact_full_name',
				'editable' => false,
			],

			[
				'id' => 'DATE_CREATE',
				'name' => Loc::getMessage('CRM_COLUMN_DATE_CREATE'),
				'sort' => 'date_create',
				'first_order' => 'desc',
				'default' => false,
				'class' => 'date',
			],
			[
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('CRM_COLUMN_CREATED_BY'),
				'sort' => 'created_by',
				'editable' => false,
				'class' => 'username',
			],
			[
				'id' => 'DATE_MODIFY',
				'name' => Loc::getMessage('CRM_COLUMN_DATE_MODIFY'),
				'sort' => 'date_modify',
				'first_order' => 'desc',
				'class' => 'date',
			],
			[
				'id' => 'MODIFY_BY',
				'name' => Loc::getMessage('CRM_COLUMN_MODIFY_BY'),
				'sort' => 'modify_by',
				'editable' => false,
				'class' => 'username',
			],
			[
				'id' => 'BEGINDATE',
				'name' => Loc::getMessage('CRM_COLUMN_BEGINDATE_1'),
				'sort' => 'begindate',
				'editable' => true,
				'type' => 'date',
				'class' => 'date',
			],
			[
				'id' => 'PRODUCT_ID',
				'name' => Loc::getMessage('CRM_COLUMN_PRODUCT_ID'),
				'sort' => false,
				'default' => $isInExportMode,
				'editable' => false,
				'type' => 'list',
			],
			[
				'id' => 'COMMENTS',
				'name' => Loc::getMessage('CRM_COLUMN_COMMENTS'),
				'sort' => false /*because of MSSQL*/,
				'editable' => false,
			]
		]
	);
}
else
{
	$arResult['HEADERS'] = array_merge(
		$arResult['HEADERS'],
		[
			[
				'id' => 'DEAL_CLIENT',
				'name' => Loc::getMessage('CRM_COLUMN_CLIENT'),
				'sort' => 'deal_client',
				'default' => true,
				'editable' => false,
			],
			[
				'id' => Crm\Item::FIELD_NAME_OBSERVERS,
				'name' => Loc::getMessage('CRM_COLUMN_OBSERVERS'),
				'sort' => false,
				'editable' => false,
			],
			[
				'id' => 'PROBABILITY',
				'name' => Loc::getMessage('CRM_COLUMN_PROBABILITY'),
				'sort' => 'probability',
				'first_order' => 'desc',
				'editable' => true,
				'align' => 'right',
			],
			[
				'id' => 'SUM',
				'name' => Loc::getMessage('CRM_COLUMN_SUM'),
				'sort' => 'opportunity_account',
				'first_order' => 'desc',
				'default' => true,
				'editable' => false,
				'align' => 'right',
			],
			[
				'id' => 'PAYMENT_STAGE',
				'name' => Loc::getMessage('CRM_COLUMN_PAYMENT_STAGE'),
				'sort' => false,
				'editable' => false,
			],
			[
				'id' => 'DELIVERY_STAGE',
				'name' => Loc::getMessage('CRM_COLUMN_DELIVERY_STAGE'),
				'sort' => false,
				'editable' => false,
			],
			[
				'id' => 'ASSIGNED_BY',
				'name' => Loc::getMessage('CRM_COLUMN_ASSIGNED_BY'),
				'sort' => 'assigned_by',
				'default' => true,
				'editable' => false,
				'class' => 'username',
			],
			[
				'id' => 'ORIGINATOR_ID',
				'name' => Loc::getMessage('CRM_COLUMN_BINDING'),
				'sort' => false,
				'editable' => empty($arResult['EXTERNAL_SALES']) ? false : ['items' => $arResult['EXTERNAL_SALES']],
				'type' => 'list',
			],

			[
				'id' => 'TITLE',
				'name' => Loc::getMessage('CRM_COLUMN_TITLE'),
				'sort' => 'title',
				'editable' => true,
			],
			[
				'id' => 'TYPE_ID',
				'name' => Loc::getMessage('CRM_COLUMN_TYPE_ID'),
				'sort' => 'type_id',
				'editable' => ['items' => CCrmStatus::GetStatusList('DEAL_TYPE')],
				'type' => 'list',
			],
			[
				'id' => 'SOURCE_ID',
				'name' => Loc::getMessage('CRM_COLUMN_SOURCE'),
				'sort' => 'source_id',
				'editable' => ['items' => CCrmStatus::GetStatusList('SOURCE')],
				'type' => 'list',
			],
			[
				'id' => 'SOURCE_DESCRIPTION',
				'name' => Loc::getMessage('CRM_COLUMN_SOURCE_DESCRIPTION'),
				'sort' => false,
				'default' => false,
				'editable' => false,
			],

			[
				'id' => 'OPPORTUNITY',
				'name' => Loc::getMessage('CRM_COLUMN_OPPORTUNITY'),
				'sort' => 'opportunity',
				'first_order' => 'desc',
				'editable' => true,
				'align' => 'right',
			],
			[
				'id' => 'CURRENCY_ID',
				'name' => Loc::getMessage('CRM_COLUMN_CURRENCY_ID'),
				'sort' => 'currency_id',
				'editable' => ['items' => CCrmCurrencyHelper::PrepareListItems()],
				'type' => 'list',
			],
			[
				'id' => 'COMPANY_ID',
				'name' => Loc::getMessage('CRM_COLUMN_COMPANY_ID'),
				'sort' => 'company_id',
				'editable' => false,
			],
			[
				'id' => 'CONTACT_ID',
				'name' => Loc::getMessage('CRM_COLUMN_CONTACT_ID'),
				'sort' => 'contact_full_name',
				'editable' => false,
			],

			[
				'id' => 'CLOSED',
				'name' => Loc::getMessage('CRM_COLUMN_CLOSED'),
				'sort' => 'closed',
				'align' => 'center',
				'editable' => [
					'items' => [
						'' => '',
						'Y' => Loc::getMessage('MAIN_YES'),
						'N' => Loc::getMessage('MAIN_NO')
					]
				],
				'type' => 'list',
			],
			[
				'id' => 'DATE_CREATE',
				'name' => Loc::getMessage('CRM_COLUMN_DATE_CREATE'),
				'sort' => 'date_create',
				'first_order' => 'desc',
				'default' => true,
				'class' => 'date',
			],
			[
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('CRM_COLUMN_CREATED_BY'),
				'sort' => 'created_by',
				'editable' => false,
				'class' => 'username',
			],
			[
				'id' => 'DATE_MODIFY',
				'name' => Loc::getMessage('CRM_COLUMN_DATE_MODIFY'),
				'sort' => 'date_modify',
				'first_order' => 'desc',
				'class' => 'date',
			],
			[
				'id' => 'MODIFY_BY',
				'name' => Loc::getMessage('CRM_COLUMN_MODIFY_BY'),
				'sort' => 'modify_by',
				'editable' => false,
				'class' => 'username',
			],
			[
				'id' => 'BEGINDATE',
				'name' => Loc::getMessage('CRM_COLUMN_BEGINDATE_1'),
				'sort' => 'begindate',
				'editable' => true,
				'type' => 'date',
				'class' => 'date',
			],
			[
				'id' => 'CLOSEDATE',
				'name' => Loc::getMessage('CRM_COLUMN_CLOSEDATE'),
				'sort' => 'closedate',
				'editable' => true,
				'type' => 'date',
			],
			[
				'id' => 'PRODUCT_ID',
				'name' => Loc::getMessage('CRM_COLUMN_PRODUCT_ID'),
				'sort' => false,
				'default' => $isInExportMode,
				'editable' => false,
				'type' => 'list',
			],
			[
				'id' => 'COMMENTS',
				'name' => Loc::getMessage('CRM_COLUMN_COMMENTS'),
				'sort' => false /*because of MSSQL*/,
				'editable' => false,
			],
			[
				'id' => 'EVENT_DATE',
				'name' => Loc::getMessage('CRM_COLUMN_EVENT_DATE'),
				'sort' => 'event_date',
				'default' => false,
			],
			[
				'id' => 'EVENT_ID',
				'name' => Loc::getMessage('CRM_COLUMN_EVENT_ID'),
				'sort' => 'event_id',
				'editable' => ['items' => CCrmStatus::GetStatusList('EVENT_TYPE')],
				'type' => 'list',
			],
			[
				'id' => 'EVENT_DESCRIPTION',
				'name' => Loc::getMessage('CRM_COLUMN_EVENT_DESCRIPTION'),
				'sort' => false,
				'editable' => false,
			],
			[
				'id' => 'WEBFORM_ID',
				'name' => Loc::getMessage('CRM_COLUMN_WEBFORM'),
				'sort' => 'webform_id',
				'type' => 'list',
			]
		]
	);
}

if ($arParams['IS_RECURRING'] !== 'Y')
{
	Tracking\UI\Grid::appendColumns($arResult['HEADERS']);

	$utmList = \Bitrix\Crm\UtmTable::getCodeNames();
	foreach ($utmList as $utmCode => $utmName)
	{
		$arResult['HEADERS'][] = array(
			'id' => $utmCode,
			'name' => $utmName,
			'sort' => false,
			'default' => $isInExportMode,
			'editable' => false
		);
	}
}

$CCrmUserType->appendGridHeaders($arResult['HEADERS']);

Crm\Service\Container::getInstance()->getParentFieldManager()->prepareGridHeaders(
	\CCrmOwnerType::Deal,
	$arResult['HEADERS']
);

$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);

if (
	\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
	&& $factory
	&& $factory->isLastActivityEnabled()
)
{
	$arResult['HEADERS'][] = ['id' => Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME, 'name' => $factory->getFieldCaption(Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME), 'sort' => mb_strtolower(Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME), 'first_order' => 'desc', 'class' => 'datetime'];
}

if ($bInternal)
{
	$arResult['HEADERS_SECTIONS'] = [
		[
			'id' => 'DEAL',
			'name' => Loc::getMessage("CRM_COLUMN_DEAL"),
			'default' => true,
			'selected' => true,
		],
	];
}
else
{
	$arResult['HEADERS_SECTIONS'] = \Bitrix\Crm\Filter\HeaderSections::getInstance()
		->sections($factory);
}

unset($factory);

$arBPData = [];
if ($isBizProcInstalled)
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(['crm', 'CCrmDocumentDeal', 'DEAL'], false);
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentDeal', 'DEAL'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::ViewWorkflow,
			$userID,
			array('crm', 'CCrmDocumentDeal', 'DEAL'),
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
}

$userDataProvider = new Bitrix\Crm\Component\EntityList\UserDataProvider\RelatedUsers(CCrmOwnerType::Deal);

$contactDataProvider = new \Bitrix\Crm\Component\EntityList\ClientDataProvider\GridDataProvider(CCrmOwnerType::Contact);
$contactDataProvider
	->setExportMode($isInExportMode)
	->setGridId($arResult['GRID_ID']);

$companyDataProvider = new \Bitrix\Crm\Component\EntityList\ClientDataProvider\GridDataProvider(CCrmOwnerType::Company);
$companyDataProvider
	->setExportMode($isInExportMode)
	->setGridId($arResult['GRID_ID']);

$observersDataProvider = new \Bitrix\Crm\Component\EntityList\UserDataProvider\Observers(CCrmOwnerType::Deal);

$arResult['HEADERS'] = array_values($arResult['HEADERS']);

if (!$bInternal)
{
	$clientFieldHeaders = [];
	if (Bitrix\Crm\Component\EntityList\ClientDataProvider::getPriorityEntityTypeId() === \CCrmOwnerType::Contact)
	{
		$clientFieldHeaders = array_merge(
			$contactDataProvider->getHeaders(),
			$companyDataProvider->getHeaders()
		);
	}
	else
	{
		$clientFieldHeaders = array_merge(
			$companyDataProvider->getHeaders(),
			$contactDataProvider->getHeaders()
		);
	}
	$arResult['HEADERS'] = array_merge(
		$arResult['HEADERS'],
		$clientFieldHeaders
	);
}

if (!empty($arParams['DEFAULT_COLUMNS']) && is_array($arParams['DEFAULT_COLUMNS']))
{
	foreach ($arResult['HEADERS'] as &$header)
	{
		$header['default'] = in_array($header['id'], $arParams['DEFAULT_COLUMNS'], true);
	}
}

if (!empty($arParams['COLUMNS_ORDER']) && is_array($arParams['COLUMNS_ORDER']))
{
	// like [ID => 0, NAME => 1]
	$desiredColumnOrder = array_flip(array_values($arParams['COLUMNS_ORDER']));

	usort($arResult['HEADERS'], function (array $left, array $right) use ($desiredColumnOrder) {
		// move unfound columns to the end of array
		$leftOrder = $desiredColumnOrder[$left['id']] ?? PHP_INT_MAX;
		$rightOrder = $desiredColumnOrder[$right['id']] ?? PHP_INT_MAX;

		return $leftOrder <=> $rightOrder;
	});
}

//region Check and fill fields restriction
$params = [
	$arResult['GRID_ID'],
	$arResult['HEADERS'],
	$entityFilter ?? null
];
$arResult['RESTRICTED_FIELDS_ENGINE'] = $fieldRestrictionManager->fetchRestrictedFieldsEngine(...$params);
$arResult['RESTRICTED_FIELDS'] = $fieldRestrictionManager->getFilterFields(...$params);
//endregion

// list all fields for export
$exportAllFieldsList = [];
if ($isInExportMode && $isStExportAllFields)
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if (
			$needExportClientFields
			|| (
				mb_strpos($arHeader['id'], 'CONTACT_') !== 0
				&& mb_strpos($arHeader['id'], 'COMPANY_') !== 0
			)
		)
		{
			$exportAllFieldsList[] = $arHeader['id'];
		}
	}
}
unset($arHeader);

//endregion Headers initialization

$settings = \CCrmViewHelper::initGridSettings(
	$arResult['GRID_ID'],
	$gridOptions,
	$arResult['HEADERS'],
	$isInExportMode,
	$arResult['CATEGORY_ID'],
	$arResult['CATEGORY_ID'] < 0,
	[
		'DEAL_SUMMARY' => 'TITLE',
	],
	$arParams['IS_RECURRING'] === 'Y',
);

$arResult['PANEL'] = \CCrmViewHelper::initGridPanel(
	\CCrmOwnerType::Deal,
	$settings,
);
unset($settings);

//region Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);

if (check_bitrix_sessid())
{
	$getAction = 'action_'.$arResult['GRID_ID'];
	//We need to check grid 'controls'
	if ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = check_bitrix_sessid();

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if (isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = $arResult['IS_AJAX_CALL'];
	}
}
//endregion

// HACK: for clear filter by CREATED_BY_ID, MODIFY_BY_ID and ASSIGNED_BY_ID
if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
	if (isset($_REQUEST['CREATED_BY_ID_name']) && $_REQUEST['CREATED_BY_ID_name'] === '')
	{
		$_REQUEST['CREATED_BY_ID'] = [];
		$_GET['CREATED_BY_ID'] = [];
	}

	if (isset($_REQUEST['MODIFY_BY_ID_name']) && $_REQUEST['MODIFY_BY_ID_name'] === '')
	{
		$_REQUEST['MODIFY_BY_ID'] = [];
		$_GET['MODIFY_BY_ID'] = [];
	}

	if (isset($_REQUEST['ASSIGNED_BY_ID_name']) && $_REQUEST['ASSIGNED_BY_ID_name'] === '')
	{
		$_REQUEST['ASSIGNED_BY_ID'] = [];
		$_GET['ASSIGNED_BY_ID'] = [];
	}
}

if (!$arResult['IS_EXTERNAL_FILTER'])
{
	$arFilter += $filterOptions->getFilter($arResult['FILTER']);
}

if (isset($arFilter['CLOSEDATE_datesel']) && $arFilter['CLOSEDATE_datesel'] === 'days' && isset($arFilter['CLOSEDATE_from']))
{
	//Issue #58007 - limit max CLOSEDATE
	$arFilter['CLOSEDATE_to'] = ConvertTimeStamp(strtotime(date("Y-m-d", time())));
}

$CCrmUserType->PrepareListFilterValues($arResult['FILTER'], $arFilter, $arResult['GRID_ID']);
$USER_FIELD_MANAGER->AdminListAddFilter(CCrmDeal::$sUFEntityID, $arFilter);

if (!$bInternal)
{
	$contactDataProvider->prepareFilter($arResult['FILTER'], $arFilter);
	$companyDataProvider->prepareFilter($arResult['FILTER'], $arFilter);
}
//region Apply Search Restrictions
$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
if (!$searchRestriction->isExceeded(CCrmOwnerType::Deal))
{
	$searchRestriction->notifyIfLimitAlmostExceed(CCrmOwnerType::Deal);

	Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Deal, $arFilter);
}
else
{
	$arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
		array('ENTITY_TYPE_ID' => CCrmOwnerType::Deal)
	);
}
//endregion

$arFilter['=IS_RECURRING'] = ($arParams['IS_RECURRING'] === 'Y') ? "Y" : 'N';

Crm\Filter\FieldsTransform\UserBasedField::applyTransformWrapper($arFilter);

//region Activity Counter Filter
CCrmEntityHelper::applySubQueryBasedFiltersWrapper(
	\CCrmOwnerType::Deal,
	$arResult['GRID_ID'],
	Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST),
	$arFilter,
	$entityFilter ?? null
);
//endregion

$arFilter = Crm\Automation\Debugger\DebuggerFilter::prepareFilter($arFilter, \CCrmOwnerType::Deal);

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter, [], '=%', false);

$arImmutableFilters = array(
	'FM', 'ID',
	'ASSIGNED_BY_ID', 'ASSIGNED_BY_ID_value',
	'CATEGORY_ID', 'IS_RETURN_CUSTOMER', 'IS_REPEATED_APPROACH', 'CURRENCY_ID',
	'CONTACT_ID', 'CONTACT_ID_value', 'ASSOCIATED_CONTACT_ID',
	'COMPANY_ID', 'COMPANY_ID_value',
	'STAGE_SEMANTIC_ID',
	'CREATED_BY_ID', 'CREATED_BY_ID_value',
	'MODIFY_BY_ID', 'MODIFY_BY_ID_value',
	'PRODUCT_ROW_PRODUCT_ID', 'PRODUCT_ROW_PRODUCT_ID_value',
	'WEBFORM_ID', 'TRACKING_SOURCE_ID', 'TRACKING_CHANNEL_CODE',
	'SEARCH_CONTENT',
	'PRODUCT_ID', 'TYPE_ID', 'SOURCE_ID', 'STAGE_ID', 'COMPANY_ID', 'CONTACT_ID',
	'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID', 'PAYMENT_STAGE', 'ORDER_SOURCE',
	'DELIVERY_STAGE', 'OBSERVER_IDS', 'COMPANY_TYPE'
);

foreach ($arFilter as $k => $v)
{
	// Check if first key character is aplpha and key is not immutable
	if (
		preg_match('/^[a-zA-Z]/', $k) !== 1
		|| in_array($k, $arImmutableFilters, true)
	)
	{
		continue;
	}

	if (Crm\Service\ParentFieldManager::isParentFieldName($k))
	{
		$arFilter[$k] = Crm\Service\ParentFieldManager::transformEncodedFilterValueIntoInteger($k, $v);
		continue;
	}

	$arMatch = [];
	if ($k === 'ORIGINATOR_ID')
	{
		// HACK: build filter by internal entities
		$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_from$/iu', $k, $arMatch))
	{
		if ($arMatch[1] === 'ACTIVE_TIME_PERIOD')
		{
			continue;
		}

		\Bitrix\Crm\UI\Filter\Range::prepareFrom($arFilter, $arMatch[1], $v);
	}
	elseif (preg_match('/(.*)_to$/iu', $k, $arMatch))
	{
		if ($arMatch[1] === 'ACTIVE_TIME_PERIOD')
		{
			continue;
		}

		if ($v != '' && ($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/u', $v))
		{
			$v = CCrmDateTimeHelper::SetMaxDayTime($v);
		}
		\Bitrix\Crm\UI\Filter\Range::prepareTo($arFilter, $arMatch[1], $v);
	}
	elseif (in_array($k, $arResult['FILTER2LOGIC']) && $v !== false)
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if ($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && mb_strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1  && $v !== false)
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

$arFilter = Crm\Deal\OrderFilter::prepareFilter($arFilter);

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

//region POST & GET actions processing
\CCrmViewHelper::processGridRequest(\CCrmOwnerType::Deal, $arResult['GRID_ID'], $arResult['PANEL']);

if ($actionData['ACTIVE'] && $actionData['METHOD'] == 'GET')
{
	if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
	{
		$ID = (int)$actionData['ID'];
		$categoryID = CCrmDeal::GetCategoryID($ID);
		$entityAttrs = CCrmDeal::GetPermissionAttributes(array($ID), $categoryID);
		if (CCrmDeal::CheckDeletePermission($ID, $userPermissions, -1, array('ENTITY_ATTRS' => $entityAttrs)))
		{
			$DB->StartTransaction();

			if ($CCrmBizProc->Delete($ID, $entityAttrs, array('DealCategoryId' => $categoryID))
				&& $CCrmDeal->Delete($ID, array('PROCESS_BIZPROC' => false)))
			{
				$DB->Commit();
			}
			else
			{
				$DB->Rollback();
			}
		}
	}

	if ($actionData['NAME'] == 'exclude' && isset($actionData['ID']))
	{
		$ID = (int)$actionData['ID'];
		if ($ID > 0 && \Bitrix\Crm\Exclusion\Manager::checkCreatePermission())
		{
			\Bitrix\Crm\Exclusion\Manager::excludeEntity(
				CCrmOwnerType::Deal,
				$ID,
				true,
				array('PERMISSIONS' => $userPermissions)
			);
		}
	}

	if (!$actionData['AJAX_CALL'])
	{
		if ($bInternal)
		{
			LocalRedirect('?'.$arParams['FORM_ID'].'_active_tab=tab_deal');
		}
		elseif($arResult['CATEGORY_ID'] >= 0)
		{
			LocalRedirect(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_DEAL_CATEGORY'],
					array('category_id' => $arResult['CATEGORY_ID'])
				)
			);
		}
		else
		{
			LocalRedirect($arParams['PATH_TO_CURRENT_LIST']);
		}
	}
}
//endregion POST & GET actions processing

$_arSort = $gridOptions->GetSorting(array(
	'sort' => array('date_create' => 'desc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

// Remove column for deleted UF
$arSelect = $gridOptions->GetVisibleColumns();

if (
	$CCrmUserType->NormalizeFields($arSelect)
	|| $contactDataProvider->removeUnavailableUserFields($arSelect)
	|| $companyDataProvider->removeUnavailableUserFields($arSelect)
)
{
	$gridOptions->SetVisibleColumns($arSelect);
}

$arResult['IS_BIZPROC_AVAILABLE'] = $isBizProcInstalled;
$arResult['ENABLE_BIZPROC'] = $isBizProcInstalled
	&& (!isset($arParams['ENABLE_BIZPROC']) || mb_strtoupper($arParams['ENABLE_BIZPROC']) === 'Y');

$arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');

if ($arResult['ENABLE_TASK'])
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
			'TITLE' => urlencode(Loc::getMessage('CRM_TASK_TITLE_PREFIX')),
			'TAGS' => urlencode(Loc::getMessage('CRM_TASK_TAG')),
			'back_url' => urlencode($arParams['PATH_TO_DEAL_LIST'])
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
		if (isset($arHeader['default']) && $arHeader['default'])
		{
			$arSelect[] = $arHeader['id'];
		}
	}

	//Disable bizproc fields processing
	$arResult['ENABLE_BIZPROC'] = false;
}
else
{
	if ($arResult['ENABLE_BIZPROC'])
	{
		//Check if bizproc fields selected
		$hasBizprocFields = false;
		foreach($arSelect as $fieldName)
		{
			if (strncmp($fieldName, 'BIZPROC_', 8) === 0)
			{
				$hasBizprocFields = true;
				break;
			}
		}
		$arResult['ENABLE_BIZPROC'] = $hasBizprocFields;
	}
}

$arSelectedHeaders = $arSelect;

if (!in_array('TITLE', $arSelect, true))
{
	//Is required for activities management
	$arSelect[] = 'TITLE';
}

if (in_array('CREATED_BY', $arSelect, true))
{
	$addictFields = array(
		'CREATED_BY_LOGIN', 'CREATED_BY_NAME', 'CREATED_BY_LAST_NAME', 'CREATED_BY_SECOND_NAME'
	);
	$arSelect = array_merge($arSelect,$addictFields);
	unset($addictFields);
}

if (in_array('MODIFY_BY', $arSelect, true))
{
	$addictFields = array(
		'MODIFY_BY_LOGIN', 'MODIFY_BY_NAME', 'MODIFY_BY_LAST_NAME', 'MODIFY_BY_SECOND_NAME'
	);
	$arSelect = array_merge($arSelect,$addictFields);
	unset($addictFields);
}

if (in_array('DEAL_SUMMARY', $arSelect, true))
{
	//$arSelect[] = 'TITLE';
	$arSelect[] = 'TYPE_ID';
}

if (in_array('SUM', $arSelect, true))
{
	$arSelect[] = 'OPPORTUNITY';
	$arSelect[] = 'CURRENCY_ID';
}

$addictFields = [];

if (in_array('DEAL_CLIENT', $arSelect, true))
{
	$addictFields = array(
		'CONTACT_ID', 'COMPANY_ID', 'COMPANY_TITLE', 'CONTACT_HONORIFIC',
		'CONTACT_NAME', 'CONTACT_SECOND_NAME','CONTACT_LAST_NAME'
	);
}
else
{
	if (in_array('CONTACT_ID', $arSelect, true))
	{
		$addictFields = array(
			'CONTACT_ID', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME','CONTACT_LAST_NAME'
		);
	}
	if (in_array('COMPANY_ID', $arSelect, true))
	{
		$arSelect[] = 'COMPANY_TITLE';
	}
}

$arSelect = array_merge($arSelect, $addictFields);
unset($addictFields);

// Always need to remove the menu items
if (!in_array('STAGE_ID', $arSelect))
	$arSelect[] = 'STAGE_ID';

if (!in_array('CATEGORY_ID', $arSelect))
	$arSelect[] = 'CATEGORY_ID';

if (!in_array('STAGE_SEMANTIC_ID', $arSelect))
	$arSelect[] = 'STAGE_SEMANTIC_ID';

// For bizproc
if (!in_array('ASSIGNED_BY', $arSelect))
	$arSelect[] = 'ASSIGNED_BY';

if (!in_array('ASSIGNED_BY_ID', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_ID';

// For preparing user html
if (!in_array('ASSIGNED_BY_LOGIN', $arSelect))
	$arSelect[] =  'ASSIGNED_BY_LOGIN';

if (!in_array('ASSIGNED_BY_NAME', $arSelect))
	$arSelect[] =  'ASSIGNED_BY_NAME';

if (!in_array('ASSIGNED_BY_LAST_NAME', $arSelect))
	$arSelect[] =  'ASSIGNED_BY_LAST_NAME';

if (!in_array('ASSIGNED_BY_SECOND_NAME', $arSelect))
	$arSelect[] =  'ASSIGNED_BY_SECOND_NAME';

// For calendar view
if (isset($arParams['CALENDAR_MODE_LIST']))
{
	if (!in_array('CLOSEDATE', $arSelect))
	{
		$arSelect[] = 'CLOSEDATE';
	}
	if (!in_array('DATE_CREATE', $arSelect))
	{
		$arSelect[] = 'DATE_CREATE';
	}
}

// ID must present in select
if (!in_array('ID', $arSelect))
{
	$arSelect[] = 'ID';
}

// IS_RETURN_CUSTOMER must present in select
if (!in_array('IS_RETURN_CUSTOMER', $arSelect))
{
	$arSelect[] = 'IS_RETURN_CUSTOMER';
}

// IS_REPEATED_APPROACH must present in select
if (!in_array('IS_REPEATED_APPROACH', $arSelect))
{
	$arSelect[] = 'IS_REPEATED_APPROACH';
}
if (in_array('ACTIVITY_ID', $arSelect, true)) // Remove ACTIVITY_ID from $arSelect
{
	$arResult['NEED_ADD_ACTIVITY_BLOCK'] = true;
	unset($arSelect[array_search('ACTIVITY_ID', $arSelect)]);
	$arSelect = array_values($arSelect);
}

$userDataProvider->prepareSelect($arSelect);
$observersDataProvider->prepareSelect($arSelect);
if (!$bInternal)
{
	$contactDataProvider->prepareSelect($arSelect);
	$companyDataProvider->prepareSelect($arSelect);
}

if ($isInExportMode)
{
	$productHeaderIndex = array_search('PRODUCT_ID', $arSelectedHeaders, true);
	if ($productHeaderIndex <= 0 && $isStExportProductsFields)
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
			'DEAL_SUMMARY' => array(
				'TITLE',
				'TYPE_ID'
			),
			'DEAL_CLIENT' => array(
				'CONTACT_ID',
				'COMPANY_ID'
			),
			'SUM' => array(
				'OPPORTUNITY',
				'CURRENCY_ID'
			),
			'ACTIVITY_ID' => []
		)
	);

	if (!in_array('ID', $arSelectedHeaders))
	{
		$arSelectedHeaders[] = 'ID';
	}

	/*
	if (!in_array('CATEGORY_ID', $arSelectedHeaders))
	{
		$arSelectedHeaders[] = 'CATEGORY_ID';
	}
	*/

	$contactDataProvider->prepareExportHeaders($arSelectedHeaders);
	$companyDataProvider->prepareExportHeaders($arSelectedHeaders);

	$arResult['SELECTED_HEADERS'] = $arSelectedHeaders;
}

$nTopCount = false;
if ($isInGadgetMode)
{
	$arSelect = array(
		'DATE_CREATE', 'TITLE', 'STAGE_ID', 'TYPE_ID',
		'OPPORTUNITY', 'CURRENCY_ID', 'COMMENTS',
		'CONTACT_ID',  'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME',
		'CONTACT_LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE'
	);
	$nTopCount = $arParams['DEAL_COUNT'];
}

if ($isInCalendarMode)
{
	$arSelect = [
		'ID', 'TITLE', 'DATE_CREATE', 'CLOSEDATE'
	];
	foreach ($arParams['CALENDAR_MODE_LIST'] as $calendarModeItem)
	{
		if ($calendarModeItem['selected'])
		{
			$calendarModeItemUser = \Bitrix\Crm\Integration\Calendar::parseUserfieldKey($calendarModeItem['id']);
			$calendarModeItemUserFieldId = $calendarModeItemUser[0];
			$calendarModeItemUserFieldType = $calendarModeItemUser[1] ?? '';
			$calendarModeItemUserFieldName = $calendarModeItemUser[2] ?? '';

			if ($calendarModeItemUserFieldName && !in_array($calendarModeItemUserFieldName, $arSelect, true))
			{
				$arSelect[] = $calendarModeItemUserFieldName;
			}
		}
	}
	$nTopCount = $arParams['DEAL_COUNT'];
}

if ($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
{
	$arFilter['PERMISSION'] = 'EXPORT';
}

if (!empty($arParams['ADDITIONAL_FILTER']) && is_array($arParams['ADDITIONAL_FILTER']))
{
	$arFilter = array_merge($arFilter, $arParams['ADDITIONAL_FILTER']);
}

// HACK: Make custom sort for ASSIGNED_BY field
$arSort = $arResult['SORT'];

if (isset($arSort['assigned_by']))
{
	if (\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isUserNameSortingEnabled())
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

$arOptions = $arExportOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if (in_array('ACTIVITY_ID', $arSelect, true))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
	$arExportOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}
if (isset($arSort['stage_sort']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STAGE_SORT';
	$arExportOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STAGE_SORT';

	if ($arResult['CATEGORY_ID'] > 0)
	{
		$arOptions['FIELD_OPTIONS']['CATEGORY_ID'] = $arResult['CATEGORY_ID'];
		$arExportOptions['FIELD_OPTIONS']['CATEGORY_ID'] = $arResult['CATEGORY_ID'];
	}
}

if (isset($arSort['contact_full_name']))
{
	$arSort['contact_last_name'] = $arSort['contact_full_name'];
	$arSort['contact_name'] = $arSort['contact_full_name'];
	unset($arSort['contact_full_name']);
}

if (isset($arSort['deal_client']))
{
	$arSort['contact_last_name'] = $arSort['deal_client'];
	$arSort['contact_name'] = $arSort['deal_client'];
	$arSort['company_title'] = $arSort['deal_client'];
	unset($arSort['deal_client']);
}

if (isset($arSort['date_create']))
{
	$arSort['id'] = $arSort['date_create'];
	unset($arSort['date_create']);
}

if (isset($arParams['IS_RECURRING']) && $arParams['IS_RECURRING'] === 'Y')
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'RECURRING';
	$recurringSortedFields = array('active', 'counter_repeat', 'next_execution', 'start_date', 'limit_date', 'limit_repeat');
	foreach ($recurringSortedFields as $fieldName)
	{
		if (isset($arSort[$fieldName]))
		{
			$arSort['crm_deal_recurring_'.$fieldName] = $arSort[$fieldName];
			unset($arSort[$fieldName]);
		}
	}
}

if (!empty($arSort) && !isset($arSort['id']))
{
	$arSort['id'] = reset($arSort);
}

if (isset($arParams['IS_EXTERNAL_CONTEXT']))
{
	$arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

//FIELD_OPTIONS
$arSelect = array_unique($arSelect, SORT_STRING);

$arResult['DEAL'] = [];
$arResult['DEAL_ID'] = [];
$arResult['CATEGORIES'] = [];
$arResult['DEAL_UF'] = [];

//region Navigation data initialization
$pageNum = 0;
if ($isInExportMode && $isStExport)
{
	$pageSize = !empty($arParams['STEXPORT_PAGE_SIZE']) ? $arParams['STEXPORT_PAGE_SIZE'] : $arParams['DEAL_COUNT'];
}
else
{
	$pageSize = !$isInExportMode
		? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['DEAL_COUNT']) : 0;
}
// For calendar mode we should clear nav params, to be able to show entries on the grid
if (isset($arParams['CALENDAR_MODE_LIST']))
{
	$pageSize = $arParams['DEAL_COUNT'];
}

$enableNextPage = false;
if (isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
{
	$pageNum = 1;
}
elseif($pageSize > 0 && (isset($arParams['PAGE_NUMBER']) || isset($_REQUEST['page'])))
{
	$pageNum = (int)(isset($arParams['PAGE_NUMBER']) ? $arParams['PAGE_NUMBER'] : $_REQUEST['page']);
	if ($pageNum < 0)
	{
		//Backward mode
		$offset = -($pageNum + 1);
		$total = CCrmDeal::GetListEx([], $arFilter, array());
		$pageNum = (int)(ceil($total / $pageSize)) - $offset;
		if ($pageNum <= 0)
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
			$_SESSION['CRM_PAGINATION_DATA'] = [];
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
	$total = \CCrmDeal::GetListEx([], $arFilter, array());
	if (is_numeric($total))
	{
		$arResult['STEXPORT_TOTAL_ITEMS'] = (int)$total;
	}
}

$lastExportedId = -1;
$limit = $pageSize + 1;

/**
 * During step export, sorting will only be done by ID
 * and optimized selection with pagination by ID > LAST_ID instead of offset
 */
if ($isInExportMode && $isStExport)
{
	$totalExportItems = $arParams['STEXPORT_TOTAL_ITEMS'] ?: $total;
	$arSort = ['ID' => 'DESC'];

	// Skip the first page because the last ID isn't present yet.
	if ($pageNum > 1)
	{
		$limit = $pageSize;
		$navListOptions['QUERY_OPTIONS'] = ['LIMIT' => $limit];

		$dbResultOnlyIds = CCrmDeal::GetListEx(
			$arSort,
			array_merge(
				$arFilter,
				['<ID' => $arParams['STEXPORT_LAST_EXPORTED_ID'] ?? -1]
			),
			false,
			false,
			['ID'],
			$navListOptions
		);

		$entityIds = [];
		while($arDealRow = $dbResultOnlyIds->GetNext())
		{
			$entityIds[] = (int) $arDealRow['ID'];
		}

		$arFilter = ['@ID' => $entityIds, 'CHECK_PERMISSIONS' => 'N'];
	}

	if (!empty($entityIds) || $pageNum === 1)
	{
		$navListOptions['QUERY_OPTIONS'] = $pageNum === 1 ? ['LIMIT' => $limit] : null;

		$dbResult = CCrmDeal::GetListEx(
			$arSort,
			$arFilter,
			false,
			false,
			$arSelect,
			$navListOptions
		);

		$qty = 0;
		while($arDeal = $dbResult->GetNext())
		{
			$arResult['DEAL'][$arDeal['ID']] = $arDeal;
			$arResult['DEAL_ID'][$arDeal['ID']] = $arDeal['ID'];
			$arResult['DEAL_UF'][$arDeal['ID']] = [];

			$categoryID = isset($arDeal['CATEGORY_ID']) ? (int)$arDeal['CATEGORY_ID'] : 0;
			if (!isset($arResult['CATEGORIES'][$categoryID]))
			{
				$arResult['CATEGORIES'][$categoryID] = [];
			}
			$arResult['CATEGORIES'][$categoryID][] = $arDeal['ID'];
		}

		if (isset($arResult['DEAL']) && count($arResult['DEAL']) > 0)
		{
			$lastExportedId = end($arResult['DEAL'])['ID'];
		}
		else
		{
			$lastExportedId = -1;
		}

	}
	$enableNextPage = $pageNum * $pageSize <= $totalExportItems;
	unset($entityIds);
}
elseif(!isset($arSort['nearest_activity']))
{
	$parameters = [
		'select' => $arSelect,
		'filter' => $arFilter,
		'order' => $arSort,
		'options' => [
			'FIELD_OPTIONS' => $arOptions['FIELD_OPTIONS'] ?? [],
			'IS_EXTERNAL_CONTEXT' => $arOptions['IS_EXTERNAL_CONTEXT'] ?? false,
		],
	];

	if ($isInGadgetMode && isset($arNavParams['nTopCount']))
	{
		$parameters['limit'] = $arNavParams['nTopCount'];
		$parameters['offset'] = null;
	}
	elseif ($isInExportMode && !$isStExport)
	{
		$parameters['limit'] = null;
		$parameters['offset'] = null;
	}
	else
	{
		$parameters['limit'] = $limit;
		$parameters['offset'] = $pageSize * ($pageNum - 1);
	}

	$listEntity = \Bitrix\Crm\ListEntity\Entity::getInstance(\CCrmOwnerType::DealName);
	$dbResult = $listEntity->getItems($parameters);

	$qty = 0;
	while($arDeal = $dbResult->GetNext())
	{
		if ($pageSize > 0 && ++$qty > $pageSize)
		{
			$enableNextPage = true;
			break;
		}

		$arResult['DEAL'][$arDeal['ID']] = $arDeal;
		$arResult['DEAL_ID'][$arDeal['ID']] = $arDeal['ID'];
		$arResult['DEAL_UF'][$arDeal['ID']] = [];

		$categoryID = isset($arDeal['CATEGORY_ID']) ? (int)$arDeal['CATEGORY_ID'] : 0;
		if (!isset($arResult['CATEGORIES'][$categoryID]))
		{
			$arResult['CATEGORIES'][$categoryID] = [];
		}
		$arResult['CATEGORIES'][$categoryID][] = $arDeal['ID'];
	}

	//region Navigation data storing
	$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);

	$arResult['DB_FILTER'] = $arFilter;

	if (!isset($_SESSION['CRM_GRID_DATA']))
	{
		$_SESSION['CRM_GRID_DATA'] = [];
	}
	$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
	//endregion
}
else
{
	$navListOptions = ($isInExportMode && !$isStExport)
		? $arExportOptions
		: array_merge(
			$arOptions,
			array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
		);

	$navDbResult = CCrmActivity::GetEntityList(
		CCrmOwnerType::Deal,
		$userID,
		$arSort['nearest_activity'],
		$arFilter,
		false,
		$navListOptions
	);

	$qty = 0;
	while($arDeal = $navDbResult->Fetch())
	{
		if ($pageSize > 0 && ++$qty > $pageSize)
		{
			$enableNextPage = true;
			break;
		}

		$arResult['DEAL'][$arDeal['ID']] = $arDeal;
		$arResult['DEAL_ID'][$arDeal['ID']] = $arDeal['ID'];
		$arResult['DEAL_UF'][$arDeal['ID']] = [];
	}

	//region Navigation data storing
	$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
	$arResult['DB_FILTER'] = $arFilter;
	if (!isset($_SESSION['CRM_GRID_DATA']))
	{
		$_SESSION['CRM_GRID_DATA'] = [];
	}
	$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
	//endregion

	$entityIDs = array_keys($arResult['DEAL']);
	if (!empty($entityIDs))
	{
		//Permissions are already checked.
		$dbResult = CCrmDeal::GetListEx(
			$arSort,
			array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			$arSelect,
			$arOptions
		);
		while($arDeal = $dbResult->GetNext())
		{
			$arResult['DEAL'][$arDeal['ID']] = $arDeal;

			$categoryID = isset($arDeal['CATEGORY_ID']) ? (int)$arDeal['CATEGORY_ID'] : 0;
			if (!isset($arResult['CATEGORIES'][$categoryID]))
			{
				$arResult['CATEGORIES'][$categoryID] = [];
			}
			$arResult['CATEGORIES'][$categoryID][] = $arDeal['ID'];
		}
	}
}

$arResult['STEXPORT_IS_FIRST_PAGE'] = $pageNum === 1 ? 'Y' : 'N';
$arResult['STEXPORT_IS_LAST_PAGE'] = $enableNextPage ? 'N' : 'Y';

$arResult['PAGINATION']['URL'] = $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'));
$enableExportEvent = $isInExportMode && HistorySettings::getCurrent()->isExportEventEnabled();
$now = time() + CTimeZone::GetOffset();

// check adding to exclusion list
$arResult['CAN_EXCLUDE'] = \Bitrix\Crm\Exclusion\Access::current()->canWrite();
$excludeApplicableList = array_keys($arResult['DEAL']);
if ($arResult['CAN_EXCLUDE'])
{
	\Bitrix\Crm\Exclusion\Applicability::filterEntities(\CCrmOwnerType::Deal, $excludeApplicableList);
	$arResult['CAN_EXCLUDE'] = !empty($excludeApplicableList);
}

if (!$bInternal)
{
	$contactDataProvider->appendResult($arResult['DEAL']);
	$companyDataProvider->appendResult($arResult['DEAL']);
}
$userDataProvider->appendResult($arResult['DEAL']);
$observersDataProvider->appendResult($arResult['DEAL']);

$parentFieldValues = Crm\Service\Container::getInstance()->getParentFieldManager()->loadParentElementsByChildren(
	\CCrmOwnerType::Deal,
	$arResult['DEAL']
);

$debugItemIds = \CCrmBizProcHelper::getActiveDebugEntityIds(\CCrmOwnerType::Deal);

foreach($arResult['DEAL'] as &$arDeal)
{
	$entityID = $arDeal['ID'];
	if ($enableExportEvent)
	{
		CCrmEvent::RegisterExportEvent(CCrmOwnerType::Deal, $entityID, $userID);
	}

	$arDeal['CAN_EXCLUDE'] = in_array($arDeal['ID'], $excludeApplicableList);

	$arDeal['CLOSEDATE'] = !empty($arDeal['CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arDeal['CLOSEDATE']), 'SHORT', SITE_ID)) : '';
	$arDeal['BEGINDATE'] = !empty($arDeal['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arDeal['BEGINDATE']), 'SHORT', SITE_ID)) : '';
	$arDeal['EVENT_DATE'] = !empty($arDeal['EVENT_DATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arDeal['EVENT_DATE']), 'SHORT', SITE_ID)) : '';
	$arDeal['~CLOSEDATE'] = $arDeal['CLOSEDATE'];
	$arDeal['~BEGINDATE'] = $arDeal['BEGINDATE'];
	$arDeal['~EVENT_DATE'] = $arDeal['EVENT_DATE'];

	$currencyID = $arDeal['~CURRENCY_ID'] ?? CCrmCurrency::GetBaseCurrencyID();
	$arDeal['~CURRENCY_ID'] = $currencyID;
	$arDeal['CURRENCY_ID'] = htmlspecialcharsbx($currencyID);
	$arDeal['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($arDeal['~OPPORTUNITY'] ?? 0.0, $arDeal['~CURRENCY_ID']);

	$arDeal['PATH_TO_DEAL_DETAILS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_DEAL_DETAILS'] ?? '',
		array('deal_id' => $entityID)
	);

	if ($arResult['ENABLE_SLIDER'])
	{
		$arDeal['PATH_TO_DEAL_SHOW'] = $arDeal['PATH_TO_DEAL_DETAILS'];
		$arDeal['PATH_TO_DEAL_EDIT'] = CCrmUrlUtil::AddUrlParams(
			$arDeal['PATH_TO_DEAL_DETAILS'] ?? '',
			array('init_mode' => 'edit')
		);
	}
	else
	{
		$arDeal['PATH_TO_DEAL_SHOW'] = CComponentEngine::makePathFromTemplate(
			($arParams['IS_RECURRING']  !== 'Y') ? $arParams['PATH_TO_DEAL_SHOW'] : $arParams['PATH_TO_DEAL_RECUR_SHOW'],
			array('deal_id' => $entityID)
		);

		$arDeal['PATH_TO_DEAL_EDIT'] = CComponentEngine::makePathFromTemplate(
			($arParams['IS_RECURRING']  !== 'Y') ? $arParams['PATH_TO_DEAL_EDIT'] : $arParams['PATH_TO_DEAL_RECUR_EDIT'],
			array('deal_id' => $entityID)
		);
	}

	$arDeal['PATH_TO_DEAL_COPY'] =
		\Bitrix\Crm\Integration\Analytics\Builder\Entity\CopyOpenEvent::createDefault(\CCrmOwnerType::Deal)
			->setSection(
				!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
					? $arParams['ANALYTICS']['c_section']
					: null
			)
			->setSubSection(
				!empty($arParams['ANALYTICS']['c_sub_section']) && is_string($arParams['ANALYTICS']['c_sub_section'])
					? $arParams['ANALYTICS']['c_sub_section']
					: null
			)
			->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU)
			->buildUri($arDeal['PATH_TO_DEAL_EDIT'] ?? '')
			->addParams([
				'copy' => 1,
			])
			->getUri()
	;

	if ($arResult['CATEGORY_ID'] >= 0)
	{
		$arDeal['PATH_TO_DEAL_DELETE'] = CHTTP::urlAddParams(
			CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_DEAL_CATEGORY'] ?? '',
				array('category_id' => $arResult['CATEGORY_ID'])
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => $arResult['SESSION_ID'])
		);
	}
	else
	{
		$arDeal['PATH_TO_DEAL_DELETE'] =  CHTTP::urlAddParams(
			$bInternal ? $APPLICATION->GetCurPage() : ($arParams['PATH_TO_CURRENT_LIST'] ?? ''),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => $arResult['SESSION_ID'])
		);
	}

	$contactID = (int)($arDeal['~CONTACT_ID'] ?? 0);
	$arDeal['PATH_TO_CONTACT_SHOW'] = $contactID <= 0
		? ''
		: CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CONTACT_SHOW'] ?? '',
			['contact_id' => $contactID]
		);

	$arDeal['~CONTACT_FORMATTED_NAME'] = $contactID <= 0 ? ''
		: CCrmContact::PrepareFormattedName(
			array(
				'HONORIFIC' => $arDeal['~CONTACT_HONORIFIC'] ?? '',
				'NAME' => $arDeal['~CONTACT_NAME'] ?? '',
				'LAST_NAME' => $arDeal['~CONTACT_LAST_NAME'] ?? '',
				'SECOND_NAME' => $arDeal['~CONTACT_SECOND_NAME'] ?? ''
			)
		);

	$arDeal['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arDeal['~CONTACT_FORMATTED_NAME'] ?? '');

	$arDeal['~CONTACT_FULL_NAME'] = $contactID <= 0 ? ''
		: CCrmContact::GetFullName(
			array(
				'HONORIFIC' => $arDeal['~CONTACT_HONORIFIC'] ?? '',
				'NAME' => $arDeal['~CONTACT_NAME'] ?? '',
				'LAST_NAME' => $arDeal['~CONTACT_LAST_NAME'] ?? '',
				'SECOND_NAME' => $arDeal['~CONTACT_SECOND_NAME'] ?? ''
			)
		);
	$arDeal['CONTACT_FULL_NAME'] = htmlspecialcharsbx($arDeal['~CONTACT_FULL_NAME'] ?? '');

	$companyID = (int)($arDeal['~COMPANY_ID'] ?? 0);
	$arDeal['PATH_TO_COMPANY_SHOW'] = $companyID <= 0
		? ''
		: CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_COMPANY_SHOW'] ?? '',
			array('company_id' => $companyID)
		);

	if ($arResult['CAN_EXCLUDE'])
	{
		$arDeal['PATH_TO_DEAL_EXCLUDE'] = CHTTP::urlAddParams(
			$curPage,
			array(
				'action_'.$arResult['GRID_ID'] => 'exclude',
				'ID' => $entityID,
				'sessid' => $arResult['SESSION_ID']
			)
		);
	}

	$arDeal['PATH_TO_USER_PROFILE'] = $arDeal['ASSIGNED_BY_SHOW_URL'] ?? '';

	$arDeal['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_BP'] ?? '',
		array('user_id' => $userID)
	);

	if (!empty($arDeal['CREATED_BY_ID']))
	{
		$arDeal['CREATED_BY'] = $arDeal['~CREATED_BY'] = $arDeal['CREATED_BY_ID'];
	}

	$arDeal['PATH_TO_USER_CREATOR'] = $arDeal['CREATED_BY_SHOW_URL'] ?? '';

	if (!empty($arDeal['MODIFY_BY_ID']))
	{
		$arDeal['MODIFY_BY'] = $arDeal['~MODIFY_BY'] = $arDeal['MODIFY_BY_ID'];
	}

	$arDeal['PATH_TO_USER_MODIFIER'] = $arDeal['MODIFY_BY_SHOW_URL'] ?? '';

	$typeID = $arDeal['TYPE_ID'] ?? '';
	$arDeal['DEAL_TYPE_NAME'] = isset($arResult['TYPE_LIST'][$typeID]) ? $arResult['TYPE_LIST'][$typeID] : $typeID;

	$stageID = $arDeal['STAGE_ID'] ?? '';
	$arDeal['STAGE_ID'] = $stageID;
	$categoryID = $arDeal['CATEGORY_ID'] = (int)($arDeal['CATEGORY_ID'] ?? 0);
	$arDeal['DEAL_STAGE_NAME'] = CCrmDeal::GetStageName($stageID, $categoryID);
	$arDeal['~DEAL_CATEGORY_NAME'] = DealCategory::getName($categoryID);
	$arDeal['DEAL_CATEGORY_NAME'] = htmlspecialcharsbx($arDeal['~DEAL_CATEGORY_NAME']);

	//region Client info
	if ($contactID > 0)
	{
		$arDeal['CONTACT_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_ID' => $contactID
		);

		$hasAccessToContact =
			$arDeal['CONTACT_IS_ACCESSIBLE']
			?? CCrmContact::CheckReadPermission($contactID, $userPermissions)
		;

		if (!$hasAccessToContact)
		{
			$arDeal['CONTACT_INFO']['IS_HIDDEN'] = true;
		}
		else
		{
			$arDeal['CONTACT_INFO'] =
				array_merge(
					$arDeal['CONTACT_INFO'],
					array(
						'TITLE' => $arDeal['~CONTACT_FORMATTED_NAME'] ?? ('['.$contactID.']'),
						'PREFIX' => "DEAL_{$arDeal['~ID']}",
						'DESCRIPTION' => $arDeal['~COMPANY_TITLE'] ?? ''
					)
				);
		}
	}

	if ($companyID > 0)
	{
		$arDeal['COMPANY_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_ID' => $companyID
		);

		$hasAccessToCompany =
			$arDeal['COMPANY_IS_ACCESSIBLE']
			?? CCrmCompany::CheckReadPermission($companyID, $userPermissions)
		;

		if (!$hasAccessToCompany)
		{
			$arDeal['COMPANY_INFO']['IS_HIDDEN'] = true;
		}
		else
		{
			$arDeal['COMPANY_INFO'] =
				array_merge(
					$arDeal['COMPANY_INFO'],
					array(
						'TITLE' => $arDeal['~COMPANY_TITLE'] ?? ('['.$companyID.']'),
						'PREFIX' => "DEAL_{$arDeal['~ID']}"
					)
				);
		}
	}

	if (isset($arDeal['CONTACT_INFO']))
	{
		$arDeal['CLIENT_INFO'] = $arDeal['CONTACT_INFO'];
	}
	elseif(isset($arDeal['COMPANY_INFO']))
	{
		$arDeal['CLIENT_INFO'] = $arDeal['COMPANY_INFO'];
	}
	//endregion

	$arDeal['DEAL_DESCRIPTION'] = '';
	if (isset($arDeal['TYPE_ID']) && isset($arResult['TYPE_LIST'][$arDeal['TYPE_ID']]))
	{
		$arDeal['DEAL_DESCRIPTION'] = $arResult['TYPE_LIST'][$arDeal['TYPE_ID']];
	}

	//region Summary & Legend
	$customerDescription = '';
	if (isset($arDeal['IS_RETURN_CUSTOMER']) && $arDeal['IS_RETURN_CUSTOMER'] === 'Y')
	{
		$customerDescription = Loc::getMessage('CRM_COLUMN_IS_RETURN_CUSTOMER');
	}
	elseif(isset($arDeal['IS_REPEATED_APPROACH']) && $arDeal['IS_REPEATED_APPROACH'] === 'Y')
	{
		$customerDescription = Loc::getMessage('CRM_COLUMN_IS_REPEATED_APPROACH');
	}

	if ($customerDescription !== '')
	{
		if ($arDeal['DEAL_DESCRIPTION'] !== '')
		{
			$arDeal['DEAL_DESCRIPTION'] .= " ({$customerDescription})";
		}
		else
		{
			$arDeal['DEAL_DESCRIPTION'] = $customerDescription;
		}
	}

	$arDeal['DEAL_LEGEND'] = isset($arDeal['SOURCE_ID']) && isset($arResult['SOURCE_LIST'][$arDeal['SOURCE_ID']])
		? $arResult['SOURCE_LIST'][$arDeal['SOURCE_ID']] : '';
	//endregion

	$originatorID = $arDeal['~ORIGINATOR_ID'] ?? '';
	if ($originatorID !== '')
	{
		$arDeal['~ORIGINATOR_NAME'] = isset($arResult['EXTERNAL_SALES'][$originatorID])
			? $arResult['EXTERNAL_SALES'][$originatorID]
			: '';

		$arDeal['ORIGINATOR_NAME'] = htmlspecialcharsbx($arDeal['~ORIGINATOR_NAME']);
	}

	if (!empty($arDeal['OBSERVERS']))
	{
		$arDeal['~OBSERVERS'] = $arDeal['OBSERVERS'];
		$arDeal['OBSERVERS'] = implode(
			"\n",
			array_column($arDeal['~OBSERVERS'], 'OBSERVER_USER_FORMATTED_NAME')
		);
	}

	if ($arResult['ENABLE_TASK'])
	{
		$arDeal['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $userID
				)
			),
			array(
				'UF_CRM_TASK' => "D_{$entityID}",
				'TITLE' => urlencode(Loc::getMessage('CRM_TASK_TITLE_PREFIX').' '),
				'TAGS' => urlencode(Loc::getMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_DEAL_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arDeal['PATH_TO_QUOTE_ADD'] =
			CHTTP::urlAddParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_QUOTE_EDIT'] ?? '',
					array('quote_id' => 0)
				),
				array('deal_id' => $entityID)
			);
		$arDeal['PATH_TO_INVOICE_ADD'] =
			CHTTP::urlAddParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_INVOICE_EDIT'] ?? '',
					array('invoice_id' => 0)
				),
				array('deal' => $entityID)
			);
	}

	if ($arResult['ENABLE_BIZPROC'])
	{
		$arDeal['BIZPROC_STATUS'] = '';
		$arDeal['BIZPROC_STATUS_HINT'] = '';

		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentDeal', 'DEAL'),
			array('crm', 'CCrmDocumentDeal', "DEAL_{$entityID}")
		);

		$arDeal['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_SHOW'] ?? '',
				array('deal_id' => $entityID)
			),
			array('CRM_DEAL_SHOW_V12_active_tab' => 'tab_bizproc')
		);

		$totalTaskQty = 0;
		$docStatesQty = count($arDocumentStates);
		if ($docStatesQty === 1)
		{
			$arDocState = $arDocumentStates[array_shift(array_keys($arDocumentStates))];

			$docTemplateID = $arDocState['TEMPLATE_ID'];
			$paramName = "BIZPROC_{$docTemplateID}";
			$docTtl = $arDocState['STATE_TITLE'] ?? '';
			$docName = $arDocState['STATE_NAME'] ?? '';
			$docTemplateName = $arDocState['TEMPLATE_NAME'] ?? '';

			if ($isInExportMode)
			{
				$arDeal[$paramName] = $docTtl;
			}
			else
			{
				$arDeal[$paramName] = '<a href="'.htmlspecialcharsbx($arDeal['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

				$docID = $arDocState['ID'];
				$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
				if ($taskQty > 0)
				{
					$totalTaskQty += $taskQty;
				}

				$arDeal['BIZPROC_STATUS'] = $taskQty > 0 ? 'attention' : 'inprogress';
				$arDeal['BIZPROC_STATUS_HINT'] =
					'<div class=\'bizproc-item-title\'>'.
					htmlspecialcharsbx($docTemplateName !== '' ? $docTemplateName : Loc::getMessage('CRM_BPLIST')).
					': <span class=\'bizproc-item-title bizproc-state-title\'><a href=\''.$arDeal['PATH_TO_BIZPROC_LIST'].'\'>'.
					htmlspecialcharsbx($docTtl !== '' ? $docTtl : $docName).'</a></span></div>';
			}
		}
		elseif($docStatesQty > 1)
		{
			foreach ($arDocumentStates as &$arDocState)
			{
				$docTemplateID = $arDocState['TEMPLATE_ID'];
				$paramName = "BIZPROC_{$docTemplateID}";
				$docTtl = $arDocState['STATE_TITLE'] ?? '';

				if ($isInExportMode)
				{
					$arDeal[$paramName] = $docTtl;
				}
				else
				{
					$arDeal[$paramName] = '<a href="'.htmlspecialcharsbx($arDeal['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

					$docID = $arDocState['ID'];
					//TODO: wait for bizproc bugs will be fixed and replace serial call of CCrmBizProcHelper::GetUserWorkflowTaskCount on single call
					$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
					if ($taskQty === 0)
					{
						continue;
					}

					if ($arDeal['BIZPROC_STATUS'] !== 'attention')
					{
						$arDeal['BIZPROC_STATUS'] = 'attention';
					}

					$totalTaskQty += $taskQty;
					if ($totalTaskQty > 5)
					{
						break;
					}
				}
			}
			unset($arDocState);

			if (!$isInExportMode)
			{
				$arDeal['BIZPROC_STATUS_HINT'] =
					'<span class=\'bizproc-item-title\'>'.Loc::getMessage('CRM_BP_R_P').': <a href=\''.$arDeal['PATH_TO_BIZPROC_LIST'].'\' title=\''.Loc::getMessage('CRM_BP_R_P_TITLE').'\'>'.$docStatesQty.'</a></span>'.
					($totalTaskQty === 0
						? ''
						: '<br /><span class=\'bizproc-item-title\'>'.Loc::getMessage('CRM_TASKS').': <a href=\''.$arDeal['PATH_TO_USER_BP'].'\' title=\''.Loc::getMessage('CRM_TASKS_TITLE').'\'>'.$totalTaskQty.($totalTaskQty > 5 ? '+' : '').'</a></span>');
			}
		}
	}

	if (in_array($arDeal['ID'], $debugItemIds))
	{
		$arDeal['TITLE_PREFIX'] = sprintf(
			'<span class="crm-debug-item-label">%s</span> ',
			Loc::getMessage('CRM_DEAL_LIST_ITEM_DEBUG_TITLE_MSGVER_1')
		);
	}

	if (!isset($arDeal['ASSIGNED_BY_ID']))
	{
		$dealAssignedBy = (int)($arDeal['~ASSIGNED_BY'] ?? 0);
		$arDeal['ASSIGNED_BY_ID'] = $dealAssignedBy;
		$arDeal['~ASSIGNED_BY_ID'] = $dealAssignedBy;
	}

	$arDeal['~ASSIGNED_BY'] = $arDeal['~ASSIGNED_BY_FORMATTED_NAME'] ?? '';
	$arDeal['ASSIGNED_BY'] = htmlspecialcharsbx($arDeal['~ASSIGNED_BY']);
	if (isset($arDeal['~TITLE']))
	{
		$arDeal['DEAL_SUMMARY'] = $arDeal['~TITLE'];
	}

	if (isset($parentFieldValues[$arDeal['ID']]))
	{
		foreach ($parentFieldValues[$arDeal['ID']] as $parentEntityTypeId => $parentEntity)
		{
			if ($isInExportMode)
			{
				$arDeal[$parentEntity['code']] = $parentEntity['title'];
			}
			else
			{
				$arDeal[$parentEntity['code']] = $parentEntity['value'];
			}
		}
	}

	$arResult['DEAL'][$entityID] = $arDeal;
}
unset($arDeal);

$arResult['DEAL'] = $CCrmUserType->normalizeBooleanValues($arResult['DEAL']);
/** @var $displayFields Crm\Service\Display\Field[] */
$displayFields = [];

foreach ($CCrmUserType->GetAbstractFields() as $userFieldId => $userFieldData)
{
	$displayFields[$userFieldId] = Crm\Service\Display\Field::createFromUserField($userFieldId, $userFieldData);
}
if (!$bInternal)
{
	$displayFields = array_merge(
		$displayFields,
		$contactDataProvider->getDisplayFields(),
		$companyDataProvider->getDisplayFields()
	);
}
if ($isInExportMode)
{
	// in export mode money fields shouldn't be formatted
	foreach ($displayFields as $displayField)
	{
		if ($displayField->isUserField() && $displayField->getType() === 'money')
		{
			$displayField->setDisplayRawValue(true);
		}
	}
}
$visibleGridColumns = $gridOptions->GetVisibleColumns();
if (empty($visibleGridColumns))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if (isset($arHeader['default']) && $arHeader['default'])
		{
			$visibleGridColumns[] = $arHeader['id'];
		}
	}
}
if ($isInExportMode && $isStExportAllFields)
{
	$displayFields = array_intersect_key($displayFields, array_flip($exportAllFieldsList));
}
else
{
	$displayFields = array_intersect_key($displayFields, array_flip($visibleGridColumns));
}

$context = ($isInExportMode ? Crm\Service\Display\Field::EXPORT_CONTEXT : Crm\Service\Display\Field::GRID_CONTEXT);
foreach ($displayFields as $displayField)
{
	$displayField->setContext($context);
}

$displayOptions =
	(new Crm\Service\Display\Options())
		->setMultipleFieldsDelimiter($isInExportMode ? ', ' : '<br />')
		->setGridId($arResult['GRID_ID'])
		->setFileUrlTemplate('/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#')
;
$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getWebFormResultsRestriction();
$restrictedItemIds = [];
$itemsMutator = null;
if (!$restriction->hasPermission())
{
	$itemIds = array_keys($arResult['DEAL']);
	$restriction->prepareDisplayOptions(\CCrmOwnerType::Deal, $itemIds, $displayOptions);
	$restrictedItemIds = $displayOptions->getRestrictedItemIds();
	if (!empty($restrictedItemIds))
	{
		$itemsMutator = new Crm\Restriction\ItemsMutator(array_merge(
			$displayOptions->getRestrictedFieldsToShow(),
			[
				'ASSIGNED_BY',
				'~STAGE_ID',
				'~CATEGORY_ID',
				'ASSIGNED_BY_ID',
				'~ASSIGNED_BY_ID',
				'ASSIGNED_BY_FORMATTED_NAME',
				'~ASSIGNED_BY_FORMATTED_NAME',
				'ASSIGNED_BY_SHOW_URL',
				'DEAL_SUMMARY',
				'DATE_CREATE',
				'DATE_MODIFY',
				'PATH_TO_DEAL_SHOW',
				'PATH_TO_DEAL_EDIT',
				'PATH_TO_DEAL_DELETE',
				'PATH_TO_DEAL_DETAILS',
				'PATH_TO_DEAL_EXCLUDE',
				'BIZPROC_STATUS',
				'BIZPROC_STATUS_HINT',
				'EDIT',
				'CURRENCY_ID',
			]
		));
		$arResult['RESTRICTED_VALUE_CLICK_CALLBACK'] = $restriction->prepareInfoHelperScript();
	}
}

if (isset($arResult['DEAL_ID']) && !empty($arResult['DEAL_ID']))
{
	// try to load product rows
	$arProductRows = CCrmDeal::LoadProductRows(array_keys($arResult['DEAL_ID']));
	foreach($arProductRows as $arProductRow)
	{
		$ownerID = $arProductRow['OWNER_ID'];
		if (!isset($arResult['DEAL'][$ownerID]))
		{
			continue;
		}

		$arEntity = &$arResult['DEAL'][$ownerID];
		if (!isset($arEntity['PRODUCT_ROWS']))
		{
			$arEntity['PRODUCT_ROWS'] = [];
		}
		$arEntity['PRODUCT_ROWS'][] = $arProductRow;
	}

	// fetch delivery and payment stage from latest related shipment/payment
	$dealIds = array_keys($arResult['DEAL_ID']);
	$shipmentStages = (new Crm\Deal\ShipmentsRepository())->getShipmentStages($dealIds);
	$paymentStages = (new Crm\Deal\PaymentsRepository())->getPaymentStages($dealIds);

	foreach ($dealIds as $dealId)
	{
		if (!isset($arResult['DEAL'][$dealId]))
		{
			continue;
		}

		if (isset($paymentStages[$dealId]))
		{
			$arResult['DEAL'][$dealId]['PAYMENT_STAGE'] = $paymentStages[$dealId];
			$displayFields['PAYMENT_STAGE'] =
				Field::createByType(Field\PaymentStatusField::TYPE, 'PAYMENT_STAGE')
					->setContext(Field::GRID_CONTEXT)
			;
		}

		if (isset($shipmentStages[$dealId]))
		{
			$arResult['DEAL'][$dealId]['DELIVERY_STAGE'] = $shipmentStages[$dealId];
			$displayFields['DELIVERY_STAGE'] =
				Field::createByType(Field\DeliveryStatusField::TYPE, 'DELIVERY_STAGE')
					->setContext(Field::GRID_CONTEXT)
			;
		}
	}

	$entityBadges = new Bitrix\Crm\Kanban\EntityBadge(CCrmOwnerType::Deal, $arResult['DEAL_ID']);
	$entityBadges->appendToEntityItems($arResult['DEAL']);
}

$displayValues =
	(new Bitrix\Crm\Service\Display(CCrmOwnerType::Deal, $displayFields, $displayOptions))
		->setItems($arResult['DEAL'])
		->getAllValues()
;

foreach ($displayValues as $dealId => $dealDisplayValues)
{
	foreach ($dealDisplayValues as $fieldId => $fieldValue)
	{
		if (isset($displayFields[$fieldId]) && $displayFields[$fieldId]->isUserField())
		{
			$arResult['DEAL_UF'][$dealId][$fieldId] = $fieldValue;
			continue;
		}

		$arResult['DEAL'][$dealId][$fieldId] = $fieldValue;
	}
}

if (!empty($restrictedItemIds) && $itemsMutator)
{
	foreach ($arResult['DEAL'] as &$item)
	{
		if (in_array($item['ID'], $restrictedItemIds))
		{
			$valueReplacer = $isInExportMode
				? $displayOptions->getRestrictedValueTextReplacer()
				: $displayOptions->getRestrictedValueHtmlReplacer()
			;
			$item = $itemsMutator->processItem($item, $valueReplacer);
			$item['DEAL_LEGEND'] = null;
			$item['~ASSIGNED_BY_ID'] = null;
			$item['EDIT'] = false;
		}
	}
}
unset($item);

$arResult['ENABLE_TOOLBAR'] = $arParams['ENABLE_TOOLBAR'] ?? false;
if ($arResult['ENABLE_TOOLBAR'])
{
	$arResult['PATH_TO_DEAL_ADD'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_DEAL_EDIT'],
		array('deal_id' => 0)
	);

	$addParams = [];

	if ($bInternal && isset($arParams['INTERNAL_CONTEXT']) && is_array($arParams['INTERNAL_CONTEXT']))
	{
		$internalContext = $arParams['INTERNAL_CONTEXT'];
		if (isset($internalContext['CONTACT_ID']))
		{
			$addParams['contact_id'] = $internalContext['CONTACT_ID'];
		}
		if (isset($internalContext['COMPANY_ID']))
		{
			$addParams['company_id'] = $internalContext['COMPANY_ID'];
		}

		if (!empty($addParams))
		{
			$arResult['DEAL_ADD_URL_PARAMS'] = $addParams;
			$arResult['PATH_TO_DEAL_ADD'] = CHTTP::urlAddParams(
				$arResult['PATH_TO_DEAL_ADD'],
				$addParams
			);
		}
	}
	else
	{
		$parentEntityTypeId = (int)($arParams['PARENT_ENTITY_TYPE_ID'] ?? 0);
		$parentEntityId = (int)($arParams['PARENT_ENTITY_ID'] ?? 0);
		if (\CCrmOwnerType::IsDefined($parentEntityTypeId) && $parentEntityId > 0)
		{
			$parentItemIdentifier = new Crm\ItemIdentifier($parentEntityTypeId, $parentEntityId);
			$arResult['PATH_TO_DEAL_ADD'] = Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
				\CCrmOwnerType::Deal,
				0,
				null,
				$parentItemIdentifier
			);
			$arResult['DEAL_ADD_URL_PARAMS'] = [
				'parentTypeId' => $parentItemIdentifier->getEntityTypeId(),
				'parentId' => $parentItemIdentifier->getEntityId(),
			];
		}
	}
}

foreach($arResult['CATEGORIES'] as $categoryID => $IDs)
{
	// checking access for operation
	$entityAttrs = CCrmDeal::GetPermissionAttributes($IDs, $categoryID);
	foreach($IDs as $ID)
	{
		$arResult['DEAL'][$ID]['EDIT'] =
			(in_array($ID, $restrictedItemIds))
				? false
				: CCrmDeal::CheckUpdatePermission(
				$ID,
				$userPermissions,
				$categoryID,
				['ENTITY_ATTRS' => $entityAttrs]
			)
		;

		$arResult['DEAL'][$ID]['DELETE'] = CCrmDeal::CheckDeletePermission(
			$ID,
			$userPermissions,
			$categoryID,
			array('ENTITY_ATTRS' => $entityAttrs)
		);

		$arResult['DEAL'][$ID]['BIZPROC_LIST'] = [];
		if ($isBizProcInstalled && !class_exists(\Bitrix\Bizproc\Controller\Workflow\Starter::class))
		{
			foreach ($arBPData as $arBP)
			{
				if (!CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::StartWorkflow,
					$userID,
					array('crm', 'CCrmDocumentDeal', 'DEAL_'.$arResult['DEAL'][$ID]['ID']),
					array(
						'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
						'DocumentStates' => $arDocumentStates,
						'WorkflowTemplateId' => $arBP['ID'],
						'CreatedBy' => $arResult['DEAL'][$ID]['~ASSIGNED_BY_ID'],
						'UserIsAdmin' => $isAdmin,
						'DealCategoryId' => $categoryID,
						'CRMEntityAttr' => $entityAttrs
					)
				))
				{
					continue;
				}

				$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
					array(
						'deal_id' => $arResult['DEAL'][$ID]['ID']
					)),
					array(
						'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => $arResult['SESSION_ID'],
						'CRM_DEAL_SHOW_V12_active_tab' => 'tab_bizproc', 'back_url' => $arParams['PATH_TO_DEAL_LIST'])
				);

				if (isset($arBP['HAS_PARAMETERS']))
				{
					$params = \Bitrix\Main\Web\Json::encode(array(
						'moduleId' => 'crm',
						'entity' => 'CCrmDocumentDeal',
						'documentType' => 'DEAL',
						'documentId' => 'DEAL_'.$arResult['DEAL'][$ID]['ID'],
						'templateId' => $arBP['ID'],
						'templateName' => $arBP['NAME'],
						'hasParameters' => $arBP['HAS_PARAMETERS']
					));
					$arBP['ONCLICK'] = 'BX.Bizproc.Starter.singleStart('.$params
						.', function(){BX.Main.gridManager.reload(\''.CUtil::JSEscape($arResult['GRID_ID']).'\');});';
				}

				$arResult['DEAL'][$ID]['BIZPROC_LIST'][] = $arBP;
			}
		}
	}
}

if (!$isInExportMode)
{
	$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] =
	$arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS'] =
	$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
	$arResult['NEED_FOR_REFRESH_ACCOUNTING'] =
	$arResult['NEED_FOR_BUILD_TIMELINE'] =
	$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] = false;

	if (!$bInternal)
	{
		if (COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_SEARCH_CONTENT', 'N') === 'Y')
		{
			$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
		}

		if (\Bitrix\Crm\Agent\Semantics\DealSemanticsRebuildAgent::getInstance()->isEnabled())
		{
			$arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS'] = true;
		}

		$arResult['NEED_FOR_BUILD_TIMELINE'] = $arParams['IS_RECURRING'] === 'Y'
			? \Bitrix\Crm\Agent\Timeline\RecurringDealTimelineBuildAgent::getInstance()->isEnabled()
			: \Bitrix\Crm\Agent\Timeline\DealTimelineBuildAgent::getInstance()->isEnabled();

		$arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT'] = \Bitrix\Crm\Agent\Search\TimelineSearchContentRebuildAgent::getInstance()->isEnabled();
		$arResult['NEED_FOR_REFRESH_ACCOUNTING'] = \Bitrix\Crm\Agent\Accounting\DealAccountSyncAgent::getInstance()->isEnabled();

		$attributeRebuildAgent = \Bitrix\Crm\Agent\Security\DealAttributeRebuildAgent::getInstance();
		$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] =
			$attributeRebuildAgent->isEnabled()
			&& ($attributeRebuildAgent->getProgressData()['TOTAL_ITEMS'] > 0)
		;

		if (CCrmPerms::IsAdmin())
		{
			if (COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_ATTR', 'N') === 'Y')
			{
				$arResult['PATH_TO_PRM_LIST'] = (string)Crm\Service\Container::getInstance()->getRouter()->getPermissionsUrl();
				$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = true;
			}
		}
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');

	return $arResult['ROWS_COUNT'] ?? null;
}
else
{
	if ($isStExport)
	{
		$this->__templateName = '.default';

		$this->IncludeComponentTemplate($sExportType);

		return array(
			'PROCESSED_ITEMS' => count($arResult['DEAL']),
			'TOTAL_ITEMS' => $arResult['STEXPORT_TOTAL_ITEMS'],
			'LAST_EXPORTED_ID' => $lastExportedId
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
			Header('Content-Disposition: attachment;filename=deals.csv');
		}
		elseif ($sExportType === 'excel')
		{
			Header('Content-Type: application/vnd.ms-excel');
			Header('Content-Disposition: attachment;filename=deals.xls');
		}
		Header('Content-Type: application/octet-stream');
		Header('Content-Transfer-Encoding: binary');

		// add UTF-8 BOM marker
		echo chr(239).chr(187).chr(191);

		$this->IncludeComponentTemplate($sExportType);

		die();
	}
}
