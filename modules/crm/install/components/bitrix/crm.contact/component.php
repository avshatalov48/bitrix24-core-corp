<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
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

$arDefaultUrlTemplates404 = [
	'index' => 'index.php',
	'list' => 'list/',
	'category' => 'category/#category_id#/',
	'service' => 'service/',
	'import' => 'import/',
	'importvcard' => 'importvcard/',
	'edit' => 'edit/#contact_id#/',
	'show' => 'show/#contact_id#/',
	'dedupe' => 'dedupe/',
	'dedupelist' => 'dedupelist/',
	'dedupewizard' => 'dedupewizard/',
	'analytics/list' => 'analytics/list/',
	'widget' => 'widget/',
	'portrait' => 'portrait/#contact_id#/',
	'details' => 'details/#contact_id#/',
	'merge' => 'merge/',
	'requisiteselect' => 'requisite/select/#contact_id#/'
];

$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = [];
$componentPage = '';
$arComponentVariables = ['contact_id'];

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE'])
	? CSite::GetNameFormat(false)
	: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

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
		$paramName = 'PATH_TO_CONTACT_' . mb_strtoupper($url);

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
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['contact_id'];

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
	else if (isset($_REQUEST['show']))
	{
		$componentPage = 'show';
	}
	else if (isset($_REQUEST['details'])){
		$componentPage = 'details';
	}
	else if (isset($_REQUEST['merge']))
	{
		$componentPage = 'merge';
	}
	else if (isset($_REQUEST['requisiteselect']))
	{
		$componentPage = 'requisiteselect';
	}
	else if (isset($_REQUEST['import']))
	{
		$componentPage = 'import';
	}
	else if (isset($_REQUEST['importvcard']))
	{
		$componentPage = 'importvcard';
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
	elseif (isset($_REQUEST['widget']))
	{
		$componentPage = 'widget';
	}
	elseif (isset($_REQUEST['portrait']))
	{
		$componentPage = 'portrait';
	}

	$arResult['PATH_TO_CONTACT_LIST'] = $arResult['PATH_TO_CONTACT_DEDUPE'] = $arResult['PATH_TO_CONTACT_DEDUPEWIZARD'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_CONTACT_DETAILS'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[contact_id]=#contact_id#&details";
	$arResult['PATH_TO_CONTACT_REQUISITE_SELECT'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[contact_id]=#contact_id#&requisiteselect";
	$arResult['PATH_TO_CONTACT_SHOW'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[contact_id]=#contact_id#&show";
	$arResult['PATH_TO_CONTACT_DETAILS'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[contact_id]=#contact_id#&details";
	$arResult['PATH_TO_CONTACT_EDIT'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[contact_id]=#contact_id#&edit";
	$arResult['PATH_TO_CONTACT_IMPORT'] = $APPLICATION->GetCurPage() . "?import";
	$arResult['PATH_TO_CONTACT_IMPORTVCARD'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_CONTACT_WIDGET'] = $APPLICATION->GetCurPage() . "?widget";
	$arResult['PATH_TO_CONTACT_PORTRAIT'] = $APPLICATION->GetCurPage() . "?portrait";
}

$arResult = array_merge(
	[
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] === 'Y' ? [] : $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'] ?? '',
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'] ?? '',
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'] ?? '',
		'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'] ?? '',
		'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'] ?? '',
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'] ?? '',
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'] ?? '',
		'PATH_TO_REQUISITE_EDIT' => $arParams['PATH_TO_REQUISITE_EDIT'] ?? '',
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'] ?? '',
	],
	$arResult
);

if (isset($_GET['redirect_to']))
{
	$viewName = mb_strtoupper(trim($_GET['redirect_to']));
	if ($viewName === '')
	{
		$viewName = Crm\Settings\EntityViewSettings::resolveName(
			Crm\Settings\ContactSettings::getCurrent()->getCurrentListViewID()
		);
	}
	$pathKey = "PATH_TO_CONTACT_{$viewName}";

	if (isset($arResult[$pathKey]))
	{
		$redirectUrl = CHTTP::urlAddParams(
			$arResult[$pathKey],
			array_diff_key($_GET, array_flip(['redirect_to'])),
			['encode' => true]
		);

		LocalRedirect($redirectUrl);
	}
}

$arResult['NAVIGATION_CONTEXT_ID'] = 'CONTACT';
if ($componentPage === 'index' || $componentPage === 'category')
{
	$componentPage = 'list';
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

	$arResult['VARIABLES']['contact_ids'] = array_map('intval', $entityIDs);
}

\CCrmEntityHelper::setEnabledFactoryFlagByRequest(
	Crm\Settings\ContactSettings::getCurrent(),
	\Bitrix\Main\Application::getInstance()->getContext()->getRequest(),
);

if (
	\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled()
	&& ($componentPage === 'edit' || $componentPage === 'show')
)
{
	$redirectUrl = CComponentEngine::MakePathFromTemplate(
		$arResult['PATH_TO_CONTACT_DETAILS'],
		['contact_id' => $arResult['VARIABLES']['contact_id']]
	);

	if (isset($_SERVER['QUERY_STRING']))
	{
		parse_str($_SERVER['QUERY_STRING'], $queryParams);
		if (!empty($queryParams))
		{
			$redirectUrl = CHTTP::urlAddParams($redirectUrl, $queryParams, array('encode' => true));
		}
	}

	LocalRedirect($redirectUrl, '301 Moved Permanently');
}
else
{
	$this->IncludeComponentTemplate($componentPage);
}
