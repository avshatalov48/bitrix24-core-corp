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
	'ps_list' => '',
	'ps_add' => 'add/',
	'ps_edit' => 'edit/#ps_id#/',
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('ps_id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'ps_list';
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
		isset($arParams['VARIABLE_ALIASES']['ps_id']) ? $arParams['VARIABLE_ALIASES']['ps_id'] : 'ps_id'
	);

	$arDefaultVariableAliases = array(
		'ps_id' => 'ps_id'
	);
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'ps_list';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'ps_edit';
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_PS_LIST'] = $curPage;
	$arResult['PATH_TO_PS_ADD'] = $curPage.'?add';
	$arResult['PATH_TO_PS_EDIT'] = $curPage.'?'.$arVariableAliases['ps_id'].'=#ps_id#&edit';
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'PS_ID' => isset($arVariables['ps_id']) ? strval($arVariables['ps_id']) : ''
		),
		$arResult
	);

$this->IncludeComponentTemplate($componentPage);
?>