<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$arDefaultUrlTemplates404 = array(
	"index" => "index.php",
	"edit" => "#entity#/#category#/",
);
$arDefaultVariableAliases404 = $arDefaultVariableAliases = array();
$arComponentVariables = array('index', 'edit');
$arVariables = array();

if(($arParams['SEF_MODE'] ?? null) === 'Y')
{
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);

	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams['SEF_FOLDER'],
		$arUrlTemplates,
		$arVariables
	);

	if(!$componentPage)
		$componentPage = 'index';

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
	$arResult = array(
		'FOLDER' => $arParams['SEF_FOLDER'],
		'URL_TEMPLATES' => $arUrlTemplates,
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases,
	);
}
elseif (!isset($arParams['ENTITY_TYPE_ID']))
{
	ShowError('SEF MODE ONLY');
	return;
}

if (isset($arParams['ENTITY_TYPE_ID']) && isset($arParams['ENTITY_CATEGORY']))
{
	$arResult['ENTITY_TYPE_ID'] = (int) $arParams['ENTITY_TYPE_ID'];
	$arResult['ENTITY_CATEGORY'] = (int) $arParams['ENTITY_CATEGORY'];
}
else
{
	$arResult['ENTITY_TYPE_ID'] = isset($arVariables['entity'])
		? \CCrmOwnerType::ResolveID($arVariables['entity']) : \CCrmOwnerType::LeadName;
	$arResult['ENTITY_CATEGORY'] = isset($arVariables['category'])  ? (int)$arVariables['category'] : 0;
}

if (!\CCrmOwnerType::IsDefined($arResult['ENTITY_TYPE_ID']))
{
	$arResult['ENTITY_TYPE_ID'] = \CCrmOwnerType::Lead;
	$arResult['ENTITY_CATEGORY'] = 0;
}

$arResult['ENTITY_TYPE_NAME'] = \CCrmOwnerType::ResolveName($arResult['ENTITY_TYPE_ID']);
$arResult['CATEGORY_NAME'] = '';

$categories = array();

if ($arResult['ENTITY_TYPE_ID'] === \CCrmOwnerType::Deal)
{
	$dealCategories = \Bitrix\Crm\Category\DealCategory::getSelectListItems();
	$arResult['CATEGORY_NAME'] = $dealCategories[0];
	foreach ($dealCategories as $id => $category)
	{
		$categories[] = array('id' => $id, 'name' => $category);
		if ($id == $arResult['ENTITY_CATEGORY'])
		{
			$arResult['CATEGORY_NAME'] = $category;
		}
	}
}

$arResult['SUBTITLE'] = CCrmOwnerType::GetCategoryCaption($arResult['ENTITY_TYPE_ID']);
$arResult['CATEGORIES'] = $categories;

if (isset($arParams['SET_TITLE']) && $arParams['SET_TITLE'] === 'Y')
{
	$APPLICATION->SetTitle(GetMessage('CRM_AUTOMATION_TITLE_1'));
}

$this->IncludeComponentTemplate();