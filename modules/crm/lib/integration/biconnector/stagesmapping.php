<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\Crm\DbHelper;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\MysqliSqlHelper;
use Bitrix\Main\DB\PgsqlSqlHelper;
use Bitrix\Main\Localization\Loc;

class StagesMapping
{
	public static function getMapping(MysqliSqlHelper|PgsqlSqlHelper $helper, string $languageId): array
	{
		$entityIdSql = DbHelper::getSqlByDbType(
			'SUBSTRING_INDEX(SUBSTRING_INDEX(S.ENTITY_ID, "_", 2), "_", -1)',
			'split_part(S."ENTITY_ID", \'_\', 2)'
		);

		return [
			'TABLE_NAME' => 'b_crm_status',
			'TABLE_ALIAS' => 'S',
			'FILTER' => [
				//ENTITY_ID = 'STATUS' OR ENTITY_ID like 'DEAL_STAGE%' OR ENTITY_ID like 'DYNAMIC%STAGE%'
				'LOGIC' => 'OR',
				'=ENTITY_ID_LEAD' => 'STATUS',
				'ENTITY_ID_DEAL' => 'DEAL_STAGE%',
				'ENTITY_ID_SMART' => 'DYNAMIC%STAGE%',
			],
			'FILTER_FIELDS' => [
				'ENTITY_ID_LEAD' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.ENTITY_ID',
					'FIELD_TYPE' => 'string',
				],
				'ENTITY_ID_DEAL' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.ENTITY_ID',
					'FIELD_TYPE' => 'string',
				],
				'ENTITY_ID_SMART' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.ENTITY_ID',
					'FIELD_TYPE' => 'string',
				],
			],
			'FIELDS' => [
				//  ID int NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'S.ID',
					'FIELD_TYPE' => 'int',
				],
				//  ENTITY_ID varchar(50) NOT NULL,
				'ENTITY_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'CASE  
    WHEN S.ENTITY_ID LIKE \'DEAL_STAGE%\' THEN 2 
    WHEN (LEFT(S.ENTITY_ID, 7) = \'DYNAMIC\') THEN ' . $entityIdSql . '
    ELSE 1 
    END',
					'FIELD_TYPE' => 'int',
				],
				//  STATUS_ID varchar(50) NOT NULL,
				'STATUS_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.STATUS_ID',
					'FIELD_TYPE' => 'string'
				],
				//  NAME varchar(100) NOT NULL,
				'NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string'
				],
				//  CATEGORY_ID int unsigned DEFAULT NULL,
				'CATEGORY_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.CATEGORY_ID',
					'FIELD_TYPE' => 'int'
				],
				'CATEGORY_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'CASE  
    WHEN S.ENTITY_ID LIKE \'DEAL_STAGE%\' THEN ifnull(CDC.NAME, \'' . $helper->forSql(static::getDefaultDealCategoryName($languageId)) . '\')
    WHEN (LEFT(S.ENTITY_ID, 7) = \'DYNAMIC\') THEN CIC.NAME
    ELSE \'\' 
    END',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'multiple_join',
					'JOIN' => 'INNER JOIN b_crm_deal_category CDC ON CDC.ID = S.CATEGORY_ID'. "\n  " . 'INNER JOIN b_crm_item_category CIC ON CIC.ID = S.CATEGORY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal_category CDC ON CDC.ID = S.CATEGORY_ID'. "\n  " . 'LEFT JOIN b_crm_item_category CIC ON CIC.ID = S.CATEGORY_ID'
				],
				//  NAME_INIT varchar(100) DEFAULT NULL,
				//  SORT int NOT NULL,
				'SORT' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.SORT',
					'FIELD_TYPE' => 'int'
				],
				//  SYSTEM char(1) NOT NULL,
				//  COLOR char(10) DEFAULT NULL,
				//  SEMANTICS char(1) DEFAULT NULL,
				'SEMANTICS' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'S.SEMANTICS',
					'FIELD_TYPE' => 'string'
				],
			]
		];
	}

	/**
	 * Returns default deal category label.
	 */
	private static function getDefaultDealCategoryName(string $languageId): string
	{
		$name = Option::get('crm', 'default_deal_category_name', '', '');
		if ($name === '')
		{
			$messages = Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/lib/category/dealcategory.php', $languageId);
			$name = $messages['CRM_DEAL_CATEGORY_DEFAULT'];
		}

		return $name;
	}
}