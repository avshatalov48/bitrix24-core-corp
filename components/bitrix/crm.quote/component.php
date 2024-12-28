<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
$isAvailable = $toolsManager->checkCrmAvailability();
if (!$isAvailable)
{
	print AvailabilityManager::getInstance()->getCrmInaccessibilityContent();

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

$arDefaultUrlTemplates404 = [
	'index' => 'index.php',
	'list' => 'list/',
	'import' => 'import/',
	'kanban' => 'kanban/',
	'edit' => 'edit/#quote_id#/',
	'show' => 'show/#quote_id#/',
	'details' => 'details/#quote_id#/',
	'payment' => 'payment/#quote_id#/',
	'deadlines' => 'deadlines/',
];

$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = [];
$componentPage = '';
$arComponentVariables = ['quote_id'];

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

	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams['SEF_FOLDER'],
		$arUrlTemplates,
		$arVariables
	);

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
	{
		$componentPage = 'index';
	}

	CComponentEngine::InitComponentVariables(
		$componentPage,
		$arComponentVariables,
		$arVariableAliases,
		$arVariables
	);

	foreach ($arUrlTemplates as $url => $value)
	{
		$paramName = 'PATH_TO_QUOTE_' . mb_strtoupper($url);

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
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['quote_id'];

	$arVariables = [];
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases(
		$arDefaultVariableAliases,
		$arParams['VARIABLE_ALIASES'] ?? []
	);
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
	else if (isset($_REQUEST['import']))
	{
		$componentPage = 'import';
	}

	$arResult['PATH_TO_QUOTE_LIST'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_QUOTE_SHOW'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['quote_id']}=#quote_id#&show";
	$arResult['PATH_TO_QUOTE_DETAILS'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['quote_id']}=#quote_id#&details";
	$arResult['PATH_TO_QUOTE_EDIT'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['quote_id']}=#quote_id#&edit";
	$arResult['PATH_TO_QUOTE_IMPORT'] = $APPLICATION->GetCurPage() . "?import";
	$arResult['PATH_TO_QUOTE_KANBAN'] = $APPLICATION->GetCurPage() . "?kanban";
	$arResult['PATH_TO_QUOTE_PAYMENT'] = $APPLICATION->GetCurPage() . "?{$arVariableAliases['quote_id']}=#quote_id#&payment";
}

$arResult['PATH_TO_QUOTE_DETAILS'] = Container::getInstance()->getRouter()->getItemDetailUrlCompatibleTemplate(\CCrmOwnerType::Quote);
$arResult['PATH_TO_MERGE'] = Container::getInstance()->getRouter()->getEntityMergeUrl(CCrmOwnerType::Quote);

$arResult = array_merge(
	[
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] === 'Y' ? [] : $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'] ?? '',
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'] ?? '',
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'] ?? '',
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'] ?? '',
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'] ?? '',
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'] ?? '',
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'] ?? '',
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
			Crm\Settings\QuoteSettings::getCurrent()->getCurrentListViewID()
		);
	}

	$pathKey = "PATH_TO_QUOTE_{$viewName}";

	if (isset($arResult[$pathKey]))
	{
		$redirectUrl = CHTTP::urlAddParams(
			$arResult[$pathKey],
			array_diff_key($_GET, array_flip(array('redirect_to'))),
			['encode' => true]
		);

		LocalRedirect($redirectUrl);
	}
}

$arResult['NAVIGATION_CONTEXT_ID'] = 'QUOTE';
if ($componentPage === 'index')
{
	$componentPage = 'list';
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

\CCrmEntityHelper::setEnabledFactoryFlagByRequest(
	Crm\Settings\QuoteSettings::getCurrent(),
	$request,
);

if (
		($componentPage === 'edit' || $componentPage === 'show' || $componentPage === 'details')
		&& \CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::Quote)
	)
{
	$url = Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Quote, (int) $arResult['VARIABLES']['quote_id']);

	$url->addParams($request->getQueryList()->toArray());

	LocalRedirect($url, false, '301 Moved Permanently');
}

if (
	$componentPage !== 'details'
	&& !Crm\Restriction\RestrictionManager::getQuotesRestriction()->hasPermission()
)
{
	$componentPage = 'restrictions';
}

$toolsManager = Crm\Service\Container::getInstance()->getIntranetToolsManager();
$isAvailable = $toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Quote);
if (!$isAvailable)
{
	$componentPage = 'disabled';
}

$router = Container::getInstance()->getRouter();
if ($componentPage === 'kanban')
{
	$router->checkAndUpdateCurrentListView($router::LIST_VIEW_KANBAN, \CCrmOwnerType::Quote);
}
elseif ($componentPage === 'list')
{
	$router->checkAndUpdateCurrentListView($router::LIST_VIEW_LIST, \CCrmOwnerType::Quote);
}
elseif ($componentPage === 'deadlines')
{
	$router->checkAndUpdateCurrentListView($router::LIST_VIEW_DEADLINES, \CCrmOwnerType::Quote);
}

$this->IncludeComponentTemplate($componentPage);
