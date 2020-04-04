<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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

$isErrorOccured = false;
$errorMessage = '';

if (!$isErrorOccured && !CModule::IncludeModule('crm'))
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

if (!$isErrorOccured && !CAllCrmInvoice::installExternalEntities())
{
	$isErrorOccured = true;
}
if(!$isErrorOccured && !CCrmQuote::LocalComponentCausedUpdater())
{
	$isErrorOccured = true;
}

if (!$isErrorOccured && !CModule::IncludeModule('currency'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
	$isErrorOccured = true;
}
if (!$isErrorOccured && !CModule::IncludeModule('catalog'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
	$isErrorOccured = true;
}
if (!$isErrorOccured && !CModule::IncludeModule('sale'))
{
	$errorMessage = GetMessage('CRM_MODULE_NOT_INSTALLED_SALE');
	$isErrorOccured = true;
}


$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$isErrorOccured && !CCrmLead::CheckReadPermission(0, $userPermissions))
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

if (!$isErrorOccured && $isInExportMode && $userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'EXPORT'))
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
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\Format\LeadAddressFormatter;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Context\GridContext;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\Conversion\LeadConversionDispatcher;

$CCrmLead = new CCrmLead(false);
$CCrmBizProc = new CCrmBizProc('LEAD');

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();

$currentPage = $APPLICATION->GetCurPage();
$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $currentPage);
$arParams['PATH_TO_LEAD_DETAILS'] = CrmCheckPath('PATH_TO_LEAD_DETAILS', $arParams['PATH_TO_LEAD_DETAILS'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&details');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $currentPage.'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $currentPage.'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $currentPage.'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_LEAD_MERGE'] = CrmCheckPath('PATH_TO_LEAD_MERGE', $arParams['PATH_TO_LEAD_MERGE'], '/lead/merge/');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $currentPage.'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_LEAD_WIDGET'] = CrmCheckPath('PATH_TO_LEAD_WIDGET', $arParams['PATH_TO_LEAD_WIDGET'], $currentPage);
$arParams['PATH_TO_LEAD_KANBAN'] = CrmCheckPath('PATH_TO_LEAD_KANBAN', $arParams['PATH_TO_LEAD_KANBAN'], $currentPage);
$arParams['PATH_TO_LEAD_CALENDAR'] = CrmCheckPath('PATH_TO_LEAD_CALENDAR', $arParams['PATH_TO_LEAD_CALENDAR'], $currentPage);
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
$arResult['SESSION_ID'] = bitrix_sessid();
$arResult['NAVIGATION_CONTEXT_ID'] = isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arResult['DISABLE_NAVIGATION_BAR'] = isset($arParams['DISABLE_NAVIGATION_BAR']) ? $arParams['DISABLE_NAVIGATION_BAR'] : 'N';
$arResult['PRESERVE_HISTORY'] = isset($arParams['PRESERVE_HISTORY']) ? $arParams['PRESERVE_HISTORY'] : false;
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

$addressLabels = EntityAddress::getShortLabels();

//Show error message if required
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
	$errorID = strtolower($_GET['error']);
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

$enableWidgetFilter = false;
$widgetFilter = null;
if (isset($arParams['WIDGET_DATA_FILTER']) && isset($arParams['WIDGET_DATA_FILTER']['WG']) && $arParams['WIDGET_DATA_FILTER']['WG'] === 'Y')
{
	$enableWidgetFilter = true;
	$widgetFilter = $arParams['WIDGET_DATA_FILTER'];
}
elseif (!$bInternal && isset($_REQUEST['WG']) && strtoupper($_REQUEST['WG']) === 'Y')
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


$enableReportFilter = Main\Application::getInstance()->getContext()->getRequest()->getQuery('from_analytics');

if ($enableReportFilter === 'Y')
{
	$boardId = Main\Application::getInstance()->getContext()->getRequest()->getQuery('board_id');
	$externalFilterId = 'report_board_' . $boardId . '_filter';
}

$arResult['IS_EXTERNAL_FILTER'] = ($enableWidgetFilter || $enableCounterFilter);

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);

$CCrmFieldMulti = new CCrmFieldMulti();
$arResult['GRID_ID'] = 'CRM_LEAD_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['HONORIFIC'] = CCrmStatus::GetStatusListEx('HONORIFIC');
$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('STATUS');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
$arResult['WEBFORM_LIST'] = WebFormManager::getListNames();
$arResult['BOOLEAN_VALUES_LIST'] = array(
	'N' => GetMessage('CRM_COLUMN_BOOLEAN_VALUES_N'),
	'Y' => GetMessage('CRM_COLUMN_BOOLEAN_VALUES_Y')
);

// Please, uncomment if required
//$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();
$arResult['PERMS']['ADD']    = !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'DELETE');
$arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
$arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
$arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];
if($arResult['CALL_LIST_UPDATE_MODE'])
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
	$defaultFilter = array('SOURCE_ID' => array(), 'STATUS_ID' => array(), 'COMMUNICATION_TYPE' => array(), 'DATE_CREATE' => '', 'ASSIGNED_BY_ID' => '');

	$currentUserID = $arResult['CURRENT_USER_ID'];;
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_my_in_work' => array(
			'name' => GetMessage('CRM_PRESET_MY_IN_WORK'),
			'disallow_for_all' => true,
			'fields' => array_merge(
				$defaultFilter,
				array(
					'ASSIGNED_BY_ID_name' => $currentUserName,
					'ASSIGNED_BY_ID' => $currentUserID,
					'STATUS_SEMANTIC_ID' => array(Bitrix\Crm\PhaseSemantics::PROCESS)
				)
			)
		),
		'filter_in_work' => array(
			'name' => GetMessage('CRM_PRESET_ALL_IN_WORK'),
			'default' => true,
			'fields' => array_merge(
				$defaultFilter,
				array('STATUS_SEMANTIC_ID' => array(Bitrix\Crm\PhaseSemantics::PROCESS))
			)
		),
		'filter_closed' => array(
			'name' => GetMessage('CRM_PRESET_ALL_CLOSED'),
			'fields' => array_merge(
				$defaultFilter,
				array('STATUS_SEMANTIC_ID' => array(Bitrix\Crm\PhaseSemantics::SUCCESS))
			)
		)
	);
}
//endregion

if (!empty($externalFilterId))
{
	Main\Loader::includeModule('report');
	$arResult['GRID_ID'] = 'report_' . $boardId . '_grid';
	$filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], []);
}
else
{
	$filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
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

//region Filter initialization
if(!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'COMMENTS', 'COMPANY_TITLE');
	if ($externalFilterId)
	{
		$entityFilter = Crm\Filter\Factory::createEntityFilter(
			new Crm\Filter\LeadSettings(array('ID' => $arResult['GRID_ID']))
		);

		$fields = $entityFilter->getFields();

		foreach ($fields as $field)
		{
			$arResult['FILTER'][] = $field->toArray();
		}

		$arResult['FILTER_PRESETS'] = [];
	}
	else
	{
		$entityFilter = Crm\Filter\Factory::createEntityFilter(
			new Crm\Filter\LeadSettings(array('ID' => $arResult['GRID_ID']))
		);
	}


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

	if (!$externalFilterId)
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
		'name' => GetMessage('CRM_COLUMN_STATUS'),
		'sort' => 'status_sort',
		'width' => 200,
		'default' => true,
		'prevent_default' => false,
		'type' => 'list',
		'editable' => array('items' => $arResult['STATUS_LIST_WRITE'])
	),
);

// Don't display activities in INTERNAL mode.
if(!$bInternal)
{
	$arResult['HEADERS'][] = array(
		'id' => 'ACTIVITY_ID',
		'name' => GetMessage('CRM_COLUMN_ACTIVITY'),
		'sort' => 'nearest_activity',
		'width' => 150,
		'default' => true,
		'prevent_default' => false
	);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'LEAD_FORMATTED_NAME', 'name' => GetMessage('CRM_COLUMN_FULL_NAME'), 'sort' => 'last_name', 'default' => true, 'editable' => false),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'default' => false, 'editable' => true),
		array(
			'id' => 'HONORIFIC',
			'name' => GetMessage('CRM_COLUMN_HONORIFIC'),
			'sort' => false,
			'type' => 'list',
			'editable' => array(
				'items' => array('0' => GetMessage('CRM_HONORIFIC_NOT_SELECTED')) + CCrmStatus::GetStatusList('HONORIFIC')
			)
		),
		array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => false, 'editable' => true, 'class' => 'username'),
		array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME'), 'sort' => 'second_name', 'default' => false, 'editable' => true, 'class' => 'username'),
		array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME'), 'sort' => 'last_name', 'default' => false, 'editable' => true, 'class' => 'username'),
		array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_BIRTHDATE'), 'sort' => 'BIRTHDATE', 'first_order' => 'desc', 'default' => false, 'editable' => true, 'type' => 'date'),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'id', 'first_order' => 'desc', 'default' => true, 'editable' => false, 'class' => 'date'),
		array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE'), 'sort' => 'source_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('SOURCE')), 'type' => 'list')

	)
);

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS']);
if($isInExportMode)
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username'),
	array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_STATUS_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
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

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);

$arBPData = array();
if ($isBizProcInstalled)
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentLead', 'LEAD'));
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

$CCrmUserType->PrepareListFilterValues($arResult['FILTER'], $arFilter, $arResult['GRID_ID']);

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmLead::$sUFEntityID, $arFilter);

//region Apply Search Restrictions
$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
if(!$searchRestriction->isExceeded(CCrmOwnerType::Lead))
{
	Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Lead, $arFilter);
}
else
{
	$arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
		array('ENTITY_TYPE_ID' => CCrmOwnerType::Lead)
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
				CCrmOwnerType::Lead,
				$counterTypeID,
				0,
				Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST)
			);

			$arFilter += $counter->prepareEntityListFilter(
				array(
					'MASTER_ALIAS' => CCrmLead::TABLE_ALIAS,
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
$arImmutableFilters = array(
	'FM', 'ID', 'CURRENCY_ID',
	'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
	'PRODUCT_ROW_PRODUCT_ID',
	'HAS_PHONE', 'HAS_EMAIL',
	'STATUS_SEMANTIC_ID',
	'WEBFORM_ID', 'IS_RETURN_CUSTOMER', 'TRACKING_SOURCE_ID', 'TRACKING_CHANNEL_CODE',
	'SEARCH_CONTENT',
	'PRODUCT_ID', 'STATUS_ID', 'SOURCE_ID', 'COMPANY_ID', 'CONTACT_ID',
	'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
);

foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
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
	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if ($arMatch[1] === 'ACTIVE_TIME_PERIOD')
		{
			continue;
		}

		\Bitrix\Crm\UI\Filter\Range::prepareFrom($arFilter, $arMatch[1], $v);
	}
	elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if ($arMatch[1] === 'ACTIVE_TIME_PERIOD')
		{
			continue;
		}

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
	elseif ($k != 'ID' && $k != 'LOGIC' && $k != '__INNER_FILTER' && $k != '__JOINS' && $k != '__CONDITIONS' && strpos($k, 'UF_') !== 0 && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1)
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

//region POST & GET actions processing
if($actionData['ACTIVE'])
{
	$arErrors = array();
	$arCurrentUserGroups = $USER->GetUserGroupArray();
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

				$obRes = CCrmLead::GetListEx(array(), $arFilterDel, false, false, array('ID'));
				while($arLead = $obRes->Fetch())
				{
					$ID = $arLead['ID'];
					$arEntityAttr = $userPermissions->GetEntityAttr('LEAD', array($ID));
					if (!$userPermissions->CheckEnityAccess('LEAD', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr)
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
		}
		if($actionData['NAME'] == 'exclude')
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

				$obRes = CCrmLead::GetListEx(array(), $arFilterDel, false, false, array('ID'));
				while($arLead = $obRes->Fetch())
				{
					$ID = $arLead['ID'];

					if(!\Bitrix\Crm\Exclusion\Access::current()->canWrite())
					{
						continue;
					}

					\Bitrix\Crm\Exclusion\Store::addFromEntity(CCrmOwnerType::Lead, $ID);

					$arEntityAttr = $userPermissions->GetEntityAttr('LEAD', array($ID));
					if(CCrmLead::CheckDeletePermission($ID, $userPermissions, array('ENTITY_ATTRS' => $arEntityAttr)))
					{
						$DB->StartTransaction();

						if ($CCrmBizProc->Delete($ID, $arEntityAttr)
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
			}
		}
		elseif($actionData['NAME'] == 'edit')
		{
			if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$arEntityAttr = $userPermissions->GetEntityAttr('LEAD', array($ID));
					if (!$userPermissions->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$dbLead = CCrmLead::GetListEx(
						array(),
						array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('STATUS_ID'),
						array()
					);
					$arLead = $dbLead ? $dbLead->Fetch() : null;
					if(!is_array($arLead))
					{
						continue;
					}

					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable']) && (is_array($arHead['editable']) || $arHead['editable'] === true) && isset($arSrcData[$arHead['id']]))
						{
							$key = $arHead['id'];
							$arUpdateData[$key] = $arSrcData[$key];
						}
						if(isset($arUpdateData['LEAD_SUMMARY']))
						{
							if(!isset($arUpdateData['TITLE']))
							{
								$arUpdateData['TITLE'] = $arUpdateData['LEAD_SUMMARY'];
							}
							unset($arUpdateData['LEAD_SUMMARY']);
						}

					}

					// Skip leads in status 'CONVERTED'. 'CONVERTED' is system status and it can not be changed.
					if(isset($arUpdateData['STATUS_ID']) && $arLead['STATUS_ID'] === 'CONVERTED')
					{
						unset($arUpdateData['STATUS_ID']);
					}

					if (!empty($arUpdateData))
					{
						$DB->StartTransaction();

						if($CCrmLead->Update($ID, $arUpdateData))
						{
							$DB->Commit();

							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Lead,
								$ID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);

							//Region automation
							if (isset($arUpdateData['STATUS_ID']) && $arUpdateData['STATUS_ID'] != $arLead['STATUS_ID'])
								\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $ID);
							//end region
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
					$arTaskID[] = 'L_'.$ID;
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
						'back_url' => urlencode($arParams['PATH_TO_LEAD_LIST'])
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

					$dbRes = CCrmLead::GetListEx(
						array(),
						$arActionFilter,
						false,
						false,
						array('ID', 'STATUS_ID')
					);

					while($arLead = $dbRes->Fetch())
					{
						// Skip leads in status 'CONVERTED'. 'CONVERTED' is system status and it can not be changed.
						if(isset($arLead['STATUS_ID']) && $arLead['STATUS_ID'] === 'CONVERTED')
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$dbRes = CCrmLead::GetListEx(
						array(),
						array(
							'@ID'=> $actionData['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('ID', 'STATUS_ID')
					);

					while($arLead = $dbRes->Fetch())
					{
						// Skip leads in status 'CONVERTED'. 'CONVERTED' is system status and it can not be changed.
						if(isset($arLead['STATUS_ID']) && $arLead['STATUS_ID'] === 'CONVERTED')
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}

				$hasErrors = false;
				$arEntityAttr = $userPermissions->GetEntityAttr('LEAD', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$arUpdateData = array('STATUS_ID' => $actionData['STATUS_ID']);

					if($CCrmLead->Update($ID, $arUpdateData))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Lead,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);

						//Region automation
						\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $ID);
						//end region
					}
					else
					{
						$hasErrors = true;
					}
				}

				if($hasErrors)
				{
					$arResult['MESSAGES'][] = array(
						'TITLE' => GetMessage('CRM_SET_STATUS_NOT_COMPLETED_TITLE'),
						'TEXT' => GetMessage('CRM_SET_STATUS_NOT_COMPLETED_TEXT')
					);
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
					$dbRes = CCrmLead::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arLead = $dbRes->Fetch())
					{
						$arIDs[] = $arLead['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $userPermissions->GetEntityAttr('LEAD', $arIDs);


				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmLead->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Lead,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
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

					$dbRes = CCrmLead::GetListEx(
						array(),
						$arActionFilter,
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arLead = $dbRes->Fetch())
					{
						if(isset($arLead['OPENED']) && $arLead['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$dbRes = CCrmLead::GetListEx(
						array(),
						array(
							'@ID'=> $actionData['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arLead = $dbRes->Fetch())
					{
						if(isset($arLead['OPENED']) && $arLead['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}

				$arEntityAttr = $userPermissions->GetEntityAttr('LEAD', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$userPermissions->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();
					$arUpdateData = array('OPENED' => $isOpened);
					if($CCrmLead->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Lead,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif($actionData['NAME'] == 'refresh_account')
		{
			$agent = \Bitrix\Crm\Agent\Accounting\LeadAccountSyncAgent::getInstance();
			if ($actionData['ALL_ROWS'])
			{
				$agent->register();
				$agent->enable(true);
			}
			elseif(isset($actionData['ID']) && is_array($actionData['ID']))
			{
				$dbRes = CCrmLead::GetListEx(
					array(),
					array('@ID'=> $actionData['ID'], 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID')
				);

				$arIDs = array();
				while($arLead = $dbRes->Fetch())
				{
					$ID = (int)$arLead['ID'];
					if(CCrmLead::CheckUpdatePermission($ID, $userPermissions))
					{
						$arIDs[] = $ID;
					}
				}

				if(!empty($arIDs))
				{
					$agent->process($arIDs);
				}
			}
		}
		if (!$actionData['AJAX_CALL'])
		{
			$redirectUrl = $arParams['PATH_TO_LEAD_LIST'];
			if(!empty($arErrors))
			{
				$errorID = uniqid('crm_err_');
				$_SESSION[$errorID] = $arErrors;
				$redirectUrl = CHTTP::urlAddParams($redirectUrl, array('error' => $errorID));
			}
			LocalRedirect($redirectUrl);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
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
				\Bitrix\Crm\Exclusion\Store::addFromEntity(CCrmOwnerType::Lead, $ID);

				if(CCrmLead::CheckDeletePermission($ID, $userPermissions))
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
	&& (!isset($arParams['ENABLE_BIZPROC']) || strtoupper($arParams['ENABLE_BIZPROC']) === 'Y');

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
	$nTopCount = $arParams['LEAD_COUNT'];
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

if($arSort['date_create'])
{
	$arSort['id'] = $arSort['date_create'];
	unset($arSort['date_create']);
}

if(!empty($arSort) && !isset($arSort['id']))
{
	$arSort['id'] = reset($arSort);
}

$arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);

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

if ($isInExportMode && $isStExport && $pageNum === 1)
{
	$total = \CCrmLead::GetListEx(array(), $arFilter, array());
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
		if ($isInExportMode && $isStExport)
		{
			if (!is_array($arSort))
			{
				$arSort = array();
			}

			if (!isset($arSort['ID']))
			{
				$order = strtoupper($arSort['nearest_activity']);
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
			$addressSort[strtoupper($k)] = $v;
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
			\Bitrix\Crm\EntityAddress::Primary,
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

$addressFormatOptions = $sExportType === 'csv'
	? array('SEPARATOR' => AddressSeparator::Comma)
	: array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'NL2BR' => true);

$now = time() + CTimeZone::GetOffset();
$activitylessItems = array();
$entityAttrs = CCrmLead::GetPermissionAttributes(array_keys($arResult['LEAD']));

// check adding to exclusion list
$arResult['CAN_EXCLUDE'] = \Bitrix\Crm\Exclusion\Access::current()->canWrite();
$excludeApplicableList = array_keys($arResult['LEAD']);
if ($arResult['CAN_EXCLUDE'])
{
	\Bitrix\Crm\Exclusion\Applicability::filterEntities(\CCrmOwnerType::Lead, $excludeApplicableList);
	$arResult['CAN_EXCLUDE'] = !empty($excludeApplicableList);
}

foreach($arResult['LEAD'] as &$arLead)
{
	$entityID = $arLead['ID'];
	if($enableExportEvent)
	{
		CCrmEvent::RegisterExportEvent(CCrmOwnerType::Lead, $entityID, $userID);
	}

	$arLead['CONVERSION_TYPE_ID'] = LeadConversionDispatcher::resolveTypeID($arLead);
	$arLead['CAN_EXCLUDE'] = in_array($arLead['ID'], $excludeApplicableList);

	if (!empty($arLead['WEB']) && strpos($arLead['WEB'], '://') === false)
		$arLead['WEB'] = 'http://'.$arLead['WEB'];

	$currencyID =  isset($arLead['CURRENCY_ID']) ? $arLead['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arLead['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($arLead['~OPPORTUNITY'], $currencyID);

	$statusID = isset($arLead['STATUS_ID']) ? $arLead['STATUS_ID'] : '';
	$arLead['LEAD_STATUS_NAME'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;

	$sourceID = isset($arLead['SOURCE_ID']) ? $arLead['SOURCE_ID'] : '';
	$arLead['LEAD_SOURCE_NAME'] = isset($arResult['SOURCE_LIST'][$sourceID]) ? $arResult['SOURCE_LIST'][$sourceID] : $sourceID;

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

	$arLead['PATH_TO_LEAD_COPY'] =  CHTTP::urlAddParams(
		$arLead['PATH_TO_LEAD_EDIT'],
		array('copy' => 1)
	);

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
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arLead['ASSIGNED_BY'])
	);

	$arLead['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_BP'],
		array('user_id' => $userID)
	);

	$arLead['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arLead['CREATED_BY'])
	);

	$arLead['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array('user_id' => $arLead['MODIFY_BY'])
	);

	$arLead['~CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arLead['~CREATED_BY_LOGIN'],
			'NAME' => $arLead['~CREATED_BY_NAME'],
			'SECOND_NAME' => $arLead['~CREATED_BY_SECOND_NAME'],
			'LAST_NAME' => $arLead['~CREATED_BY_LAST_NAME']
		),
		true, false
	);
	$arLead['CREATED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~CREATED_BY_FORMATTED_NAME']);

	$arLead['~MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arLead['~MODIFY_BY_LOGIN'],
			'NAME' => $arLead['~MODIFY_BY_NAME'],
			'SECOND_NAME' => $arLead['~MODIFY_BY_SECOND_NAME'],
			'LAST_NAME' => $arLead['~MODIFY_BY_LAST_NAME']
		),
		true, false
	);
	$arLead['MODIFY_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~MODIFY_BY_FORMATTED_NAME']);

	$sourceID = isset($arLead['~SOURCE_ID']) ? $arLead['~SOURCE_ID'] : '';
	$arLead['LEAD_SOURCE_NAME'] = $sourceID !== '' ? (isset($arResult['SOURCE_LIST'][$sourceID]) ? $arResult['SOURCE_LIST'][$sourceID] : $sourceID) : '';
	$arLead['~LEAD_SOURCE_NAME'] = htmlspecialcharsback($arLead['~LEAD_SOURCE_NAME']);

	$arLead['~LEAD_FORMATTED_NAME'] = CCrmLead::PrepareFormattedName(
		array(
			'HONORIFIC' => isset($arLead['~HONORIFIC']) ? $arLead['~HONORIFIC'] : '',
			'NAME' => isset($arLead['~NAME']) ? $arLead['~NAME'] : '',
			'SECOND_NAME' => isset($arLead['~SECOND_NAME']) ? $arLead['~SECOND_NAME'] : '',
			'LAST_NAME' => isset($arLead['~LAST_NAME']) ? $arLead['~LAST_NAME'] : ''
		)
	);

	$arLead['LEAD_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~LEAD_FORMATTED_NAME']);

	//region Client info
	$contactID = isset($arLead['~CONTACT_ID']) ? intval($arLead['~CONTACT_ID']) : 0;
	if($contactID > 0)
	{
		$arLead['~CONTACT_FORMATTED_NAME'] = $contactID <= 0 ? ''
			: CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($arLead['~CONTACT_HONORIFIC']) ? $arLead['~CONTACT_HONORIFIC'] : '',
					'NAME' => isset($arLead['~CONTACT_NAME']) ? $arLead['~CONTACT_NAME'] : '',
					'LAST_NAME' => isset($arLead['~CONTACT_LAST_NAME']) ? $arLead['~CONTACT_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arLead['~CONTACT_SECOND_NAME']) ? $arLead['~CONTACT_SECOND_NAME'] : ''
				)
			);
		$arLead['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~CONTACT_FORMATTED_NAME']);

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
						'TITLE' => isset($arLead['~CONTACT_FORMATTED_NAME']) ? $arLead['~CONTACT_FORMATTED_NAME'] : ('['.$contactID.']'),
						'PREFIX' => "LEAD_{$arLead['~ID']}",
						'DESCRIPTION' => isset($arLead['~ASSOCIATED_COMPANY_TITLE']) ? $arLead['~ASSOCIATED_COMPANY_TITLE'] : ''
					)
				);
		}
	}
	$companyID = isset($arLead['~COMPANY_ID']) ? intval($arLead['~COMPANY_ID']) : 0;
	if($companyID > 0)
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
						'TITLE' => isset($arLead['~ASSOCIATED_COMPANY_TITLE']) ? $arLead['~ASSOCIATED_COMPANY_TITLE'] : ('['.$companyID.']'),
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
		? GetMessage('CRM_COLUMN_IS_RETURN_CUSTOMER1') : '';

	if(isset($arLead['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arLead['ACTIVITY_TIME']);
		$arLead['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arLead['~ACTIVITY_IS_CURRENT_DAY'] = $arLead['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
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

		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentLead', 'LEAD'),
			array('crm', 'CCrmDocumentLead', "LEAD_{$entityID}")
		);

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
		$arLead['FULL_ADDRESS'] = LeadAddressFormatter::format($arLead, $addressFormatOptions);
	}

	$userActivityID = isset($arLead['~ACTIVITY_ID']) ? intval($arLead['~ACTIVITY_ID']) : 0;
	$commonActivityID = isset($arLead['~C_ACTIVITY_ID']) ? intval($arLead['~C_ACTIVITY_ID']) : 0;
	if($userActivityID <= 0 && $commonActivityID <= 0)
	{
		$activitylessItems[] = $entityID;
	}
}
unset($arLead);

if(!empty($activitylessItems))
{
	$waitingInfos = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentInfos(CCrmOwnerType::Lead, $activitylessItems);
	foreach($waitingInfos as $waitingInfo)
	{
		$entityID = (int)$waitingInfo['OWNER_ID'];
		if(isset($arResult['LEAD'][$entityID]))
		{
			$arResult['LEAD'][$entityID]['~WAITING_TITLE'] = $waitingInfo['TITLE'];
		}
	}
}

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

		$arResult['LEAD'][$iLeadId]['BIZPROC_LIST'] = array();

		if ($isBizProcInstalled)
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
}

if (!$isInExportMode)
{
	$arResult['ANALYTIC_TRACKER'] = array(
		'lead_enabled' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isEnabled() ? 'Y' : 'N'
	);

	$arResult['CONVERSION'] = array();
	if($arResult['CAN_CONVERT'])
	{
		foreach(LeadConversionDispatcher::getAllConfigurations() as $conversionTypeID => $conversionConfig)
		{
			/** @var Bitrix\Crm\Conversion\LeadConversionConfig  $conversionConfig */
			$schemeID = $conversionConfig->getCurrentSchemeID();

			$arResult['CONVERSION']['SCHEMES'][$conversionTypeID] = array(
				'ORIGIN_URL' => $currentPage,
				'SCHEME_ID' => $schemeID,
				'SCHEME_NAME' => \Bitrix\Crm\Conversion\LeadConversionScheme::resolveName($schemeID),
				'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\LeadConversionScheme::getDescription($schemeID),
				'SCHEME_CAPTION' => GetMessage('CRM_LEAD_CREATE_ON_BASIS')
			);
		}
		$arResult['CONVERSION']['CONFIGS'] = LeadConversionDispatcher::getJavaScriptConfigurations();
	}

	$arResult['NEED_FOR_REBUILD_DUP_INDEX'] =
		$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
		$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] =
		$arResult['NEED_FOR_REFRESH_ACCOUNTING'] =
		$arResult['NEED_FOR_BUILD_TIMELINE'] = false;

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

		if(CCrmPerms::IsAdmin())
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX', 'N') === 'Y')
			{
				$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = true;
			}
			if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR', 'N') === 'Y')
			{
				$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
				$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = true;
			}
		}
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');
	return $arResult['ROWS_COUNT'];
}
else
{
	if ($isStExport)
	{
		$this->__templateName = '.default';

		$this->IncludeComponentTemplate($sExportType);

		return array(
			'PROCESSED_ITEMS' => count($arResult['LEAD']),
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
		if (defined('BX_UTF') && BX_UTF)
			echo chr(239).chr(187).chr(191);

		$this->IncludeComponentTemplate($sExportType);

		die();
	}
}
?>