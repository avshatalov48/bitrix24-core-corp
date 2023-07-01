<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Fields\Status;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\Type\DateTime;

$APPLICATION->SetPageProperty('title', Loc::getMessage('TASKS_EFFECTIVE_TITLE_FULL'));
$APPLICATION->SetTitle(Loc::getMessage('TASKS_EFFECTIVE_TITLE_FULL'));

if (!function_exists('prepareTaskRowUserBalloonHtml'))
{
	/**
	 * @param int $userId
	 * @param int $taskId
	 * @param array $arParams
	 * @return string
	 */
	function prepareTaskRowUserBalloonHtml(int $userId, int $taskId, array $arParams): string
	{
		$user = $arParams['~USER_DATA'][$userId];
		$user['NAME'] = $arParams['~USER_NAMES'][$userId];
		$user['AVATAR'] = UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100);
		$user['IS_CRM'] = array_key_exists('UF_USER_CRM_ENTITY', $user) && !empty($user['UF_USER_CRM_ENTITY']);
		$user['IS_EXTERNAL'] = User::isExternalUser($userId);
		$user['URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'], ['user_id' => $userId]);
		$user['URL'] = ($user['IS_EXTERNAL'] ? Socialnetwork\Task::addContextToURL($user['URL'], $taskId) : $user['URL']);

		$userName = htmlspecialcharsbx($user['NAME']);
		$userUrl = htmlspecialcharsbx($user['URL']);

		$userIcon = '';
		if ($user['IS_EXTERNAL'])
		{
			$userIcon = ' tasks-grid-avatar-extranet';
		}
		if ($user['EXTERNAL_AUTH_ID'] === 'email')
		{
			$userIcon = ' tasks-grid-avatar-mail';
		}
		if ($user['IS_CRM'])
		{
			$userIcon = ' tasks-grid-avatar-crm';
		}

		$emptyAvatar = ($user['AVATAR'] ? '' : ' tasks-grid-avatar-empty');
		$style = ($user['AVATAR'] ? ' style="background-image: url(\''.Uri::urnEncode($user['AVATAR']).'\')"' : '');

		return
			'<div class="tasks-grid-username-wrapper">'
				."<a href='{$userUrl}' class='tasks-grid-username'>"
					."<span class='ui-icon ui-icon-common-user tasks-grid-avatar{$emptyAvatar}'><i{$style}></i></span>"
					."<span class='tasks-grid-username-inner{$userIcon}'>{$userName}</span>"
				.'</a>'
			.'</div>'
		;
	}
}

$arResult['ROWS'] = [];

if (!isset($arResult['LIST']) || !is_array($arResult['LIST']) || empty($arResult['LIST']))
{
	return;
}

$users = [];
foreach ($arResult['LIST'] as $row)
{
	$users[$row['CREATED_BY']] = $row['CREATED_BY'];
}
$arParams['~USER_NAMES'] = User::getUserName($users);
$arParams['~USER_DATA'] = User::getData($users);

$userId = User::getId();

foreach ($arResult['LIST'] as $task)
{
	$taskId = $task['ID'];
	$taskLink = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_TASKS_TASK'],
		[
			'user_id' => User::getId(),
			'task_id' => $taskId,
			'action' => 'view',
		]
	);
	$groupLink = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_GROUP_LIST'],
		[
			'user_id' => User::getId(),
			'group_id' => $task['GROUP_ID'],
		]
	);

	$task['TASK'] = "<a href='{$taskLink}'>".htmlspecialcharsbx($task['TITLE'])."</a>";
	$task['GROUP'] = "<a href='{$groupLink}'>".htmlspecialcharsbx($task['GROUP_NAME'])."</a>";

	$task['ORIGINATOR'] = prepareTaskRowUserBalloonHtml($task['CREATED_BY'], $taskId, $arParams);
	$task['STATUS'] = htmlspecialcharsbx(Status::getTranslate($task['STATUS']));

	$task['DEADLINE'] = ($task['DEADLINE'] ? DateTime::createFrom($task['DEADLINE']) : null);
	$task['CREATED_DATE'] = ($task['CREATED_DATE'] ? DateTime::createFrom($task['CREATED_DATE']) : null);
	$task['CLOSED_DATE'] = ($task['CLOSED_DATE'] ? DateTime::createFrom($task['CLOSED_DATE']) : null);

	$arResult['ROWS'][] = [
		'id' => $taskId,
		'columns' => $task,
	];
}