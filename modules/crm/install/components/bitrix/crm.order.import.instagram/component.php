<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

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

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));

	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));

	return;
}

if (!\Bitrix\Catalog\Access\AccessController::getCurrent()->check(\Bitrix\Catalog\Access\ActionDictionary::ACTION_CATALOG_READ))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));

	return;
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

$arParams['IFRAME'] = isset($arParams['IFRAME']) ? $arParams['IFRAME'] : $request->get('IFRAME') === 'Y';

$arDefaultUrlTemplates404 = [
	'view' => '',
	'edit' => 'edit/',
	'feedback' => 'feedback/',
];

$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = [];
$componentPage = '';
$arComponentVariables = ['id'];

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = [];
	$arComponentVariables = ['id'];
	$arVariables = [];
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'view';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
}
else
{
	$arComponentVariables = [
		isset($arParams['VARIABLE_ALIASES']['id']) ? $arParams['VARIABLE_ALIASES']['id'] : 'id',
	];

	$arDefaultVariableAliases = ['id' => 'id'];
	$arVariables = [];
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'view';
	
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'edit';
	}
}

if (!is_array($arResult))
{
	$arResult = [];
}
else
{
	$arResult = array_merge(
		[
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] === 'Y' ? [] : $arVariableAliases,
		],
		$arResult
	);
}

$status = \Bitrix\Crm\Order\Import\Instagram::getStatus();

if ($componentPage === 'view' && (empty($status['ACTIVE']) || empty($status['CONNECTION'])))
{
	$componentPage = 'edit';
}

$curPage = $arParams['SEF_FOLDER'];
$arResult['PATH_TO_CONNECTOR_INSTAGRAM_VIEW'] = $arResult['PATH_TO_CONNECTOR_INSTAGRAM_VIEW_FULL'] = $curPage.$arUrlTemplates['view'];
$arResult['PATH_TO_CONNECTOR_INSTAGRAM_EDIT'] = $arResult['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL'] = $curPage.$arUrlTemplates['edit'];
$arResult['PATH_TO_CONNECTOR_INSTAGRAM_FEEDBACK'] = $curPage.$arUrlTemplates['feedback'];

$addParams = [];

if ($arParams['IFRAME'])
{
	$addParams['IFRAME'] = 'Y';
	$addParams['IFRAME_TYPE'] = 'SIDE_SLIDER';
}

if (!empty($addParams))
{
	$arResult['PATH_TO_CONNECTOR_INSTAGRAM_VIEW_FULL'] = (new \Bitrix\Main\Web\Uri($arResult['PATH_TO_CONNECTOR_INSTAGRAM_VIEW']))
		->addParams($addParams)
		->getUri();
	$arResult['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL'] = (new \Bitrix\Main\Web\Uri($arResult['PATH_TO_CONNECTOR_INSTAGRAM_EDIT']))
		->addParams($addParams)
		->getUri();
}

$this->IncludeComponentTemplate($componentPage);