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

		$result['user']['DICTIONARY'] = [];
		if (\Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT))
		{
			$result['user']['DICTIONARY'][] = \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT;
		}
		else
		{
			unset($result['user']['FIELDS']['DEPARTMENT']);
		}

		if (
			\Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::USER_STRUCTURE_DEPARTMENT)
			&& \Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::DEPARTMENT_PARENT_AGGREGATION)
		)
		{
			$result['user']['DICTIONARY'][] = \Bitrix\BIConnector\Dictionary::USER_STRUCTURE_DEPARTMENT;
			$result['user']['DICTIONARY'][] = \Bitrix\BIConnector\Dictionary::DEPARTMENT_PARENT_AGGREGATION;

			$dshrJoin = 'INNER JOIN b_biconnector_dictionary_data DSHR ON DSHR.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_STRUCTURE_DEPARTMENT . ' AND DSHR.VALUE_ID = U.ID';
			$dshrLeftJoin = 'LEFT JOIN b_biconnector_dictionary_data DSHR ON DSHR.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_STRUCTURE_DEPARTMENT . ' AND DSHR.VALUE_ID = U.ID';

			$departmentJoin =  'INNER JOIN b_biconnector_dict_structure_agg AS SN ON SN.DEP_ID = SUBSTRING_INDEX(DSHR.VALUE_STR, \',\', 1)';
			$departmentLeftJoin = 'LEFT JOIN b_biconnector_dict_structure_agg AS SN ON SN.DEP_ID = SUBSTRING_INDEX(DSHR.VALUE_STR, \',\', 1)';

			$result['user']['FIELDS'] = array_merge($result['user']['FIELDS'], [
				'DEPARTMENT_IDS' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'DSHR.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'JOIN' => $dshrJoin,
					'LEFT_JOIN' => $dshrLeftJoin,
				],
				'DEPARTMENT_ID' => [
					'FIELD_NAME' => 'SN.DEP_ID',
					'JOIN' => $departmentJoin,
					'LEFT_JOIN' => $departmentLeftJoin,
				],
				'DEPARTMENT_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'SN.DEP_NAME',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEPARTMENT_ID_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'CONCAT(\'[\', SN.DEP_ID, \'] \', SN.DEP_NAME)',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP1' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'SUBSTRING_INDEX(SN.DEP_NAMES, \',\', 1)',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP2' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => '
					CASE
		WHEN SUBSTRING_INDEX(SN.DEP_IDS, \',\', 1) = SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 2), \',\', -1)
			THEN NULL
		ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_NAMES, \',\', 2), \',\', -1) END',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP3' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'CASE
		WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 2), \',\', -1) = SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 3), \',\', -1)
			THEN NULL
		ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_NAMES, \',\', 3), \',\', -1) END',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP1_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'SUBSTRING_INDEX(SN.DEP_IDS, \',\', 1)',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP2_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'CASE
		WHEN SUBSTRING_INDEX(SN.DEP_IDS, \',\', 1) = SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 2), \',\', -1)
			THEN NULL
		ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 2), \',\', -1) END',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP3_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'CASE
		WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 2), \',\', -1) = SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 3), \',\', -1)
			THEN NULL
		ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 3), \',\', -1) END',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP1_N' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'SUBSTRING_INDEX(SN.DEP_NAME_IDS, \',\', 1)',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP2_N' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'CASE
		WHEN SUBSTRING_INDEX(SN.DEP_IDS, \',\', 1) = SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 2), \',\', -1)
			THEN NULL
		ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_NAME_IDS, \',\', 2), \',\', -1) END',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
				'DEP3_N' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'CASE
		WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 2), \',\', -1) = SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_IDS, \',\', 3), \',\', -1)
			THEN NULL
		ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(SN.DEP_NAME_IDS, \',\', 3), \',\', -1) END',
					'FIELD_TYPE' => 'string',
					'JOIN' => [
						$dshrJoin,
						$departmentJoin,
					],
					'LEFT_JOIN' => [
						$dshrLeftJoin,
						$departmentLeftJoin,
					],
				],
			]);
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
