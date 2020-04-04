<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
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

global $APPLICATION;

$componentPage = '';
$arDefaultUrlTemplates404 = array(
	'vat_list' => '',
	'vat_add' => 'add/',
	'vat_edit' => 'edit/#vat_id#/',
	'vat_show' => 'show/#vat_id#/'
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('vat_id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'vat_list';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$key = 'PATH_TO_'.strtoupper($url);
		$arResult[$key] = isset($arParams[$key][0]) ? $arParams[$key] : $arParams['SEF_FOLDER'].$value;
	}
}
else
{
	$arComponentVariables = array(
		isset($arParams['VARIABLE_ALIASES']['vat_id']) ? $arParams['VARIABLE_ALIASES']['vat_id'] : 'vat_id'
	);

	$arDefaultVariableAliases = array(
		'vat_id' => 'vat_id'
	);
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'vat_list';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'vat_edit';
	}
	elseif (isset($_REQUEST['show']))
	{
		$componentPage = 'vat_show';
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_VAT_LIST'] = $curPage;
	$arResult['PATH_TO_VAT_ADD'] = $curPage.'?add';
	$arResult['PATH_TO_VAT_EDIT'] = $curPage.'?'.$arVariableAliases['vat_id'].'=#vat_id#&edit';
	$arResult['PATH_TO_VAT_SHOW'] = $curPage.'?'.$arVariableAliases['vat_id'].'=#vat_id#&show';
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'VAT_ID' => isset($arVariables['vat_id']) ? strval($arVariables['vat_id']) : ''
		),
		$arResult
	);

$this->IncludeComponentTemplate($componentPage);
?>