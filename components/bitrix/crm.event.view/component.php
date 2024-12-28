<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;

/** @var CrmEventViewComponent $this */

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityType = $arParams['ENTITY_TYPE'] ?? null;
$toolsManager = Container::getInstance()->getIntranetToolsManager();
$isAvailable = (
	$entityType
		? $toolsManager->checkEntityTypeAvailability(CCrmOwnerType::ResolveID($entityType))
		: $toolsManager->checkCrmAvailability()
);
if(!$isAvailable)
{
	print AvailabilityManager::getInstance()->getCrmInaccessibilityContent();

	return;
}

if (!(CCrmPerms::IsAccessEnabled()))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_EVENT_LIST'] = CrmCheckPath(
	'PATH_TO_EVENT_LIST',
		$arParams['PATH_TO_EVENT_LIST'] ?? null,
	$APPLICATION->GetCurPage()
);
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath(
	'PATH_TO_LEAD_SHOW',
		$arParams['PATH_TO_LEAD_SHOW'] ?? null,
	$APPLICATION->GetCurPage() . '?lead_id=#lead_id#&show'
);
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath(
	'PATH_TO_DEAL_SHOW',
		$arParams['PATH_TO_DEAL_SHOW'] ?? null,
	$APPLICATION->GetCurPage() . '?deal_id=#deal_id#&show'
);
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath(
	'PATH_TO_QUOTE_SHOW',
		$arParams['PATH_TO_QUOTE_SHOW'] ?? null,
	$APPLICATION->GetCurPage() . '?quote_id=#quote_id#&show'
);
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath(
	'PATH_TO_CONTACT_SHOW',
		$arParams['PATH_TO_CONTACT_SHOW'] ?? null,
	$APPLICATION->GetCurPage() . '?contact_id=#contact_id#&show'
);
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath(
	'PATH_TO_COMPANY_SHOW',
		$arParams['PATH_TO_COMPANY_SHOW'] ?? null,
	$APPLICATION->GetCurPage() . '?company_id=#company_id#&show'
);
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath(
	'PATH_TO_USER_PROFILE',
		$arParams['PATH_TO_USER_PROFILE'] ?? null,
	'/company/personal/user/#user_id#/'
);

$arResult['EVENT_ENTITY_LINK'] = isset($arParams['EVENT_ENTITY_LINK']) && $arParams['EVENT_ENTITY_LINK'] == 'Y'? 'Y': 'N';
$arResult['EVENT_HINT_MESSAGE'] = isset($arParams['EVENT_HINT_MESSAGE']) && $arParams['EVENT_HINT_MESSAGE'] == 'N'? 'N': 'Y';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['INTERNAL'] = isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y';
$arResult['SHOW_INTERNAL_FILTER'] = isset($arParams['SHOW_INTERNAL_FILTER']) && $arParams['SHOW_INTERNAL_FILTER'] === 'Y';
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL']? 'N': 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['PATH_TO_EVENT_DELETE'] =  CHTTP::urlAddParams($arParams['PATH_TO_EVENT_LIST'], array('sessid' => bitrix_sessid()));

$arResult['SESSION_ID'] = bitrix_sessid();


if(isset($arParams['ENABLE_CONTROL_PANEL']))
{
	$arResult['ENABLE_CONTROL_PANEL'] = (bool)$arParams['ENABLE_CONTROL_PANEL'];
}
else
{
	$arResult['ENABLE_CONTROL_PANEL'] = !(isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y');
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

if (!RestrictionManager::isHistoryViewPermitted())
{
	$this->__templateName = '.default';
	$this->IncludeComponentTemplate('restrictions');
	return;
}

$arFilter = array();
$arSort = array();

$bInternal = false;
if (($arParams['INTERNAL'] ?? 'N') == 'Y' || ($arParams['GADGET'] ?? 'N') == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
$arResult['INTERNAL_EDIT'] = false;
if (isset($arParams['INTERNAL_EDIT']) && $arParams['INTERNAL_EDIT'] === 'Y')
{
	$arResult['INTERNAL_EDIT'] = true;
}
$arResult['GADGET'] =  isset($arParams['GADGET']) && $arParams['GADGET'] == 'Y'? 'Y': 'N';
$isInGadgetMode = $arResult['GADGET'] === 'Y';

$entityType = isset($arParams['ENTITY_TYPE']) ? $arParams['ENTITY_TYPE'] : '';
$entityTypeID = CCrmOwnerType::ResolveID($entityType);

$entityId = (isset($arParams['ENTITY_ID']) && !is_array($arParams['ENTITY_ID']) && $arParams['ENTITY_ID'] > 0)
	? $arParams['ENTITY_ID']
	: null;

if ($entityTypeID !== CCrmOwnerType::Undefined && $entityId > 0)
{
	if (!\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($entityTypeID, $entityId))
	{
		$arResult['ERROR'] = GetMessage('CRM_PERMISSION_DENIED');
		$this->IncludeComponentTemplate();
		return;
	}
	$arFilter['CHECK_PERMISSIONS'] = 'N';
}

if ($entityType !== '')
{
	$arFilter['ENTITY_TYPE'] = $arResult['ENTITY_TYPE'] = $entityType;
}

if (isset($arParams['ENTITY_ID']))
{
	if (is_array($arParams['ENTITY_ID']))
	{
		array_walk(
			$arParams['ENTITY_ID'],
			function (&$v) {
				$v = (int)$v;
			}
		);
		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = $arParams['ENTITY_ID'];
	}
	elseif ($arParams['ENTITY_ID'] > 0)
	{
		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = (int)$arParams['ENTITY_ID'];
	}
}
else
{
	$ownerTypeID = isset($arParams['OWNER_TYPE']) ? CCrmOwnerType::ResolveID($arParams['OWNER_TYPE']) : CCrmOwnerType::Undefined;
	$ownerID = isset($arParams['OWNER_ID']) ? (int)$arParams['OWNER_ID'] : 0;
	if($ownerID > 0 && $ownerTypeID === CCrmOwnerType::Company && $entityTypeID === CCrmOwnerType::Contact)
	{
		$dbRes = CCrmContact::GetListEx(array(), array('COMPANY_ID' => $ownerID), false, false, array('ID'));
		$arContactID = array();
		while($arRow = $dbRes->Fetch())
		{
			$arContactID[] = (int)$arRow['ID'];
		}

		if(empty($arContactID))
		{
			return 0;
		}

		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = $arContactID;
	}
}

if(isset($arParams['EVENT_COUNT']))
	$arResult['EVENT_COUNT'] = intval($arParams['EVENT_COUNT']) > 0? intval($arParams['EVENT_COUNT']): 20;
else
	$arResult['EVENT_COUNT'] = 20;

$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['VIEW_ID'] = isset($arParams['VIEW_ID']) ? $arParams['VIEW_ID'] : '';

$filterFieldPrefix = '';
if($bInternal)
{
	if($arResult['VIEW_ID'] !== '')
	{
		$filterFieldPrefix = $arResult['VIEW_ID'].'_';
	}
	elseif($arResult['TAB_ID'] !== '')
	{
		$filterFieldPrefix = mb_strtoupper($arResult['TAB_ID']).'_';
	}
}

$arResult['FILTER_FIELD_PREFIX'] = $filterFieldPrefix;

$tabParamName = $arResult['FORM_ID'] !== '' ? $arResult['FORM_ID'].'_active_tab' : 'active_tab';
$activeTabID = isset($_REQUEST[$tabParamName]) ? $_REQUEST[$tabParamName] : '';

$arResult['GRID_ID'] = $arResult['INTERNAL'] ? 'CRM_INTERNAL_EVENT_LIST' : 'CRM_EVENT_LIST';
if($arResult['VIEW_ID'] !== '')
{
	$arResult['GRID_ID'] .= '_'.$arResult['VIEW_ID'];
}
elseif($arResult['TAB_ID'] !== '')
{
	$arResult['GRID_ID'] .= '_'.mb_strtoupper($arResult['TAB_ID']);
}


if(check_bitrix_sessid())
{
	//Deletion of DELETE event is disabled
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['action_'.$arResult['GRID_ID']]))
	{
		if ($_REQUEST['action_'.$arResult['GRID_ID']] == 'delete' && isset($_REQUEST['ID']))
		{
			$CCrmEvent =  new CCrmEvent;

			if(is_array($_REQUEST['ID']))
			{
				foreach($_REQUEST['ID'] as $ID)
				{
					$ID = (int)$ID;
					if($ID > 0 && CCrmEvent::GetEventType($ID) !== $CCrmEvent::TYPE_DELETE)
					{
						$CCrmEvent->Delete($ID);
					}
				}
			}
			elseif($_REQUEST['ID'] > 0)
			{
				$ID = (int)$_REQUEST['ID'];
				if($ID > 0 && CCrmEvent::GetEventType($ID) !== $CCrmEvent::TYPE_DELETE)
				{
					$CCrmEvent->Delete($ID);
				}
			}
			unset($_REQUEST['ID']); // otherwise the filter will work
		}

		if (!$arResult['IS_AJAX_CALL'])
			LocalRedirect('?'.$arParams['FORM_ID'].'_active_tab=tab_event');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action_'.$arResult['GRID_ID']]))
	{
		if ($_GET['action_'.$arResult['GRID_ID']] == 'delete')
		{
			$CCrmEvent =  new CCrmEvent;
			$ID = (int)$_GET['ID'];
			if($ID > 0 && CCrmEvent::GetEventType($ID) !== $CCrmEvent::TYPE_DELETE)
			{
				$CCrmEvent->Delete($ID);
			}
			unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}

		if (!$arResult['IS_AJAX_CALL'])
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab='.$arResult['TAB_ID'] : '');
	}
}


$arResult['FILTER'] = array();
$arResult['FILTER_PRESETS'] = array();
if (!$arResult['INTERNAL'] || $arResult['SHOW_INTERNAL_FILTER'])
{
	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => 'ID', 'default' => false),
	);
	$arResult['FILTER2LOGIC'] = array('EVENT_DESC');

	if(!$arResult['INTERNAL'])
	{
		$enabledEntityTypeNames = array();
		$currentUserPerms = CCrmPerms::GetCurrentUserPermissions();
		if(!$currentUserPerms->HavePerm(\CCrmOwnerType::LeadName, BX_CRM_PERM_NONE, 'READ'))
		{
			$enabledEntityTypeNames[] = \CCrmOwnerType::LeadName;
		}
		if(!$currentUserPerms->HavePerm(\CCrmOwnerType::ContactName, BX_CRM_PERM_NONE, 'READ'))
		{
			$enabledEntityTypeNames[] = \CCrmOwnerType::ContactName;
		}
		if(!$currentUserPerms->HavePerm(\CCrmOwnerType::CompanyName, BX_CRM_PERM_NONE, 'READ'))
		{
			$enabledEntityTypeNames[] = \CCrmOwnerType::CompanyName;
		}
		if(CCrmDeal::CheckReadPermission(0, $currentUserPerms))
		{
			$enabledEntityTypeNames[] = \CCrmOwnerType::DealName;
		}
		if(!$currentUserPerms->HavePerm(\CCrmOwnerType::QuoteName, BX_CRM_PERM_NONE, 'READ'))
		{
			$enabledEntityTypeNames[] = \CCrmOwnerType::QuoteName;
		}

		if(!empty($enabledEntityTypeNames))
		{
			$destSelectorParams = array(
				'apiVersion' => 3,
				'context' => 'CRM_EVENT_FILTER_ENTITY',
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
				'convertJson' => 'Y'
			);

			$entityTypeCounter = 0;
			foreach($enabledEntityTypeNames as $entityTypeName)
			{
				switch($entityTypeName)
				{
					case \CCrmOwnerType::LeadName:
						$destSelectorParams['enableCrmLeads'] = 'Y';
						$destSelectorParams['addTabCrmLeads'] = 'Y';
						$entityTypeCounter++;
						break;
					case \CCrmOwnerType::DealName:
						$destSelectorParams['enableCrmDeals'] = 'Y';
						$destSelectorParams['addTabCrmDeals'] = 'Y';
						$entityTypeCounter++;
						break;
					case \CCrmOwnerType::ContactName:
						$destSelectorParams['enableCrmContacts'] = 'Y';
						$destSelectorParams['addTabCrmContacts'] = 'Y';
						$entityTypeCounter++;
						break;
					case \CCrmOwnerType::CompanyName:
						$destSelectorParams['enableCrmCompanies'] = 'Y';
						$destSelectorParams['addTabCrmCompanies'] = 'Y';
						$entityTypeCounter++;
						break;
					case \CCrmOwnerType::QuoteName:
						$destSelectorParams['enableCrmQuotes'] = 'Y';
						$destSelectorParams['addTabCrmQuotes'] = 'Y';
						$entityTypeCounter++;
						break;
					default:
				}
			}
			if ($entityTypeCounter <= 1)
			{
				$destSelectorParams['addTabCrmLeads'] = 'N';
				$destSelectorParams['addTabCrmDeals'] = 'N';
				$destSelectorParams['addTabCrmContacts'] = 'N';
				$destSelectorParams['addTabCrmCompanies'] = 'N';
				$destSelectorParams['addTabCrmQuotes'] = 'N';
			}

			$arResult['FILTER'][] = array(
				'id' => 'ENTITY',
				'name' => GetMessage('CRM_COLUMN_ENTITY'),
				'type' => 'dest_selector',
				'params' => $destSelectorParams
			);

			$arResult['FILTER'][] = array(
				'id' => 'ENTITY_TYPE',
				'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'),
				'default' => true,
				'type' => 'list',
				'items' => array(
					'' => '',
					'LEAD' => GetMessage('CRM_ENTITY_TYPE_LEAD'),
					'CONTACT' => GetMessage('CRM_ENTITY_TYPE_CONTACT'),
					'COMPANY' => GetMessage('CRM_ENTITY_TYPE_COMPANY'),
					'DEAL' => GetMessage('CRM_ENTITY_TYPE_DEAL'),
					'QUOTE' => GetMessage('CRM_ENTITY_TYPE_QUOTE_MSGVER_1')
				)
			);
		}
	}

	$eventTypeItems = CCrmEvent::GetEventTypes();
	unset($eventTypeItems[\CCrmEvent::TYPE_LINK], $eventTypeItems[\CCrmEvent::TYPE_UNLINK]);
	$eventTypeItems[$this::FILTER_VALUE_RELATIONS] = GetMessage('CRM_EVENT_TYPE_RELATIONS');

	$arResult['FILTER'][] = array('id' => 'EVENT_TYPE', 'name' => GetMessage('CRM_COLUMN_EVENT_TYPE'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + $eventTypeItems);
	unset($eventTypeItems);
	$arResult['FILTER'][] = array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE'));
	$arResult['FILTER'][] = array('id' => 'EVENT_DESC', 'name' => GetMessage('CRM_COLUMN_EVENT_DESC'));
	$arResult['FILTER'][] = array(
		'id' => 'CREATED_BY_ID',
		'name' => GetMessage('CRM_COLUMN_CREATED_BY_ID'),
		'default' => true,
		'type' => 'dest_selector',
		'params' => array(
			'context' => 'CRM_EVENT_FILTER_CREATED_BY_ID',
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
	);
	$arResult['FILTER'][] = array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'default' => true, 'type' => 'date');

	$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CREATE_TODAY'), 'fields' => array('DATE_CREATE_datesel' => 'today')),
		'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CREATE_YESTERDAY'), 'fields' => array('DATE_CREATE_datesel' => 'yesterday')),
		'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CREATE_MY'), 'fields' => array('CREATED_BY_ID' => $currentUserID, 'CREATED_BY_ID_name' => $currentUserName))
	);
}

$arResult['HEADERS'] = array();
$arResult['HEADERS'][] = ['id' => 'ID', 'name' => 'ID', 'sort' => '', 'default' => false, 'editable' => false];
$arResult['HEADERS'][] = ['id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'event_rel_id', 'default' => true, 'editable' => false, 'width'=>'140px'];
if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
{
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'sort' => '', 'default' => true, 'editable' => false);
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TITLE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TITLE'), 'sort' => '', 'default' => true, 'editable' => false);
}
$arResult['HEADERS'][] = array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => '', 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_NAME', 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'sort' => '', 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_DESC', 'name' => GetMessage('CRM_COLUMN_EVENT_DESC'), 'sort' => '', 'default' => true, 'editable' => false);

$arNavParams = array(
	'nPageSize' => $arResult['EVENT_COUNT']
);

$gridOptions = new \Bitrix\Main\Grid\Options($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$filterOptions = new \Bitrix\Crm\Filter\UiFilterOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arFilter += $filterOptions->getFilter($arResult['FILTER']);

foreach ($arFilter as $k => $v)
{
	$arMatch = array();
	if (preg_match('/(.*)_from$/iu', $k, $arMatch))
	{
		if($v !== '')
		{
			$arFilter['>='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	else if (preg_match('/(.*)_to$/iu', $k, $arMatch))
	{
		if($v !== '')
		{
			if($arMatch[1] == 'DATE_CREATE' && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/u', $v))
			{
				$v = CCrmDateTimeHelper::SetMaxDayTime($v);
			}
			$arFilter['<='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif ($k === 'EVENT_TYPE' && $v === $this::FILTER_VALUE_RELATIONS)
	{
		unset($arFilter['EVENT_TYPE']);
		$arFilter['@EVENT_TYPE'] = [\CCrmEvent::TYPE_LINK, \CCrmEvent::TYPE_UNLINK];
	}
	else if (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			//Bugfix #42761 replace logic field name
			$arFilter['?'.($k === 'EVENT_DESC' ? 'EVENT_TEXT_1' : $k)] = $v;
		}
		unset($arFilter[$k]);
	}
	else if ($k == 'CREATED_BY_ID')
	{
		// For suppress comparison by LIKE
		$arFilter['=CREATED_BY_ID'] = $v;
		unset($arFilter['CREATED_BY_ID']);
	}
}

\Bitrix\Crm\UI\Filter\EntityHandler::internalize($arResult['FILTER'], $arFilter);

$_arSort = $gridOptions->GetSorting(array(
	'sort' => array('event_rel_id' => 'desc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arNavParams = $gridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;
$arSelect = $gridOptions->GetVisibleColumns();
// HACK: ignore entity related fields if entity info is not displayed
if ($arResult['EVENT_ENTITY_LINK'] !== 'Y')
{
	$key = array_search('ENTITY_TYPE', $arSelect, true);
	if($key !== false)
	{
		unset($arSelect[$key]);
	}

	$key = array_search('ENTITY_TITLE', $arSelect, true);
	if($key !== false)
	{
		unset($arSelect[$key]);
	}
}

$gridOptions->SetVisibleColumns($arSelect);

$nTopCount = false;
if ($isInGadgetMode)
{
	$nTopCount = $arResult['EVENT_COUNT'];
}

if($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

$arEntityList = Array();
$arResult['EVENT'] = Array();

//region Navigation data initialization
$pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['EVENT_COUNT']);
$enableNextPage = false;

$pageNum = null;
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
		$total =CCrmEvent::GetListEx(array(), $arFilter, array());
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

//endregion

if ($isInGadgetMode && isset($arNavParams['nTopCount']))
{
	$arOptions = array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount']));
}
else
{
	$arOptions = array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)));
}

//Disable user data selection for performance reasons
$obRes = CCrmEvent::GetListEx(
	$arResult['SORT'],
	$arFilter,
	false,
	false,
	[
		'ID',
	],
	$arOptions
);
$loadedItems = [];
while ($arEvent = $obRes->Fetch())
{
	$loadedItems[$arEvent['ID']] = $arEvent['ID'];
}
if (empty($loadedItems))
{
	$loadedItems[] = 0;
}

$obRes = CCrmEvent::GetListEx(
	[],
	['@ID' => $loadedItems, 'CHECK_PERMISSIONS' => 'N'],
	false,
	false,
	array_diff(
		array_keys(CCrmEvent::GetFields()),
		[
			'CREATED_BY_LOGIN',
			'CREATED_BY_NAME',
			'CREATED_BY_LAST_NAME',
			'CREATED_BY_SECOND_NAME',
			'CREATED_BY_PERSONAL_PHOTO'
		]
	)
);

$loadedData = [];
while ($arEvent = $obRes->Fetch())
{
	$loadedData[$arEvent['ID']] = $arEvent;
}

$userIDs = array();
$qty = 0;
foreach ($loadedItems as $loadedItemId)
{
	$arEvent = $loadedData[$loadedItemId] ?? null;
	if (!$arEvent)
	{
		continue;
	}

	if(++$qty > $pageSize)
	{
		$enableNextPage = true;
		break;
	}

	$arEvent['~FILES'] = $arEvent['FILES'];
	$arEvent['~EVENT_NAME'] = $arEvent['EVENT_NAME'];

	$userID = isset($arEvent['CREATED_BY_ID']) ? (int)$arEvent['CREATED_BY_ID'] : 0;
	if ($userID > 0 && !isset($userIDs[$userID]))
	{
		$userIDs[$userID] = true;
	}

	$arEvent['EVENT_NAME'] = htmlspecialcharsbx($arEvent['~EVENT_NAME']);

	$arEvent['~EVENT_TEXT_1'] = $arEvent['EVENT_TEXT_1'];
	$arEvent['~EVENT_TEXT_2'] = $arEvent['EVENT_TEXT_2'];

	$entityType = isset($arEvent['ENTITY_TYPE']) ? $arEvent['ENTITY_TYPE'] : '';
	$entityField = isset($arEvent['ENTITY_FIELD']) ? $arEvent['ENTITY_FIELD'] : '';

	if($entityField === 'COMMENTS'
		&& ($entityType === 'LEAD' || $entityType === 'CONTACT' || $entityType === 'COMPANY' || $entityType === 'DEAL'))
	{
		$arEvent['EVENT_TEXT_1'] = $arEvent['~EVENT_TEXT_1'];
		$arEvent['EVENT_TEXT_2'] = $arEvent['~EVENT_TEXT_2'];
	}
	else
	{
		$arEvent['EVENT_TEXT_1'] = TextHelper::convertHtmlToText($arEvent['~EVENT_TEXT_1'], true);
		$arEvent['EVENT_TEXT_2'] = TextHelper::convertHtmlToText($arEvent['~EVENT_TEXT_2'], true);
	}

	$arEvent['EVENT_DESC'] = $this->compileEventDesc($arEvent);

	$arEvent['FILES'] = $arEvent['~FILES'] = $arEvent['FILES'] !== '' ? unserialize($arEvent['FILES'], ['allowed_classes' => false]) : array();
	if (!empty($arEvent['FILES']))
	{
		$i=1;
		$arFiles = array();
		$rsFile = CFile::GetList(array(), array('@ID' => implode(',', $arEvent['FILES'])));
		while($arFile = $rsFile->Fetch())
		{
			$arFiles[$i++] = array(
				'NAME' => $arFile['ORIGINAL_NAME'],
				'PATH' => CComponentEngine::MakePathFromTemplate(
					'/bitrix/components/bitrix/crm.event.view/show_file.php?eventId=#event_id#&fileId=#file_id#',
					array('event_id' => $arEvent['ID'], 'file_id' => $arFile['ID'])
				),
				'SIZE' => CFile::FormatSize($arFile['FILE_SIZE'], 1)
			);
		}
		$arEvent['FILES'] = $arFiles;
	}
	$arEntityList[$arEvent['ENTITY_TYPE']][$arEvent['ENTITY_ID']] = $arEvent['ENTITY_ID'];

	$arEvent['PATH_TO_DELETE'] = CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_EVENT_LIST'],
		array(
			'action_'.$arResult['GRID_ID'] => 'delete',
			'ID' => $arEvent['ID'],
			'sessid' => $arResult['SESSION_ID']
		)
	);
	$arResult['EVENT'][] = $arEvent;
}

$userInfos = array();
if(!empty($userIDs))
{
	$dbUsers = CUser::GetList(
		'ID',
		'ASC',
		array('ID' => implode('||', array_keys($userIDs))),
		array('FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'))
	);

	$userNameFormat = CSite::GetNameFormat(false);
	while($arUser = $dbUsers->Fetch())
	{
		$userID = (int)$arUser['ID'];

		$userInfo = array('USER' => $arUser);
		$userInfo['FULL_NAME'] = CUser::FormatName($userNameFormat, $arUser, true, false);
		$userInfo['PROFILE_URL'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_PROFILE'],
			array('user_id' => $userID)
		);
		$userPhotoID = isset($arUser['PERSONAL_PHOTO']) ? (int)$arUser['PERSONAL_PHOTO'] : 0;
		if($userPhotoID > 0)
		{
			$file = new CFile();
			$fileInfo = $file->ResizeImageGet(
				$userPhotoID,
				array('width' => 38, 'height'=> 38),
				BX_RESIZE_IMAGE_EXACT
			);
			if(is_array($fileInfo) && isset($fileInfo['src']))
			{
				$userInfo['PERSONAL_PHOTO_URL'] = $fileInfo['src'];
			}
		}
		$userInfos[$userID] = $userInfo;
	}
}

$arResult['EVENT'] = $this->enrichRelationEvents($arResult['EVENT']);

for($i = 0, $length = count($arResult['EVENT']); $i < $length; $i++)
{
	$userID = isset($arResult['EVENT'][$i]['CREATED_BY_ID']) ? (int)$arResult['EVENT'][$i]['CREATED_BY_ID'] : 0;
	if($userID <= 0 || !isset($userInfos[$userID]))
	{
		$arResult['EVENT'][$i]['~CREATED_BY_FULL_NAME'] = '';
		$arResult['EVENT'][$i]['CREATED_BY_PHOTO_URL'] = '';

		continue;
	}

	$userInfo = $userInfos[$userID];
	$arResult['EVENT'][$i]['~CREATED_BY_FULL_NAME'] = $userInfo['FULL_NAME'];
	$arResult['EVENT'][$i]['CREATED_BY_FULL_NAME'] = htmlspecialcharsbx($userInfo['FULL_NAME']);
	$arResult['EVENT'][$i]['CREATED_BY_LINK'] = $userInfo['PROFILE_URL'];
	$arResult['EVENT'][$i]['CREATED_BY_PHOTO_URL'] = isset($userInfo['PERSONAL_PHOTO_URL'])
		? $userInfo['PERSONAL_PHOTO_URL'] : '';
}

//region Navigation data storing
$arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
// Prepare raw filter ('=CREATED_BY' => 'CREATED_BY')
$arResult['DB_FILTER'] = array();
foreach($arFilter as $filterKey => &$filterItem)
{
	$info = CSqlUtil::GetFilterOperation($filterKey);
	$arResult['DB_FILTER'][$info['FIELD']] = $filterItem;
}
unset($filterItem);

if(!isset($_SESSION['CRM_GRID_DATA']))
{
	$_SESSION['CRM_GRID_DATA'] = array();
}
$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
//endregion

if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
{
	$router = Container::getInstance()->getRouter();
	foreach ($arEntityList as $typeName => $ids)
	{
		if (empty($ids))
		{
			continue;
		}
		$entityTypeId = \CCrmOwnerType::ResolveID($typeName);
		if ($entityTypeId === CCrmOwnerType::Order)
		{
			continue;
		}
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			continue;
		}
		$isCategoriesSupported = $factory->isCategoriesSupported();
		$items = $factory->getItemsFilteredByPermissions(['filter' => [
			'@ID' => $ids,
		]]);
		foreach ($items as $item)
		{
			$itemId = $item->getId();
			$arEntityList[$typeName][$itemId] = [
				'ENTITY_TITLE' => $item->getHeading(),
				'ENTITY_LINK' => $router->getItemDetailUrl(
					$entityTypeId,
					$itemId,
					$isCategoriesSupported ? $item->getCategoryId() : null
				)
			];
		}
	}
	foreach($arResult['EVENT'] as $key => $ar)
	{
		$entityInfo = $arEntityList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']];
		$arResult['EVENT'][$key]['ENTITY_TITLE'] = isset($entityInfo['ENTITY_TITLE'])
			? htmlspecialcharsbx($entityInfo['ENTITY_TITLE']) : '';
		$arResult['EVENT'][$key]['ENTITY_LINK'] = isset($entityInfo['ENTITY_LINK'])
			? $entityInfo['ENTITY_LINK'] : '';
	}
}

$this->IncludeComponentTemplate();

return $obRes->SelectedRowsCount();
