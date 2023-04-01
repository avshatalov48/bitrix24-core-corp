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

if(!CCrmQuote::LocalComponentCausedUpdater())
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

$arResult['MYCOMPANY_MODE'] = (isset($arParams['MYCOMPANY_MODE']) && $arParams['MYCOMPANY_MODE'] === 'Y') ? 'Y' : 'N';

if ($arResult['MYCOMPANY_MODE'] === 'Y')
{
	global $USER;
	$CrmPerms = new CCrmPerms($USER->GetID());
	if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));

		return;
	}
}

$arDefaultUrlTemplates404 = [
	'index' => 'index.php',
	'list' => 'list/',
	'category' => 'category/#category_id#/',
	'import' => 'import/',
	'edit' => 'edit/#company_id#/',
	'show' => 'show/#company_id#/',
	'dedupe' => 'dedupe/',
	'dedupelist' => 'dedupelist/',
	'dedupewizard' => 'dedupewizard/',
	'widget' => 'widget/',
	'analytics/list' => 'analytics/list/',
	'portrait' => 'portrait/#company_id#/',
	'details' => 'details/#company_id#/',
	'merge' => 'merge/',
	'requisiteselect' => 'requisite/select/#company_id#/'
];

$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = [];
$componentPage = '';
$arComponentVariables = ['company_id'];

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE'])
	? CSite::GetNameFormat(false)
	: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $arParams["NAME_TEMPLATE"]);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arVariables = [];
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates(
		$arDefaultUrlTemplates404,
		$arParams['SEF_URL_TEMPLATES'] ?? []
	);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases(
		$arDefaultVariableAliases404,
		$arParams['VARIABLE_ALIASES'] ?? []
	);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
	{
		$componentPage = 'index';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
	foreach ($arUrlTemplates as $url => $value)
	{
		$paramName = 'PATH_TO_COMPANY_' . mb_strtoupper($url);

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
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['company_id'];

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
	else if (isset($_REQUEST['details']))
	{
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

	$arResult['PATH_TO_COMPANY_LIST'] = $arResult['PATH_TO_COMPANY_DEDUPE'] = $arResult['PATH_TO_COMPANY_DEDUPEWIZARD'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_COMPANY_DETAILS'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[company_id]=#company_id#&details";
	$arResult['PATH_TO_COMPANY_REQUISITE_SELECT'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[company_id]=#company_id#&requisiteselect";
	$arResult['PATH_TO_COMPANY_SHOW'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[company_id]=#company_id#&show";
	$arResult['PATH_TO_COMPANY_DETAILS'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[company_id]=#company_id#&details";
	$arResult['PATH_TO_COMPANY_EDIT'] = $APPLICATION->GetCurPage() . "?$arVariableAliases[company_id]=#company_id#&edit";
	$arResult['PATH_TO_COMPANY_IMPORT'] = $APPLICATION->GetCurPage() . "?import";
	$arResult['PATH_TO_COMPANY_WIDGET'] = $APPLICATION->GetCurPage() . "?widget";
	$arResult['PATH_TO_COMPANY_PORTRAIT'] = $APPLICATION->GetCurPage() . "?portrait";
}

$arResult = array_merge(
	[
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] === 'Y' ? []: $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'] ?? '',
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'] ?? '',
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'] ?? '',
		'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'] ?? '',
		'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'] ?? '',
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'] ?? '',
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'] ?? '',
		'PATH_TO_REQUISITE_EDIT' => $arParams['PATH_TO_REQUISITE_EDIT'] ?? '',
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'] ?? '',
		'MYCOMPANY_MODE' => $arResult['MYCOMPANY_MODE'] === 'Y' ? 'Y' : 'N'
	],
	$arResult
);

if (isset($_GET['redirect_to']))
{
	$viewName = mb_strtoupper(trim($_GET['redirect_to']));
	if ($viewName === '')
	{
		$viewName = Crm\Settings\EntityViewSettings::resolveName(
			Crm\Settings\CompanySettings::getCurrent()->getCurrentListViewID()
		);
	}
	$pathKey = "PATH_TO_COMPANY_{$viewName}";

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

$arResult['NAVIGATION_CONTEXT_ID'] = 'COMPANY';
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

	$arResult['VARIABLES']['company_ids'] = array_map('intval', $entityIDs);
}

\CCrmEntityHelper::setEnabledFactoryFlagByRequest(
	Crm\Settings\CompanySettings::getCurrent(),
	\Bitrix\Main\Application::getInstance()->getContext()->getRequest(),
);

if (
	\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled()
	&& ($componentPage === 'edit' || $componentPage === 'show')
)
{
	$redirectUrl = CComponentEngine::MakePathFromTemplate(
		$arResult['PATH_TO_COMPANY_DETAILS'],
		['company_id' => $arResult['VARIABLES']['company_id']]
	);

	if (isset($_SERVER['QUERY_STRING']))
	{
		parse_str($_SERVER['QUERY_STRING'], $queryParams);
		if (!empty($queryParams))
		{
			$redirectUrl = CHTTP::urlAddParams($redirectUrl, $queryParams, ['encode' => true]);
		}
	}

	LocalRedirect($redirectUrl, '301 Moved Permanently');
}
else
{
	$this->IncludeComponentTemplate($componentPage);
}
