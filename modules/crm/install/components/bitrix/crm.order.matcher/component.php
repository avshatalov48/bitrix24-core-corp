<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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

$arParams['IFRAME'] = isset($arParams['IFRAME']) ? $arParams['IFRAME'] : false;
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE'])
	? CSite::GetNameFormat(false)
	: str_replace(["#NOBR#","#/NOBR#"], array("",""), $arParams["NAME_TEMPLATE"]);


$arDefaultUrlTemplates404 = [
	'form' => '#person_type_id#/',
	'property' => '#person_type_id#/prop/#property_id#/'
];

$arDefaultVariableAliases404 = [];
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('id');

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'form';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	// ToDO ?
	//foreach ($arUrlTemplates as $url => $value)
	//{
	//	$key = 'PATH_TO_ORDER_'.strtoupper($url);
	//	$arResult[$key] = isset($arParams[$key][0]) ? $arParams[$key] : $arParams['SEF_FOLDER'] . $value;
	//}
}
else
{
	$arComponentVariables = array(
		isset($arParams['VARIABLE_ALIASES']['id']) ? $arParams['VARIABLE_ALIASES']['id'] : 'id'
	);

	$arDefaultVariableAliases = ['id' => 'id'];
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'form';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'property';
	}
}

if (!is_array($arResult))
{
	$arResult = array();
}
else
{
	$arResult =	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] === 'Y' ? [] : $arVariableAliases
		),
		$arResult
	);
}

$curPage = $arParams['SEF_FOLDER'];
$arResult['PATH_TO_ORDER_FORM'] = $curPage;
$arResult['PATH_TO_ORDER_FORM_WITH_PT'] = $curPage.$arUrlTemplates['form'];
$arResult['PATH_TO_ORDER_PROPERTY_EDIT'] = $curPage.$arUrlTemplates['property'];;

$this->IncludeComponentTemplate($componentPage);
?>
