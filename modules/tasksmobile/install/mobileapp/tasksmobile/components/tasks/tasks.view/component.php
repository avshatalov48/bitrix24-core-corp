<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}



use Bitrix\Main;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Kanban\TimeLineTable;
use Bitrix\Tasks\Ui\Avatar;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

global $USER;

CModule::IncludeModule('tasks');

$userId = (int)$USER->GetID();
$snmRouterPath = SITE_DIR.'mobile/tasks/snmrouter/';
$snmRouterPathAjax = SITE_DIR.'mobile/?mobile_action=task_ajax';

$result = [
	'settings' => [
		'nameFormat' => CSite::GetNameFormat(),
		'profilePath' => '/company/personal/user/#user_id#/',
		'taskPaths' => [
			'add' => $snmRouterPath.'?routePage=edit&USER_ID=#userId#&TASK_ID=0',
			'addSub' => $snmRouterPath.'?routePage=edit&PARENT_ID=#parentTaskId#',
			'update' => $snmRouterPath.'?routePage=edit&USER_ID=#userId#&TASK_ID=#taskId#',
			'removeAjax' => $snmRouterPathAjax,
		],
		'userInfo' => getUserInfo($userId),
	],
	'deadlines' => getDeadlines(),
];

/**
 * @param int $userId
 * @return array|mixed|null
 * @throws Main\ArgumentException
 * @throws Main\ObjectPropertyException
 * @throws Main\SystemException
 */
function getUserInfo(int $userId)
{
	static $users = [];

	if (!$userId)
	{
		return null;
	}

	if (!array_key_exists($userId, $users))
	{
		if (!($user = UserTable::getRowById($userId)))
		{
			return null;
		}

		$userName = \CUser::FormatName(
			CSite::GetNameFormat(),
			[
				'LOGIN' => $user['LOGIN'],
				'NAME' => $user['NAME'],
				'LAST_NAME' => $user['LAST_NAME'],
				'SECOND_NAME' => $user['SECOND_NAME'],
			],
			true,
			false
		);

		$users[$userId] = [
			'id' => $userId,
			'name' => $userName,
			'link' => CComponentEngine::makePathFromTemplate("/company/personal/user/{$userId}/"),
			'icon' => Avatar::getPerson($user['PERSONAL_PHOTO']),
			'isAdmin' => User::isSuper($userId),
		];
	}

	return $users[$userId];
}

/**
 * @return array
 * @throws Main\ObjectException
 */
function getDeadlines(): array
{
	$tomorrow = MakeTimeStamp(TimeLineTable::getDateClient().' 23:59:59') + 86400;
	$deadlines = ['tomorrow' => (new DateTime(TimeLineTable::getClosestWorkHour($tomorrow)))->getTimestamp()];
	$map = [
		'PERIOD2' => 'today',
		'PERIOD3' => 'thisWeek',
		'PERIOD4' => 'nextWeek',
		'PERIOD6' => 'moreThanTwoWeeks',
	];
	foreach (TimeLineTable::getStages() as $key => $val)
	{
		if (array_key_exists($key, $map))
		{
			$deadlines[$map[$key]] = (new DateTime($val['UPDATE']['DEADLINE']))->getTimestamp();
		}
	}

	return $deadlines;
}

return $result;
