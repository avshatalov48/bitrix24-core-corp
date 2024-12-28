<?php
namespace Bitrix\BIConnector\Integration\Socialnetwork;

use Bitrix\Main\Localization\Loc;

class Group
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key socialnetwork_group to the second event parameter.
	 * Fills it with data to retrieve information from b_sonet_group table.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$params = $event->getParameters();
		//$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$rolesMember = \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember();
		$rolesMembersString = implode("', '", $rolesMember);

		$moderatorRole = \Bitrix\Socialnetwork\UserToGroupTable::ROLE_MODERATOR;

		//CREATE TABLE b_sonet_group
		$result['socialnetwork_group'] = [
			'TABLE_NAME' => 'b_sonet_group',
			'TABLE_ALIAS' => 'G',
			'FILTER' => [
				'=VISIBLE' => 'Y',
			],
			'FILTER_FIELDS' => [
				'VISIBLE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.VISIBLE',
					'FIELD_TYPE' => 'string',
				],
			],
			'FIELDS' => [
				//ID int not null auto_increment,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.ID',
					'FIELD_TYPE' => 'int',
				],
				//SITE_ID char(2) not null,
				'SITE_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.SITE_ID',
					'FIELD_TYPE' => 'string',
				],
				//NAME varchar(255) not null,
				'NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.NAME',
					'FIELD_TYPE' => 'string',
				],
				//DESCRIPTION text null,
				'DESCRIPTION' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.DESCRIPTION',
					'FIELD_TYPE' => 'string',
				],
				//DATE_CREATE datetime not null,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'G.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
				],
				//DATE_UPDATE datetime not null,
				'DATE_MODIFY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'G.DATE_UPDATE',
					'FIELD_TYPE' => 'datetime',
				],
				//TODO:ACTIVE char(1) not null default 'Y',
				//TODO:VISIBLE char(1) not null default 'Y',
				//OPENED char(1) not null default 'N',
				'OPENED' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.OPENED',
					'FIELD_TYPE' => 'string',
				],
				//SUBJECT_ID int not null,
				'SUBJECT' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_sonet_group_subject S ON S.ID = G.SUBJECT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_sonet_group_subject S ON S.ID = G.SUBJECT_ID',
				],
				//OWNER_ID int not null,
				'OWNER_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'G.OWNER_ID',
					'FIELD_TYPE' => 'int',
				],
				'OWNER_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(G.OWNER_ID is null, null, concat_ws(\' \', nullif(UO.NAME, \'\'), nullif(UO.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UO',
					'JOIN' => 'INNER JOIN b_user UO ON UO.ID = G.OWNER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UO ON UO.ID = G.OWNER_ID',
				],
				'OWNER' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(G.OWNER_ID is null, null, concat_ws(\' \', concat(\'[\', G.OWNER_ID, \']\'), nullif(UO.NAME, \'\'), nullif(UO.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UO',
					'JOIN' => 'INNER JOIN b_user UO ON UO.ID = G.OWNER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UO ON UO.ID = G.OWNER_ID',
				],
				//KEYWORDS varchar(255) null,
				'KEYWORDS' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.KEYWORDS',
					'FIELD_TYPE' => 'string',
				],
				//TODO:IMAGE_ID int null,
				//TODO:AVATAR_TYPE varchar(50) null,
				'MODERATORS_IDS' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'MODERATORS_IDS',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'UM.ID',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UGM',
					'JOIN' => [
						"INNER JOIN b_sonet_user2group UGM ON G.ID = UGM.GROUP_ID AND UGM.ROLE = '{$moderatorRole}'",
						"INNER JOIN b_user UM ON UGM.USER_ID = UM.ID AND UM.ACTIVE = 'Y'",
					],
					'LEFT_JOIN' => [
						"LEFT JOIN b_sonet_user2group UGM ON G.ID = UGM.GROUP_ID AND UGM.ROLE = '{$moderatorRole}'",
						"LEFT JOIN b_user UM ON UGM.USER_ID = UM.ID AND UM.ACTIVE = 'Y'",
					],
				],
				'MEMBERS_IDS' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'MEMBERS_IDS',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'U.ID',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UG',
					'JOIN' => [
						"INNER JOIN b_sonet_user2group UG ON G.ID = UG.GROUP_ID AND UG.ROLE IN ('{$rolesMembersString}')",
						"INNER JOIN b_user U ON UG.USER_ID = U.ID AND U.ACTIVE = 'Y'",
					],
					'LEFT_JOIN' => [
						"LEFT JOIN b_sonet_user2group UG ON G.ID = UG.GROUP_ID AND UG.ROLE IN ('{$rolesMembersString}')",
						"LEFT JOIN b_user U ON UG.USER_ID = U.ID AND U.ACTIVE = 'Y'",
					],
				],
				'TYPE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' =>
						'if(
							G.TYPE is null,
							if(
								G.SCRUM_MASTER_ID is null,
								if(
									G.PROJECT = \'Y\',
									\'project\',
									\'group\'
								),
								\'scrum\'
							),
							G.TYPE)',
					'FIELD_TYPE' => 'string',
				],
				//NUMBER_OF_MEMBERS int not null default 0,
				'NUMBER_OF_MEMBERS' => [
					'IS_METRIC' => 'Y',
					'FIELD_NAME' => 'G.NUMBER_OF_MEMBERS',
					'FIELD_TYPE' => 'int',
				],
				//TODO:NUMBER_OF_MODERATORS int not null default 0,
				//TODO:INITIATE_PERMS char(1) not null default 'K',
				//DATE_ACTIVITY datetime not null,
				'DATE_ACTIVITY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'G.DATE_ACTIVITY',
					'FIELD_TYPE' => 'datetime',
				],
				//CLOSED char(1) not null default 'N',
				'CLOSED' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.CLOSED',
					'FIELD_TYPE' => 'string',
				],
				//TODO:SPAM_PERMS char(1) not null default 'K',
				//PROJECT char(1) not null default 'N',
				'PROJECT' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.PROJECT',
					'FIELD_TYPE' => 'string',
				],
				'PROJECT_DATE_START' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.PROJECT_DATE_START',
					'FIELD_TYPE' => 'datetime',
				],
				'PROJECT_DATE_FINISH' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.PROJECT_DATE_FINISH',
					'FIELD_TYPE' => 'datetime',
				],
				'SCRUM_MASTER_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.SCRUM_MASTER_ID',
					'FIELD_TYPE' => 'int',
				],
				'SCRUM_MASTER_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'if(G.SCRUM_MASTER_ID is null, null, concat_ws(\' \', nullif(USM.NAME, \'\'), nullif(USM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'USM',
					'JOIN' => 'INNER JOIN b_user USM ON USM.ID = G.SCRUM_MASTER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user USM ON USM.ID = G.SCRUM_MASTER_ID',
				],
				'SCRUM_MASTER' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'if(G.SCRUM_MASTER_ID is null, null, concat_ws(\' \', concat(\'[\', G.SCRUM_MASTER_ID, \']\'), nullif(USM.NAME, \'\'), nullif(USM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'USM',
					'JOIN' => 'INNER JOIN b_user USM ON USM.ID = G.SCRUM_MASTER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user USM ON USM.ID = G.SCRUM_MASTER_ID',
				],
				'SCRUM_SPRINT_DURATION' => [
					'IS_METRIC' => 'Y',
					'FIELD_NAME' => 'G.SCRUM_SPRINT_DURATION',
					'FIELD_TYPE' => 'int',
				],
				//SCRUM_TASK_RESPONSIBLE char(1) null,
				'SCRUM_TASK_RESPONSIBLE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'G.SCRUM_TASK_RESPONSIBLE',
					'FIELD_TYPE' => 'string',
				],
				//TODO:SEARCH_INDEX mediumtext null,
				//TODO:LANDING char(1) null,
				//TODO:SCRUM_OWNER_ID int null,
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['socialnetwork_group']['TABLE_DESCRIPTION'] = $messages['SN_BIC_GROUP_TABLE'] ?: 'socialnetwork_group';
		foreach ($result['socialnetwork_group']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			if ($fieldCode === 'OPENED')
			{
				$fieldCode = 'OPENED_MSGVER_2';
			}

			$fieldsWithFirstVersionedMessages = [
				'ID',
				'PROJECT',
				'DATE_CREATE',
				'DATE_MODIFY',
				'CLOSED',
				'SUBJECT',
				'OWNER',
				'OWNER_ID',
				'OWNER_NAME',
				'KEYWORDS',
				'NUMBER_OF_MEMBERS',
				'DATE_ACTIVITY',
			];
			if (in_array($fieldCode, $fieldsWithFirstVersionedMessages))
			{
				$fieldCode = $fieldCode . '_MSGVER_1';
			}

			$fieldInfo['FIELD_DESCRIPTION'] = $messages['SN_BIC_GROUP_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['SN_BIC_GROUP_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}
}
