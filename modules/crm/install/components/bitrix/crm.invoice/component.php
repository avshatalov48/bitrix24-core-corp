<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Restriction\AvailabilityManager;

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

if(!CAllCrmInvoice::installExternalEntities())
	return;
if(!CCrmQuote::LocalComponentCausedUpdater())
	return;

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

$showRecurring = \Bitrix\Crm\Recurring\Manager::isAllowedExpose(\Bitrix\Crm\Recurring\Manager::INVOICE);

global $APPLICATION;

$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'list' => 'list/',
	'recur' => 'recur/',
	'edit' => 'edit/#invoice_id#/',
	'recur_edit' => 'recur/edit/#invoice_id#/',
	'recur_show' => 'recur/show/#invoice_id#/',
	'recur_expose' => 'recur/edit/#invoice_id#/?expose=Y',
	'show' => 'show/#invoice_id#/',
	'payment' => 'payment/#invoice_id#/',
	'widget' => 'widget/',
	'kanban' => 'kanban/'
);

$arDefaultVariableAliases404 = array(

);
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('invoice_id');

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if ($showRecurring && in_array($componentPage, array("recur", "recur_edit", "recur_expose", "recur_show")))
		$arResult['IS_RECURRING'] = 'Y';

	if ($componentPage == "recur")
	{
		$componentPage = 'list';
	}
	elseif (($componentPage == "recur_edit" || $componentPage == "recur_expose"))
	{
		$componentPage = 'edit';
	}
	elseif ($componentPage == "recur_show")
	{
		$componentPage = 'show';
	}

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
		$componentPage = 'index';

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$pathToInvoiceKey = 'PATH_TO_INVOICE_' . mb_strtoupper($url);
		$pathToInvoice = $arParams[$pathToInvoiceKey] ?? null;
		if (empty($pathToInvoice))
		{
			$arResult[$pathToInvoiceKey] = $arParams['SEF_FOLDER'] . $value;
		}
		else
		{
			$arResult[$pathToInvoiceKey] = $arParams['PATH_TO_' . mb_strtoupper($url)];
		}
	}
}
else
{
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['invoice_id'];

	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['recur']) && isset($_REQUEST['edit']) && $showRecurring)
	{
		$componentPage = 'edit';
		$arResult['IS_RECURRING'] = 'Y';
	}
	else if (isset($_REQUEST['edit']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['copy']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['recur']) && isset($_REQUEST['show']) && $showRecurring)
	{
		$componentPage = 'show';
		$arResult['IS_RECURRING'] = 'Y';
	}
	else if (isset($_REQUEST['show']))
		$componentPage = 'show';
	else if (isset($_REQUEST['payment']))
		$componentPage = 'payment';
	else if (isset($_REQUEST['recur']))
	{
		$componentPage = 'list';
		if ($showRecurring)
			$arResult['IS_RECURRING'] = 'Y';
	}

	$curPage = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_INVOICE_LIST'] = $curPage;
	$arResult['PATH_TO_INVOICE_SHOW'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&show";
	$arResult['PATH_TO_INVOICE_EDIT'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&edit";
	$arResult['PATH_TO_INVOICE_RECUR_EDIT'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&edit&recur";
	$arResult['PATH_TO_INVOICE_RECUR_SHOW'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&show&recur";
	$arResult['PATH_TO_INVOICE_RECUR_EXPOSE'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&edit&recur&expose=Y";
	$arResult['PATH_TO_INVOICE_PAYMENT'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&payment";
	$arResult['PATH_TO_PRODUCT_EDIT'] = $curPage."?$arVariableAliases[product_id]=#product_id#&edit";
	$arResult['PATH_TO_PRODUCT_SHOW'] = $curPage."?$arVariableAliases[product_id]=#product_id#&show";
	$arResult['PATH_TO_INVOICE_WIDGET'] = $curPage."?widget";
	$arResult['PATH_TO_INVOICE_RECUR'] = $curPage."?recur";
	$arResult['PATH_TO_INVOICE_KANBAN'] = $curPage."?kanban";
}

$arResult = array_merge(
	array(
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] == 'Y'? array(): $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
		'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'],
		'PATH_TO_QUOTE_SHOW' => $arParams['PATH_TO_QUOTE_SHOW'],
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
		'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],
	),
	$arResult
);

if(isset($_GET['redirect_to']))
{
	$viewName = mb_strtoupper(trim($_GET['redirect_to']));
	if($viewName === '')
	{
		$viewName = Crm\Settings\EntityViewSettings::resolveName(
			Crm\Settings\InvoiceSettings::getCurrent()->getCurrentListViewID()
		);
	}
	$pathKey = "PATH_TO_INVOICE_{$viewName}";
	if(isset($arResult[$pathKey]))
	{
		$redirectUrl = CHTTP::urlAddParams(
			$arResult[$pathKey],
			array_diff_key($_GET, array_flip(array('redirect_to'))),
			array('encode' => true)
		);
		LocalRedirect($redirectUrl);
	}
}

$arResult['NAVIGATION_CONTEXT_ID'] = 'INVOICE';
if($componentPage === 'index')
{
	$componentPage = 'list';
}

$hasPermissions = Crm\Restriction\RestrictionManager::getInvoicesRestriction()->hasPermission();
if (!$hasPermissions)
{
	$componentPage = 'restrictions';
}

$toolsManager = Crm\Service\Container::getInstance()->getIntranetToolsManager();
$isAvailable = $toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Invoice);
if (!$isAvailable)
{
	$componentPage = 'disabled';
}

$this->IncludeComponentTemplate($componentPage);