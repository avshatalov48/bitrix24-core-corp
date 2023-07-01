<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
global $USER;
$userId = $USER->GetID();
CModule::IncludeModule('tasks');

$result = [
	"settings" => [
		"nameFormat" => CSite::GetNameFormat(),
		"profilePath" => '/company/personal/user/#user_id#/',
		"userInfo" => getUserInfo($userId)
	]
];


$list = \Bitrix\Tasks\Integration\SocialNetwork::getLogDestination('', ['AVATAR_WIDTH'=>100, 'AVATAR_HEIGHT'=>100, 'USE_PROJECTS'=>'Y']);

$result['userList'] = [];
if(!empty($list['LAST']['USERS']))
{
	foreach ($list['LAST']['USERS'] as $userCode)
	{
		$user = $list['USERS'][$userCode];
		$result['userList'][] = [
			'id' => $user['entityId'],
			'name' => $user['name'],
			'icon' => $user['avatar']
		];
	}
}

if(count($result['userList']) < 50)
{
	$dbRes = \Bitrix\Main\UserTable::getList(
		[
			'filter' => [
				"=ACTIVE" => "Y",
				"!ID" => [$USER->GetID()],
				'IS_REAL_USER'=>'Y'
			],
			'limit' => 50-count($result['userList']),
			'select' => ["ID", 'LOGIN', "NAME", "LAST_NAME", 'SECOND_NAME', "PERSONAL_PHOTO", "EMAIL"]
		]
	);
	while ($t = $dbRes->fetch())
	{
        // format name
        $userName = \CUser::FormatName(
            CSite::GetNameFormat(),
            [
                'LOGIN' => $t['LOGIN'],
                'NAME' => $t['NAME'],
                'LAST_NAME' => $t['LAST_NAME'],
                'SECOND_NAME' => $t['SECOND_NAME']
            ],
            true, false
        );

        $replaceList = ['user_id' => $t['ID']];
        $template = '/company/personal/user/#user_id#/';
        $link = \CComponentEngine::makePathFromTemplate($template, $replaceList);

        $result['userList'][$t['ID']] = [
            'id' => $t['ID'],
            'name' => $userName,
            'link' => $link,
            'icon' => \Bitrix\Tasks\Ui\Avatar::getPerson($userFields['PERSONAL_PHOTO'] ?? null)
        ];

    }

}

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

	if (empty($users[$userId]))
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
