<?php
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
$arResult['PATH_TO_FULL_VIEW'] = $arParams['PATH_TO_FULL_VIEW'] = CrmCheckPath('PATH_TO_FULL_VIEW', $arParams['PATH_TO_FULL_VIEW'], COption::GetOptionString('crm', 'path_to_activity_list'));
$arParams['PATH_TO_ACTIVITY_LIST'] = CrmCheckPath('PATH_TO_ACTIVITY_LIST', $arParams['PATH_TO_ACTIVITY_LIST'], COption::GetOptionString('crm', 'path_to_activity_list'));
$arParams['PATH_TO_ACTIVITY_WIDGET'] = CrmCheckPath('PATH_TO_ACTIVITY_WIDGET', $arParams['PATH_TO_ACTIVITY_WIDGET'], $APPLICATION->GetCurPage().'?widget');
$bindings = (isset($arParams['BINDINGS']) && is_array($arParams['BINDINGS'])) ? $arParams['BINDINGS'] : array();
// Check show mode
$showMode = isset($arParams['SHOW_MODE']) ? strtoupper(strval($arParams['SHOW_MODE'])) : 'ALL';
$arResult['SHOW_MODE'] = $showMode;
$arResult['PATH_TO_USER_PROFILE'] = $arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', isset($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : '', '/company/personal/user/#user_id#/');
// Check permissions (READ by default)
$permissionType = isset($arParams['PERMISSION_TYPE']) ? strtoupper((string)$arParams['PERMISSION_TYPE']) : 'READ';
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
$currentUserName = $arResult['CURRENT_USER_NAME'] = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);

$filterFieldPrefix = $arResult['FILTER_FIELD_PREFIX'] = $arResult['TAB_ID'] !== '' ? strtoupper($arResult['TAB_ID']).'_' : '';
$tabParamName = $arResult['FORM_ID'] !== '' ? $arResult['FORM_ID'].'_active_tab' : 'active_tab';
$activeTabID = isset($_REQUEST[$tabParamName]) ? $_REQUEST[$tabParamName] : '';

$topCount = $arResult['TOP_COUNT'] = isset($arParams['TOP_COUNT']) ? intval($arParams['TOP_COUNT']) : 0;
$arFilter = array();
$arResult['OWNER_UID'] = '';

$arBindingFilter = array();
for($i = count($bindings); $i >= 0; $i--)
{
	$binding = $bindings[$i];
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
	$arResult['OWNER_UID'] .=  strtolower(CCrmOwnerType::ResolveName($ownerTypeID)).($ownerID > 0 ? '_'.$ownerID : '');
}

if(!empty($arBindingFilter))
{
	$arFilter['BINDINGS'] = $arBindingFilter;
}

$arResult['UID'] = $arResult['GRID_ID'] = 'CRM_ACTIVITY_LIST_'.($arResult['PREFIX'] !== '' ? $arResult['PREFIX'] : strtoupper($arResult['OWNER_UID']));
$arResult['IS_INTERNAL'] = $arResult['OWNER_UID'] !== '';

$enableWidgetFilter = !$arResult['IS_INTERNAL'] && isset($_REQUEST['WG']) && strtoupper($_REQUEST['WG']) === 'Y';
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

if (intval($arParams['ITEM_COUNT']) <= 0)
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
$arResult['HEADERS'][] = array('id' => 'CREATED', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_CREATED'), 'sort' => 'CREATED', 'default' => false, 'editable' => false, 'class' => 'date');

$arResult['FILTER'] = array();
$arResult['FILTER_PRESETS'] = array();

$typeListItems = array(
	strval(CCrmActivityType::Meeting) => CCrmActivityType::ResolveDescription(CCrmActivityType::Meeting),
	strval(CCrmActivityType::Call).'.'.strval(CCrmActivityDirection::Incoming) => GetMessage('CRM_ACTIVITY_INCOMING_CALL'),
	strval(CCrmActivityType::Call).'.'.strval(CCrmActivityDirection::Outgoing) => GetMessage('CRM_ACTIVITY_OUTGOING_CALL'),
	strval(CCrmActivityType::Task) => CCrmActivityType::ResolveDescription(CCrmActivityType::Task),
	strval(CCrmActivityType::Email).'.'.strval(CCrmActivityDirection::Incoming) => GetMessage('CRM_ACTIVITY_INCOMING_EMAIL'),
	strval(CCrmActivityType::Email).'.'.strval(CCrmActivityDirection::Outgoing) => GetMessage('CRM_ACTIVITY_OUTGOING_EMAIL')
);

$providers = CCrmActivity::GetProviders();
foreach ($providers as $provider)
{
	if (!$provider::isActive())
		continue;

	$providerPresets = $provider::getTypesFilterPresets();
	foreach ($providerPresets as $preset)
	{
		$providerTypeId = isset($preset['PROVIDER_TYPE_ID']) ? $preset['PROVIDER_TYPE_ID'] : '*';
		$direction = isset($preset['DIRECTION']) ? $preset['DIRECTION'] : '*';
		$key = $provider::getId().'.'.$providerTypeId.'.'.$direction;
		$typeListItems[$key] = $preset['NAME'];
	}
}

if($arResult['TAB_ID'] === ''
	&& $_SERVER['REQUEST_METHOD'] === 'GET'
	&& isset($_GET['conv']))
{
	if(CCrmPerms::IsAdmin())
	{
		$conv = strtoupper($_GET['conv']);
		if($conv === 'EXEC_CAL')
		{
			CCrmActivityConverter::ConvertCalEvents(false, true);
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'Y');
		}
		elseif($conv === 'EXEC_TASK')
		{
			CCrmActivityConverter::ConvertTasks(false, true);
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'Y');
		}
		elseif($conv === 'SKIP_CAL')
		{
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'Y');
		}
		elseif($conv === 'SKIP_TASK')
		{
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'Y');
		}
		elseif($conv === 'RESET_CAL')
		{
			COption::RemoveOption('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS');
		}
		elseif($conv === 'RESET_TASK')
		{
			COption::RemoveOption('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS');
		}
	}

	LocalRedirect(CHTTP::urlDeleteParams($APPLICATION->GetCurPage(), array('conv')));
}

$arResult['FILTER'] = array(
	array('id' => "{$filterFieldPrefix}ID", 'name' => 'ID', 'default' => false),
	array('id' => "{$filterFieldPrefix}COMPLETED", 'name' => GetMessage('CRM_ACTIVITY_FILTER_COMPLETED'), 'type'=> 'list', 'items'=> array('Y' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_COMPLETED'), 'N' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_NOT_COMPLETED')), 'params' => array('multiple' => 'Y'), 'default' => true),
	array('id' => "{$filterFieldPrefix}TYPE_ID", 'name' => GetMessage('CRM_ACTIVITY_FILTER_TYPE_ID'), 'type'=> 'list', 'items'=> $typeListItems, 'params' => array('multiple' => 'Y'), 'default' => true),
	array('id' => "{$filterFieldPrefix}PRIORITY", 'name' => GetMessage('CRM_ACTIVITY_FILTER_PRIORITY'), 'type'=> 'list', 'items'=> CCrmActivityPriority::PrepareFilterItems(), 'params' => array('multiple' => 'Y'), 'default' => true),
	array(
		'id' => "{$filterFieldPrefix}RESPONSIBLE_ID",
		'name' => GetMessage('CRM_ACTIVITY_FILTER_RESPONSIBLE'),
		'default' => true,
		'type' => 'custom_entity',
		'selector' => array(
			'TYPE' => 'user',
			'DATA' => array('ID' => 'responsible_id', 'FIELD_ID' => "{$filterFieldPrefix}RESPONSIBLE_ID")
		)
	),
	array('id' => "{$filterFieldPrefix}START",  'name' => GetMessage('CRM_ACTIVITY_FILTER_START'), 'default' => false, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}END",  'name' => GetMessage('CRM_ACTIVITY_FILTER_END_2'), 'default' => false, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}DEADLINE",  'name' => GetMessage('CRM_ACTIVITY_FILTER_DEADLINE'), 'default' => true, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}CREATED",  'name' => GetMessage('CRM_ACTIVITY_FILTER_CREATED'), 'default' => true, 'type' => 'date')
);

if($displayReference)
{
	$arResult['FILTER'][] = array(
		'id' => "{$filterFieldPrefix}REFERENCE",
		'name' => GetMessage('CRM_ACTIVITY_COLUMN_REFERENCE'),
		'type' => 'custom_entity',
		'selector' => array(
			'TYPE' => 'crm_entity',
			'DATA' => array(
				'ID' => "REFERENCE",
				'FIELD_ID' => "{$filterFieldPrefix}REFERENCE",
				'ENTITY_TYPE_NAMES' => array(CCrmOwnerType::LeadName, CCrmOwnerType::DealName),
				'IS_MULTIPLE' => false
			)
		)
	);
}

if($displayClient)
{
	$arResult['FILTER'][] = array(
		'id' => "{$filterFieldPrefix}CLIENT",
		'name' => GetMessage('CRM_ACTIVITY_COLUMN_CLIENT'),
		'type' => 'custom_entity',
		'selector' => array(
			'TYPE' => 'crm_entity',
			'DATA' => array(
				'ID' => "CLIENT",
				'FIELD_ID' => "{$filterFieldPrefix}CLIENT",
				'ENTITY_TYPE_NAMES' => array(CCrmOwnerType::ContactName, CCrmOwnerType::CompanyName),
				'IS_MULTIPLE' => false
			)
		)
	);
}

$arResult['FILTER_PRESETS'] = array(
	'not_completed' => array(
		'name' => GetMessage('CRM_PRESET_NOT_COMPLETED'),
		'default' => true,
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selN' => 'N'),
			"{$filterFieldPrefix}RESPONSIBLE_ID_name" => $currentUserName,
			"{$filterFieldPrefix}RESPONSIBLE_ID" => $currentUserID
		)
	),
	'completed' => array(
		'name' => GetMessage('CRM_PRESET_COMPLETED'),
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
$filterOptions = new \Bitrix\Main\UI\Filter\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $gridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arGridFilter = $filterOptions->getFilter($arResult['FILTER']);
if(!$enableWidgetFilter)
{
	$arFilter += $arGridFilter;
}

// converts data from filter
Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Activity, $arFilter);

$arResult['GRID_CONTEXT'] = CCrmGridContext::Parse($arGridFilter);

if(!$arResult['GRID_CONTEXT']['FILTER_INFO']['IS_APPLIED'])
{
	$clearFilterKey = 'activity_list_clear_filter'.strtolower($arResult['UID']);
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
		$prefixLength = strlen($filterFieldPrefix);
		foreach($arGridFilter as $key=>&$value)
		{
			if(strpos($key, $filterFieldPrefix) === false)
			{
				$arFilter[$key] = $value;
			}
			else
			{
				$arFilter[substr($key, $prefixLength)] = $value;
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
	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
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

		if(strlen($v) > 0 && in_array($fieldID, $arDatetimeFields, true))
		{
			$arFilter['>='.$fieldID] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
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

		if(strlen($v) > 0 && in_array($fieldID, $arDatetimeFields, true))
		{
			if (!preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
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
		if ($arHeader['default'])
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

$dbRes = CCrmActivity::GetList($arSort, $arFilter, false, false, $arSelect, $arOptions);
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
		$arRes['DESCRIPTION_RAW'] = strip_tags(
			preg_replace(
				'/(<br[^>]*>)+/is'.BX_UTF_PCRE_MODIFIER, "\n",
				$bbCodeParser->convertText($description)
			)
		);
	}
	elseif($descriptionType === CCrmContentType::Html)
	{
		$arRes['DESCRIPTION_RAW'] =
			strip_tags(
				preg_replace(
					'/(<br[^>]*>)+/is'.BX_UTF_PCRE_MODIFIER,
					"\n",
					html_entity_decode($description)
				)
			);
	}
	else//CCrmContentType::PlainText and other
	{
		$arRes['DESCRIPTION_RAW'] = preg_replace(
			"/[\r\n]+/".BX_UTF_PCRE_MODIFIER,
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

	if($ownerID > 0 && ($ownerTypeID === CCrmOwnerType::Deal || $ownerTypeID === CCrmOwnerType::Lead))
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
		($by = 'ID'),
		($order = 'ASC'),
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
$arResult['ENABLE_EMAIL_ADD'] = !$arResult['READ_ONLY'] && IsModuleInstalled('subscribe');
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
	$arResult['USE_QUICK_FILTER'] = strtoupper($arResult['USE_QUICK_FILTER']) === 'Y';
}
$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : true;
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
		$disableStorageEdit = isset($_GET['disable_storage_edit']) && strtoupper($_GET['disable_storage_edit']) === 'Y';
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

	if(CCrmPerms::IsAdmin())
	{
		$curPage = $APPLICATION->GetCurPage();
		//Converting existing calendar events
		if(COption::GetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'N') !== 'Y')
		{
			if(CCrmActivityConverter::IsCalEventConvertigRequired())
			{
				$arResult['NEED_FOR_CONVERTING_OF_CALENDAR_EVENTS'] = true;
				$arResult['CAL_EVENT_CONV_EXEC_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'exec_cal'));
				$arResult['CAL_EVENT_CONV_SKIP_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'skip_cal'));
			}
			else
			{
				COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'Y');
			}
		}

		//Converting existing tasks
		if(COption::GetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'N') !== 'Y')
		{
			if(CCrmActivityConverter::IsTaskConvertigRequired())
			{
				$arResult['NEED_FOR_CONVERTING_OF_TASKS'] = true;
				$arResult['TASK_CONV_EXEC_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'exec_task'));
				$arResult['TASK_CONV_SKIP_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'skip_task'));
			}
			else
			{
				COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'Y');
			}
		}
	}
}

// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}
$this->IncludeComponentTemplate();
return $arResult['ROWS_COUNT'];
