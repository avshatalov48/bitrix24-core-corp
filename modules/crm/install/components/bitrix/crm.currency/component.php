<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;

$componentPage = '';
$arDefaultUrlTemplates404 = array(
	'currency_list' => '',
	'currency_add' => 'add/',
	'currency_edit' => 'edit/#currency_id#/'
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('currency_id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'currency_list';
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
	    isset($arParams['VARIABLE_ALIASES']['currency_id']) ? $arParams['VARIABLE_ALIASES']['currency_id'] : 'currency_id'
	);

	$arDefaultVariableAliases = array(
		'currency_id' => 'currency_id'
	);
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'currency_list';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'currency_edit';
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_CURRENCY_LIST'] = $curPage;
	$arResult['PATH_TO_CURRENCY_ADD'] = $curPage.'?add';
	$arResult['PATH_TO_CURRENCY_EDIT'] = $curPage.'?'.$arVariableAliases['currency_id'].'=#currency_id#&edit';
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'CURRENCY_ID' => isset($arVariables['currency_id']) ? strval($arVariables['currency_id']) : ''
		),
		$arResult
	);

if(!CCrmCurrency::EnsureReady())
{
	ShowError(CCrmCurrency::GetLastError());
}

$this->IncludeComponentTemplate($componentPage);
?>