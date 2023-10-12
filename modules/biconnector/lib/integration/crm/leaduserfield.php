<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class LeadUserField
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_lead_uf to the second event parameter.
	 * Fills it with data to retrieve information from b_uts_crm_lead table.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		global $USER_FIELD_MANAGER;

		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return;
		}

		$params = $event->getParameters();
		//$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$userFields = $USER_FIELD_MANAGER->getUserFields(\CCrmLead::$sUFEntityID, 0, $languageId);
		if (!$userFields)
		{
			return;
		}

		$result['crm_lead_uf'] = [
			'TABLE_NAME' => 'b_uts_crm_lead',
			'TABLE_ALIAS' => 'LUF',
			'FIELDS' => [
				'LEAD_ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'LUF.VALUE_ID',
					'FIELD_TYPE' => 'int',
				],
				//b_crm_lead.DATE_MODIFY DATETIME NULL,
				'DATE_MODIFY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.DATE_MODIFY',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'L',
					'JOIN' => 'INNER JOIN b_crm_lead L ON L.ID = LUF.VALUE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_lead L ON L.ID = LUF.VALUE_ID',
				],
				//b_crm_lead.DATE_CREATE DATETIME NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'L',
					'JOIN' => 'INNER JOIN b_crm_lead L ON L.ID = LUF.VALUE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_lead L ON L.ID = LUF.VALUE_ID',
				],
				//b_crm_lead.DATE_CLOSED DATETIME NULL,
				'DATE_CLOSED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.DATE_CLOSED',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'L',
					'JOIN' => 'INNER JOIN b_crm_lead L ON L.ID = LUF.VALUE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_lead L ON L.ID = LUF.VALUE_ID',
				],
			],
		];
		foreach ($userFields as $userField)
		{
			$dbType = '';
			if ($userField['USER_TYPE'] && is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype']))
			{
				$dbType = call_user_func_array([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype'], [$userField]);
			}

			if ($dbType === 'date' && $userField['MULTIPLE'] == 'N')
			{
				$result['crm_lead_uf']['FIELDS'][$userField['FIELD_NAME']] = [
					'FIELD_DESCRIPTION' => $userField['EDIT_FORM_LABEL'],
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'LUF.' . $userField['FIELD_NAME'],
					'FIELD_TYPE' => 'date',
				];
			}
			elseif ($dbType === 'datetime' && $userField['MULTIPLE'] == 'N')
			{
				$result['crm_lead_uf']['FIELDS'][$userField['FIELD_NAME']] = [
					'FIELD_DESCRIPTION' => $userField['EDIT_FORM_LABEL'],
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'LUF.' . $userField['FIELD_NAME'],
					'FIELD_TYPE' => 'datetime',
				];
			}
			else
			{
				$result['crm_lead_uf']['FIELDS'][$userField['FIELD_NAME']] = [
					'FIELD_DESCRIPTION' => $userField['EDIT_FORM_LABEL'],
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'LUF.' . $userField['FIELD_NAME'],
					'FIELD_TYPE' => 'string',
					'CALLBACK' => function($value, $dateFormats) use($userField, $dbType)
					{
						global $USER_FIELD_MANAGER;

						if ($dbType === 'date')
						{
							return \Bitrix\BIConnector\PrettyPrinter::formatUserFieldAsDate($userField, $value, $dateFormats['date_format_php']);
						}

						if ($dbType === 'datetime')
						{
							return \Bitrix\BIConnector\PrettyPrinter::formatUserFieldAsDate($userField, $value, $dateFormats['datetime_format_php']);
						}

						$cacheKey = serialize($value);
						$cachedResult = \Bitrix\BIConnector\MemoryCache::get($userField['ID'], $cacheKey);
						if (isset($cachedResult))
						{
							return $cachedResult;
						}
						else
						{
							if ($userField['MULTIPLE'] == 'Y')
							{
								$result = $USER_FIELD_MANAGER->onAfterFetch(
									$userField,
									unserialize($value, ['allowed_classes' => \Bitrix\BIConnector\PrettyPrinter::$allowedUnserializeClassesList])
								);
							}
							else
							{
								$result = $USER_FIELD_MANAGER->onAfterFetch($userField, $value);
							}

							$localUF = $userField;
							$localUF['VALUE'] = $result;

							$returnResult = $USER_FIELD_MANAGER->getPublicText($localUF);
							\Bitrix\BIConnector\MemoryCache::set($userField['ID'], $cacheKey, $returnResult);

							return $returnResult;
						}
					}
				];
			}
		}

		$messages = \Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_lead_uf']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_LEAD_UF_TABLE'] ?: 'crm_lead_uf';
		foreach ($result['crm_lead_uf']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			if (isset($messages['CRM_BIC_LEAD_UF_FIELD_' . $fieldCode]))
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_LEAD_UF_FIELD_' . $fieldCode];
			}

			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			if (isset($messages['CRM_BIC_LEAD_UF_FIELD_' . $fieldCode . '_FULL']))
			{
				$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_LEAD_UF_FIELD_' . $fieldCode . '_FULL'] ?? '';
			}
		}
		unset($fieldInfo);
	}
}
