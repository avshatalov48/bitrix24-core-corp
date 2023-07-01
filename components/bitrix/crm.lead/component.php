<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	
	return;
}

if (!CAllCrmInvoice::installExternalEntities())
{
	return;
}

if (!CCrmQuote::LocalComponentCausedUpdater())
{
	return;
}

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));

	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));

	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));

	return;
}

global $APPLICATION;

use Bitrix\Crm;
use Bitrix\Crm\Restriction\RestrictionManager;

$arDefaultUrlTemplates404 = [
	'index' => 'index.php',
	'list' => 'list/',
	'analytics/list' => 'analytics/list/',
	'service' => 'service/',
	'import' => 'import/',
	'widget' => 'widget/',
	'kanban' => 'kanban/',
	'edit' => 'edit/#lead_id#/',
	'show' => 'show/#lead_id#/',
	'convert' => 'convert/#lead_id#/',
	'dedupe' => 'dedupe/',
	'dedupelist' => 'dedupelist/',
	'dedupewizard' => 'dedupewizard/',
	'merge' => 'merge/',
	'details' => 'details/#lead_id#/',
	'calendar' => 'calendar/',
	'automation' => 'automation/#category_id#/',
	'activity' => 'activity/',
];

$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = [];
$componentPage = '';
$originalComponentPage = '';
$arComponentVariables = ['lead_id'];

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE'])
	? CSite::GetNameFormat(false)
	: str_replace(['#NOBR#','#/NOBR#'], ['', ''], $arParams['NAME_TEMPLATE']);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arVariables = [];
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
	{
		$componentPage = 'index';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$paramName = 'PATH_TO_LEAD_' . mb_strtoupper($url);

		if (empty($arParams[$paramName]))
		{
			$arResult[$paramName] = $arParams['SEF_FOLDER'] . $value;
		}
		else
		{
			$arResult[$paramName] = $arParams['PATH_TO_' . mb_strtoupper($url)];
		}
	}
}
else
{
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['lead_id'];

	$arVariables = [];
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'edit';
	}
	else if (isset($_REQUEST['copy']))
	{
		$componentPage = 'edit';
	}
	else if (isset($_REQUEST['card']))
	{
		$componentPage = 'card';
	}
	else if (isset($_REQUEST['convert']))
	{
		$componentPage = 'convert';
	}
	else if (isset($_REQUEST['import']))
	{
		$componentPage = 'import';
	}
	elseif (isset($_REQUEST['show']))
	{
		$componentPage = 'show';
	}
	else if (isset($_REQUEST['merge']))
	{
		$componentPage = 'merge';
	}
	else if (isset($_REQUEST['details']))
	{
		$componentPage = 'details';
	}
	else if (isset($_REQUEST['automation']))
	{
		$componentPage = 'automation';
	}
	else if (isset($_REQUEST['analytics/list']))
	{
		$componentPage = 'analytics/list';
	}
	elseif (isset($_REQUEST['dedupe']))
	{
		$componentPage = 'dedupe';
	}
	elseif (isset($_REQUEST['dedupelist']))
	{
		$componentPage = 'dedupelist';
	}
	elseif (isset($_REQUEST['dedupewizard']))
	{
		$componentPage = 'dedupewizard';
	}

	$arResult['PATH_TO_LEAD_LIST'] = $arResult['PATH_TO_LEAD_DEDUPE'] = $arResult['PATH_TO_LEAD_DEDUPEWIZARD'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_LEAD_SHOW'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['lead_id']}=#lead_id#&show";
	$arResult['PATH_TO_LEAD_DETAILS'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['lead_id']}=#lead_id#&details";
	$arResult['PATH_TO_LEAD_EDIT'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['lead_id']}=#lead_id#&edit";
	$arResult['PATH_TO_LEAD_CONVERT'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['lead_id']}=#lead_id#&convert";
	$arResult['PATH_TO_LEAD_WIDGET'] = $APPLICATION->GetCurPage() . "?widget";
	$arResult['PATH_TO_LEAD_KANBAN'] = $APPLICATION->GetCurPage() . "?kanban";
	$arResult['PATH_TO_LEAD_CALENDAR'] = $APPLICATION->GetCurPage() . "?calendar";
	$arResult['PATH_TO_LEAD_AUTOMATION'] = $APPLICATION->GetCurPage() . "?automation";
}

$arResult = array_merge(
	[
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] === 'Y'? [] : $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'] ?? '',
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'] ?? '',
		'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'] ?? '',
		'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'] ?? '',
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'] ?? '',
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'] ?? '',
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'] ?? '',
	],
	$arResult
);

\CCrmEntityHelper::setEnabledFactoryFlagByRequest(
	Crm\Settings\LeadSettings::getCurrent(),
	\Bitrix\Main\Application::getInstance()->getContext()->getRequest()
);

if (isset($_GET['redirect_to']))
{
	$viewName = mb_strtoupper(trim($_GET['redirect_to']));

	if ($viewName === '')
	{
		$viewName = Crm\Settings\EntityViewSettings::resolveName(
			Crm\Settings\LeadSettings::getCurrent()->getCurrentListViewID()
		);
	}

	$pathKey = "PATH_TO_LEAD_{$viewName}";

	if (isset($arResult[$pathKey]))
	{
		$redirectUrl = CHTTP::urlAddParams(
			$arResult[$pathKey],
			array_diff_key($_GET, array_flip(array('redirect_to'))),
			array('encode' => true)
		);

		LocalRedirect($redirectUrl);
	}
}

$originalComponentPage = $componentPage;

$arResult['NAVIGATION_CONTEXT_ID'] = 'LEAD';

if ($componentPage === 'index')
{
	$componentPage = 'list';
}
elseif ($componentPage === 'activity')
{
	$arResult['KANBAN_VIEW_MODE'] = \Bitrix\Crm\Kanban\ViewMode::MODE_ACTIVITIES;
	$arResult['PATH_TO_LEAD_KANBAN'] = $arResult['PATH_TO_LEAD_ACTIVITY'];

	$componentPage = 'kanban';
}

if (isset($_GET['id']))
{
	$entityIDs = null;
	if (is_array($_GET['id']))
	{
		$entityIDs = $_GET['id'];
	}
	else
	{
		$entityIDs = explode(',', $_GET['id']);
	}
	$arResult['VARIABLES']['lead_ids'] = array_map('intval', $entityIDs);
}

if (
	\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled()
	&& ($componentPage === 'edit' || $componentPage === 'show')
)
{
	$redirectUrl = CComponentEngine::MakePathFromTemplate(
		$arResult['PATH_TO_LEAD_DETAILS'],
		array('lead_id' => $arResult['VARIABLES']['lead_id'])
	);

	if (isset($_SERVER['QUERY_STRING']))
	{
		parse_str($_SERVER['QUERY_STRING'], $queryParams);
		if (!empty($queryParams))
		{
			$redirectUrl = CHTTP::urlAddParams($redirectUrl, $queryParams, array('encode' => true));
		}
	}
	LocalRedirect($redirectUrl, false,'301 Moved Permanently');
}

if ($componentPage !== 'details')
{
	$isLeadsRestricted = !RestrictionManager::getLeadsRestriction()->hasPermission();
	$isActivityFieldRestricted = $originalComponentPage === 'activity' && RestrictionManager::getActivityFieldRestriction()->isExceeded();

	if ($isLeadsRestricted || $isActivityFieldRestricted)
	{
		$componentPage = 'restrictions';
	}
}

$this->IncludeComponentTemplate($componentPage);
