<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

/** @var array $arParams */
/** @var array $arResult */

$taskData = $arParams['TEMPLATE_DATA']['DATA']['TASK'];
$scrumData = $arParams['TEMPLATE_DATA']['DATA']['SCRUM'];

$arParams['TEMPLATE_DATA']['PATH_TO_TEMPLATES_TEMPLATE'] = UI\Task\Template::makeActionUrl(
	$arParams['PATH_TO_TEMPLATES_TEMPLATE'],
	($taskData['SE_TEMPLATE']['ID'] ?? null),
	'view'
);
$arParams['TEMPLATE_DATA']['PATH_TO_TEMPLATES_TEMPLATE_SOURCE'] = UI\Task\Template::makeActionUrl(
	$arParams['PATH_TO_TEMPLATES_TEMPLATE'],
	($taskData['SE_TEMPLATE.SOURCE']['ID'] ?? null),
	'view'
);

$arParams['TEMPLATE_DATA']['EPIC'] = $scrumData['EPIC'];

$arParams['TEMPLATE_DATA']['TAGS'] = UI\Task\Tag::formatTagString($taskData['SE_TAG'] ?? null);

//Dates
$dates = [
	'STATUS_CHANGED_DATE',
	'DEADLINE',
	'CREATED_DATE',
	'START_DATE_PLAN',
	'END_DATE_PLAN',
];
foreach ($dates as $date)
{
	$formattedDate = "";
	if (isset($taskData[$date]) && mb_strlen($taskData[$date]))
	{
		$culture = Context::getCurrent()->getCulture();
		$format = "{$culture->getShortDateFormat()} {$culture->getShortTimeFormat()}";
		$parsed = UI::parseDateTime($taskData[$date]);
		$formattedDate = FormatDate($format, $parsed);
	}
	$arParams['TEMPLATE_DATA'][$date] = $formattedDate;
}

$currentUserId = User::getId();
$members = array_merge(
	(is_array($taskData['SE_ORIGINATOR']) ? [$taskData['SE_ORIGINATOR']] : []),
	(is_array($taskData['SE_RESPONSIBLE']) ? $taskData['SE_RESPONSIBLE'] : []),
	(is_array($taskData['SE_ACCOMPLICE']) ? $taskData['SE_ACCOMPLICE'] : []),
	(is_array($taskData['SE_AUDITOR']) ? $taskData['SE_AUDITOR'] : [])
);

$iAmAuditor = false;
if (is_array($taskData['SE_AUDITOR']))
{
	foreach ($taskData['SE_AUDITOR'] as $user)
	{
		if ((int)$user['ID'] === $currentUserId)
		{
			$iAmAuditor = true;
			break;
		}
	}
}

$showIntranetControl = false;
foreach ($members as $member)
{
	if (
		(int)$member['ID'] === $currentUserId
		&& !$member['IS_EMAIL_USER']
	)
	{
		$showIntranetControl = true;
		break;
	}
}

$arParams['TEMPLATE_DATA']['I_AM_AUDITOR'] = $iAmAuditor;
$arParams['TEMPLATE_DATA']['SHOW_INTRANET_CONTROL'] = $showIntranetControl;

if ($arParams['USER'])
{
	$arParams['USER'] = User::extractPublicData($arParams['USER']);
	$arParams['USER']['AVATAR'] = UI::getAvatar($arParams['USER']['PERSONAL_PHOTO'], 58, 58);
}