<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$CCrmDeal = new CCrmDeal();
if (!CCrmDeal::CheckReadPermission(0, $userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if(!CCrmCurrency::EnsureReady())
{
	ShowError(CCrmCurrency::GetLastError());
}

global $APPLICATION;

use Bitrix\Crm\Category\DealCategory;

$arResult['PATH_TO_DEAL_LIST'] = $arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath(
	'PATH_TO_DEAL_LIST',
	isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
	'#SITE_DIR#crm/deal/'
);
$arParams['PATH_TO_DEAL_CATEGORY'] = CrmCheckPath(
	'PATH_TO_DEAL_CATEGORY',
		$arParams['PATH_TO_DEAL_CATEGORY'],
		$APPLICATION->GetCurPage().'?category_id=#category_id#'
);

$arResult['USE_AMCHARTS'] = $arParams['USE_AMCHARTS'] = true;
$arParams['GRID_ID_SUFFIX'] = '';
$arParams['DISABLE_COMPENSATION'] = !empty($arParams['DISABLE_COMPENSATION']) && $arParams['DISABLE_COMPENSATION'] == 'Y' ? 'Y' : 'N';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['CURRENT_USER_ID'] = CCrmPerms::GetCurrentUserID();
//ALLOW_FUNNEL_TYPE_CHANGE = 'Y' by default
$arParams['ALLOW_FUNNEL_TYPE_CHANGE'] = $arResult['ALLOW_FUNNEL_TYPE_CHANGE'] = !empty($arParams['ALLOW_FUNNEL_TYPE_CHANGE'])
	&& $arParams['ALLOW_FUNNEL_TYPE_CHANGE'] == 'N' ? 'N' : 'Y';

$arResult['CATEGORY_ID'] = 0;
$arResult['CATEGORY_INFO'] = DealCategory::getJavaScriptInfos(CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions));

$arParams['FUNNEL_TYPE'] = $arResult['FUNNEL_TYPE'] =  $arParams['DISABLE_COMPENSATION'] === 'Y' ? 'CLASSICAL' : 'CUMULATIVE';
$arResult['FUNNEL_TYPE_VALUES'] = array(
	array(
		'value' => 'CLASSICAL',
		'text' => GetMessage('CRM_FUNNEL_TYPE_CLASSICAL')
	),
	array(
		'value' => 'CUMULATIVE',
		'text' => GetMessage('CRM_FUNNEL_TYPE_CUMULATIVE2')
	)
);

$userFunnelType = '';
if($arParams['ALLOW_FUNNEL_TYPE_CHANGE'] === 'Y' && $arResult['CURRENT_USER_ID'] > 0)
{
	$userFunnelType = CUserOptions::GetOption('crm.deal.funnel', 'funnel_type', '', $arResult['CURRENT_USER_ID']);
	if($userFunnelType !== '')
	{
		$arParams['FUNNEL_TYPE'] = $arResult['FUNNEL_TYPE'] = $userFunnelType;
		$arParams['DISABLE_COMPENSATION'] = $userFunnelType === 'CLASSICAL' ? 'Y' : 'N';
	}
}

$userCategoryID = 0;
if($arResult['CURRENT_USER_ID'] > 0)
{
	$userCategoryID = (int)CUserOptions::GetOption('crm.deal.funnel', 'category_id', 0, $arResult['CURRENT_USER_ID']);
	if(DealCategory::isEnabled($userCategoryID))
	{
		$arParams['CATEGORY_ID'] = $arResult['CATEGORY_ID'] = $userCategoryID;
	}
	elseif($userCategoryID !== 0)
	{
		$userCategoryID = 0;
	}
}

$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && strlen($arParams['GADGET_ID']) > 0)
	$arResult['GADGET'] = 'Y';

//Change of funnel type -->
if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && $arResult['CURRENT_USER_ID'] > 0)
{
	if(isset($_POST['FUNNEL_TYPE']) && $arParams['ALLOW_FUNNEL_TYPE_CHANGE'] === 'Y')
	{
		$funnelType = strtoupper($_POST['FUNNEL_TYPE']);
		if($funnelType !== $userFunnelType && ($funnelType === 'CLASSICAL' || $funnelType === 'CUMULATIVE'))
		{
			CUserOptions::SetOption('crm.deal.funnel', 'funnel_type', $funnelType, false, $arResult['CURRENT_USER_ID']);
			LocalRedirect($APPLICATION->GetCurPage());
		}
	}
	if(isset($_POST['CATEGORY_ID']))
	{
		$categoryID = (int)$_POST['CATEGORY_ID'];
		if($categoryID !== $userCategoryID)
		{
			CUserOptions::SetOption('crm.deal.funnel', 'category_id', $categoryID, false, $arResult['CURRENT_USER_ID']);
			LocalRedirect($APPLICATION->GetCurPage());
		}
	}
}
//<-- Change of funnel type


$arFilter = $arSort = array();
$bInternal = false;
if (!empty($arParams['INTERNAL_FILTER']) || $arResult['GADGET'] == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
	$arFilter = $arParams['INTERNAL_FILTER'];

global $USER_FIELD_MANAGER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);

$arResult['STAGE_LIST'] = DealCategory::getStageList($arResult['CATEGORY_ID']);
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

$arResult['FILTER'] = array();
$arResult['GRID_ID'] = 'CRM_DEAL_FUNNEL';

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array();

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'CONTACT',
			'INPUT_NAME' => 'CONTACT_ID',
			'INPUT_VALUE' => isset($_REQUEST['CONTACT_ID']) ? intval($_REQUEST['CONTACT_ID']) : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sValContact = ob_get_contents();
	ob_end_clean();

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'COMPANY',
			'INPUT_NAME' => 'COMPANY_ID',
			'INPUT_VALUE' => isset($_REQUEST['COMPANY_ID']) ? intval($_REQUEST['COMPANY_ID']) : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sValCompany = ob_get_contents();
	ob_end_clean();

	$arResult['FILTER'] = array(
		array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'type' => 'number'),
		array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'type' => 'list', 'items' => array('' => '') + CCrmCurrencyHelper::PrepareListItems()),
		array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_PROBABILITY'), 'type' => 'number'),
		//array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID'), 'default' => 'Y',  'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('PRODUCT')),
		array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_CLOSED'), 'type' => 'list', 'items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
		array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE_ID'),'default' => 'Y',  'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('DEAL_TYPE')),
		array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_BEGINDATE'), 'type' => 'date'),
		array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_CLOSEDATE'), 'type' => 'date'),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'default' => 'Y', 'type' => 'date'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'default' => 'Y', 'type' => 'date'),
		array('id' => 'MODIFY_BY_ID',  'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'enable_settings' => false, 'type' => 'user'),
		array('id' => 'ASSIGNED_BY_ID',  'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'default' => 'Y', 'enable_settings' => false, 'type' => 'user'),
		array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_LIST'), 'type' => 'custom', 'value' => $sValContact),
		array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_LIST'), 'type' => 'custom', 'value' => $sValCompany)
	);

	$CCrmUserType->ListAddFilterFields($arResult['FILTER'], $arResult['FILTER2LOGIC'], $arResult['GRID_ID']);

	$arResult['FILTER_PRESETS'] = array(
		'filter_week' => array('name' => GetMessage('CRM_PRESET_WEEK'), 'fields' => array('DATE_MODIFY_datesel' => 'week')),
		'filter_week_prev' => array('name' => GetMessage('CRM_PRESET_WEEK_PREV'), 'fields' => array('DATE_MODIFY_datesel' => 'week_ago')),
		'filter_month' => array('name' => GetMessage('CRM_PRESET_MONTH'), 'fields' => array('DATE_MODIFY_datesel' => 'month')),
		'filter_month_prev' => array('name' => GetMessage('CRM_PRESET_MONTH_PREV'), 'fields' => array('DATE_MODIFY_datesel' => 'month_ago')),
		'filter_my_week' => array('name' => GetMessage('CRM_PRESET_MY_WEEK'), 'fields' => array('DATE_MODIFY_datesel' => 'week', "ASSIGNED_BY_ID"=>__format_user4search(), "ASSIGNED_BY_ID[]"=>$GLOBALS['USER']->GetID())),
		'filter_my_week_ago' => array('name' => GetMessage('CRM_PRESET_MY_WEEK_AGO'), 'fields' => array('DATE_MODIFY_datesel' => 'week_ago', "ASSIGNED_BY_ID"=>__format_user4search(), "ASSIGNED_BY_ID[]"=>$GLOBALS['USER']->GetID()))
	);
}

if ($arParams['USE_AMCHARTS'])
{
	$arResult['HEADERS'] = array(
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => false, 'default' => true, 'editable' => false),
		array('id' => 'PROCENT', 'name' => GetMessage('CRM_COLUMN_PROCENT'), 'sort' => false, 'default' => true, 'editable' => false, 'align' => 'right'),
		array('id' => 'COUNT_FUNNEL', 'name' => GetMessage('CRM_COLUMN_COUNT'), 'sort' => false, 'default' => true, 'editable' => false, 'align' => 'right')
	);
}
else
{
	$arResult['HEADERS'] = array(
		array('id' => 'FUNNEL', 'name' => GetMessage('CRM_COLUMN_FUNNEL'), 'sort' => false, 'default' => false, 'editable' => false, 'align' => 'center'),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => false, 'default' => true, 'editable' => false),
		array('id' => 'PROCENT', 'name' => GetMessage('CRM_COLUMN_PROCENT'), 'sort' => false, 'default' => true, 'editable' => false, 'align' => 'right'),
		array('id' => 'COUNT_FUNNEL', 'name' => GetMessage('CRM_COLUMN_COUNT'), 'sort' => false, 'default' => true, 'editable' => false, 'align' => 'right')
	);
}

$i = 0;
foreach ($arResult['CURRENCY_LIST'] as $k => $v)
{
	$arResult['HEADERS'][] = array('id' => $k, 'name' => GetMessage('CRM_COLUMN_SUMM', array('#CURRENCY#' => htmlspecialcharsbx($v))), 'sort' => false, 'default' => $i == 0, 'editable' => false, 'align' => 'right');
	$i++;
}

$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);

if (isset($_REQUEST['clear_filter']) && $_REQUEST['clear_filter'] == 'Y')
{
	$urlParams = array();
	foreach($arResult['FILTER'] as $id => $arFilter)
	{
		if ($arFilter['type'] == 'user')
		{
			$urlParams[] = $arFilter['id'];
			$urlParams[] = $arFilter['id'].'_name';
		}
		else
			$urlParams[] = $arFilter['id'];
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	LocalRedirect($APPLICATION->GetCurPageParam('', $urlParams));
}

$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmDeal::$sUFEntityID, $arFilter);

// converts data from filter
if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
{
	if ($arFilter['FIND_list'] == 't_n_ln')
	{
		$arFilter['TITLE'] = $arFilter['FIND'];
		$arFilter['NAME'] = $arFilter['FIND'];
		$arFilter['LAST_NAME'] = $arFilter['FIND'];
		$arFilter['LOGIC'] = 'OR';
	}
	else
		$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
	unset($arFilter['FIND_list'], $arFilter['FIND']);
}

$arImmutableFilters = array(
	'TYPE_ID',
	'CONTACT_ID',
	'COMPANY_ID',
	'ASSIGNED_BY_ID',
	'MODIFY_BY_ID',
	'CURRENCY_ID',
	'CLOSED'
);
foreach ($arFilter as $k => $v)
{
	$v = trim($v);

	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	$arMatch = array();
	if (in_array($k, array('EMAIL', 'WEB', 'PHONE', 'IM')))
	{
		$arFilter['FM'][] = array(
			'TYPE_ID' => $k,
			'%VALUE' => $v // Compare by 'LIKE'
		);
	}
	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if($v !== '')
		{
			$arFilter['>='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	else if (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if($v !== '')
		{
			$fieldName = $arMatch[1];
			if (($fieldName == 'DATE_CREATE' || $fieldName == 'DATE_MODIFY' || $fieldName == 'BEGINDATE' || $fieldName == 'CLOSEDATE') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
				$v = CCrmDateTimeHelper::SetMaxDayTime($v);

			$arFilter['<='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	else if (strpos($k, 'UF_') !== 0 && $k != 'LOGIC')
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

if($arResult['CATEGORY_ID'] >= 0)
{
	$arResult['PATH_TO_DEAL_CATEGORY'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_DEAL_CATEGORY'],
		array('category_id' => $arResult['CATEGORY_ID'])
	);
}
$arFilter['CATEGORY_ID'] = $arResult['CATEGORY_ID'];
$obRes = CCrmDeal::GetListEx(array(), $arFilter, false, false, array('ID', 'STAGE_ID', 'OPPORTUNITY', 'CURRENCY_ID'));
$arResult['STAGE_COUNT'] = array();
$arResult['FUNNEL'] = array();

$iDealCount = 0;
while($arDeal = $obRes->Fetch())
{
	//Normalizing for compatibility
	$currencyID = CCrmCurrency::NormalizeCurrencyID($arDeal['CURRENCY_ID']);
	$stageID = $arDeal['STAGE_ID'];
	$opportunity = (double)$arDeal['OPPORTUNITY'];

	if (!isset($arResult['STAGE_COUNT'][$stageID]))
	{
		$arResult['STAGE_COUNT'][$stageID]['COUNT'] = 1;
		$arResult['STAGE_COUNT'][$stageID]['COUNT_FUNNEL'] = 1;
		$arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY'] = array($currencyID => $opportunity);
		$arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY_FUNNEL'] = array($currencyID => $opportunity);
	}
	else
	{
		$arResult['STAGE_COUNT'][$stageID]['COUNT']++;
		$arResult['STAGE_COUNT'][$stageID]['COUNT_FUNNEL']++;

		if(!isset($arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY'][$currencyID]))
		{
			$arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY'][$currencyID] = 0;
		}
		$arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY'][$currencyID] += $opportunity;

		if(!isset($arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY_FUNNEL'][$currencyID]))
		{
			$arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY_FUNNEL'][$currencyID] = 0;
		}
		$arResult['STAGE_COUNT'][$stageID]['OPPORTUNITY_FUNNEL'][$currencyID] += $opportunity;
	}
	$iDealCount++;
}

// arrange the number of status and money in the reverse order
$arResult['STAGE_LIST_REVERSE'] = array_reverse($arResult['STAGE_LIST'], true);
$bafterWON = false;
if ($arParams['DISABLE_COMPENSATION'] !== 'Y')
{
	$successStageID = CCrmDeal::GetSuccessStageID($arResult['CATEGORY_ID']);
	foreach ($arResult['STAGE_LIST_REVERSE'] as $k => $v)
	{
		if ($k === $successStageID)
			$bafterWON = true;

		if ($bafterWON == false)
			continue;

		reset($arResult['STAGE_LIST']);
		foreach ($arResult['STAGE_LIST'] as $k2 => $v2)
		{
			if ($k2 == $k)
				break ;

			if (!isset($arResult['STAGE_COUNT'][$k2]['COUNT_FUNNEL']))
			{
				$arResult['STAGE_COUNT'][$k2]['COUNT_FUNNEL'] = 0;
			}

			if (!isset($arResult['STAGE_COUNT'][$k]['COUNT']))
			{
				$arResult['STAGE_COUNT'][$k]['COUNT'] = 0;
			}
			$arResult['STAGE_COUNT'][$k2]['COUNT_FUNNEL'] += $arResult['STAGE_COUNT'][$k]['COUNT'];

			if(!isset($arResult['STAGE_COUNT'][$k]['OPPORTUNITY']))
			{
				$arResult['STAGE_COUNT'][$k]['OPPORTUNITY'] = array();
			}

			if(!isset($arResult['STAGE_COUNT'][$k2]['OPPORTUNITY_FUNNEL']))
			{
				$arResult['STAGE_COUNT'][$k2]['OPPORTUNITY_FUNNEL'] = array();
			}

			foreach($arResult['STAGE_COUNT'][$k]['OPPORTUNITY'] as $currencyID => $opportunity)
			{
				if(!isset($arResult['STAGE_COUNT'][$k2]['OPPORTUNITY_FUNNEL'][$currencyID]))
				{
					$arResult['STAGE_COUNT'][$k2]['OPPORTUNITY_FUNNEL'][$currencyID] = 0;
				}

				$arResult['STAGE_COUNT'][$k2]['OPPORTUNITY_FUNNEL'][$currencyID] += $opportunity;
			}

			if ($k2 == $successStageID)
				break 2;
		}
	}
}

// calculate procent
foreach ($arResult['STAGE_LIST'] as $k => $v)
{
	$info = $arResult['STAGE_COUNT'][$k];
	$info['PROCENT'] = ($iDealCount > 0 ? round(($arResult['STAGE_COUNT'][$k]['COUNT_FUNNEL']*100)/$iDealCount) : 0);
	$info['TITLE'] = $v;
	$info['STAGE_ID'] = $k;

	foreach ($arResult['CURRENCY_LIST'] as $currencyID => $currencyName)
	{
		$sum = isset($info['OPPORTUNITY_FUNNEL']) && isset($info['OPPORTUNITY_FUNNEL'][$currencyID])
			? (double)$info['OPPORTUNITY_FUNNEL'][$currencyID] : 0.0;

		$info['OPPORTUNITY_FUNNEL_'.$currencyID] = $sum;
	}

	$arResult['FUNNEL'][] = &$info;
	unset($info);
}

$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$this->IncludeComponentTemplate();
$APPLICATION->SetTitle(GetMessage('CRM_DEAL_NAV_TITLE_LIST'));
return $iDealCount;
?>
