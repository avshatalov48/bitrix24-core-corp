<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */

$taskData = $arParams["TEMPLATE_DATA"]["DATA"]["TASK"];

$arParams["TEMPLATE_DATA"]["PATH_TO_TEMPLATES_TEMPLATE"] = \Bitrix\Tasks\UI\Task\Template::makeActionUrl($arParams["PATH_TO_TEMPLATES_TEMPLATE"], $taskData["SE_TEMPLATE"]["ID"], 'view');
$arParams["TEMPLATE_DATA"]["PATH_TO_TEMPLATES_TEMPLATE_SOURCE"] = \Bitrix\Tasks\UI\Task\Template::makeActionUrl($arParams["PATH_TO_TEMPLATES_TEMPLATE"], $taskData["SE_TEMPLATE.SOURCE"]["ID"], 'view');

$arParams["TEMPLATE_DATA"]["TAGS"] = \Bitrix\Tasks\UI\Task\Tag::formatTagString($taskData["SE_TAG"]);

//Dates
$dates = array(
	"STATUS_CHANGED_DATE",
	"DEADLINE",
	"CREATED_DATE",
	"START_DATE_PLAN",
	"END_DATE_PLAN"
);

foreach ($dates as $date)
{
	$formattedDate = "";
	if (isset($taskData[$date]) && strlen($taskData[$date]))
	{
		$formattedDate = \Bitrix\Tasks\UI::formatDateTime(\Bitrix\Tasks\UI::parseDateTime($taskData[$date]), '^'.\Bitrix\Tasks\UI::getDateTimeFormat());
	}
	
	$arParams["TEMPLATE_DATA"][$date] = $formattedDate;
}

$iAmAuditor = false;
$currentUserId = \Bitrix\Tasks\Util\User::getId();
if(is_array($taskData["SE_AUDITOR"]))
{
	foreach($taskData["SE_AUDITOR"] as $user)
	{
		if($user['ID'] == $currentUserId)
		{
			$iAmAuditor = true;
			break;
		}
	}
}

if($arParams['USER'])
{
	$arParams['USER'] = \Bitrix\Tasks\Util\User::extractPublicData($arParams['USER']);
	$arParams['USER']['AVATAR'] = \Bitrix\Tasks\UI::getAvatar($arParams['USER']['PERSONAL_PHOTO'], 58, 58);
}

$arParams['TEMPLATE_DATA']['I_AM_AUDITOR'] = $iAmAuditor;