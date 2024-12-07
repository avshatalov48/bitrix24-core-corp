<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class DynamicItems
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_dynamic_items_XX to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_dynamic_items_XX tables.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return;
		}

		$params = $event->getParameters();
		$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];
		$messages = Loc::loadLanguageFile(__FILE__, $languageId);

		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		$types = \Bitrix\Crm\Model\Dynamic\TypeTable::getList()->fetchCollection();
		foreach ($types as $type)
		{
			$statusEntityId = \Bitrix\Main\Application::getConnection()->getSqlHelper()->forSql(\CCrmOwnerType::ResolveName($type->getEntityTypeId()));

			$result['crm_dynamic_items_' . $type->getEntityTypeId()] = [
				'TABLE_NAME' => $type->getTableName(),
				'TABLE_DESCRIPTION' => $type->getTitle(),
				'TABLE_ALIAS' => 'D',
				'FIELDS' => [
					'ID' => [
						'IS_PRIMARY' => 'Y',
						'FIELD_NAME' => 'D.ID',
						'FIELD_TYPE' => 'int',
					],
					'XML_ID' => [
						'FIELD_NAME' => 'D.XML_ID',
						'FIELD_TYPE' => 'string',
					],
					'TITLE' => [
						'FIELD_NAME' => 'D.TITLE',
						'FIELD_TYPE' => 'string',
					],
					'CREATED_BY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.CREATED_BY',
						'FIELD_TYPE' => 'int',
					],
					'CREATED_BY_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'concat_ws(\' \', nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UC',
						'JOIN' => 'INNER JOIN b_user UC ON UC.ID = D.CREATED_BY',
						'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = D.CREATED_BY',
					],
					'CREATED_BY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', D.CREATED_BY, \']\'), nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UC',
						'JOIN' => 'INNER JOIN b_user UC ON UC.ID = D.CREATED_BY',
						'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = D.CREATED_BY',
					],
					'UPDATED_BY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.UPDATED_BY',
						'FIELD_TYPE' => 'int',
					],
					'UPDATED_BY_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'concat_ws(\' \', nullif(UU.NAME, \'\'), nullif(UU.LAST_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UU',
						'JOIN' => 'INNER JOIN b_user UU ON UU.ID = D.UPDATED_BY',
						'LEFT_JOIN' => 'LEFT JOIN b_user UU ON UU.ID = D.UPDATED_BY',
					],
					'UPDATED_BY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', D.UPDATED_BY, \']\'), nullif(UU.NAME, \'\'), nullif(UU.LAST_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UU',
						'JOIN' => 'INNER JOIN b_user UU ON UU.ID = D.UPDATED_BY',
						'LEFT_JOIN' => 'LEFT JOIN b_user UU ON UU.ID = D.UPDATED_BY',
					],
					'MOVED_BY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.MOVED_BY',
						'FIELD_TYPE' => 'int',
					],
					'MOVED_BY_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'concat_ws(\' \', nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UM',
						'JOIN' => 'INNER JOIN b_user UM ON UM.ID = D.MOVED_BY',
						'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = D.MOVED_BY',
					],
					'MOVED_BY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', D.MOVED_BY, \']\'), nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\'))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UM',
						'JOIN' => 'INNER JOIN b_user UM ON UM.ID = D.MOVED_BY',
						'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = D.MOVED_BY',
					],
					'CREATED_TIME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.CREATED_TIME',
						'FIELD_TYPE' => 'datetime',
					],
					'UPDATED_TIME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.UPDATED_TIME',
						'FIELD_TYPE' => 'datetime',
					],
					'MOVED_TIME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.MOVED_TIME',
						'FIELD_TYPE' => 'datetime',
					],
					'CATEGORY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.CATEGORY_ID',
						'FIELD_TYPE' => 'int',
					],
					'CATEGORY_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.CATEGORY_ID is null, null, concat_ws(\' \', ifnull(DC.NAME, \'' . $helper->forSql(static::getDefaultCategoryName($languageId)) . '\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'DC',
						'JOIN' => 'INNER JOIN b_crm_item_category DC ON DC.ID = D.CATEGORY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_item_category DC ON DC.ID = D.CATEGORY_ID',
					],
					'CATEGORY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.CATEGORY_ID is null, null, concat_ws(\' \', concat(\'[\', D.CATEGORY_ID, \']\'), ifnull(DC.NAME, \'' . $helper->forSql(static::getDefaultCategoryName($languageId)) . '\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'DC',
						'JOIN' => 'INNER JOIN b_crm_item_category DC ON DC.ID = D.CATEGORY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_item_category DC ON DC.ID = D.CATEGORY_ID',
					],
					'OPENED' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.OPENED',
						'FIELD_TYPE' => 'string',
					],
					'STAGE_ID' => [
						'IS_METRIC' => 'N',
						'FIELD_NAME' => 'D.STAGE_ID',
						'FIELD_TYPE' => 'string',
					],
					'STAGE_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'S.NAME',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'S',
						'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID like \'' . $statusEntityId . '_STAGE_%\' and S.STATUS_ID = D.STAGE_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'' . $statusEntityId . '_STAGE_%\' and S.STATUS_ID = D.STAGE_ID',
					],
					'STAGE' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.STAGE_ID is null, null, concat_ws(\' \', concat(\'[\', D.STAGE_ID, \']\'), nullif(S.NAME, \'\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'S',
						'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID like \'' . $statusEntityId . '_STAGE_%\' and S.STATUS_ID = D.STAGE_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'' . $statusEntityId . '_STAGE_%\' and S.STATUS_ID = D.STAGE_ID',
					],
					'PREVIOUS_STAGE_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.PREVIOUS_STAGE_ID',
						'FIELD_TYPE' => 'string',
					],
					'BEGINDATE' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.BEGINDATE',
						'FIELD_TYPE' => 'datetime',
					],
					'CLOSEDATE' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.CLOSEDATE',
						'FIELD_TYPE' => 'datetime',
					],
					'COMPANY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.COMPANY_ID',
						'FIELD_TYPE' => 'int',
					],
					'COMPANY_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'CO.TITLE',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'CO',
						'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
					],
					'COMPANY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.COMPANY_ID is null, null, concat_ws(\' \', concat(\'[\', D.COMPANY_ID, \']\'), nullif(CO.TITLE, \'\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'CO',
						'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
					],
					'CONTACT_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.CONTACT_ID',
						'FIELD_TYPE' => 'int',
					],
					'CONTACT_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'C.FULL_NAME',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'C',
						'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
					],
					'CONTACT' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.CONTACT_ID is null, null, concat_ws(\' \', concat(\'[\', D.CONTACT_ID, \']\'), nullif(C.FULL_NAME, \'\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'C',
						'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
					],
					'OPPORTUNITY' => [
						'IS_METRIC' => 'Y',
						'AGGREGATION_TYPE' => 'SUM',
						'FIELD_NAME' => 'D.OPPORTUNITY',
						'FIELD_TYPE' => 'double',
					],
					'IS_MANUAL_OPPORTUNITY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.IS_MANUAL_OPPORTUNITY',
						'FIELD_TYPE' => 'string',
					],
					'TAX_VALUE' => [
						'IS_METRIC' => 'Y',
						'AGGREGATION_TYPE' => 'SUM',
						'FIELD_NAME' => 'D.TAX_VALUE',
						'FIELD_TYPE' => 'double',
					],
					'CURRENCY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.CURRENCY_ID',
						'FIELD_TYPE' => 'string',
					],
					'OPPORTUNITY_ACCOUNT' => [
						'IS_METRIC' => 'Y',
						'AGGREGATION_TYPE' => 'SUM',
						'FIELD_NAME' => 'D.OPPORTUNITY_ACCOUNT',
						'FIELD_TYPE' => 'double',
					],
					'TAX_VALUE_ACCOUNT' => [
						'IS_METRIC' => 'Y',
						'AGGREGATION_TYPE' => 'SUM',
						'FIELD_NAME' => 'D.TAX_VALUE_ACCOUNT',
						'FIELD_TYPE' => 'double',
					],
					'ACCOUNT_CURRENCY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.ACCOUNT_CURRENCY_ID',
						'FIELD_TYPE' => 'string',
					],
					'MYCOMPANY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.MYCOMPANY_ID',
						'FIELD_TYPE' => 'int',
					],
					'MYCOMPANY_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'CM.TITLE',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'CM',
						'JOIN' => 'INNER JOIN b_crm_company CM ON CM.ID = D.MYCOMPANY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_company CM ON CM.ID = D.MYCOMPANY_ID',
					],
					'MYCOMPANY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.MYCOMPANY_ID is null, null, concat_ws(\' \', concat(\'[\', D.MYCOMPANY_ID, \']\'), nullif(CM.TITLE, \'\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'CM',
						'JOIN' => 'INNER JOIN b_crm_company CM ON CM.ID = D.MYCOMPANY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_company CM ON CM.ID = D.MYCOMPANY_ID',
					],
					'SOURCE_ID' => [
						'IS_METRIC' => 'N',
						'AGGREGATION_TYPE' => 'NO_AGGREGATION',
						'FIELD_NAME' => 'D.SOURCE_ID',
						'FIELD_TYPE' => 'string',
					],
					'SOURCE_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'SS.NAME',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'SS',
						'JOIN' => 'INNER JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
					],
					'SOURCE' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.SOURCE_ID is null, null, concat_ws(\' \', concat(\'[\', D.SOURCE_ID, \']\'), nullif(SS.NAME, \'\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'SS',
						'JOIN' => 'INNER JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
					],
					'SOURCE_DESCRIPTION' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.SOURCE_DESCRIPTION',
						'FIELD_TYPE' => 'string',
					],
					'ASSIGNED_BY_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.ASSIGNED_BY_ID',
						'FIELD_TYPE' => 'int',
					],
					'ASSIGNED_BY_NAME' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.ASSIGNED_BY_ID is null, null, concat_ws(\' \', nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UA',
						'JOIN' => 'INNER JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
					],
					'ASSIGNED_BY' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'if(D.ASSIGNED_BY_ID is null, null, concat_ws(\' \', concat(\'[\', D.ASSIGNED_BY_ID, \']\'), nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
						'FIELD_TYPE' => 'string',
						'TABLE_ALIAS' => 'UA',
						'JOIN' => 'INNER JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
						'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
					],
					'WEBFORM_ID' => [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.WEBFORM_ID',
						'FIELD_TYPE' => 'int',
					],
				],
			];

			if (isset($messages['CRM_DYNAMIC_ITEMS_TABLE_PREFIX']))
			{
				$result['crm_dynamic_items_' . $type->getEntityTypeId()]['TABLE_DESCRIPTION'] = $messages['CRM_DYNAMIC_ITEMS_TABLE_PREFIX'] . ' ' . $result['crm_dynamic_items_' . $type->getEntityTypeId()]['TABLE_DESCRIPTION'];
			}

			foreach ($result['crm_dynamic_items_' . $type->getEntityTypeId()]['FIELDS'] as $fieldCode => &$fieldInfo)
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_DYNAMIC_ITEMS_FIELD_' . $fieldCode];
				if (!$fieldInfo['FIELD_DESCRIPTION'])
				{
					$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
				}

				$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_DYNAMIC_ITEMS_FIELD_' . $fieldCode . '_FULL'] ?? '';
			}
			unset($fieldInfo);

			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($type->getEntityTypeId());
			if ($factory)
			{
				foreach ($factory->getUserFields() as $userField)
				{
					$uf = [
						'IS_METRIC' => 'N', // 'Y'
						'FIELD_NAME' => 'D.' . $userField['FIELD_NAME'],
					];

					$dbType = '';
					if ($userField['USER_TYPE'] && is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype']))
					{
						$dbType = call_user_func_array([$userField['USER_TYPE']['CLASS_NAME'], 'getdbcolumntype'], [$userField]);
					}

					if ($dbType === 'date' && $userField['MULTIPLE'] == 'N')
					{
						$uf['FIELD_TYPE'] = 'date';
					}
					elseif ($dbType === 'datetime' && $userField['MULTIPLE'] == 'N')
					{
						$uf['FIELD_TYPE'] = 'datetime';
					}
					else
					{
						$uf['FIELD_TYPE'] = 'string';
						$uf['CALLBACK'] = function($value, $dateFormats) use($userField, $dbType)
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
						};
					}

					$uf['FIELD_DESCRIPTION'] = $userField['EDIT_FORM_LABEL'];

					$result['crm_dynamic_items_' . $type->getEntityTypeId()]['FIELDS'][$userField['FIELD_NAME']] = $uf;
				}
			}
		}
	}

	/**
	 * Returns default deal category label.
	 *
	 * @param string $languageId Interface language identifier.
	 *
	 * @return string
	 */
	protected static function getDefaultCategoryName($languageId)
	{
		$name = \Bitrix\Main\Config\Option::get('crm', 'default_deal_category_name', '', '');
		if ($name === '')
		{
			$messages = Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/lib/category/dealcategory.php', $languageId);
			$name = $messages['CRM_DEAL_CATEGORY_DEFAULT'];
		}
		return $name;
	}
}
