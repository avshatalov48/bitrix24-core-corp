<?php
namespace Bitrix\BIConnector\Integration\Voximplant;

use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\Localization\Loc;

class Call
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key telephony_call to the second event parameter.
	 * Fills it with data to retrieve information from b_voximplant_statistic table.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		if (!\Bitrix\Main\Loader::includeModule('voximplant'))
		{
			return;
		}

		$params = $event->getParameters();
		$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		$viHistoryMessages = Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/voximplant/classes/general/vi_history.php', $languageId);

		//CREATE TABLE b_voximplant_statistic
		$result['telephony_call'] = [
			'TABLE_NAME' => 'b_voximplant_statistic',
			'TABLE_ALIAS' => 'S',
			'FIELDS' => [
				//ID int(11) NOT NULL auto_increment,
				//CALL_ID varchar(255) NOT NULL,
				'CALL_ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CALL_ID',
					'FIELD_TYPE' => 'string',
				],
				//TODO:ACCOUNT_ID int(11) NULL,
				//TODO:APPLICATION_ID int(11) NULL,
				//TODO:APPLICATION_NAME varchar(80) NULL,
				//PORTAL_USER_ID int(11) NULL,
				'PORTAL_USER_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.PORTAL_USER_ID',
					'FIELD_TYPE' => 'int',
				],
				'PORTAL_USER' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', S.PORTAL_USER_ID, \']\'), nullif(U.NAME, \'\'), nullif(U.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'U',
					'JOIN' => 'INNER JOIN b_user U ON U.ID = S.PORTAL_USER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user U ON U.ID = S.PORTAL_USER_ID',
				],
				'PORTAL_USER_DEPARTMENT' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'D.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'D',
					'JOIN' => 'INNER JOIN b_biconnector_dictionary_data D ON D.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND D.VALUE_ID = S.PORTAL_USER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_biconnector_dictionary_data D ON D.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND D.VALUE_ID = S.PORTAL_USER_ID',
				],
				//PORTAL_NUMBER varchar(50) NULL,
				'PORTAL_NUMBER' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.PORTAL_NUMBER',
					'FIELD_TYPE' => 'string',
				],
				//PHONE_NUMBER varchar(20) NOT NULL,
				'PHONE_NUMBER' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.PHONE_NUMBER',
					'FIELD_TYPE' => 'string',
				],
				//INCOMING varchar(50) not null default '1',
				'CALL_TYPE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.INCOMING',
					'FIELD_TYPE' => 'int',
				],
				//SESSION_ID bigint unsigned NULL,
				//TODO:EXTERNAL_CALL_ID varchar(64) NULL,
				//TODO:CALL_CATEGORY varchar(20) NULL default 'external',
				//TODO:CALL_LOG varchar(2000) NULL,
				//TODO:CALL_DIRECTION varchar(255) NULL,
				//CALL_DURATION int(11) NOT NULL default 0,
				'CALL_DURATION' => [
					'IS_METRIC' => 'Y',
					'FIELD_NAME' => 'S.CALL_DURATION',
					'FIELD_TYPE' => 'int',
				],
				//CALL_START_DATE datetime not null,
				'CALL_START_TIME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CALL_START_DATE',
					'FIELD_TYPE' => 'datetime',
				],
				//TODO:CALL_STATUS int(11) NULL default 0,
				//CALL_FAILED_CODE varchar(255) NULL,
				'CALL_STATUS_CODE' => static::getCallStatusCodeDescription($helper, $viHistoryMessages),
				'CALL_STATUS_CODE_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CALL_FAILED_CODE',
					'FIELD_TYPE' => 'string',
				],
				'CALL_STATUS_CODE_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CALL_FAILED_CODE',
					'FIELD_TYPE' => 'string',
					'CALLBACK' => function($value, $dateFormats) use ($viHistoryMessages)
					{
						$messageId = 'VI_STATUS_' . $value;
						return $viHistoryMessages[$messageId] ?? '';
					}
				],
				//CALL_FAILED_REASON varchar(255) NULL,
				'CALL_STATUS_REASON' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CALL_FAILED_REASON',
					'FIELD_TYPE' => 'string',
				],
				//TODO:CALL_RECORD_ID INT(11) NULL,
				//TODO:CALL_RECORD_URL varchar(2000) NULL,
				//CALL_WEBDAV_ID INT(11) NULL,
				'RECORD_FILE_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CALL_WEBDAV_ID',
					'FIELD_TYPE' => 'int',
				],
				//CALL_VOTE smallint(1) DEFAULT 0,
				'CALL_VOTE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CALL_VOTE',
					'FIELD_TYPE' => 'int',
				],
				//COST double(11, 4) NULL default 0,
				'COST' => [
					'IS_METRIC' => 'Y',
					'FIELD_NAME' => 'S.COST',
					'FIELD_TYPE' => 'double',
				],
				//COST_CURRENCY varchar(50) NULL,
				'COST_CURRENCY' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.COST_CURRENCY',
					'FIELD_TYPE' => 'string',
				],
				//CRM_ENTITY_TYPE varchar(50) NULL,
				'CRM_ENTITY_TYPE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CRM_ENTITY_TYPE',
					'FIELD_TYPE' => 'string',
				],
				//CRM_ENTITY_ID int(11) NULL,
				'CRM_ENTITY_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CRM_ENTITY_ID',
					'FIELD_TYPE' => 'int',
				],
				//CRM_ACTIVITY_ID int(11) NULL,
				'CRM_ACTIVITY_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CRM_ACTIVITY_ID',
					'FIELD_TYPE' => 'int',
				],
				//REST_APP_ID int(11) NULL,
				'REST_APP_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.REST_APP_ID',
					'FIELD_TYPE' => 'int',
				],
				//REST_APP_NAME varchar(255) NULL,
				'REST_APP_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.REST_APP_NAME',
					'FIELD_TYPE' => 'string',
				],
				//TRANSCRIPT_PENDING char(1) NULL,
				'TRANSCRIPT_PENDING' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.TRANSCRIPT_PENDING',
					'FIELD_TYPE' => 'string',
				],
				//TRANSCRIPT_ID int(11) NULL,
				'TRANSCRIPT_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.TRANSCRIPT_ID',
					'FIELD_TYPE' => 'int',
				],
				//REDIAL_ATTEMPT int(11) NULL,
				'REDIAL_ATTEMPT' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.REDIAL_ATTEMPT',
					'FIELD_TYPE' => 'int',
				],
				//COMMENT text null,
				'COMMENT' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.COMMENT',
					'FIELD_TYPE' => 'string',
				],
			],
		];

		if (\Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT))
		{
			$result['telephony_call']['DICTIONARY'] = [
				\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT,
			];
		}
		else
		{
			unset($result['telephony_call']['FIELDS']['PORTAL_USER_DEPARTMENT']);
		}

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['telephony_call']['TABLE_DESCRIPTION'] = $messages['VI_BIC_CALL_TABLE'] ?: 'telephony_call';
		foreach ($result['telephony_call']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['VI_BIC_CALL_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['VI_BIC_CALL_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}

	/**
	 * @param SqlHelper $helper
	 * @param array $viHistoryMessages
	 *
	 * @return array
	 */
	protected static function getCallStatusCodeDescription(SqlHelper $helper, array $viHistoryMessages): array
	{
		$cases = [];
		foreach ($viHistoryMessages as $key => $value)
		{
			if (str_starts_with($key, 'VI_STATUS_') && $key !== 'VI_STATUS_OTHER')
			{
				$code = str_replace('VI_STATUS_', '', $key);
				$codeName = "[{$code}] {$value}";
				$cases[] = "WHEN S.CALL_FAILED_CODE = {$helper->convertToDbString($code)} THEN {$helper->convertToDbString($codeName)}";
			}
		}

		$otherStatusName = $viHistoryMessages['VI_STATUS_OTHER'] ? " {$viHistoryMessages['VI_STATUS_OTHER']}" : '';
		$otherCase = "concat('[', S.CALL_FAILED_CODE, ']', {$helper->convertToDbString($otherStatusName)})";

		if (!empty($cases))
		{
			$fieldName = 'CASE ' . implode(' ', $cases) . ' ELSE ' . $otherCase . ' END';
		}
		else
		{
			$fieldName = $otherCase;
		}


		return [
			'IS_METRIC' => 'N',
			'FIELD_NAME' => $fieldName,
			'FIELD_TYPE' => 'string',
		];
	}
}
