<?php

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Bitrix\Main\UI\Extension::load("ui.tooltip");

/** @global CMain $APPLICATION */
global $APPLICATION, $USER;

// PARSE PARAMS
$arResult['PATH_TO_FULL_VIEW'] = $arParams['PATH_TO_FULL_VIEW'] = CrmCheckPath('PATH_TO_FULL_VIEW', $arParams['PATH_TO_FULL_VIEW'] ?? null, COption::GetOptionString('crm', 'path_to_activity_list'));
$arParams['PATH_TO_ACTIVITY_LIST'] = CrmCheckPath('PATH_TO_ACTIVITY_LIST', $arParams['PATH_TO_ACTIVITY_LIST'] ?? null, COption::GetOptionString('crm', 'path_to_activity_list'));
$arParams['PATH_TO_ACTIVITY_KANBAN'] = CrmCheckPath(
	'PATH_TO_ACTIVITY_KANBAN',
	$arParams['PATH_TO_ACTIVITY_KANBAN'] ?? null,
	COption::GetOptionString('crm', 'path_to_activity_kanban')
);
$arParams['PATH_TO_ACTIVITY_WIDGET'] = CrmCheckPath('PATH_TO_ACTIVITY_WIDGET', $arParams['PATH_TO_ACTIVITY_WIDGET'] ?? null, $APPLICATION->GetCurPage().'?widget');
$bindings = (isset($arParams['BINDINGS']) && is_array($arParams['BINDINGS'])) ? $arParams['BINDINGS'] : array();
// Check show mode
$showMode = isset($arParams['SHOW_MODE'])? mb_strtoupper(strval($arParams['SHOW_MODE'])) : 'ALL';
$arResult['SHOW_MODE'] = $showMode;
$arResult['PATH_TO_USER_PROFILE'] = $arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', isset($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : '', '/company/personal/user/#user_id#/');
// Check permissions (READ by default)
$permissionType = isset($arParams['PERMISSION_TYPE'])? mb_strtoupper((string)$arParams['PERMISSION_TYPE']) : 'READ';
if($permissionType !== 'READ' && $permissionType !== 'WRITE')
{
	$permissionType = 'READ';
}

$arResult['READ_ONLY'] = $permissionType == 'READ';

$arResult['PREFIX'] = isset($arParams['PREFIX']) ? strval($arParams['PREFIX']) : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['FORM_TYPE'] = isset($arParams['FORM_TYPE']) ? $arParams['FORM_TYPE'] : '';
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$arResult['FORM_URI'] = isset($arParams['FORM_URI']) ? $arParams['FORM_URI'] : '';

$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
$currentUserID = $arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$currentUserName = $arResult['CURRENT_USER_NAME'] = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE'] ?? null);

$filterFieldPrefix = $arResult['FILTER_FIELD_PREFIX'] = $arResult['TAB_ID'] !== '' ? mb_strtoupper($arResult['TAB_ID']).'_' : '';
$tabParamName = $arResult['FORM_ID'] !== '' ? $arResult['FORM_ID'].'_active_tab' : 'active_tab';
$activeTabID = isset($_REQUEST[$tabParamName]) ? $_REQUEST[$tabParamName] : '';

$topCount = $arResult['TOP_COUNT'] = isset($arParams['TOP_COUNT']) ? intval($arParams['TOP_COUNT']) : 0;
$arFilter = array();
$arResult['OWNER_UID'] = '';

$arBindingFilter = array();
foreach($bindings as $binding)
{
	$ownerTypeID = isset($binding['TYPE_ID']) ? intval($binding['TYPE_ID']) : 0;
	if($ownerTypeID <= 0)
	{
		$ownerTypeName = isset($binding['TYPE_NAME']) ? $binding['TYPE_NAME'] : '';
		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
		if($ownerTypeID <= 0)
		{
			continue;
		}
	}

	$innerFilter = array(
		'OWNER_TYPE_ID' => $ownerTypeID
	);

	$ownerID = isset($binding['ID']) ? intval($binding['ID']) : 0;
	if($ownerID > 0)
	{
		$innerFilter['OWNER_ID'] = $ownerID;
	}

	$arBindingFilter[] = $innerFilter;

	if($arResult['OWNER_UID'] !== '')
	{
		$arResult['OWNER_UID'] .= '_';
	}
	$arResult['OWNER_UID'] .= mb_strtolower(CCrmOwnerType::ResolveName($ownerTypeID)).($ownerID > 0? '_'.$ownerID : '');
}

if(!empty($arBindingFilter))
{
	$arFilter['BINDINGS'] = $arBindingFilter;
}

$isCustomSectionActivities = false;
if (\Bitrix\Main\Loader::includeModule('intranet') && method_exists(\Bitrix\Intranet\CustomSection\Manager::class, 'getSystemPagesCodes'))
{
	$customSectionCode = $arParams['CUSTOM_SECTION_CODE'] ?? null;
	$isCustomSectionActivities = IntranetManager::isCustomSectionExists($customSectionCode);
	if ($isCustomSectionActivities)
	{
		$arFilter['@OWNER_TYPE_ID'] = IntranetManager::getEntityTypesInCustomSection($customSectionCode);
	}
	else
	{
		$allEntityTypesInSections = IntranetManager::getEntityTypesInCustomSections();
		if (!empty($allEntityTypesInSections))
		{
			$arFilter['!@OWNER_TYPE_ID'] = $allEntityTypesInSections;
		}
	}
}

$arResult['UID'] = $arResult['GRID_ID'] =
	'CRM_ACTIVITY_LIST_'
	. ($arResult['PREFIX'] !== '' ? $arResult['PREFIX'] : mb_strtoupper($arResult['OWNER_UID']))
	. ($isCustomSectionActivities ? "_{$customSectionCode}" : '')
;
$arResult['IS_INTERNAL'] = $arResult['OWNER_UID'] !== '';

$enableWidgetFilter = !$arResult['IS_INTERNAL'] && isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y';
if($enableWidgetFilter)
{
	$dataSourceFilter = null;

	$dataSourceName = isset($_REQUEST['DS']) ? $_REQUEST['DS'] : '';
	if($dataSourceName !== '')
	{
		$dataSource = null;
		try
		{
			$dataSource = Bitrix\Crm\Widget\Data\DataSourceFactory::create(array('name' => $dataSourceName), $currentUserID, true);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}

		try
		{
			$dataSourceFilter = $dataSource ? $dataSource->prepareEntityListFilter($_REQUEST) : null;
			// request from analytic reports, disable control panel
			if(isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
			{
				$arResult['ENABLE_CONTROL_PANEL'] = false;
			}
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

$arResult['IS_EXTERNAL_FILTER'] = $enableWidgetFilter;
$arResult['SHOW_MISMATCH_NOTIFY'] = $enableWidgetFilter && isset($_REQUEST['SHOW_MISMATCH_NOTIFY']) && $_REQUEST['SHOW_MISMATCH_NOTIFY'] === 'Y';

if(count($arBindingFilter) === 1)
{
	$arBinding = $arBindingFilter[0];
	$arResult['OWNER_TYPE'] = CCrmOwnerType::ResolveName($arBinding['OWNER_TYPE_ID']);
	$arResult['OWNER_ID'] = isset($arBinding['OWNER_ID']) ? $arBinding['OWNER_ID'] : 0;
}
elseif(isset($arParams['OWNER']))
{
	$arResult['OWNER_TYPE'] = isset($arParams['OWNER']['TYPE_NAME']) ? $arParams['OWNER']['TYPE_NAME'] : '';
	$arResult['OWNER_ID'] = isset($arParams['OWNER']['ID']) ? $arParams['OWNER']['ID'] : 0;;
}
else
{
	$arResult['OWNER_TYPE'] = '';
	$arResult['OWNER_ID'] = 0;
}

$arResult['OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($arResult['OWNER_TYPE']);

if($showMode === 'COMPLETED')
{
	$arFilter['__INNER_FILTER_SHOW_MODE'] = array(
		'RESPONSIBLE_ID' => $currentUserID,
		'LOGIC' => 'AND',
		'COMPLETED' => 'Y'
	);
}
elseif($showMode === 'NOT_COMPLETED')
{
	$arFilter['__INNER_FILTER_SHOW_MODE'] = array(
		'RESPONSIBLE_ID' => $currentUserID,
		'LOGIC' => 'AND',
		'COMPLETED' => 'N'
	);
}
elseif($showMode === 'ALL_NOT_COMPLETED')
{
	$arFilter['COMPLETED'] = 'N';
}
elseif($showMode === 'NOT_COMPLETED_OR_RECENT_CHANGED')
{
	$arFilter['__INNER_FILTER_SHOW_MODE'] = array(
		'LOGIC' => 'AND',
		'RESPONSIBLE_ID' => $currentUserID,
		'__INNER_FILTER' => array(
			'LOGIC' => 'OR',
			'COMPLETED' => 'N',
			'>=LAST_UPDATED' => ConvertTimeStamp(AddToTimeStamp(array('HH' => -1), time() + CTimeZone::GetOffset()), 'FULL')
		)
	);
}

if (intval($arParams['ITEM_COUNT'] ?? 0) <= 0)
{
	$arParams['ITEM_COUNT'] = 20;
}

$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath(
	'PATH_TO_USER_PROFILE',
	isset($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : '',
	'/company/personal/user/#user_id#/'
);

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'type'=> 'number', 'name' => 'ID', 'sort' => 'id', 'width' => 60, 'default' => false, 'editable' => false, 'class' => 'minimal')
);

$arResult['HEADERS'][] = array('id' => 'SUBJECT', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_SUBJECT'), 'width' => 200, 'default' => true, 'editable' => true);
$arResult['HEADERS'][] = array('id' => 'START_TIME', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_START'), 'default' => false, 'editable' => true, 'class' => 'datetime');
$arResult['HEADERS'][] = array('id' => 'END_TIME', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_END_2'), 'default' => false, 'editable' => true, 'class' => 'datetime');
$arResult['HEADERS'][] = array('id' => 'DEADLINE', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_DEADLINE'), 'sort' => 'DEADLINE', 'default' => true, 'editable' => false, 'class' => 'datetime');
$displayReference = $arResult['DISPLAY_REFERENCE'] = isset($arParams['DISPLAY_REFERENCE']) ? $arParams['DISPLAY_REFERENCE'] : false;
$arResult['HEADERS'][] = array('id' => 'REFERENCE', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_REFERENCE'), 'default' => $displayReference, 'editable' => false);

$displayClient = $arResult['DISPLAY_CLIENT'] = isset($arParams['DISPLAY_CLIENT']) ? $arParams['DISPLAY_CLIENT'] : true;
$arResult['HEADERS'][] = array('id' => 'CLIENT', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_CLIENT'), 'default' => $displayClient, 'editable' => false);

$arResult['HEADERS'][] = array('id' => 'DESCRIPTION', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_DESCRIPTION'), 'default' => false, 'editable' => true);
$arResult['HEADERS'][] = array('id' => 'RESPONSIBLE_FULL_NAME', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_RESPONSIBLE'), 'sort' => 'RESPONSIBLE', 'default' => true, 'editable' => false, 'class' => 'username');
$arResult['HEADERS'][] = array('id' => 'COMPLETED', 'type'=> 'list', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_COMPLETED'), 'sort' => 'COMPLETED', 'default' => true, 'prevent_default' => false, 'editable' => array('items' => array('N' => GetMessage('CRM_ACTIVITY_STATUS_NOT_COMPLETED'), 'Y' => GetMessage('CRM_ACTIVITY_STATUS_COMPLETED'))));
$arResult['HEADERS'][] = array('id' => 'CREATED', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_CREATED'), 'sort' => 'ID', 'default' => false, 'editable' => false, 'class' => 'date');

$arResult['FILTER'] = array();
$arResult['FILTER_PRESETS'] = array();


$typeListItems = \Bitrix\Crm\Activity\Provider\Base::makeTypeCodeNameList();

// @todo replace to Bitrix\Crm\Filter\ActivityDataProvider
$arResult['FILTER'] = array(
	array('id' => "{$filterFieldPrefix}ID", 'name' => 'ID', 'default' => false),
	array('id' => "{$filterFieldPrefix}COMPLETED", 'name' => GetMessage('CRM_ACTIVITY_FILTER_COMPLETED'), 'type'=> 'list', 'items'=> array('Y' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_COMPLETED'), 'N' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_NOT_COMPLETED')), 'params' => array('multiple' => 'Y'), 'default' => true),
	array('id' => "{$filterFieldPrefix}TYPE_ID", 'name' => GetMessage('CRM_ACTIVITY_FILTER_TYPE_ID'), 'type'=> 'list', 'items'=> $typeListItems, 'params' => array('multiple' => 'Y'), 'default' => true),
	array('id' => "{$filterFieldPrefix}PRIORITY", 'name' => GetMessage('CRM_ACTIVITY_FILTER_PRIORITY'), 'type'=> 'list', 'items'=> CCrmActivityPriority::PrepareFilterItems(), 'params' => array('multiple' => 'Y'), 'default' => true),
	array(
		'id' => "{$filterFieldPrefix}RESPONSIBLE_ID",
		'name' => GetMessage('CRM_ACTIVITY_FILTER_RESPONSIBLE'),
		'default' => true,
		'type' => 'dest_selector',
		'params' => array(
			'context' => 'CRM_ACTIVITY_FILTER_RESPONSIBLE_ID',
			'multiple' => 'N',
			'contextCode' => 'U',
			'enableAll' => 'N',
			'enableSonetgroups' => 'N',
			'allowEmailInvitation' => 'N',
			'allowSearchEmailUsers' => 'N',
			'departmentSelectDisable' => 'Y',
			'isNumeric' => 'Y',
			'prefix' => 'U',
		)
	),
	array('id' => "{$filterFieldPrefix}START",  'name' => GetMessage('CRM_ACTIVITY_FILTER_START'), 'default' => false, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}END",  'name' => GetMessage('CRM_ACTIVITY_FILTER_END_2'), 'default' => false, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}DEADLINE",  'name' => GetMessage('CRM_ACTIVITY_FILTER_DEADLINE'), 'default' => true, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}CREATED",  'name' => GetMessage('CRM_ACTIVITY_FILTER_CREATED'), 'default' => true, 'type' => 'date')
);

if($displayReference)
{
	$referenceFilter = [
		'id' => "{$filterFieldPrefix}REFERENCE",
		'name' => GetMessage('CRM_ACTIVITY_COLUMN_REFERENCE'),
		'type' => 'dest_selector',
		'params' => [
			'apiVersion' => 3,
			'context' => 'CRM_ACTIVITY_FILTER_REFERENCE',
			'contextCode' => 'CRM',
			'useClientDatabase' => 'N',
			'enableAll' => 'N',
			'enableDepartments' => 'N',
			'enableUsers' => 'N',
			'enableSonetgroups' => 'N',
			'allowEmailInvitation' => 'N',
			'allowSearchEmailUsers' => 'N',
			'departmentSelectDisable' => 'Y',
			'enableCrm' => 'Y',
			'enableCrmLeads' => 'Y',
			'enableCrmDeals' => 'Y',
			'addTabCrmLeads' => 'Y',
			'addTabCrmDeals' => 'Y',
			'convertJson' => 'Y'
		]
	];

	if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
	{
		$referenceFilter['params']['addTabCrmSmartInvoices'] = 'Y';
	}

	$dynamicTypesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap()->load([
		'isLoadCategories' => false,
		'isLoadStages' => false,
	]);
	foreach ($dynamicTypesMap->getTypes() as $type)
	{
		$entityTypeId = $type->getEntityTypeId();
		$referenceFilter['params']['enableCrmDynamics'][$entityTypeId] = 'Y';
		$referenceFilter['params']['addTabCrmDynamics'][$entityTypeId] = 'Y';
		$code = 'DYNAMICS_' . $entityTypeId;
		$referenceFilter['params']['crmDynamicTitles'][$code] = htmlspecialcharsbx($type->getTitle());
	}

	$arResult['FILTER'][] = $referenceFilter;
}
if ($displayClient)
{
	$arResult['FILTER'][] = array(
		'id' => "{$filterFieldPrefix}CLIENT",
		'name' => GetMessage('CRM_ACTIVITY_COLUMN_CLIENT'),
		'type' => 'dest_selector',
		'params' => array(
			'apiVersion' => 3,
			'context' => 'CRM_ACTIVITY_FILTER_REFERENCE',
			'contextCode' => 'CRM',
			'useClientDatabase' => 'N',
			'enableAll' => 'N',
			'enableDepartments' => 'N',
			'enableUsers' => 'N',
			'enableSonetgroups' => 'N',
			'allowEmailInvitation' => 'N',
			'allowSearchEmailUsers' => 'N',
			'departmentSelectDisable' => 'Y',
			'enableCrm' => 'Y',
			'enableCrmContacts' => 'Y',
			'enableCrmCompanies' => 'Y',
			'addTabCrmCompanies' => 'Y',
			'addTabCrmContacts' => 'Y',
			'convertJson' => 'Y'
		)
	);
}

$arResult['FILTER_PRESETS'] = array(
	'not_completed' => array(
		'name' => GetMessage('CRM_PRESET_NOT_COMPLETED'),
		'default' => true,
		'disallow_for_all' => true,
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selN' => 'N'),
			"{$filterFieldPrefix}RESPONSIBLE_ID_name" => $currentUserName,
			"{$filterFieldPrefix}RESPONSIBLE_ID" => $currentUserID
		)
	),
	'completed' => array(
		'name' => GetMessage('CRM_PRESET_COMPLETED'),
		'disallow_for_all' => true,
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selY' => 'Y'),
			"{$filterFieldPrefix}RESPONSIBLE_ID_name" => $currentUserName,
			"{$filterFieldPrefix}RESPONSIBLE_ID" => $currentUserID
		)
	),
	'not_completed_all' => array(
		'name' => GetMessage('CRM_PRESET_NOT_COMPLETED_ALL'),
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selN' => 'N')
		)
	),
	'completed_all' => array(
		'name' => GetMessage('CRM_PRESET_COMPLETED_ALL'),
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selY' => 'Y')
		)
	)
);


// HACK: for clear filter by RESPONSIBLE_ID
if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	$filterItemID = "{$filterFieldPrefix}RESPONSIBLE_ID";
	$filterItemName = "{$filterFieldPrefix}RESPONSIBLE_ID_name";
	if(isset($_REQUEST[$filterItemName]) && $_REQUEST[$filterItemName] === '')
	{
		$_REQUEST[$filterItemID] = $_GET[$filterItemID] = array();
	}
}

//region Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() overvise the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);

if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['UID'];
	//We need to check grid 'controls'
	$controls = isset($_POST['controls']) && is_array($_POST['controls']) ? $_POST['controls'] : array();
	if ($actionData['METHOD'] == 'POST' && (isset($controls[$postAction]) || isset($_POST[$postAction])))
	{
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

		if(isset($_POST['ACTION_RESPONSIBLE_ID']) || isset($controls['ACTION_RESPONSIBLE_ID']))
		{
			$responsibleID = 0;
			if(isset($_POST['ACTION_RESPONSIBLE_ID']))
			{
				if(!is_array($_POST['ACTION_RESPONSIBLE_ID']))
				{
					$responsibleID = intval($_POST['ACTION_RESPONSIBLE_ID']);
				}
				elseif(count($_POST['ACTION_RESPONSIBLE_ID']) > 0)
				{
					$responsibleID = intval($_POST['ACTION_RESPONSIBLE_ID'][0]);
				}
				unset($_POST['ACTION_RESPONSIBLE_ID'], $_REQUEST['ACTION_RESPONSIBLE_ID']);
			}
			else
			{
				$responsibleID = (int)$controls['ACTION_RESPONSIBLE_ID'];
			}

			$actionData['RESPONSIBLE_ID'] = $responsibleID;
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_POST['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
		}
	}
}
//endregion

$arSort = array('DEADLINE' => 'ASC');
$arNavParams = array('nPageSize' => $arParams['ITEM_COUNT']);

if($topCount > 0)
{
	$arNavParams['nTopCount'] = $topCount;
}

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$filterOptions = new \Bitrix\Crm\Filter\UiFilterOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $gridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arGridFilter = $filterOptions->getFilter($arResult['FILTER']);
Task::transformTaskInFilter($arGridFilter);
if(!$enableWidgetFilter)
{
	$arFilter += $arGridFilter;
}

// converts data from filter
Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Activity, $arFilter);

$arResult['GRID_CONTEXT'] = CCrmGridContext::Parse($arGridFilter);

if(!$arResult['GRID_CONTEXT']['FILTER_INFO']['IS_APPLIED'])
{
	$clearFilterKey = 'activity_list_clear_filter'.mb_strtolower($arResult['UID']);
	if(isset($_REQUEST['clear_filter'])
		&& $_REQUEST['clear_filter'] !== '')
	{
		$_SESSION[$clearFilterKey] = $arResult['CLEAR_FILTER'] = true;
	}
	elseif(isset($_SESSION[$clearFilterKey]) && $_SESSION[$clearFilterKey])
	{
		$arResult['CLEAR_FILTER'] = true;
	}
}

if(empty($arGridFilter) && isset($arParams['DEFAULT_FILTER']) && is_array($arParams['DEFAULT_FILTER']))
{
	$arGridFilter = $arParams['DEFAULT_FILTER'];
}

$arResult['GRID_FILTER'] = $arGridFilter;

if(!empty($arGridFilter))
{
	// Clear SHOW_MODE filter if grid filter is enabled
	$showMode = $arResult['SHOW_MODE'] = 'ALL';
	if(isset($arFilter['__INNER_FILTER_SHOW_MODE']))
	{
		unset($arFilter['__INNER_FILTER_SHOW_MODE']);
	}

	if($filterFieldPrefix === '')
	{
		$arFilter = array_merge($arFilter, $arGridFilter);
	}
	else
	{
		$prefixLength = mb_strlen($filterFieldPrefix);
		foreach($arGridFilter as $key=>&$value)
		{
			if(mb_strpos($key, $filterFieldPrefix) === false)
			{
				$arFilter[$key] = $value;
			}
			else
			{
				$arFilter[mb_substr($key, $prefixLength)] = $value;
			}
		}
		unset($value);
	}
}
elseif($arResult['CLEAR_FILTER'])
{
	// Clear SHOW_MODE filter if grid filter is enabled
	$showMode = $arResult['SHOW_MODE'] = 'ALL';
	if(isset($arFilter['__INNER_FILTER_SHOW_MODE']))
	{
		unset($arFilter['__INNER_FILTER_SHOW_MODE']);
	}
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

$arImmutableFilters = array(
	'ID',
	'SEARCH_CONTENT',
	'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
);
$arDatetimeFields = array('CREATED', 'LAST_UPDATED', 'START_TIME', 'END_TIME', 'DEADLINE');
$arUserBindings = array();
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	if($k === 'REFERENCE' || $k === '=REFERENCE' || $k === 'CLIENT' || $k === '=CLIENT')
	{
		$ownerData = explode('_', $v);
		if(count($ownerData) > 1)
		{
			$ownerTypeID = CCrmOwnerType::ResolveID($ownerData[0]);
			$ownerID = intval($ownerData[1]);
			if($ownerTypeID > 0 && $ownerID > 0)
			{
				$arUserBindings[] =
					array(
						'OWNER_TYPE_ID' => $ownerTypeID,
						'OWNER_ID' => $ownerID
					);
			}
		}
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_from$/iu', $k, $arMatch))
	{
		$fieldID = $arMatch[1];
		if($fieldID === 'END')
		{
			$fieldID = 'END_TIME';
		}
		elseif($fieldID === 'START')
		{
			$fieldID = 'START_TIME';
		}

		if($v <> '' && in_array($fieldID, $arDatetimeFields, true))
		{
			$arFilter['>='.$fieldID] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_to$/iu', $k, $arMatch))
	{
		$fieldID = $arMatch[1];
		if($fieldID === 'END')
		{
			$fieldID = 'END_TIME';
		}
		elseif($fieldID === 'START')
		{
			$fieldID = 'START_TIME';
		}

		if($v <> '' && in_array($fieldID, $arDatetimeFields, true))
		{
			if (!preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/u', $v))
			{
				$v = CCrmDateTimeHelper::SetMaxDayTime($v);
			}
			$arFilter['<='.$fieldID] = $v;
		}
		unset($arFilter[$k]);
	}
}

if(!empty($arUserBindings))
{
	//override bindings
	$arFilter['BINDINGS'] = $arUserBindings;
}

$arGridSort = $gridOptions->GetSorting(
	array(
		'sort' => array('DEADLINE' => 'ASC'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arSort = $arGridSort['sort'];

$arResult['SORT'] = $arSort;
$arResult['SORT_VARS'] = $arGridSort['vars'];

//region Select
$arSelect = $gridOptions->GetVisibleColumns();
$arSelectMap = array_fill_keys($arSelect, true);

if (empty($arSelectMap))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'] ?? false)
		{
			$arSelectMap[$arHeader['id']] = true;
		}
	}
}

$arSelectMap['ID'] = true;
$arSelectMap['TYPE_ID'] = true;
$arSelectMap['DIRECTION'] = true;
$arSelectMap['OWNER_ID'] = true;
$arSelectMap['OWNER_TYPE_ID'] = true;
$arSelectMap['PROVIDER_ID'] = true;
$arSelectMap['PROVIDER_TYPE_ID'] = true;
$arSelectMap['ASSOCIATED_ENTITY_ID'] = true;

if(!isset($arSelectMap['RESPONSIBLE_ID']))
{
	$arSelectMap['RESPONSIBLE_ID'] = true;
}
if(!isset($arSelectMap['SUBJECT']))
{
	$arSelectMap['SUBJECT'] = true;
}
if(!isset($arSelectMap['PRIORITY']))
{
	$arSelectMap['PRIORITY'] = true;
}
if(!isset($arSelectMap['START_TIME']))
{
	$arSelectMap['START_TIME'] = true;
}
if(!isset($arSelectMap['END_TIME']))
{
	$arSelectMap['END_TIME'] = true;
}
if(!isset($arSelectMap['DEADLINE']))
{
	$arSelectMap['DEADLINE'] = true;
}
if(!isset($arSelectMap['COMPLETED']))
{
	$arSelectMap['COMPLETED'] = true;
}

if(isset($arSelectMap['DESCRIPTION']))
{
	$arSelectMap['DESCRIPTION_TYPE'] = true;
}

if(isset($arSelectMap['RESPONSIBLE_FULL_NAME']))
{
	$arSelectMap['RESPONSIBLE_ID'] = true;
	unset($arSelectMap['RESPONSIBLE_FULL_NAME']);
}
$arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);
//endregion

if(isset($arSort['RESPONSIBLE']))
{
	$assignedBySort = $arSort['RESPONSIBLE'];
	if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isUserNameSortingEnabled())
	{
		$arSort['RESPONSIBLE_LAST_NAME'] = $assignedBySort;
		$arSort['RESPONSIBLE_NAME'] = $assignedBySort;
	}
	else
	{
		$arSort['RESPONSIBLE_ID'] = $assignedBySort;
	}
	unset($arSort['RESPONSIBLE']);
}


if(!isset($arResult['GRID_CONTEXT']))
{
	$arResult['GRID_CONTEXT'] = CCrmGridContext::GetEmpty();
}
$arResult['GRID_FILTER_INFO'] = $arResult['GRID_CONTEXT']['FILTER_INFO'];

if (!$enableWidgetFilter)
{
	foreach($arFilter as $fieldID => $values)
	{
		if($fieldID !== 'TYPE_ID')
		{
			continue;
		}

		if(!is_array($values))
		{
			$values = array($values);
		}

		$innerFilter = array();

		foreach($values as $i => $val)
		{
			$ary = explode('.', $val, 3);
			if (count($ary) > 2)
			{
				$innerFilter["__INNER_FILTER_TYPE_$i"] = array(
					'LOGIC' => 'AND',
					'PROVIDER_ID' => (string)$ary[0]
				);
				if (!empty($ary[1]) && $ary[1] !== '*')
					$innerFilter["__INNER_FILTER_TYPE_$i"]['PROVIDER_TYPE_ID'] = (string)$ary[1];
				if (!empty($ary[2]) && $ary[2] !== '*')
					$innerFilter["__INNER_FILTER_TYPE_$i"]['DIRECTION'] = (int)$ary[2];
			}
			else if(count($ary) > 1)
			{
				$innerFilter["__INNER_FILTER_TYPE_$i"] = array(
					'LOGIC' => 'AND',
					'TYPE_ID' => intval($ary[0]),
					'DIRECTION' => intval($ary[1])
				);
			}
			else
			{
				$innerFilter["__INNER_FILTER_TYPE_$i"] = array(
					'LOGIC' => 'AND',
					'TYPE_ID' => intval($ary[0])
				);
			}
		}

		unset($arFilter['TYPE_ID']);
		$innerFilter['LOGIC'] = 'OR';
		$arFilter['__INNER_FILTER'] = $innerFilter;
		break;
	}
}

if ($permissionType === 'WRITE' && $actionData['ACTIVE'] && $actionData['METHOD'] == 'POST')
{
	$actionName = $actionData['NAME'];
	$forAll = $actionData['ALL_ROWS'];

	if ($actionName === 'delete')
	{
		$dbResult = null;
		if($forAll)
		{
			$dbResult = CCrmActivity::GetList(
				array(),
				$arFilter,
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID')
			);
		}
		elseif(isset($actionData['ID']) && !empty($actionData['ID']))
		{
			$dbResult = CCrmActivity::GetList(
				array(),
				array('@ID' => $actionData['ID']),
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID')
			);
		}

		if(is_object($dbResult))
		{
			while($arActivity = $dbResult->Fetch())
			{
				if(CCrmActivity::CheckItemDeletePermission($arActivity, $currentUserPermissions))
				{
					CCrmActivity::Delete($arActivity['ID']);
				}
			}
		}
	}
	elseif($actionName === 'edit')
	{
		if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
		{
			global $DB;
			foreach($actionData['FIELDS'] as $ID => $arSrcData)
			{
				//Modification of emails is not allowed
				$dbActivity = CCrmActivity::GetList(array(), array('=ID' => $ID), false, false, array('TYPE_ID'));
				$arActivity = $dbActivity ? $dbActivity->Fetch() : null;
				if(!(is_array($arActivity) && isset($arActivity['TYPE_ID']) && (int)$arActivity['TYPE_ID'] !== CCrmActivityType::Email))
				{
					continue;
				}

				if(!CCrmActivity::CheckItemUpdatePermission($arActivity, $currentUserPermissions))
				{
					continue;
				}

				$arUpdateData = array();
				foreach ($arResult['HEADERS'] as $arHead)
				{
					if (isset($arHead['editable']) && (is_array($arHead['editable']) || $arHead['editable'] === true) && isset($arSrcData[$arHead['id']]))
					{
						$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
					}
				}

				if (!empty($arUpdateData))
				{
					CCrmActivity::Update($ID, $arUpdateData);
				}
			}
		}
	}
	elseif($actionName === 'mark_as_completed' || $actionName === 'mark_as_not_completed')
	{
		$completed = $actionName === 'mark_as_completed' ? 'Y' : 'N';
		if($forAll)
		{
			$arActionFilter = $arFilter;
			$dbResult = CCrmActivity::GetList(
				array(),
				$arActionFilter,
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'TYPE_ID', 'ASSOCIATED_ENTITY_ID', 'COMPLETED')
			);

			while($arActivity = $dbResult->Fetch())
			{
				if($arActivity['COMPLETED'] === $completed)
				{
					continue;
				}

				if(!CCrmActivity::CheckCompletePermission(
					$arActivity['OWNER_TYPE_ID'],
					$arActivity['OWNER_ID'],
					$currentUserPermissions,
					array('FIELDS' => $arActivity)))
				{
					continue;
				}

				$arActivity['COMPLETED'] = $completed;
				CCrmActivity::Update($arActivity['ID'], $arActivity);
			}
		}
		elseif(isset($actionData['ID']) && !empty($actionData['ID']))
		{
			$arActionFilter = $arFilter;
			$arActionFilter['@ID'] = $actionData['ID'];
			$dbResult = CCrmActivity::GetList(
				array(),
				$arActionFilter,
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'TYPE_ID', 'ASSOCIATED_ENTITY_ID', 'COMPLETED')
			);
			while($arActivity = $dbResult->Fetch())
			{
				if($arActivity['COMPLETED'] === $completed)
				{
					continue;
				}


				if(!CCrmActivity::CheckCompletePermission(
					$arActivity['OWNER_TYPE_ID'],
					$arActivity['OWNER_ID'],
					$currentUserPermissions,
					array('FIELDS' => $arActivity)))
				{
					continue;
				}

				$arActivity['COMPLETED'] = $completed;
				CCrmActivity::Update($arActivity['ID'], $arActivity);
			}
		}
	}
	elseif ($actionName === 'assign_to')
	{
		if(isset($actionData['RESPONSIBLE_ID']) && $actionData['RESPONSIBLE_ID'] > 0)
		{
			$responsibleID = $actionData['RESPONSIBLE_ID'];
			$dbResult = null;
			if ($forAll)
			{
				$dbResult = CCrmActivity::GetList(
					array(),
					$arFilter,
					false,
					false,
					array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'RESPONSIBLE_ID')
				);
			}
			elseif(isset($actionData['ID']) && !empty($actionData['ID']))
			{
				$dbResult = CCrmActivity::GetList(
					array(),
					array_merge($arFilter, array('@ID' => $actionData['ID'])),
					false,
					false,
					array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'RESPONSIBLE_ID')
				);
			}

			if(is_object($dbResult))
			{
				while($arItem = $dbResult->Fetch())
				{
					$currentResponsibleID = isset($arItem['RESPONSIBLE_ID']) ? (int)$arItem['RESPONSIBLE_ID'] : 0;
					if($currentResponsibleID === $responsibleID)
					{
						continue;
					}

					if(CCrmActivity::CheckItemUpdatePermission($arItem, $currentUserPermissions))
					{
						CCrmActivity::Update($arItem['ID'], array('RESPONSIBLE_ID' => $responsibleID));
					}
				}
			}
		}
	}

	if (!isset($_POST['AJAX_CALL']))
	{
		LocalRedirect($APPLICATION->GetCurPageParam(urlencode($tabParamName).'='.urlencode($arResult['TAB_ID']), array($tabParamName)));
	}
//	else
//	{
//		$arResult['AJAX_RELOAD_ITEMS'] = true;
//	}
}

//region Navigation data initialization
$pageNum = 0;
$pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['ITEM_COUNT']);
$enableNextPage = false;
if(isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
{
	$pageNum = 1;
}
elseif($pageSize > 0 && isset($_REQUEST['page']))
{
	$pageNum = (int)$_REQUEST['page'];
	if($pageNum < 0)
	{
		//Backward mode
		$offset = -($pageNum + 1);
		$total = CCrmActivity::GetList(array(), $arFilter, array());
		$pageNum = (int)(ceil($total / $pageSize)) - $offset;
		if($pageNum <= 0)
		{
			$pageNum = 1;
		}
	}
}
if($pageNum > 0)
{
	if(!isset($_SESSION['CRM_PAGINATION_DATA']))
	{
		$_SESSION['CRM_PAGINATION_DATA'] = array();
	}
	$_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
}
else
{
	if(!$arResult['IS_INTERNAL']
		&& !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
		&& isset($_SESSION['CRM_PAGINATION_DATA'])
		&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']])
		&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM']))
	{
		$pageNum = (int)$_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
	}

	if($pageNum <= 0)
	{
		$pageNum = 1;
	}
}
//endregion

$arOptions = isset($arNavParams['nTopCount']) && $arNavParams['nTopCount'] > 0
	? array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount']))
	: array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)));

$arSelect[] = 'PROVIDER_PARAMS';

$dbRes = CCrmActivity::GetList(
	$arSort,
	$arFilter,
	false,
	false,
	$arSelect,
	$arOptions
);
$arResult['ITEMS'] = array();
$bbCodeParser = new CTextParser();
$responsibleIDs = array();
$ownerMap = array();
$items = array();

$qty = 0;
while($arRes = $dbRes->GetNext())
{
	if(++$qty > $pageSize)
	{
		$enableNextPage = true;
		break;
	}

	$itemID = intval($arRes['~ID']);
	$ownerID = intval($arRes['~OWNER_ID']);
	$ownerTypeID = intval($arRes['~OWNER_TYPE_ID']);

	if($arResult['READ_ONLY'])
	{
		$arRes['CAN_EDIT'] = $arRes['CAN_COMPLETE'] = $arRes['CAN_DELETE'] = false;
	}
	else
	{
		if($ownerID > 0 && $ownerTypeID > 0)
		{
			$arRes['CAN_EDIT'] = CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID, $currentUserPermissions);
			$arRes['CAN_COMPLETE'] = (int)$arRes['~TYPE_ID'] !== CCrmActivityType::Task
				? $arRes['CAN_EDIT']
				: CCrmActivity::CheckCompletePermission(
					$ownerTypeID,
					$ownerID,
					$currentUserPermissions,
					array('FIELDS' => $arRes)
				);
			$arRes['CAN_DELETE'] = CCrmActivity::CheckDeletePermission($ownerTypeID, $ownerID, $currentUserPermissions);
		}
		else
		{
			$arRes['CAN_EDIT'] = $arRes['CAN_COMPLETE'] = $arRes['CAN_DELETE'] = true;
		}
	}

	$responsibleID = isset($arRes['~RESPONSIBLE_ID'])
		? intval($arRes['~RESPONSIBLE_ID']) : 0;
	$arRes['~RESPONSIBLE_ID'] = $responsibleID;
	if($responsibleID <= 0)
	{
		$arRes['RESPONSIBLE'] = false;
		$arRes['RESPONSIBLE_FULL_NAME'] = '';
		$arRes['PATH_TO_RESPONSIBLE'] = '';
	}
	elseif(!in_array($responsibleID, $responsibleIDs, true))
	{
		$responsibleIDs[] = $responsibleID;
	}

	//$arRes['SETTINGS'] = (isset($arRes['~SETTINGS']) && $arRes['~SETTINGS'] !== '') ? unserialize($arRes['~SETTINGS']) : array();
	//Lazy communications loading
	//$arRes['COMMUNICATIONS'] = CCrmActivity::GetCommunications($itemID);
	$arRes['COMMUNICATIONS_LOADED'] = false;

	$description = isset($arRes['~DESCRIPTION']) ? $arRes['~DESCRIPTION'] : '';
	$descriptionType = isset($arRes['DESCRIPTION_TYPE']) ? intval($arRes['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;

	if($descriptionType === CCrmContentType::BBCode)
	{
		$arRes['DESCRIPTION_RAW'] = \Bitrix\Crm\Format\TextHelper::convertHtmlToText(
			$bbCodeParser->convertText($description),
		);
	}
	elseif($descriptionType === CCrmContentType::Html)
	{
		$arRes['DESCRIPTION_RAW'] =
			strip_tags(
				preg_replace(
					'/(<br[^>]*>)+/isu',
					"\n",
					html_entity_decode($description)
				)
			);
	}
	else//CCrmContentType::PlainText and other
	{
		$arRes['DESCRIPTION_RAW'] = preg_replace(
			"/[\r\n]+/u",
			"<br/>",
			htmlspecialcharsbx($description)
		);
	}
	$arRes['ENABLE_DESCRIPTION_CUT'] = true;

	if(isset($arRes['~DEADLINE']) && CCrmDateTimeHelper::IsMaxDatabaseDate($arRes['~DEADLINE']))
	{
		$arRes['~DEADLINE'] = $arRes['DEADLINE'] = '';
	}

	$ownerTypeID = isset($arRes['OWNER_TYPE_ID']) ? (int)$arRes['OWNER_TYPE_ID'] : 0;
	$ownerID = isset($arRes['OWNER_ID']) ? (int)$arRes['OWNER_ID'] : 0;

	if ($ownerID > 0
		&& (
			$ownerTypeID === CCrmOwnerType::Deal
			|| $ownerTypeID === CCrmOwnerType::Lead
			|| $ownerTypeID === CCrmOwnerType::Quote
			|| \CCrmOwnerType::isUseDynamicTypeBasedApproach($ownerTypeID)
		)
	)
	{
		if(!isset($ownerMap[$ownerTypeID]))
		{
			$ownerMap[$ownerTypeID] = array();
		}

		if(!isset($ownerMap[$ownerTypeID][$ownerID]))
		{
			$ownerMap[$ownerTypeID][$ownerID] = array();
		}
	}

	$items[$itemID] = $arRes;
}

$arResult['OWNER_INFOS'] = array();
foreach($ownerMap as $ownerTypeID => $ownerInfos)
{
	CCrmOwnerType::PrepareEntityInfoBatch($ownerTypeID, $ownerInfos, false);
	$arResult['OWNER_INFOS'][$ownerTypeID] = $ownerInfos;
}

if($displayClient && !empty($items))
{
	$clientInfos = CCrmActivity::PrepareClientInfos(array_keys($items));

	foreach($clientInfos as $itemID => &$clientInfo)
	{
		$items[$itemID]['CLIENT_INFO'] = $clientInfo;
	}
	unset($clientInfo);
}

$arResult['ITEMS'] = array_values($items);

$responsibleInfos = array();
if(!empty($responsibleIDs))
{
	$dbUsers = CUser::GetList(
		'ID',
		'ASC',
		array('ID' => implode('||', $responsibleIDs)),
		array('FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'TITLE'))
	);

	$userNameFormat = CSite::GetNameFormat(false);
	while($arUser = $dbUsers->Fetch())
	{
		$userID = intval($arUser['ID']);

		$responsibleInfo = array('USER' => $arUser);
		$responsibleInfo['FULL_NAME'] = CUser::FormatName($userNameFormat, $arUser, true, false);
		$responsibleInfo['HTML_FULL_NAME'] = htmlspecialcharsbx($responsibleInfo['FULL_NAME']);
		$responsibleInfo['PATH'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_PROFILE'],
			array('user_id' => $userID)
		);
		$responsibleInfos[$userID] = &$responsibleInfo;
		unset($responsibleInfo);
	}

	foreach($arResult['ITEMS'] as &$item)
	{
		$responsibleID = $item['~RESPONSIBLE_ID'];
		if(!isset($responsibleInfos[$responsibleID]))
		{
			continue;
		}

		$responsibleInfo = $responsibleInfos[$responsibleID];

		$item['RESPONSIBLE'] = $responsibleInfo['USER'];
		$item['~RESPONSIBLE_FULL_NAME'] = $responsibleInfo['FULL_NAME'];
		$item['RESPONSIBLE_FULL_NAME'] = $responsibleInfo['HTML_FULL_NAME'];
		$item['PATH_TO_RESPONSIBLE'] = $responsibleInfo['PATH'];
	}
	unset($item);
}

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

$arResult['SHOW_TOP'] = isset($arParams['SHOW_TOP']) && intval($arParams['SHOW_TOP']) > 0 ? intval($arParams['SHOW_TOP']) : 0;
$arResult['ENABLE_TASK_ADD'] = !$arResult['READ_ONLY'] && IsModuleInstalled('tasks');
$arResult['ENABLE_CALENDAR_EVENT_ADD'] = !$arResult['READ_ONLY'] && IsModuleInstalled('calendar');
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : 'N';
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['USE_QUICK_FILTER'] = isset($arParams['USE_QUICK_FILTER']) ? $arParams['USE_QUICK_FILTER'] : false;
$arResult['NAVIGATION_CONTEXT_ID'] = isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arResult['PRESERVE_HISTORY'] = isset($arParams['PRESERVE_HISTORY']) ? $arParams['PRESERVE_HISTORY'] : false;
if(is_string($arResult['USE_QUICK_FILTER']))
{
	$arResult['USE_QUICK_FILTER'] = mb_strtoupper($arResult['USE_QUICK_FILTER']) === 'Y';
}

$arResult['ENABLE_TOOLBAR'] = (bool)($arParams['ENABLE_TOOLBAR'] ?? true);
$arResult['ENABLE_CREATE_TOOLBAR_BUTTON'] = $arResult['ENABLE_TOOLBAR'] && !$isCustomSectionActivities;

$arResult['ENABLE_WEBDAV'] = IsModuleInstalled('webdav');
if(!$arResult['ENABLE_WEBDAV'])
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

if($_SERVER['REQUEST_METHOD'] === 'GET' && !Bitrix\Main\Grid\Context::isInternalRequest())
{
	if(isset($_GET['open_view']))
	{
		$itemID = intval($_GET['open_view']);
		if($itemID > 0)
		{
			$arResult['OPEN_VIEW_ITEM_ID'] = $itemID;
		}
	}
	elseif(isset($_GET['open_edit']))
	{
		$itemID = intval($_GET['open_edit']);
		if($itemID > 0)
		{
			$arResult['OPEN_EDIT_ITEM_ID'] = $itemID;
		}
		$disableStorageEdit = isset($_GET['disable_storage_edit']) && mb_strtoupper($_GET['disable_storage_edit']) === 'Y';
		if($disableStorageEdit)
		{
			$arResult['DISABLE_STORAGE_EDIT'] = true;
		}
	}
}

$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
	$arResult['NEED_FOR_BUILD_TIMELINE'] =
	$arResult['NEED_FOR_CONVERTING_OF_CALENDAR_EVENTS'] =
	$arResult['NEED_FOR_CONVERTING_OF_TASKS'] = false;

if($arResult['TAB_ID'] === '')
{
	if(COption::GetOptionString('crm', '~CRM_REBUILD_ACTIVITY_SEARCH_CONTENT', 'N') === 'Y')
	{
		$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
	}

	if(COption::GetOptionString('crm', '~CRM_BUILD_ACTIVITY_TIMELINE', 'N') === 'Y')
	{
		$arResult['NEED_FOR_BUILD_TIMELINE'] = true;
	}
}

$APPLICATION->SetTitle(htmlspecialcharsbx(Loc::getMessage('CRM_ACTIVITY_MAIN_TITLE')));
// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}
$this->IncludeComponentTemplate();
return ($arResult['ROWS_COUNT'] ?? null);
