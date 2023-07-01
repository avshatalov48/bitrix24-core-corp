<?php
namespace Bitrix\BIConnector\Integration\Main;

use Bitrix\Main\Localization\Loc;

class User
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key user to the second event parameter.
	 * Fills it with data to retrieve information from b_user table.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();
		//$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$result['user'] = [
			'TABLE_NAME' => 'b_user',
			'TABLE_ALIAS' => 'U',
			'FILTER' => [
				'=IS_EXTERNAL' => 'N',
			],
			'FILTER_FIELDS' => [
				'IS_EXTERNAL' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => "CASE WHEN EXTERNAL_AUTH_ID IN ('" . implode("', '", \Bitrix\Main\UserTable::getExternalUserTypes()) . "') THEN 'Y' ELSE 'N' END",
					'FIELD_TYPE' => 'string',
				],
			],
			'FIELDS' => [
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'U.ID',
					'FIELD_TYPE' => 'int',
				],
				'ACTIVE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'U.ACTIVE',
					'FIELD_TYPE' => 'string',
				],
				'NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(U.NAME, \'\'), nullif(U.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
				],
				'DEPARTMENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'D',
					'JOIN' => 'INNER JOIN b_biconnector_dictionary_data D ON D.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND D.VALUE_ID = U.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_biconnector_dictionary_data D ON D.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND D.VALUE_ID = U.ID',
				],
			],
		];

		if (!\Bitrix\BIConnector\DictionaryManager::validateCache(\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT))
		{
			unset($result['user']['FIELDS']['DEPARTMENT']);
		}

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['user']['TABLE_DESCRIPTION'] = $messages['MAIN_BIC_USER_TABLE'] ?: 'user';
		foreach ($result['user']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['MAIN_BIC_USER_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['MAIN_BIC_USER_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}
}
