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
	'activity_custom_type_list' => ''
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && $componentPage !== '' && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'activity_custom_type_list';
	}

	foreach ($arUrlTemplates as $url => $value)
	{
		$key = 'PATH_TO_'.strtoupper($url);
		$arResult[$key] = !empty($arParams[$key]) ? $arParams[$key] : $arParams['SEF_FOLDER'].$value;
	}
}
else
{
	$componentPage = 'activity_custom_type_list';
	$curPage = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST'] = $curPage;
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => array(),
			'ALIASES' => array(),
			'PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST' => CrmCheckPath('PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST', $arParams['PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST'], COption::GetOptionString('crm', 'path_to_activity_custom_type_list')),

		),
		$arResult
	);

$this->IncludeComponentTemplate($componentPage);
?>