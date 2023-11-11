<?

use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Status;

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

$arParams['FORM_NAME'] = preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['FORM_NAME']) ? $arParams['FORM_NAME'] : false;
$arParams['INPUT_NAME'] = preg_match('/^[a-zA-Z0-9_-]+$/', $arParams['INPUT_NAME']) ? $arParams['INPUT_NAME'] : false;
$arParams['SITE_ID'] = isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : SITE_ID;
$arResult["NAME"] = htmlspecialcharsbx($arParams["NAME"]);
$arResult["~NAME"] = $arParams["NAME"];

$arGetListParams = array('NAV_PARAMS' => array('nTopCount' => 15));
$arSelect = array();
if(array_key_exists('SELECT', $arParams))
{
	$arSelect = $arParams['SELECT'];
}
$arOrder = array("STATUS" => "ASC", "DEADLINE" => "DESC", "PRIORITY" => "DESC", "ID" => "DESC");
$arFilter = [
	'DOER' => \Bitrix\Tasks\Util\User::getId(),
	'STATUS' => [
		MetaStatus::UNSEEN,
		MetaStatus::EXPIRED,
		Status::NEW,
		Status::PENDING,
		Status::IN_PROGRESS,
	],
];

if (is_array($arParams["FILTER"]))
	$arFilter = array_merge($arFilter, $arParams["FILTER"]);

$dbRes = CTasks::GetList($arOrder, $arFilter, $arSelect, $arGetListParams);
$arResult["LAST_TASKS"] = array();
while ($arRes = $dbRes->GetNext())
{
	if (array_key_exists('TITLE', $arRes))
	{
		$arRes['TITLE'] = \Bitrix\Main\Text\Emoji::decode($arRes['TITLE']);
	}
	if (array_key_exists('DESCRIPTION', $arRes) && $arRes['DESCRIPTION'] !== '')
	{
		$arRes['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::decode($arRes['DESCRIPTION']);
	}
	$arResult["LAST_TASKS"][] = $arRes;
}

// current tasks
if (!is_array($arParams['VALUE']))
	$arParams['VALUE'] = explode(',', $arParams['VALUE']);

foreach ($arParams['VALUE'] as $key => $ID)
	$arParams['VALUE'][$key] = intval(trim($ID));

$arParams['VALUE'] = array_unique($arParams['VALUE']);

$arResult["CURRENT_TASKS"] = array();
if (sizeof($arParams["VALUE"]))
{
	$dbRes = CTasks::GetList(
		array('TITLE' => 'ASC'),
		array('ID'    => $arParams['VALUE'])
	);

	while ($arRes = $dbRes->GetNext())
	{
		if (array_key_exists('TITLE', $arRes))
		{
			$arRes['TITLE'] = \Bitrix\Main\Text\Emoji::decode($arRes['TITLE']);
		}
		if (array_key_exists('DESCRIPTION', $arRes) && $arRes['DESCRIPTION'] !== '')
		{
			$arRes['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::decode($arRes['DESCRIPTION']);
		}
		$arResult["CURRENT_TASKS"][] = $arRes;
	}
}

$APPLICATION->AddHeadScript($this->GetPath().'/templates/.default/tasks.js');

$this->IncludeComponentTemplate();