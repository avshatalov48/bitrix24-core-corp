<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class DealUserField
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_deal_uf to the second event parameter.
	 * Fills it with data to retrieve information from b_uts_crm_deal table.
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

		$userFields = $USER_FIELD_MANAGER->getUserFields(\CCrmDeal::$sUFEntityID, 0, $languageId);
		if (!$userFields)
		{
			return;
		}

		$result['crm_deal_uf'] = [
			'TABLE_NAME' => 'b_uts_crm_deal',
			'TABLE_ALIAS' => 'DUF',
			'FIELDS' => [
				'DEAL_ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'DUF.VALUE_ID',
					'FIELD_TYPE' => 'int',
				],
				//b_crm_deal.DATE_CREATE DATETIME NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'D',
					'JOIN' => 'INNER JOIN b_crm_deal D ON D.ID = DUF.VALUE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal D ON D.ID = DUF.VALUE_ID',
				],
				//b_crm_deal.CLOSEDATE DATETIME DEFAULT NULL,
				'CLOSEDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CLOSEDATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'D',
					'JOIN' => 'INNER JOIN b_crm_deal D ON D.ID = DUF.VALUE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal D ON D.ID = DUF.VALUE_ID',
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
				$result['crm_deal_uf']['FIELDS'][$userField['FIELD_NAME']] = [
					'FIELD_DESCRIPTION' => $userField['EDIT_FORM_LABEL'],
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'DUF.' . $userField['FIELD_NAME'],
					'FIELD_TYPE' => 'date',
				];
			}
			elseif ($dbType === 'datetime' && $userField['MULTIPLE'] == 'N')
			{
				$result['crm_deal_uf']['FIELDS'][$userField['FIELD_NAME']] = [
					'FIELD_DESCRIPTION' => $userField['EDIT_FORM_LABEL'],
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'DUF.' . $userField['FIELD_NAME'],
					'FIELD_TYPE' => 'datetime',
				];
			}
			else
			{
				$result['crm_deal_uf']['FIELDS'][$userField['FIELD_NAME']] = [
					'FIELD_DESCRIPTION' => $userField['EDIT_FORM_LABEL'],
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'DUF.' . $userField['FIELD_NAME'],
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
								$result = [$USER_FIELD_MANAGER->onAfterFetch($userField, $value)];
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
		$result['crm_deal_uf']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_DEAL_UF_TABLE'] ?: 'crm_deal_uf';
		foreach ($result['crm_deal_uf']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			if (isset($messages['CRM_BIC_DEAL_UF_FIELD_' . $fieldCode]))
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_DEAL_UF_FIELD_' . $fieldCode];
			}

			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			if (isset($messages['CRM_BIC_DEAL_UF_FIELD_' . $fieldCode . '_FULL']))
			{
				$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_DEAL_UF_FIELD_' . $fieldCode . '_FULL'] ?? '';
			}
		}
		unset($fieldInfo);
	}
}
