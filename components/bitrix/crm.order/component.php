<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var CMain $APPLICATION */
/** @var array $arResult */

use Bitrix\Catalog;
use Bitrix\Crm\Settings\OrderSettings;
use Bitrix\Crm\Product\Url;
use Bitrix\Crm\Service;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
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

$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'list' => 'list/',
	'details' => 'details/#order_id#/',
	'check_details' => 'check/details/#check_id#/',
	'shipment_details' => 'shipment/details/#shipment_id#/',
	'payment_details' => 'payment/details/#payment_id#/',
	'automation' => 'automation/#category_id#/',
	'analytics/list' => 'analytics/list/',
	'kanban' => 'kanban/',
);

$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('order_id', 'check_id', 'shipment_id', 'payment_id');

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arParams['BUILDER_CONTEXT'] = $arParams['BUILDER_CONTEXT'] ?? '';
if (
	$arParams['BUILDER_CONTEXT'] !== Catalog\Url\ShopBuilder::TYPE_ID
	&& $arParams['BUILDER_CONTEXT'] !== Url\ProductBuilder::TYPE_ID
)
{
	$arParams['BUILDER_CONTEXT'] = Catalog\Url\ShopBuilder::TYPE_ID;
}

if ($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
		$componentPage = 'index';

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		if($arParams['PATH_TO_ORDER_'.mb_strtoupper($url)] == '')
			$arResult['PATH_TO_ORDER_'.mb_strtoupper($url)] = $arParams['SEF_FOLDER'].$value;
		else
			$arResult['PATH_TO_ORDER_'.mb_strtoupper($url)] = $arParams['PATH_TO_'.mb_strtoupper($url)];
	}
}
else
{
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['order_id'];
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['check_id'];
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['shipment_id'];
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['payment_id'];

	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['check']))
		$componentPage = 'check_details';
	if (isset($_REQUEST['shipment']))
		$componentPage = 'shipment_details';
	if (isset($_REQUEST['payment']))
		$componentPage = 'payment_details';
	else if (isset($_REQUEST['edit']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['copy']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['card']))
		$componentPage = 'card';
	else if (isset($_REQUEST['show']))
		$componentPage = 'show';
	else if (isset($_REQUEST['details']))
		$componentPage = 'details';
	else if (isset($_REQUEST['import']))
		$componentPage = 'import';
	else if (isset($_REQUEST['automation']))
		$componentPage = 'automation';

	$arResult['PATH_TO_ORDER_LIST'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_ORDER_SHOW'] = $APPLICATION->GetCurPage()."?{$arVariableAliases['order_id']}=#order_id#&show";
	$arResult['PATH_TO_ORDER_DETAILS'] = $APPLICATION->GetCurPage()."?{$arVariableAliases['order_id']}=#order_id#&details";
	$arResult['PATH_TO_ORDER_CHECK_DETAILS'] = $APPLICATION->GetCurPage()."?{$arVariableAliases['check_id']}=#check_id#&details";
	$arResult['PATH_TO_ORDER_SHIPMENT_DETAILS'] = $APPLICATION->GetCurPage()."?{$arVariableAliases['shipment_id']}=#shipment_id#&details";
	$arResult['PATH_TO_ORDER_PAYMENT_DETAILS'] = $APPLICATION->GetCurPage()."?{$arVariableAliases['payment_id']}=#payment_id#&details";
	$arResult['PATH_TO_ORDER_EDIT'] = $APPLICATION->GetCurPage()."?{$arVariableAliases['order_id']}=#order_id#&edit";
	$arResult['PATH_TO_ORDER_IMPORT'] = $APPLICATION->GetCurPage()."?import";
	$arResult['PATH_TO_ORDER_KANBAN'] = $APPLICATION->GetCurPage()."?kanban";
	$arResult['PATH_TO_ORDER_PAYMENT'] = $APPLICATION->GetCurPage()."?{$arVariableAliases['order_id']}=#order_id#&payment";
	$arResult['PATH_TO_ORDER_AUTOMATION'] = $APPLICATION->GetCurPage()."?automation";
}

$arResult = array_merge(
	array(
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] == 'Y'? array(): $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_BUYER_PROFILE' => '/shop/settings/sale_buyers_profile/?USER_ID=#user_id#&lang=' . LANGUAGE_ID,
	),
	$arResult
);

$arResult['NAVIGATION_CONTEXT_ID'] = 'ORDER';

if($componentPage === 'index')
{
	$componentPage = (OrderSettings::getCurrent()->getCurrentListViewID() == OrderSettings::VIEW_KANBAN) ? 'kanban' : 'list';
}

if (
	!CCrmSaleHelper::isWithOrdersMode()
	&& (
		$componentPage === 'index'
		|| $componentPage === 'list'
		|| $componentPage === 'automation'
		|| $componentPage === 'analytics/list'
		|| $componentPage === 'kanban'
		|| (
			(
				$componentPage === 'details'
				&& (int)$arResult['VARIABLES']['order_id'] === 0
			)
		)
	)
)
{
	LocalRedirect(Service\Container::getInstance()->getRouter()->getItemListUrl(\CCrmOwnerType::Deal));
}

$this->IncludeComponentTemplate($componentPage);
