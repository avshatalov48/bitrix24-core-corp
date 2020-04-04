<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
global $USER;
$userId = $USER->GetID();
CModule::IncludeModule('tasks');

$snmRouterPath = SITE_DIR.'mobile/tasks/snmrouter/';
$snmRouterPathAjax = SITE_DIR.'mobile/?mobile_action=task_ajax';

$result = [
	"settings" => [
		"nameFormat" => CSite::GetNameFormat(),
		"profilePath" => '/company/personal/user/#user_id#/',
		"taskPaths" => [
			'add'=>$snmRouterPath . '?routePage=edit&USER_ID=#userId#&TASK_ID=0',
			'addSub'=>$snmRouterPath . '?routePage=edit&PARENT_ID=#parentTaskId#',
			'update'=>$snmRouterPath . '?routePage=edit&USER_ID=#userId#&TASK_ID=#taskId#',
			'removeAjax'=>$snmRouterPathAjax
		],
		"userInfo" => getUserInfo($userId)
	]
];



/**
 * @param $userId
 *
 * @return mixed|null
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ObjectPropertyException
 * @throws \Bitrix\Main\SystemException
 */
function getUserInfo($userId)
{
	global $USER;
	static $users = [];

	if (!$userId)
	{
		return null;
	}

	if (!$users[$userId])
	{
		// prepare link to profile
		$replaceList = ['user_id' => $userId];
		$template = '/company/personal/user/#user_id#/';
		$link = \CComponentEngine::makePathFromTemplate($template, $replaceList);

		$userFields = \Bitrix\Main\UserTable::getRowById($userId);
		if (!$userFields)
		{
			return null;

		}

		// format name
		$userName = \CUser::FormatName(
			CSite::GetNameFormat(),
			[
				'LOGIN' => $userFields['LOGIN'],
				'NAME' => $userFields['NAME'],
				'LAST_NAME' => $userFields['LAST_NAME'],
				'SECOND_NAME' => $userFields['SECOND_NAME']
			],
			true, false
		);

		$users[$userId] = [
			'id' => $userId,
			'name' => $userName,
			'link' => $link,
			'icon' => \Bitrix\Tasks\Ui\Avatar::getPerson($userFields['PERSONAL_PHOTO']),
			'isAdmin' => \CModule::IncludeModule('bitrix24') ? \CBitrix24::isPortalAdmin($USER->getId()) : $USER->IsAdmin()
		];
	}

	return $users[$userId];
}


return $result;
