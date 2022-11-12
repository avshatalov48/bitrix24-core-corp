<?php
namespace Bitrix\BIConnector\Integration\Socialnetwork;

use Bitrix\Main\Localization\Loc;

class Group
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key telephony_call to the second event parameter.
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
		$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

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
				//TODO:PROJECT_DATE_START datetime null,
				//TODO:PROJECT_DATE_FINISH datetime null,
				//TODO:SEARCH_INDEX mediumtext null,
				//TODO:LANDING char(1) null,
				//TODO:SCRUM_OWNER_ID int null,
				//TODO:SCRUM_MASTER_ID int null,
				//TODO:SCRUM_SPRINT_DURATION int null,
				//TODO:SCRUM_TASK_RESPONSIBLE char(1) null,
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['socialnetwork_group']['TABLE_DESCRIPTION'] = $messages['SN_BIC_GROUP_TABLE'] ?: 'socialnetwork_group';
		foreach ($result['socialnetwork_group']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['SN_BIC_GROUP_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['SN_BIC_GROUP_FIELD_' . $fieldCode . '_FULL'];
		}
		unset($fieldInfo);
	}
}
