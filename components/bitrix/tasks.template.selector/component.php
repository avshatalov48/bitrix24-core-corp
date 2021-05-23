<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

$arParams['MULTIPLE'] = $arParams['MULTIPLE'] == 'Y' ? 'Y' : 'N'; // allow multiple tasks selection

// Hide plus/minus icons
if (isset($arParams['HIDE_ADD_REMOVE_CONTROLS']) && ($arParams['HIDE_ADD_REMOVE_CONTROLS'] === 'Y'))
	$arParams['HIDE_ADD_REMOVE_CONTROLS'] = 'Y';
else
	$arParams['HIDE_ADD_REMOVE_CONTROLS'] = 'N';

$userId = \Bitrix\Tasks\Util\User::getId();

$arParams['FORM_NAME'] = preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['FORM_NAME']) ? $arParams['FORM_NAME'] : false;
$arParams['INPUT_NAME'] = preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['INPUT_NAME']) ? $arParams['INPUT_NAME'] : false;
$arParams['SITE_ID'] = isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : SITE_ID;
$arResult["NAME"] = htmlspecialcharsbx($arParams["NAME"]);
$arResult["~NAME"] = $arParams["NAME"];

$arNavParams = array('NAV_PARAMS' => array('nTopCount' => 15));
$arSelect = array();
$arOrder = array("ID" => "DESC");
$arFilter = array(
	'CREATED_BY' => $userId,
	'!TPARAM_TYPE' => CTaskTemplates::TYPE_FOR_NEW_USER
);
$arGetListParams = array(
	'USER_ID' => $userId,
	'USER_IS_ADMIN' => \Bitrix\Tasks\Integration\SocialNetwork\User::isAdmin(),
);

if (is_array($arParams["FILTER"]))
	$arFilter = array_merge($arFilter, $arParams["FILTER"]);

if(intval($arParams['TEMPLATE_ID']))
{
	$arFilter['BASE_TEMPLATE_ID'] = intval($arParams['TEMPLATE_ID']);
	$arFilter['!=ID'] = intval($arParams['TEMPLATE_ID']); // do not link to itself
	$arGetListParams['EXCLUDE_TEMPLATE_SUBTREE'] = true; // do not link to it`s subtree
}

$dbRes = CTaskTemplates::GetList($arOrder, $arFilter, $arNavParams, $arGetListParams, $arSelect);
$arResult["LAST_TEMPLATES"] = array();
while ($arRes = $dbRes->GetNext())
	$arResult["LAST_TEMPLATES"][] = $arRes;

// current templates
if (!is_array($arParams['VALUE']))
	$arParams['VALUE'] = explode(',', $arParams['VALUE']);

foreach ($arParams['VALUE'] as $key => $ID)
	$arParams['VALUE'][$key] = intval(trim($ID));

$arParams['VALUE'] = array_unique($arParams['VALUE']);

$arResult["CURRENT_TEMPLATES"] = array();
if (sizeof($arParams["VALUE"]))
{
	$dbRes = CTaskTemplates::GetList(
		array('TITLE' => 'ASC'),
		array(
			'ID'    => $arParams['VALUE'],
			'!TPARAM_TYPE' => CTaskTemplates::TYPE_FOR_NEW_USER
		),
		false,
		$arGetListParams
	);

	while ($arRes = $dbRes->GetNext())
		$arResult["CURRENT_TEMPLATES"][] = $arRes;
}

$this->IncludeComponentTemplate();
