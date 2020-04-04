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
	'tax_list' => '',
	'tax_add' => 'add/',
	'tax_edit' => 'edit/#tax_id#/',
	'tax_show' => 'show/#tax_id#/'
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('tax_id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'tax_list';
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
		isset($arParams['VARIABLE_ALIASES']['tax_id']) ? $arParams['VARIABLE_ALIASES']['tax_id'] : 'tax_id'
	);

	$arDefaultVariableAliases = array(
		'tax_id' => 'tax_id'
	);
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'tax_list';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'tax_edit';
	}
	elseif (isset($_REQUEST['show']))
	{
		$componentPage = 'tax_show';
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_TAX_LIST'] = $curPage;
	$arResult['PATH_TO_TAX_ADD'] = $curPage.'?add';
	$arResult['PATH_TO_TAX_EDIT'] = $curPage.'?'.$arVariableAliases['tax_id'].'=#tax_id#&edit';
	$arResult['PATH_TO_TAX_SHOW'] = $curPage.'?'.$arVariableAliases['tax_id'].'=#tax_id#&show';
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'TAX_ID' => isset($arVariables['tax_id']) ? strval($arVariables['tax_id']) : ''
		),
		$arResult
	);

$this->IncludeComponentTemplate($componentPage);
?>