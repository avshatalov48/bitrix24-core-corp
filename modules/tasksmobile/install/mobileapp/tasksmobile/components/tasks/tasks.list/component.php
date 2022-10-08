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
		'userInfo' => getUserInfo($userId),
	],
	'deadlines' => getDeadlines(),
	'userList' => [],
	'diskFolderId' => Integration\Disk::getFolderForUploadedFiles($userId)->getData()['FOLDER_ID'],
];

$dbRes = UserTable::getList([
	'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'PERSONAL_PHOTO'],
	'filter' => [
		'=ACTIVE' => 'Y',
		'!ID' => [$userId],
		'IS_REAL_USER' => 'Y',
	],
	'limit' => 20,
]);
while ($user = $dbRes->fetch())
{
	$result['userList'][$user['ID']] = getUserData($user);
}

/**
 * @param int $userId
 * @return array|null
 * @throws Main\ArgumentException
 * @throws Main\ObjectPropertyException
 * @throws Main\SystemException
 */
function getUserInfo(int $userId): ?array
{
	static $users = [];

	if (!$userId)
	{
		return null;
	}

	if (!$users[$userId])
	{
		if (!($user = UserTable::getRowById($userId)))
		{
			return null;
		}
		$users[$userId] = getUserData($user);
	}

	return $users[$userId];
}

/**
 * @param array $user
 * @return array
 */
function getUserData(array $user): array
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
