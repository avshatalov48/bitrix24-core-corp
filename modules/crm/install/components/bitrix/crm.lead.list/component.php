<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
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

use Bitrix\Crm;
use Bitrix\Crm\Agent\Duplicate\Background\LeadIndexRebuild;
use Bitrix\Crm\Agent\Duplicate\Background\LeadMerge;
use Bitrix\Crm\Agent\Duplicate\Volatile\IndexRebuild;
use Bitrix\Crm\Component\EntityList\FieldRestrictionManager;
use Bitrix\Crm\Component\EntityList\FieldRestrictionManagerTypes;
use Bitrix\Crm\Context\GridContext;
use Bitrix\Crm\Conversion\LeadConversionDispatcher;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Integrity\Volatile;
use Bitrix\Crm\LeadAddress;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

$isErrorOccurred = false;
$errorMessage = '';

if (!$isErrorOccurred && !CModule::IncludeModule('crm'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED');
	$isErrorOccurred = true;
}

$isBizProcInstalled = IsModuleInstalled('bizproc');
if (!$isErrorOccurred && $isBizProcInstalled)
{
	if (!CModule::IncludeModule('bizproc'))
	{
		$errorMessage = GetMessage('BIZPROC_MODULE_NOT_INSTALLED');
		$isErrorOccurred = true;
	}
	elseif (!CBPRuntime::isFeatureEnabled())
	{
		$isBizProcInstalled = false;
	}
}

if (!$isErrorOccurred && !CAllCrmInvoice::installExternalEntities())
{
	$isErrorOccurred = true;
}
if(!$isErrorOccurred && !CCrmQuote::LocalComponentCausedUpdater())
{
	$isErrorOccurred = true;
}

if (!$isErrorOccurred && !CModule::IncludeModule('currency'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
	$isErrorOccurred = true;
}
if (!$isErrorOccurred && !CModule::IncludeModule('catalog'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
	$isErrorOccurred = true;
}
if (!$isErrorOccurred && !CModule::IncludeModule('sale'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED_SALE');
	$isErrorOccurred = true;
}


$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$isErrorOccurred && !CCrmLead::CheckReadPermission(0, $userPermissions))
{
	$errorMessage = GetMessage('CRM_PERMISSION_DENIED');
	$isErrorOccurred = true;
}

//region Export params
$sExportType = !empty($arParams['EXPORT_TYPE']) ?
	strval($arParams['EXPORT_TYPE']) : (!empty($_REQUEST['type']) ? strval($_REQUEST['type']) : '');
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
$arResult['STEXPORT_EXPORT_ALL_FIELDS'] = ($isStExport && $isStExportAllFields) ? 'Y' : 'N';

$isStExportProductsFields = (isset($arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_PRODUCT_FIELDS'])
						&& $arParams['STEXPORT_INITIAL_OPTIONS']['EXPORT_PRODUCT_FIELDS'] === 'Y');
$arResult['STEXPORT_EXPORT_PRODUCT_FIELDS'] = ($isStExport && $isStExportProductsFields) ? 'Y' : 'N';

$arResult['STEXPORT_MODE'] = $isStExport ? 'Y' : 'N';
$arResult['STEXPORT_TOTAL_ITEMS'] = isset($arParams['STEXPORT_TOTAL_ITEMS']) ?
	(int)$arParams['STEXPORT_TOTAL_ITEMS'] : 0;
//endregion

if (!$isErrorOccurred && $isInExportMode && $userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'EXPORT'))
{
	$errorMessage = GetMessage('CRM_PERMISSION_DENIED');
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

$CCrmLead = new CCrmLead(false);
$CCrmBizProc = new CCrmBizProc('LEAD');
$fieldRestrictionManager = new FieldRestrictionManager(
	FieldRestrictionManager::MODE_GRID,
	[FieldRestrictionManagerTypes::OBSERVERS, FieldRestrictionManagerTypes::ACTIVITY],
	CCrmOwnerType::Lead
);

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();

$currentPage = $APPLICATION->GetCurPage();

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath(
	'PATH_TO_LEAD_LIST',
	$arParams['PATH_TO_LEAD_LIST'] ?? '',
	$currentPage
);
$arParams['PATH_TO_LEAD_DETAILS'] = CrmCheckPath(
	'PATH_TO_LEAD_DETAILS',
	$arParams['PATH_TO_LEAD_DETAILS'] ?? '',
	$currentPage . '?lead_id=#lead_id#&details'
);
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath(
	'PATH_TO_LEAD_SHOW',
	$arParams['PATH_TO_LEAD_SHOW'] ?? '',
	$currentPage . '?lead_id=#lead_id#&show'
);
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath(
	'PATH_TO_LEAD_EDIT',
	$arParams['PATH_TO_LEAD_EDIT'] ?? '',
	$currentPage . '?lead_id=#lead_id#&edit'
);
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath(
	'PATH_TO_LEAD_CONVERT',
	$arParams['PATH_TO_LEAD_CONVERT'] ?? '',
	$currentPage . '?lead_id=#lead_id#&convert'
);
$arParams['PATH_TO_LEAD_MERGE'] = CrmCheckPath(
	'PATH_TO_LEAD_MERGE',
	$arParams['PATH_TO_LEAD_MERGE'] ?? '',
	'/lead/merge/'
);
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath(
	'PATH_TO_QUOTE_EDIT',
	$arParams['PATH_TO_QUOTE_EDIT'] ?? '',
	$currentPage . '?quote_id=#quote_id#&edit'
);
$arParams['PATH_TO_LEAD_WIDGET'] = CrmCheckPath(
	'PATH_TO_LEAD_WIDGET',
	$arParams['PATH_TO_LEAD_WIDGET'] ?? '',
	$currentPage
);
$arParams['PATH_TO_LEAD_KANBAN'] = CrmCheckPath(
	'PATH_TO_LEAD_KANBAN',
	$arParams['PATH_TO_LEAD_KANBAN'] ?? '',
	$currentPage
);
$arParams['PATH_TO_LEAD_CALENDAR'] = CrmCheckPath(
	'PATH_TO_LEAD_CALENDAR',
	$arParams['PATH_TO_LEAD_CALENDAR'] ?? '',
	$currentPage
);
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath(
	'PATH_TO_USER_PROFILE',
	$arParams['PATH_TO_USER_PROFILE'] ?? '',
	'/company/personal/user/#user_id#/'
);
$arParams['PATH_TO_USER_BP'] = CrmCheckPath(
	'PATH_TO_USER_BP',
	$arParams['PATH_TO_USER_BP'] ?? '',
	'/company/personal/bizproc/'
);
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE'])
	? CSite::GetNameFormat(false)
	: str_replace(['#NOBR#', '#/NOBR#'], ['', ''], $arParams['NAME_TEMPLATE']);

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
$arResult['SESSION_ID'] = bitrix_sessid();
$arResult['NAVIGATION_CONTEXT_ID'] = $arParams['NAVIGATION_CONTEXT_ID'] ?? '';
$arResult['DISABLE_NAVIGATION_BAR'] = $arParams['DISABLE_NAVIGATION_BAR'] ?? 'N';
$arResult['PRESERVE_HISTORY'] = $arParams['PRESERVE_HISTORY'] ?? false;
$arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();
$arResult['TIME_FORMAT'] = CCrmDateTimeHelper::getDefaultDateTimeFormat();

$addressLabels = EntityAddress::getShortLabels();

//Show error message if required
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
	$errorID = mb_strtolower($_GET['error']);
	if(preg_match('/^crm_err_/', $errorID) === 1)
	{
		if(!isset($_SESSION[$errorID]))
		{
			LocalRedirect(CHTTP::urlDeleteParams($arParams['PATH_TO_LEAD_LIST'], array('error')));
		}

		$arErrors = $_SESSION[$errorID];
		if(is_array($arErrors) && !empty($arErrors))
		{
			$errorHtml = '';
			foreach($arErrors as $error)
			{
				if($errorHtml !== '')
				{
					$errorHtml .= '<br />';
				}
				$errorHtml .= htmlspecialcharsbx($error);
			}
			$arResult['ERROR_HTML'] = $errorHtml;
		}
		unset($arErrors, $_SESSION[$errorID]);
	}
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

//region Old logic of the counter panel (not used)
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
				CCrmOwnerType::Lead,
				$counterTypeID,
				$userID,
				Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST)
			);

			$arFilter = $counter->prepareEntityListFilter(
				array(
					'MASTER_ALIAS' => CCrmLead::TABLE_ALIAS,
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

$enableReportFilter = Main\Application::getInstance()->getContext()->getRequest()->getQuery('from_analytics') === 'Y';

if ($enableReportFilter)
{
	$boardId = Main\Application::getInstance()->getContext()->getRequest()->getQuery('board_id');
	$boardId = preg_replace('/[^\w\-_]/', '', $boardId);
	$externalFilterId = 'report_board_' . $boardId . '_filter';

	$reportId = Bitrix\Main\Context::getCurrent()->getRequest()['report_id'];

	if($reportId != '')
	{
		$reportHandler = Crm\Integration\Report\ReportHandlerFactory::createWithReportId($reportId);
		$reportFilter = $reportHandler ? $reportHandler->prepareEntityListFilter(Bitrix\Main\Context::getCurrent()->getRequest()) : null;

		if(is_array($reportFilter) && !empty($reportFilter))
		{
			$arFilter = $reportFilter;
		}
		else
		{
			$enableReportFilter = false;
		}
	}
}

$arResult['IS_EXTERNAL_FILTER'] = ($enableWidgetFilter || $enableCounterFilter || $enableReportFilter);

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);

$CCrmFieldMulti = new CCrmFieldMulti();

$arResult['GRID_ID'] = (new Crm\Component\EntityList\GridId(CCrmOwnerType::Lead))
	->getValue((string)($arParams['GRID_ID_SUFFIX'] ?? ''))
;

$arResult['HONORIFIC'] = CCrmStatus::GetStatusListEx('HONORIFIC');
$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('STATUS');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
$arResult['WEBFORM_LIST'] = WebFormManager::getListNamesEncoded();
$arResult['BOOLEAN_VALUES_LIST'] = array(
	'N' => GetMessage('CRM_COLUMN_BOOLEAN_VALUES_N'),
	'Y' => GetMessage('CRM_COLUMN_BOOLEAN_VALUES_Y')
);

// Please, uncomment if required
//$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();
$arResult['PERMS']['ADD'] = !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE'] = !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'DELETE');

[$callListId, $callListContext] = \CCrmViewHelper::getCallListIdAndContextFromRequest();
$arResult['CALL_LIST_ID'] = $callListId;
$arResult['CALL_LIST_CONTEXT'] = $callListContext;
unset($callListId, $callListContext);

if (\CCrmViewHelper::isCallListUpdateMode(\CCrmOwnerType::Lead))
{
	AddEventHandler('crm', 'onCrmLeadListItemBuildMenu', array('\Bitrix\Crm\CallList\CallList', 'handleOnCrmLeadListItemBuildMenu'));
}

CCrmLead::PrepareConversionPermissionFlags(0, $arResult, $userPermissions);

$arResult['~STATUS_LIST_WRITE']= CCrmStatus::GetStatusList('STATUS');
$arResult['STATUS_LIST_WRITE'] = array();
unset($arResult['~STATUS_LIST_WRITE']['CONVERTED'], $arResult['~STATUS_LIST_EX']['CONVERTED']);
foreach ($arResult['~STATUS_LIST_WRITE'] as $sStatusId => $sStatusTitle)
{
	if ($userPermissions->GetPermType('LEAD', 'WRITE', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST_WRITE'][$sStatusId] = $sStatusTitle;
}

//region Filter Presets Initialization
if(!$bInternal)
{
	$entityFilter = Crm\Filter\Factory::createEntityFilter(
		new Crm\Filter\LeadSettings(['ID' => $arResult['GRID_ID']])
	);

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
		$arResult['FILTER_PRESETS'] = (new Bitrix\Crm\Filter\Preset\Lead())
			->setUserId($arResult['CURRENT_USER_ID'])
			->setUserName(CCrmViewHelper::GetFormattedUserName($arResult['CURRENT_USER_ID'], $arParams['NAME_TEMPLATE']))
			->setDefaultValues($entityFilter->getDefaultFieldIDs())
			->getDefaultPresets()
		;
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
if ($arParams['LEAD_COUNT'] <= 0)
{
	$arParams['LEAD_COUNT'] = 20;
}
$arNavParams = $gridOptions->GetNavParams(array('nPageSize' => $arParams['LEAD_COUNT']));
$arNavParams['bShowAll'] = false;
if(isset($arNavParams['nPageSize']) && $arNavParams['nPageSize'] > 100)
{
	$arNavParams['nPageSize'] = 100;
}
//endregion

//region Filter fields cleanup
$fieldRestrictionManager->removeRestrictedFields($filterOptions, $gridOptions);
//endregion

//region Filter initialization
if(!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'COMMENTS', 'COMPANY_TITLE');

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

	if(!in_array('ACTIVITY_RESPONSIBLE_IDS', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ACTIVITY_RESPONSIBLE_IDS';
	}

	if(!in_array('ACTIVITY_FASTSEARCH_CREATED', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'ACTIVITY_FASTSEARCH_CREATED';
	}

	if(!in_array('WEBFORM_ID', $effectiveFilterFieldIDs, true))
	{
		$effectiveFilterFieldIDs[] = 'WEBFORM_ID';
	}

	Tracking\UI\Filter::appendEffectiveFields($effectiveFilterFieldIDs);

	//endregion

	if (empty($externalFilterId))
	{
		foreach($effectiveFilterFieldIDs as $filterFieldID)
		{
			$filterField = $entityFilter->getField($filterFieldID);
			if($filterField)
			{
				$arResult['FILTER'][] = $filterField->toArray();
			}
		}
	}
}
//endregion

if(!$arResult['IS_EXTERNAL_FILTER'])
{
	$arFilter += $filterOptions->getFilter($arResult['FILTER']);
}

// Headers initialization -->
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'first_order' => 'desc', 'width' => 60, 'default' => false, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
	array('id' => 'LEAD_SUMMARY', 'name' => GetMessage('CRM_COLUMN_LEAD'), 'sort' => 'title', 'width' => 200, 'default' => true, 'editable' => true),
	array(
		'id' => 'STATUS_ID',
		'name' => GetMessage('CRM_COLUMN_STATUS_MSGVER_1'),
		'sort' => 'status_sort',
		'width' => 200,
		'default' => true,
		'prevent_default' => false,
		'type' => 'list',
		'editable' => array('items' => $arResult['STATUS_LIST_WRITE'])
	),
);

// Don't display activities in INTERNAL mode.
if (!$bInternal)
{
	$arResult['HEADERS'][] = [
		'id' => 'ACTIVITY_ID',
		'name' => GetMessage('CRM_COLUMN_ACTIVITY'),
		'sort' => 'nearest_activity',
		'width' => 150,
		'default' => true,
		'prevent_default' => false
	];
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	[
		[
			'id' => 'LEAD_FORMATTED_NAME',
			'name' => GetMessage('CRM_COLUMN_FULL_NAME'),
			'sort' => 'last_name',
			'default' => true,
			'editable' => false,
		],
		[
			'id' => 'TITLE',
			'name' => GetMessage('CRM_COLUMN_TITLE'),
			'sort' => 'title',
			'default' => false,
			'editable' => true,
		],
		[
			'id' => 'HONORIFIC',
			'name' => GetMessage('CRM_COLUMN_HONORIFIC'),
			'sort' => false,
			'type' => 'list',
			'editable' => [
				'items' => ['0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')] + CCrmStatus::GetStatusList('HONORIFIC')
			],
		],
		[
			'id' => 'NAME',
			'name' => GetMessage('CRM_COLUMN_NAME'),
			'sort' => 'name',
			'default' => false,
			'editable' => true,
			'class' => 'username',
		],
		[
			'id' => 'SECOND_NAME',
			 'name' => GetMessage('CRM_COLUMN_SECOND_NAME'),
			 'sort' => 'second_name',
			 'default' => false,
			 'editable' => true,
			 'class' => 'username',
		],
		[
			'id' => 'LAST_NAME',
			'name' => GetMessage('CRM_COLUMN_LAST_NAME'),
			'sort' => 'last_name',
			'default' => false,
			'editable' => true,
			'class' => 'username',
		],
		[
			'id' => 'BIRTHDATE',
			'name' => GetMessage('CRM_COLUMN_BIRTHDATE'),
			'sort' => 'BIRTHDATE',
			'first_order' => 'desc',
			 'default' => false,
			 'editable' => true,
			 'type' => 'date',
		],
		[
			'id' => 'DATE_CREATE',
			'name' => GetMessage('CRM_COLUMN_DATE_CREATE'),
			'sort' => 'id',
			'first_order' => 'desc',
			'default' => true,
			'editable' => false,
			'class' => 'date',
		],
		[
			'id' => 'SOURCE_ID',
			'name' => GetMessage('CRM_COLUMN_SOURCE'),
			'sort' => 'source_id',
			'default' => false,
			'editable' => [
				'items' => CCrmStatus::GetStatusList('SOURCE')
			],
			'type' => 'list',
		],
		[
			'id' => Crm\Item::FIELD_NAME_OBSERVERS,
			'name' => Loc::getMessage('CRM_COLUMN_OBSERVERS'),
			'sort' => false,
			'editable' => false,
		],
	]
);

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS'], ['LINK']);
if ($isInExportMode)
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username'),
	array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_STATUS_DESCRIPTION_MSGVER_1'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
	array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
	array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false, 'class' => 'username'),
	array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'first_order' => 'desc', 'default' => false, 'class' => 'date'),
	array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false, 'class' => 'username'),
	array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE'), 'sort' => 'company_title', 'default' => false, 'editable' => true),
	array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST'), 'sort' => 'post', 'default' => false, 'editable' => true),

	array('id' => 'FULL_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country', 'default' => false, 'editable' => false),

	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
	array('id' => 'SUM', 'name' => GetMessage('CRM_COLUMN_SUM'), 'sort' => 'opportunity_account', 'default' => false, 'editable' => false, 'align' => 'right'),
	array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY_2'), 'sort' => 'opportunity', 'first_order' => 'desc', 'default' => false, 'editable' => true, 'align' => 'right'),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'default' => false, 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
	array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID'), 'sort' => false, 'default' => $isInExportMode, 'editable' => false, 'type' => 'list'),
	array('id' => 'WEBFORM_ID', 'name' => GetMessage('CRM_COLUMN_WEBFORM'), 'sort' => 'webform_id', 'default' => false, 'type' => 'list'),
	array('id' => 'IS_RETURN_CUSTOMER', 'name' => GetMessage('CRM_COLUMN_IS_RETURN_CUSTOMER1'), 'sort' => 'is_return_customer', 'default' => false, 'type' => 'list'),
	array('id' => 'LEAD_CLIENT', 'name' => GetMessage('CRM_COLUMN_CLIENT'), 'sort' => 'lead_client', 'default' => false, 'editable' => false)
));

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

$CCrmUserType->appendGridHeaders($arResult['HEADERS']);

Crm\Service\Container::getInstance()->getParentFieldManager()->prepareGridHeaders(
	\CCrmOwnerType::Lead,
	$arResult['HEADERS']
);

$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
if (
	\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
	&& $factory
	&& $factory->isLastActivityEnabled()
)
{
	$arResult['HEADERS'][] = ['id' => Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME, 'name' => $factory->getFieldCaption(Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME), 'sort' => mb_strtolower(Crm\Item::FIELD_NAME_LAST_ACTIVITY_TIME), 'first_order' => 'desc', 'class' => 'datetime'];
}

$observersDataProvider = new \Bitrix\Crm\Component\EntityList\UserDataProvider\Observers(CCrmOwnerType::Lead);

$arResult['HEADERS_SECTIONS'] = \Bitrix\Crm\Filter\HeaderSections::getInstance()
	->sections($factory);

unset($factory);

$arBPData = array();
if ($isBizProcInstalled)
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentLead', 'LEAD'), false);
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentLead', 'LEAD'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::ViewWorkflow,
			$userID,
			array('crm', 'CCrmDocumentLead', 'LEAD'),
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
		$arResult['HEADERS'][] = array('id' => 'BIZPROC_'.$arBP['ID'], 'name' => $arBP['NAME'], 'sort' => false, 'default' => false, 'editable' => false);
	}
}

//region Check and fill fields restriction
$params = [
	$arResult['GRID_ID'] ?? '',
	$arResult['HEADERS'] ?? [],
	$entityFilter ?? null
];
$arResult['RESTRICTED_FIELDS_ENGINE'] = $fieldRestrictionManager->fetchRestrictedFieldsEngine(...$params);
$arResult['RESTRICTED_FIELDS'] = $fieldRestrictionManager->getFilterFields(...$params);
//endregion

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

//endregion Headers initialization

$settings = \CCrmViewHelper::initGridSettings(
	$arResult['GRID_ID'],
	$gridOptions,
	$arResult['HEADERS'],
	$isInExportMode,
	columnNameToEditableFieldNameMap: [
		'LEAD_SUMMARY' => 'TITLE',
	],
);

$arResult['PANEL'] = \CCrmViewHelper::initGridPanel(
	\CCrmOwnerType::Lead,
	$settings,
);
unset($settings);

//region Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
if(check_bitrix_sessid())
{
	$getAction = 'action_'.$arResult['GRID_ID'];
	//We need to check grid 'controls'
	if ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
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

$CCrmUserType->PrepareListFilterValues($arResult['FILTER'], $arFilter, $arResult['GRID_ID']);

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmLead::$sUFEntityID, $arFilter);

//region Apply Search Restrictions
$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
if(!$searchRestriction->isExceeded(CCrmOwnerType::Lead))
{
	$searchRestriction->notifyIfLimitAlmostExceed(CCrmOwnerType::Lead);

	Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Lead, $arFilter);
}
else
{
	$arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
		array('ENTITY_TYPE_ID' => CCrmOwnerType::Lead)
	);
}
//endregion

Crm\Filter\FieldsTransform\UserBasedField::applyTransformWrapper($arFilter);

//region Activity Counter Filter
CCrmEntityHelper::applySubQueryBasedFiltersWrapper(
	\CCrmOwnerType::Lead,
	$arResult['GRID_ID'],
	Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST),
	$arFilter,
	$entityFilter
);
//endregion

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter, array(), '=%', false);
$arImmutableFilters = array(
	'FM', 'ID', 'CURRENCY_ID',
	'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
	'PRODUCT_ROW_PRODUCT_ID',
	'HAS_PHONE', 'HAS_EMAIL',
	'STATUS_SEMANTIC_ID',
	'WEBFORM_ID', 'IS_RETURN_CUSTOMER', 'TRACKING_SOURCE_ID', 'TRACKING_CHANNEL_CODE',
	'SEARCH_CONTENT',
	'PRODUCT_ID', 'STATUS_ID', 'SOURCE_ID', 'COMPANY_ID', 'CONTACT_ID',
	'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID', 'OBSERVER_IDS'
);

foreach ($arFilter as $k => $v)
{
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
		|| $k === 'ADDRESS_COUNTRY')
	{
		$arFilter["=%{$k}"] = "{$v}%";
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
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif($k === 'STATUS_CONVERTED')
	{
		if($v !== '')
		{
			$arFilter[$v === 'N' ? 'STATUS_SEMANTIC_ID' : '!STATUS_SEMANTIC_ID'] = 'P';
		}
		unset($arFilter['STATUS_CONVERTED']);
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

//region POST & GET actions processing
\CCrmViewHelper::processGridRequest(\CCrmOwnerType::Lead, $arResult['GRID_ID'], $arResult['PANEL']);

if($actionData['ACTIVE'])
{
	$arErrors = array();
	if ($actionData['METHOD'] == 'GET')
	{
		$arErrors = array();
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);
			$arEntityAttr = $userPermissions->GetEntityAttr('LEAD', array($ID));
			if(CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::LeadName, $ID, $userPermissions, $arEntityAttr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $arEntityAttr)
					&& $CCrmLead->Delete($ID, array('CHECK_DEPENDENCIES' => true, 'PROCESS_BIZPROC' => false)))
				{
					$DB->Commit();
				}
				else
				{
					$arErrors[] = $CCrmLead->LAST_ERROR;
					$DB->Rollback();
				}
			}
		}
		if ($actionData['NAME'] == 'exclude' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);
			if(\Bitrix\Crm\Exclusion\Access::current()->canWrite())
			{
				$DB->StartTransaction();

				try
				{
					\Bitrix\Crm\Exclusion\Manager::excludeEntity(
						\CCrmOwnerType::Lead,
						$ID,
						true,
						['PERMISSIONS' => $userPermissions],
					);

					$isSuccess = true;
				}
				catch (Main\ObjectException $deleteException)
				{
					$arErrors[] = $deleteException->getMessage();

					$isSuccess = false;
				}

				if ($isSuccess)
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
			$redirectUrl = $bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_lead' : $arParams['PATH_TO_LEAD_LIST'];
			if(!empty($arErrors))
			{
				$errorID = uniqid('crm_err_');
				$_SESSION[$errorID] = $arErrors;
				$redirectUrl = CHTTP::urlAddParams($redirectUrl, array('error' => $errorID));
			}
			LocalRedirect($redirectUrl);
		}
	}
}
// <-- POST & GET actions processing

$_arSort = $gridOptions->GetSorting(
	array(
		'sort' => array('id' => 'desc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arSelect = $gridOptions->GetVisibleColumns();

// Remove column for deleted UF
if ($CCrmUserType->NormalizeFields($arSelect))
{
	$gridOptions->SetVisibleColumns($arSelect);
}

$arSelectMap = array_fill_keys($arSelect, true);

$arResult['IS_BIZPROC_AVAILABLE'] = $isBizProcInstalled;
$arResult['ENABLE_BIZPROC'] = $isBizProcInstalled
	&& (!isset($arParams['ENABLE_BIZPROC']) || mb_strtoupper($arParams['ENABLE_BIZPROC']) === 'Y');

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
			'back_url' => urlencode($arParams['PATH_TO_LEAD_LIST'])
		)
	);
}

// Export all fields
if ($isInExportMode && $isStExport && $isStExportAllFields)
{
	$arSelectMap = $exportAllFieldsList;
}

// Fill in default values if empty
if (empty($arSelectMap))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'] ?? false)
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
}

$arSelectedHeaders = array_keys($arSelectMap);

if ($isInGadgetMode)
{
	$arSelectMap['DATE_CREATE'] =
		$arSelectMap['TITLE'] =
		$arSelectMap['STATUS_ID'] =
		$arSelectMap['HONORIFIC'] =
		$arSelectMap['NAME'] =
		$arSelectMap['SECOND_NAME'] =
		$arSelectMap['LAST_NAME'] =
		$arSelectMap['POST'] =
		$arSelectMap['COMPANY_TITLE'] = true;
}
else
{
	if(isset($arSelectMap['LEAD_SUMMARY']))
	{
		$arSelectMap['TITLE'] =
			$arSelectMap['SOURCE_ID'] = true;
	}

	if(isset($arSelectMap['LEAD_FORMATTED_NAME']))
	{
		$arSelectMap['NAME'] =
			$arSelectMap['HONORIFIC'] =
			$arSelectMap['SECOND_NAME'] =
			$arSelectMap['LAST_NAME'] = true;
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

	// Always need to remove the menu items
	if(!isset($arSelectMap['STATUS_ID']))
	{
		$arSelectMap['STATUS_ID'] = true;
	}

	// for bizproc
	if(!isset($arSelectMap['ASSIGNED_BY']))
	{
		$arSelectMap['ASSIGNED_BY'] = true;
	}

	// For preparing user html
	$arSelectMap['ASSIGNED_BY_LOGIN'] =
		$arSelectMap['ASSIGNED_BY_NAME'] =
		$arSelectMap['ASSIGNED_BY_LAST_NAME'] =
		$arSelectMap['ASSIGNED_BY_SECOND_NAME'] = true;

	if(isset($arSelectMap['SUM']))
	{
		$arSelectMap['OPPORTUNITY'] =
			$arSelectMap['CURRENCY_ID'] = true;
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

	if(isset($arSelectMap['LEAD_CLIENT']))
	{
		$arSelectMap['CONTACT_ID'] =
			$arSelectMap['COMPANY_ID'] =
			$arSelectMap['ASSOCIATED_COMPANY_TITLE'] =
			$arSelectMap['CONTACT_HONORIFIC'] =
			$arSelectMap['CONTACT_NAME'] =
			$arSelectMap['CONTACT_SECOND_NAME'] =
			$arSelectMap['CONTACT_LAST_NAME'] = true;
	}

	// ID must present in select
	if(!isset($arSelectMap['ID']))
	{
		$arSelectMap['ID'] = true;
	}
}

// IS_RETURN_CUSTOMER must present in select
if(!isset($arSelectMap['IS_RETURN_CUSTOMER']))
{
	$arSelectMap['IS_RETURN_CUSTOMER'] = true;
}

if ($isInExportMode)
{
	$productHeaderIndex = array_search('PRODUCT_ID', $arSelectedHeaders, true);
	//$productRowsEnabled = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isProductRowExportEnabled();

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
			'LEAD_SUMMARY' => array(
				'TITLE',
				'SOURCE_ID'
			),
			'LEAD_FORMATTED_NAME' => array(
				'HONORIFIC',
				'NAME',
				'SECOND_NAME',
				'LAST_NAME'
			),
			'SUM' => array(
				'OPPORTUNITY',
				'CURRENCY_ID'
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
	$nTopCount = $arParams['LEAD_COUNT'] ?? 0;
}

if ($isInCalendarMode)
{
	$nTopCount = $arParams['DEAL_COUNT'] ?? 0;
}

if($nTopCount > 0)
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
if(isset($arSort['assigned_by']))
{
	$assignedBySort = $arSort['assigned_by'];
	if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isUserNameSortingEnabled())
	{
		$arSort['assigned_by_last_name'] = $assignedBySort;
		$arSort['assigned_by_name'] = $assignedBySort;
	}
	else
	{
		$arSort['assigned_by_id'] = $assignedBySort;
	}
	unset($arSort['assigned_by']);
}

$arOptions = $arExportOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(isset($arSelectMap['ACTIVITY_ID']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
	$arExportOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}

if(isset($arSort['status_sort']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STATUS_SORT';
	$arExportOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STATUS_SORT';
}

if(isset($arSort['lead_client']))
{
	$arSort['contact_last_name'] =
	$arSort['contact_name'] =
	$arSort['associated_company_title'] = $arSort['lead_client'];
	unset($arSort['lead_client']);
}

if (isset($arSort['date_create']))
{
	$arSort['id'] = $arSort['date_create'];
	unset($arSort['date_create']);
}

if(!empty($arSort) && !isset($arSort['id']))
{
	$arSort['id'] = reset($arSort);
}

$arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);

if (in_array('ACTIVITY_ID', $arSelect, true)) // Remove ACTIVITY_ID from $arSelect
{
	$arResult['NEED_ADD_ACTIVITY_BLOCK'] = true;
	unset($arSelect[array_search('ACTIVITY_ID', $arSelect)]);
	$arSelect = array_values($arSelect);
}

$observersDataProvider->prepareSelect($arSelect);

// For calendar view
if (isset($arParams['CALENDAR_MODE_LIST']) && !in_array('DATE_CREATE', $arSelect))
{
	$arSelect[] = 'DATE_CREATE';
}

$arResult['LEAD'] = array();
$arResult['LEAD_ID'] = array();
$arResult['LEAD_UF'] = array();

//region Navigation data initialization
$pageNum = 0;
if ($isInExportMode && $isStExport)
{
	$pageSize = !empty($arParams['STEXPORT_PAGE_SIZE']) ? $arParams['STEXPORT_PAGE_SIZE'] : $arParams['LEAD_COUNT'];
}
else
{
	$pageSize = !$isInExportMode
		? (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['LEAD_COUNT']) : 0;
}
// For calendar mode we should clear nav params, to be able to show entries on the grid
if (isset($arParams['CALENDAR_MODE_LIST']))
{
	$pageSize = $arParams['LEAD_COUNT'];
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
		$total = CCrmLead::GetListEx(array(), $arFilter, array());
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
			&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']])
		)
		{
			$paginationData = $_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']];
			if(isset($paginationData['PAGE_NUM'])
				&& isset($paginationData['PAGE_SIZE'])
				&& $paginationData['PAGE_SIZE'] == $pageSize
			)
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

if ($isInCalendarMode)
{
	$arSelect = [
		'ID', 'TITLE', 'DATE_CREATE'
	];

	foreach ($arParams['CALENDAR_MODE_LIST'] as $calendarModeItem)
	{
		if ($calendarModeItem['selected'])
		{
			$calendarModeItemUserFieldId = null;
			$calendarModeItemUserFieldType = null;
			$calendarModeItemUserFieldName = null;
			$parsedKeys = \Bitrix\Crm\Integration\Calendar::parseUserfieldKey($calendarModeItem['id']);
			if (count($parsedKeys) > 1)
			{
				[$calendarModeItemUserFieldId, $calendarModeItemUserFieldType, $calendarModeItemUserFieldName] = $parsedKeys;
			}

			if (
				isset($calendarModeItemUserFieldName)
				&& !in_array($calendarModeItemUserFieldName, $arSelect, true)
			)
			{
				$arSelect[] = $calendarModeItemUserFieldName;
			}
		}
	}
}

if ($isInExportMode && $isStExport && $pageNum === 1)
{
	$total = \CCrmLead::GetListEx(array(), $arFilter, array());
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

		$dbResultOnlyIds = CCrmLead::GetListEx(
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

		$entityIds = array();
		while($arDealRow = $dbResultOnlyIds->GetNext())
		{
			$entityIds[] = (int) $arDealRow['ID'];
		}

		$arFilter = ['@ID' => $entityIds, 'CHECK_PERMISSIONS' => 'N'];
	}

	if (!empty($entityIds) || $pageNum === 1)
	{
		$navListOptions['QUERY_OPTIONS'] = $pageNum === 1 ? ['LIMIT' => $limit] : null;

		$dbResult = CCrmLead::GetListEx(
			$arSort,
			$arFilter,
			false,
			false,
			$arSelect,
			$navListOptions
		);

		$qty = 0;
		while($arLead = $dbResult->GetNext())
		{
			$arResult['LEAD'][$arLead['ID']] = $arLead;
			$arResult['LEAD_ID'][$arLead['ID']] = $arLead['ID'];
			$arResult['LEAD_UF'][$arLead['ID']] = [];
		}

		if (isset($arResult['LEAD']) && count($arResult['LEAD']) > 0)
		{
			$lastExportedId = end($arResult['LEAD'])['ID'];
		}
		else
		{
			$lastExportedId = -1;
		}
	}
	$enableNextPage = $pageNum * $pageSize <= $totalExportItems;
	unset($entityIds);
}
elseif(isset($arSort['nearest_activity']))
{
	$navListOptions = ($isInExportMode && !$isStExport)
		? array()
		: array_merge(
			$arOptions,
			array('QUERY_OPTIONS' => array('LIMIT' => $limit, 'OFFSET' => $pageSize * ($pageNum - 1)))
		);

	$navDbResult = CCrmActivity::GetEntityList(
		CCrmOwnerType::Lead,
		$userID,
		$arSort['nearest_activity'],
		$arFilter,
		false,
		$navListOptions
	);

	$qty = 0;
	while($arLead = $navDbResult->Fetch())
	{
		if($pageSize > 0 && ++$qty > $pageSize)
		{
			$enableNextPage = true;
			break;
		}

		$arResult['LEAD'][$arLead['ID']] = $arLead;
		$arResult['LEAD_ID'][$arLead['ID']] = $arLead['ID'];
		$arResult['LEAD_UF'][$arLead['ID']] = array();
	}

	//region Navigation data storing
	$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);

	$arResult['DB_FILTER'] = $arFilter;
	$arResult['DB_FILTER_HASH'] = GridContext::prepareFilterHash($arFilter);
	GridContext::setFilter($arResult['GRID_ID'], $arResult['DB_FILTER']);
	GridContext::setFilterHash($arResult['GRID_ID'], $arResult['DB_FILTER_HASH']);
	//endregion

	$entityIDs = array_keys($arResult['LEAD']);
	if(!empty($entityIDs))
	{
		//Permissions are already checked.
		$dbResult = CCrmLead::GetListEx(
			$arSort,
			array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			$arSelect,
			$arOptions
		);
		while($arLead = $dbResult->GetNext())
		{
			$arResult['LEAD'][$arLead['ID']] = $arLead;
		}
	}
}
else
{
	$addressSort = array();
	foreach($arSort as $k => $v)
	{
		if(strncmp($k, 'address', 7) === 0)
		{
			$addressSort[mb_strtoupper($k)] = $v;
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

		$navDbResult = \Bitrix\Crm\LeadAddress::getEntityList(
			\Bitrix\Crm\EntityAddressType::Primary,
			$addressSort,
			$arFilter,
			false,
			$navListOptions
		);

		$qty = 0;
		while($arLead = $navDbResult->Fetch())
		{
			if($pageSize > 0 && ++$qty > $pageSize)
			{
				$enableNextPage = true;
				break;
			}

			$arResult['LEAD'][$arLead['ID']] = $arLead;
			$arResult['LEAD_ID'][$arLead['ID']] = $arLead['ID'];
			$arResult['LEAD_UF'][$arLead['ID']] = array();
		}

		//region Navigation data storing
		$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
		$arResult['DB_FILTER'] = $arFilter;
		$arResult['DB_FILTER_HASH'] = GridContext::prepareFilterHash($arFilter);
		GridContext::setFilter($arResult['GRID_ID'], $arResult['DB_FILTER']);
		GridContext::setFilterHash($arResult['GRID_ID'], $arResult['DB_FILTER_HASH']);
		//endregion

		$entityIDs = array_keys($arResult['LEAD']);
		if(!empty($entityIDs))
		{
			$arSort['ID'] = array_shift(array_slice($addressSort, 0, 1));
			//Permissions are already checked.
			$dbResult = CCrmLead::GetListEx(
				$arSort,
				array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				$arSelect,
				$arOptions
			);
			while($arLead = $dbResult->GetNext())
			{
				$arResult['LEAD'][$arLead['ID']] = $arLead;
			}
		}
	}
	else
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

		$listEntity = \Bitrix\Crm\ListEntity\Entity::getInstance(\CCrmOwnerType::LeadName);
		$dbResult = $listEntity->getItems($parameters);

		$qty = 0;
		while($arLead = $dbResult->GetNext())
		{
			if($pageSize > 0 && ++$qty > $pageSize)
			{
				$enableNextPage = true;
				break;
			}

			$arResult['LEAD'][$arLead['ID']] = $arLead;
			$arResult['LEAD_ID'][$arLead['ID']] = $arLead['ID'];
			$arResult['LEAD_UF'][$arLead['ID']] = array();
		}

		//region Navigation data storing
		$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);

		$arResult['DB_FILTER'] = $arFilter;
		$arResult['DB_FILTER_HASH'] = GridContext::prepareFilterHash($arFilter);
		GridContext::setFilter($arResult['GRID_ID'], $arResult['DB_FILTER']);
		GridContext::setFilterHash($arResult['GRID_ID'], $arResult['DB_FILTER_HASH']);
		//endregion
	}
}

$arResult['STEXPORT_IS_FIRST_PAGE'] = $pageNum === 1 ? 'Y' : 'N';
$arResult['STEXPORT_IS_LAST_PAGE'] = $enableNextPage ? 'N' : 'Y';

$arResult['PAGINATION']['URL'] = $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'));
$enableExportEvent = $isInExportMode && HistorySettings::getCurrent()->isExportEventEnabled();

$now = time() + CTimeZone::GetOffset();
$entityAttrs = CCrmLead::GetPermissionAttributes(array_keys($arResult['LEAD']));

// check adding to exclusion list
$arResult['CAN_EXCLUDE'] = \Bitrix\Crm\Exclusion\Access::current()->canWrite();
$excludeApplicableList = array_keys($arResult['LEAD']);
if ($arResult['CAN_EXCLUDE'])
{
	\Bitrix\Crm\Exclusion\Applicability::filterEntities(\CCrmOwnerType::Lead, $excludeApplicableList);
	$arResult['CAN_EXCLUDE'] = !empty($excludeApplicableList);
}

$allDocumentStates = [];
if ($arResult['ENABLE_BIZPROC'] && !empty($arResult['LEAD']))
{
	$entityIds = array_map(function ($item)
		{
			return "LEAD_{$item['ID']}";
		},
		$arResult['LEAD']);

	$documentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentLead', 'LEAD'),
		array('crm', 'CCrmDocumentLead', $entityIds)
	);
	foreach ($documentStates as $stateId => $documentState)
	{
		if (isset($documentState['DOCUMENT_ID']))
		{
			$allDocumentStates[$documentState['DOCUMENT_ID'][2]][$stateId] = $documentState;
		}
	}
}

$observersDataProvider->appendResult($arResult['LEAD']);

$parentFieldValues = Crm\Service\Container::getInstance()->getParentFieldManager()->loadParentElementsByChildren(
	\CCrmOwnerType::Lead,
	$arResult['LEAD']
);

foreach($arResult['LEAD'] as &$arLead)
{
	$entityID = $arLead['ID'];
	if($enableExportEvent)
	{
		CCrmEvent::RegisterExportEvent(CCrmOwnerType::Lead, $entityID, $userID);
	}

	$arLead['CONVERSION_TYPE_ID'] = LeadConversionDispatcher::resolveTypeID($arLead);
	$arLead['CAN_EXCLUDE'] = in_array($arLead['ID'], $excludeApplicableList);

	if (!empty($arLead['WEB']) && mb_strpos($arLead['WEB'], '://') === false)
	{
		$arLead['WEB'] = 'http://' . $arLead['WEB'];
	}

	$currencyID = $arLead['CURRENCY_ID'] ?? CCrmCurrency::GetBaseCurrencyID();
	$arLead['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($arLead['~OPPORTUNITY'] ?? 0.0, $currencyID);

	$statusID = $arLead['STATUS_ID'] ?? '';
	$arLead['LEAD_STATUS_NAME'] = $arResult['STATUS_LIST'][$statusID] ?? htmlspecialcharsbx($statusID);

	$arLead['DELETE'] = $arLead['EDIT'] = !$arResult['INTERNAL'];

	if($arResult['INTERNAL'])
	{
		$arLead['DELETE'] = $arLead['EDIT'] = false;
	}
	else
	{
		$arLead['EDIT'] = CCrmLead::CheckUpdatePermission(
			$entityID,
			$userPermissions,
			array('ENTITY_ATTRS' => $entityAttrs)
		);

		$arLead['DELETE'] = CCrmLead::CheckDeletePermission(
			$entityID,
			$userPermissions,
			array('ENTITY_ATTRS' => $entityAttrs)
		);
	}

	$arLead['PATH_TO_LEAD_DETAILS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_LEAD_DETAILS'],
		array('lead_id' => $entityID)
	);

	if($arResult['ENABLE_SLIDER'])
	{
		$arLead['PATH_TO_LEAD_SHOW'] = $arLead['PATH_TO_LEAD_DETAILS'];
		$arLead['PATH_TO_LEAD_EDIT'] = CCrmUrlUtil::AddUrlParams(
			$arLead['PATH_TO_LEAD_DETAILS'],
			array('init_mode' => 'edit')
		);
	}
	else
	{
		$arLead['PATH_TO_LEAD_SHOW'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_LEAD_SHOW'],
			array('lead_id' => $entityID)
		);

		$arLead['PATH_TO_LEAD_EDIT'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_LEAD_EDIT'],
			array('lead_id' => $entityID)
		);
	}

	$arLead['PATH_TO_LEAD_COPY'] =
		\Bitrix\Crm\Integration\Analytics\Builder\Entity\CopyOpenEvent::createDefault(\CCrmOwnerType::Lead)
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
			->buildUri($arLead['PATH_TO_LEAD_EDIT'])
			->addParams([
				'copy' => 1,
			])
			->getUri()
	;

	$arLead['PATH_TO_LEAD_CONVERT'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_LEAD_CONVERT'],
		array('lead_id' => $entityID)
	);

	if($arLead['DELETE'])
	{
		$arLead['PATH_TO_LEAD_DELETE'] =  CHTTP::urlAddParams(
			$bInternal ? $currentPage : $arParams['PATH_TO_LEAD_LIST'],
			array(
				'action_'.$arResult['GRID_ID'] => 'delete',
				'ID' => $entityID,
				'sessid' => $arResult['SESSION_ID']
			)
		);
	}

	if($arResult['CAN_EXCLUDE'])
	{
		$arLead['PATH_TO_LEAD_EXCLUDE'] =  CHTTP::urlAddParams(
			$bInternal ? $currentPage : $arParams['PATH_TO_LEAD_LIST'],
			array(
				'action_'.$arResult['GRID_ID'] => 'exclude',
				'ID' => $entityID,
				'sessid' => $arResult['SESSION_ID']
			)
		);
	}

	$arLead['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'] ?? '',
		array('user_id' => $arLead['ASSIGNED_BY'] ?? null)
	);

	$arLead['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_BP'] ?? '',
		array('user_id' => $userID)
	);

	$arLead['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'] ?? '',
		array('user_id' => $arLead['CREATED_BY'] ?? null)
	);

	$arLead['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'] ?? '',
		array('user_id' => $arLead['MODIFY_BY'] ?? null)
	);

	$arLead['~CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arLead['~CREATED_BY_LOGIN'] ?? null,
			'NAME' => $arLead['~CREATED_BY_NAME'] ?? null,
			'SECOND_NAME' => $arLead['~CREATED_BY_SECOND_NAME'] ?? null,
			'LAST_NAME' => $arLead['~CREATED_BY_LAST_NAME'] ?? null
		),
		true, false
	);
	$arLead['CREATED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~CREATED_BY_FORMATTED_NAME'] ?? '');

	$arLead['~MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arLead['~MODIFY_BY_LOGIN'] ?? null,
			'NAME' => $arLead['~MODIFY_BY_NAME'] ?? null,
			'SECOND_NAME' => $arLead['~MODIFY_BY_SECOND_NAME'] ?? null,
			'LAST_NAME' => $arLead['~MODIFY_BY_LAST_NAME'] ?? null
		),
		true, false
	);
	$arLead['MODIFY_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~MODIFY_BY_FORMATTED_NAME'] ?? '');

	$sourceID = $arLead['~SOURCE_ID'] ?? '';
	$arLead['LEAD_SOURCE_NAME'] = $sourceID !== ''
		? ($arResult['SOURCE_LIST'][$sourceID] ?? htmlspecialcharsbx($sourceID))
		: '';

	$arLead['~LEAD_SOURCE_NAME'] = htmlspecialcharsback($arLead['~LEAD_SOURCE_NAME'] ?? '');

	$arLead['~LEAD_FORMATTED_NAME'] = CCrmLead::PrepareFormattedName([
		'HONORIFIC' => $arLead['~HONORIFIC'] ?? '',
		'NAME' => $arLead['~NAME'] ?? '',
		'SECOND_NAME' => $arLead['~SECOND_NAME'] ?? '',
		'LAST_NAME' => $arLead['~LAST_NAME'] ?? ''
	]);

	$arLead['LEAD_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~LEAD_FORMATTED_NAME'] ?? '');

	//region Client info
	$contactID = (int)($arLead['~CONTACT_ID'] ?? 0);
	if ($contactID > 0)
	{
		$arLead['~CONTACT_FORMATTED_NAME'] = $contactID <= 0
			? ''
			: CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => $arLead['~CONTACT_HONORIFIC'] ?? '',
					'NAME' => $arLead['~CONTACT_NAME'] ?? '',
					'LAST_NAME' => $arLead['~CONTACT_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arLead['~CONTACT_SECOND_NAME'] ?? ''
				)
			);
		$arLead['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~CONTACT_FORMATTED_NAME'] ?? '');

		$arLead['CONTACT_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_ID' => $contactID
		);

		if(!CCrmContact::CheckReadPermission($contactID, $userPermissions))
		{
			$arLead['CONTACT_INFO']['IS_HIDDEN'] = true;
		}
		else
		{
			$arLead['CONTACT_INFO'] =
				array_merge(
					$arLead['CONTACT_INFO'],
					array(
						'TITLE' => $arLead['~CONTACT_FORMATTED_NAME'] ?? ('['.$contactID.']'),
						'PREFIX' => "LEAD_{$arLead['~ID']}",
						'DESCRIPTION' => $arLead['~ASSOCIATED_COMPANY_TITLE'] ?? ''
					)
				);
		}
	}

	$companyID = (int)($arLead['~COMPANY_ID'] ?? 0);
	if ($companyID > 0)
	{
		$arLead['COMPANY_INFO'] = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_ID' => $companyID
		);

		if(!CCrmCompany::CheckReadPermission($companyID, $userPermissions))
		{
			$arLead['COMPANY_INFO']['IS_HIDDEN'] = true;
		}
		else
		{
			$arLead['COMPANY_INFO'] =
				array_merge(
					$arLead['COMPANY_INFO'],
					array(
						'TITLE' => $arLead['~ASSOCIATED_COMPANY_TITLE'] ?? ('['.$companyID.']'),
						'PREFIX' => "LEAD_{$arLead['~ID']}"
					)
				);
		}
	}

	if(isset($arLead['CONTACT_INFO']))
	{
		$arLead['CLIENT_INFO'] = $arLead['CONTACT_INFO'];
	}
	elseif(isset($arLead['COMPANY_INFO']))
	{
		$arLead['CLIENT_INFO'] = $arLead['COMPANY_INFO'];
	}
	//endregion

	$arLead['LEAD_LEGEND'] = isset($arLead['IS_RETURN_CUSTOMER']) && $arLead['IS_RETURN_CUSTOMER'] === 'Y'
		? GetMessage('CRM_COLUMN_IS_RETURN_CUSTOMER1')
		: '';

	if (!empty($arLead['OBSERVERS']))
	{
		$arLead['~OBSERVERS'] = $arLead['OBSERVERS'];
		$arLead['OBSERVERS'] = implode(
			"\n",
			array_column($arLead['~OBSERVERS'], 'OBSERVER_USER_FORMATTED_NAME')
		);
	}

	if ($arResult['ENABLE_TASK'])
	{
		$arLead['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $userID
				)
			),
			array(
				'UF_CRM_TASK' => "L_{$entityID}",
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX').' '),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_LEAD_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arLead['PATH_TO_QUOTE_ADD'] =
			CHTTP::urlAddParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_QUOTE_EDIT'],
					array('quote_id' => 0)
				),
				array('lead_id' => $entityID)
			);
	}

	if ($arResult['ENABLE_BIZPROC'])
	{
		$arLead['BIZPROC_STATUS'] = '';
		$arLead['BIZPROC_STATUS_HINT'] = '';

		$arDocumentStates = is_array($allDocumentStates["LEAD_{$entityID}"]) ?
			$allDocumentStates["LEAD_{$entityID}"] : [];

		$arLead['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_LEAD_SHOW'],
				array('lead_id' => $entityID)
			),
			array('CRM_LEAD_SHOW_V12_active_tab' => 'tab_bizproc')
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
				$arLead[$paramName] = $docTtl;
			}
			else
			{
				$arLead[$paramName] = '<a href="'.htmlspecialcharsbx($arLead['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';
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
					$arLead[$paramName] = $docTtl;
				}
				else
				{
					$arLead[$paramName] = '<a href="'.htmlspecialcharsbx($arLead['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';
				}
			}
			unset($arDocState);
		}
	}

	$arLead['ASSIGNED_BY_ID'] = $arLead['~ASSIGNED_BY_ID'] = isset($arLead['~ASSIGNED_BY']) ? (int)$arLead['~ASSIGNED_BY'] : 0;
	$arLead['~ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => isset($arLead['~ASSIGNED_BY_LOGIN']) ? $arLead['~ASSIGNED_BY_LOGIN'] : '',
			'NAME' => isset($arLead['~ASSIGNED_BY_NAME']) ? $arLead['~ASSIGNED_BY_NAME'] : '',
			'LAST_NAME' => isset($arLead['~ASSIGNED_BY_LAST_NAME']) ? $arLead['~ASSIGNED_BY_LAST_NAME'] : '',
			'SECOND_NAME' => isset($arLead['~ASSIGNED_BY_SECOND_NAME']) ? $arLead['~ASSIGNED_BY_SECOND_NAME'] : ''
		),
		true, false
	);
	$arLead['ASSIGNED_BY'] = htmlspecialcharsbx($arLead['~ASSIGNED_BY']);
	if(isset($arLead['~TITLE']))
	{
		$arLead['LEAD_SUMMARY'] = $arLead['~TITLE'];
	}

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		if ($sExportType === 'csv')
		{
			$arLead['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatTextComma(
				LeadAddress::mapEntityFields($arLead)
			);
		}
		else
		{
			$arLead['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
				LeadAddress::mapEntityFields($arLead)
			);
		}
	}

	if (isset($parentFieldValues[$entityID]))
	{
		foreach ($parentFieldValues[$entityID] as $parentEntityTypeId => $parentEntity)
		{
			if ($isInExportMode)
			{
				$arLead[$parentEntity['code']] = $parentEntity['title'];
			}
			else
			{
				$arLead[$parentEntity['code']] = $parentEntity['value'];
			}
		}
	}
}
unset($arLead);

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['LEAD'],
	$arResult['LEAD_UF'],
	($sExportType !== '' ? ', ' : '<br />'),
	$sExportType !== '',
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

$arResult['ENABLE_TOOLBAR'] = $arParams['ENABLE_TOOLBAR'] ?? false;
if ($arResult['ENABLE_TOOLBAR'])
{
	if($bInternal && isset($arParams['PARENT_ENTITY_TYPE_ID']) && isset($arParams['PARENT_ENTITY_ID']))
	{
		$parentEntityTypeId = (int)$arParams['PARENT_ENTITY_TYPE_ID'];
		$parentEntityId = (int)$arParams['PARENT_ENTITY_ID'];
		if (\CCrmOwnerType::IsDefined($parentEntityTypeId) && $parentEntityId > 0)
		{
			$arResult['PATH_TO_LEAD_ADD'] = Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
				\CCrmOwnerType::Lead,
				0,
				null,
				new Crm\ItemIdentifier($parentEntityTypeId, $parentEntityId)
			);
		}
	}
}

if (isset($arResult['LEAD_ID']) && !empty($arResult['LEAD_ID']))
{
	// try to load product rows
	$arProductRows = CCrmLead::LoadProductRows(array_keys($arResult['LEAD_ID']));
	foreach($arProductRows as $arProductRow)
	{
		$ownerID = $arProductRow['OWNER_ID'];
		if(!isset($arResult['LEAD'][$ownerID]))
		{
			continue;
		}

		$arEntity = &$arResult['LEAD'][$ownerID];
		if(!isset($arEntity['PRODUCT_ROWS']))
		{
			$arEntity['PRODUCT_ROWS'] = array();
		}
		$arEntity['PRODUCT_ROWS'][] = $arProductRow;
	}

	// adding crm multi field to result array
	$arFmList = array();
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arResult['LEAD_ID']));
	while($ar = $res->Fetch())
	{
		if (!$isInExportMode)
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		else
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = $ar['VALUE'];
		$arResult['LEAD'][$ar['ELEMENT_ID']]['~'.$ar['COMPLEX_ID']][] = $ar['VALUE'];
	}

	foreach ($arFmList as $elementId => $arFM)
	{
		foreach ($arFM as $complexId => $arComplexName)
		{
			$arResult['LEAD'][$elementId][$complexId] = implode(', ', $arComplexName);
		}
	}

	// checking access for operation
	$arLeadAttr = CCrmPerms::GetEntityAttr('LEAD', $arResult['LEAD_ID']);
	foreach ($arResult['LEAD_ID'] as $iLeadId)
	{
		if ($arResult['LEAD'][$iLeadId]['EDIT'])
			$arResult['LEAD'][$iLeadId]['EDIT'] = $userPermissions->CheckEnityAccess('LEAD', 'WRITE', $arLeadAttr[$iLeadId]);
		if ($arResult['LEAD'][$iLeadId]['DELETE'])
			$arResult['LEAD'][$iLeadId]['DELETE'] = $userPermissions->CheckEnityAccess('LEAD', 'DELETE', $arLeadAttr[$iLeadId]);

		$arResult['LEAD'][$iLeadId]['BIZPROC_LIST'] = [];

		if ($isBizProcInstalled && !class_exists(\Bitrix\Bizproc\Controller\Workflow\Starter::class))
		{
			foreach ($arBPData as $arBP)
			{
				if (!CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::StartWorkflow,
					$userID,
					array('crm', 'CCrmDocumentLead', 'LEAD_'.$arResult['LEAD'][$iLeadId]['ID']),
					array(
						'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
						'DocumentStates' => $arDocumentStates,
						'WorkflowTemplateId' => $arBP['ID'],
						'CreatedBy' => $arResult['LEAD'][$iLeadId]['~ASSIGNED_BY_ID'],
						'UserIsAdmin' => $isAdmin,
						'CRMEntityAttr' => $arLeadAttr
					)
				))
				{
					continue;
				}

				$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array(
						'lead_id' => $arResult['LEAD'][$iLeadId]['ID']
					)),
					array(
						'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => $arResult['SESSION_ID'],
						'CRM_LEAD_SHOW_V12_active_tab' => 'tab_bizproc', 'back_url' => $arParams['PATH_TO_LEAD_LIST'])
				);

				if (isset($arBP['HAS_PARAMETERS']))
				{
					$params = \Bitrix\Main\Web\Json::encode(array(
						'moduleId' => 'crm',
						'entity' => 'CCrmDocumentLead',
						'documentType' => 'LEAD',
						'documentId' => 'LEAD_'.$arResult['LEAD'][$iLeadId]['ID'],
						'templateId' => $arBP['ID'],
						'templateName' => $arBP['NAME'],
						'hasParameters' => $arBP['HAS_PARAMETERS']
					));
					$arBP['ONCLICK'] = 'BX.Bizproc.Starter.singleStart('.$params
						.', function(){BX.Main.gridManager.reload(\''.CUtil::JSEscape($arResult['GRID_ID']).'\');});';
				}

				$arResult['LEAD'][$iLeadId]['BIZPROC_LIST'][] = $arBP;
			}
		}
	}

	$entityBadges = new Bitrix\Crm\Kanban\EntityBadge(CCrmOwnerType::Lead, $arResult['LEAD_ID']);
	$entityBadges->appendToEntityItems($arResult['LEAD']);
}

if (!$isInExportMode)
{
	$arResult['CONVERSION'] = array();
	foreach(LeadConversionDispatcher::getAllConfigurations() as $conversionTypeID => $conversionConfig)
	{
		/** @var Bitrix\Crm\Conversion\LeadConversionConfig  $conversionConfig */
		$schemeID = $conversionConfig->getCurrentSchemeID();

		// we always need schemes for termination control rendering
		$arResult['CONVERSION']['SCHEMES'][$conversionTypeID] = array(
			'ORIGIN_URL' => $currentPage,
			'SCHEME_ID' => $schemeID,
			'SCHEME_NAME' => \Bitrix\Crm\Conversion\LeadConversionScheme::resolveName($schemeID),
			'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\LeadConversionScheme::getDescription($schemeID),
			'SCHEME_CAPTION' => GetMessage('CRM_LEAD_CREATE_ON_BASIS')
		);
	}
	if($arResult['CAN_CONVERT'])
	{
		$arResult['CONVERSION']['CONFIGS'] = LeadConversionDispatcher::getJavaScriptConfigurations();
		$arResult['CONVERTER_ID_PREFIX'] = $arResult['GRID_ID'];
	}

	$arResult['NEED_FOR_REBUILD_DUP_INDEX'] =
		$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
		$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] =
		$arResult['NEED_FOR_REFRESH_ACCOUNTING'] =
		$arResult['NEED_FOR_BUILD_TIMELINE'] =
		$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] = false;

	if(!$bInternal)
	{
		if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_SEARCH_CONTENT', 'N') === 'Y')
		{
			$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
		}

		if(\Bitrix\Crm\Agent\Semantics\LeadSemanticsRebuildAgent::getInstance()->isEnabled())
		{
			$arResult['NEED_FOR_REBUILD_LEAD_SEMANTICS'] = true;
		}

		$arResult['NEED_FOR_BUILD_TIMELINE'] = \Bitrix\Crm\Agent\Timeline\LeadTimelineBuildAgent::getInstance()->isEnabled();
		$arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT'] = \Bitrix\Crm\Agent\Search\TimelineSearchContentRebuildAgent::getInstance()->isEnabled();
		$arResult['NEED_FOR_REFRESH_ACCOUNTING'] = \Bitrix\Crm\Agent\Accounting\LeadAccountSyncAgent::getInstance()->isEnabled();

		$attributeRebuildAgent = \Bitrix\Crm\Agent\Security\LeadAttributeRebuildAgent::getInstance();
		$arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'] =
			$attributeRebuildAgent->isEnabled()
			&& ($attributeRebuildAgent->getProgressData()['TOTAL_ITEMS'] > 0)
		;

		if(CCrmPerms::IsAdmin())
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX', 'N') === 'Y')
			{
				$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = true;
			}
			if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR', 'N') === 'Y')
			{
				$arResult['PATH_TO_PRM_LIST'] = (string)Crm\Service\Container::getInstance()->getRouter()->getPermissionsUrl();
				$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = true;
			}
		}

		//region Show the process of indexing duplicates
		$isNeedToShowDupIndexProcess = false;
		$agent = LeadIndexRebuild::getInstance($userID);
		if ($agent->isActive())
		{
			$state = $agent->state()->getData();
			if (isset($state['STATUS']) && $state['STATUS'] === LeadIndexRebuild::STATUS_RUNNING)
			{
				$isNeedToShowDupIndexProcess = true;
			}
		}
		$arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS'] = $isNeedToShowDupIndexProcess;
		unset($isNeedToShowDupIndexProcess, $agent);
		//endregion Show the process of indexing duplicates

		//region Show the process of merge duplicates
		$isNeedToShowDupMergeProcess = false;
		$agent = LeadMerge::getInstance($userID);
		if ($agent->isActive())
		{
			$state = $agent->state()->getData();
			if (isset($state['STATUS']) && $state['STATUS'] === LeadMerge::STATUS_RUNNING)
			{
				$isNeedToShowDupMergeProcess = true;
			}
		}
		$arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS'] = $isNeedToShowDupMergeProcess;
		unset($isNeedToShowDupMergeProcess, $agent);
		//endregion Show the process of merge duplicates
	}

	//region Show the progress of data preparing for volatile duplicate types
	$isNeedToShowDupVolDataPrepare = false;
	$typeInfo = Volatile\TypeInfo::getInstance()->getIdsByEntityTypes([CCrmOwnerType::Lead]);
	if (isset($typeInfo[CCrmOwnerType::Lead]))
	{
		foreach ($typeInfo[CCrmOwnerType::Lead] as $id)
		{
			$agent = IndexRebuild::getInstance($id);
			if ($agent->isActive())
			{
				$state = $agent->state()->getData();
				/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
				if (isset($state['STATUS']) && $state['STATUS'] === IndexRebuild::STATUS_RUNNING)
				{
					$isNeedToShowDupVolDataPrepare = true;
				}
			}
		}
	}
	$arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE'] = $isNeedToShowDupVolDataPrepare;
	unset($isNeedToShowDupVolDataPrepare, $typeInfo, $id, $agent, $state);
	//endregion Show the progress of data preparing for volatile duplicate types

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');

	return $arResult['ROWS_COUNT'] ?? null;
}
else
{
	if ($isStExport)
	{
		$this->__templateName = '.default';

		$this->IncludeComponentTemplate($sExportType);

		return array(
			'PROCESSED_ITEMS' => count($arResult['LEAD']),
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
			Header('Content-Disposition: attachment;filename=leads.csv');
		}
		elseif ($sExportType === 'excel')
		{
			Header('Content-Type: application/vnd.ms-excel');
			Header('Content-Disposition: attachment;filename=leads.xls');
		}
		Header('Content-Type: application/octet-stream');
		Header('Content-Transfer-Encoding: binary');

		// add UTF-8 BOM marker
		echo chr(239).chr(187).chr(191);

		$this->IncludeComponentTemplate($sExportType);

		die();
	}
}
?>
