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
	'mail_template_list' => '',
	'mail_template_add' => 'add/',
	'mail_template_edit' => 'edit/#element_id#/'
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('element_id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && $componentPage !== '' && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'mail_template_list';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$key = 'PATH_TO_'.strtoupper($url);
		$arResult[$key] = !empty($arParams[$key]) ? $arParams[$key] : $arParams['SEF_FOLDER'].$value;
	}
}
else
{
	$arComponentVariables = array(!empty($arParams['VARIABLE_ALIASES']['element_id'])
			? $arParams['VARIABLE_ALIASES']['element_id'] : 'element_id'
	);

	$arDefaultVariableAliases = array('element_id' => 'element_id');
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'mail_template_list';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'mail_template_edit';
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_MAIL_TEMPLATE_LIST'] = $curPage;
	$arResult['PATH_TO_MAIL_TEMPLATE_ADD'] = $curPage.'?add';
	$arResult['PATH_TO_MAIL_TEMPLATE_EDIT'] = $curPage.'?'.$arVariableAliases['element_id'].'=#element_id#&edit';
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'ELEMENT_ID' => isset($arVariables['element_id']) ? $arVariables['element_id'] : ''
		),
		$arResult
	);

$this->IncludeComponentTemplate($componentPage);
?>