<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Kanban\TimeLineTable;
use Bitrix\Tasks\Ui\Avatar;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

global $USER;

CModule::IncludeModule('tasks');

$userId = (int)$USER->GetID();
$result = [
	'settings' => [
		'nameFormat' => CSite::GetNameFormat(),
		'profilePath' => '/company/personal/user/#user_id#/',
		'currentUser' => getUserData($userId),
	],
	'deadlines' => getDeadlines(),
	'diskFolderId' => Integration\Disk::getFolderForUploadedFiles($userId)->getData()['FOLDER_ID'],
];

/**
 * @param int $userId
 * @return array|null
 */
function getUserData(int $userId): ?array
{
	static $users = [];

	if (!$userId)
	{
		return null;
	}

	if (!array_key_exists($userId, $users))
	{
		$userResult = CUser::GetList(
			'',
			'',
			['ID_EQUAL_EXACT' => $userId],
			['FIELDS' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO', 'WORK_POSITION']]
		);
		if (!($user = $userResult->Fetch()))
		{
			return null;
		}
		$users[$userId] = prepareUserData($user);
	}

	return $users[$userId];
}

/**
 * @param array $user
 * @return array
 */
function prepareUserData(array $user): array
{
	$userId = $user['ID'];
	$userName = CUser::FormatName(
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

	return [
		'id' => $userId,
		'name' => $userName,
		'link' => CComponentEngine::makePathFromTemplate("/company/personal/user/{$userId}/"),
		'icon' => Avatar::getPerson($user['PERSONAL_PHOTO']),
		'isAdmin' => User::isSuper($userId),
		'workPosition' => $user['WORK_POSITION'],
	];
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
