<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Util\User;

//region TITLE
$APPLICATION->SetPageProperty('title', Loc::getMessage('TASKS_EFFECTIVE_TITLE_FULL'));
$APPLICATION->SetTitle(Loc::getMessage('TASKS_EFFECTIVE_TITLE_FULL'));
//endregion TITLE

if (!function_exists('prepareTaskRowUserBaloonHtml'))
{
	function prepareTaskRowUserBaloonHtml($userId, $taskId, $arParams)
	{
		$pathToUserProfile = $arParams['PATH_TO_USER_PROFILE'];

		$user = $arParams['~USER_DATA'][$userId];
		$user['NAME'] = $arParams['~USER_NAMES'][$userId];

		$user['AVATAR'] = \Bitrix\Tasks\UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100);
		$user['IS_EXTERNAL'] = \Bitrix\Tasks\Util\User::isExternalUser($user['ID']);
		$user['IS_CRM'] = array_key_exists('UF_USER_CRM_ENTITY', $user) && !empty($user['UF_USER_CRM_ENTITY']);

		$user['URL'] = CComponentEngine::MakePathFromTemplate($pathToUserProfile,array("user_id" => $userId));
		$user['URL'] = $user['IS_EXTERNAL']
			? \Bitrix\Tasks\Integration\Socialnetwork\Task::addContextToURL($user['URL'],$taskId)
			: $user['URL'];

		// $arParams['USER']['IS_EXTERNAL']   = true || false
		$userIcon = '';
		if ($user['IS_EXTERNAL'])
		{
			$userIcon = 'tasks-grid-avatar-extranet';
		}
		if ($user["EXTERNAL_AUTH_ID"] == 'email')
		{
			$userIcon = 'tasks-grid-avatar-mail';
		}
		if ($user["IS_CRM"])
		{
			$userIcon = 'tasks-grid-avatar-crm';
		}

		$userAvatar = 'tasks-grid-avatar-empty';
		if ($user['AVATAR'])
		{
			$userAvatar = '';
		}

		$userName = '<span class="tasks-grid-avatar  '.$userAvatar.' '.$userIcon.'" 
			'.($user['AVATAR'] ? 'style="background-image: url(\''.Uri::urnEncode($user['AVATAR']).'\')"' : '').'></span>';

		$userName .= '<span class="tasks-grid-username-inner '.$userIcon.'">'. htmlspecialcharsbx($user['NAME']) .'</span>';

		return '<div class="tasks-grid-username-wrapper"><a href="'.$user['URL'].'" class="tasks-grid-username">'.
			   $userName.
			   '</a></div>';
	}
}

$rows = [];

if ($arResult['VIOLATION_LIST'])
{
	$users = [];

	foreach ($arResult['VIOLATION_LIST'] as $item)
	{
		$users[] = $item['TASK_ORIGINATOR_ID'];
	}
	$users = array_unique($users);
	$arParams['~USER_NAMES'] = User::getUserName($users);
	$arParams['~USER_DATA'] = User::getData($users);

	foreach ($arResult['VIOLATION_LIST'] as $item)
	{
		$userId = User::getId();

		$groupLink = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_GROUP_LIST'],
			[
				'user_id' => $userId,
				'group_id' => $item['GROUP_ID'],
			]
		);
		$taskLink = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_TASK'],
			[
				'user_id' => $userId,
				'task_id' => $item['TASK_ID'],
				'action' => 'view',
			]
		);

		$item['USER_TYPE'] = (
			in_array($item['USER_TYPE'], ['R', 'A'])
				? Loc::getMessage('TASKS_USER_TYPE_'.$item['USER_TYPE'].'2')
				: '-'
		);
		$item['ORIGINATOR'] = prepareTaskRowUserBaloonHtml($item['TASK_ORIGINATOR_ID'], $item['TASK_ID'], $arParams);

		$item['GROUP'] = '<a href="'.$groupLink.'">'.htmlspecialcharsbx($item['GROUP_NAME']).'</a>';
		$item['TASK'] = '<a href="'.$taskLink.'">'.htmlspecialcharsbx($item['TASK_TITLE']).'</a> '
			.($item['TASK_ZOMBIE'] ? '<em>'.Loc::getMessage('TASKS_EFFECTIVE_DETAIL_DELETED').'</em>' : '');

		if ($item['TASK_ZOMBIE'])
		{
			$item['TASK'] = htmlspecialcharsbx($item['TASK_TITLE']) . ' <em>'.Loc::getMessage('TASKS_EFFECTIVE_DETAIL_DELETED').'</em>';
		}
		else
		{
			$item['TASK'] = '<a href="'.$taskLink.'">'.htmlspecialcharsbx($item['TASK_TITLE']).'</a>';
		}

		$rows[] = $item;
	}
}

$arResult['TEMPLATE_DATA'] = array(
	// contains data generated in result_modifier.php
);

$grid = new Bitrix\Tasks\Grid\Effective\Grid($rows, $arParams);
$preparedRows = $grid->prepareRows();

$arResult['ROWS'] = [];
foreach ($rows as $key => $row)
{
	$taskId = (int)$row['TASK_ID'];

	if (!$row['DATE_REPAIR'])
	{
		$preparedRows[$key]['content']['DATE_REPAIR'] = Loc::getMessage('TASKS_VIOLATION_NOT_REPAIR');
	}

	$arResult['ROWS'][] = [
		'id' => $taskId,
		'columns' => $preparedRows[$key]['content'],
	];
}