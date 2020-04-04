<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
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

/**@var $USER \CUser*/
global $USER;
$CrmPerms = new CCrmPerms($USER->GetID());
if($CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['IFRAME'] = isset($arParams['IFRAME']) ? $arParams['IFRAME'] : true;
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);


$arDefaultUrlTemplates404 = array(
	'list' => '',
	'edit' => 'edit/#id#/',
	'design' => 'design/#id#/',
	'fill' => 'fill/#id#/',
	'ads' => 'ads/#id#/?type=#ads_type#',
);

$arDefaultVariableAliases404 = array(

);
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('id');

if ($arParams['SEF_MODE'] == 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'list';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$key = 'PATH_TO_WEB_FORM_'.strtoupper($url);
		$arResult[$key] = isset($arParams[$key][0]) ? $arParams[$key] : $arParams['SEF_FOLDER'] . $value;
	}
}
else
{
	$arComponentVariables = array(
		isset($arParams['VARIABLE_ALIASES']['id']) ? $arParams['VARIABLE_ALIASES']['id'] : 'id'
	);

	$arDefaultVariableAliases = array(
		'id' => 'id'
	);
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'list';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'edit';
	}
	if (isset($_REQUEST['design']))
	{
		$componentPage = 'design';
	}
	if (isset($_REQUEST['ads']))
	{
		$componentPage = 'ads';
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_WEB_FORM_LIST'] = $curPage;
	$arResult['PATH_TO_WEB_FORM_EDIT'] = $curPage.'?'.$arVariableAliases['id'].'=#id#&edit';
	$arResult['PATH_TO_WEB_FORM_ADS'] = $curPage.'?'.$arVariableAliases['id'].'=#id#&type=#ads_type#&ads';

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
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'FORM_ID' => isset($arVariables['id']) ? strval($arVariables['id']) : '',
			'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
			'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
			'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
			'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE']
		),
		$arResult
	);
}

$this->IncludeComponentTemplate($componentPage);
?>