<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\Main\DB\MysqliSqlHelper;
use Bitrix\Main\DB\PgsqlSqlHelper;

class QuoteProductMapping
{
	use ProductDataHelperTrait;
	public static function getMapping(MysqliSqlHelper|PgsqlSqlHelper $helper): array
	{
		$discountSql = self::getDiscountSql($helper);

		return [
			'TABLE_NAME' => 'b_crm_product_row',
			'TABLE_ALIAS' => 'PR',
			'FILTER' => [
				'=OWNER_TYPE' => \CCrmOwnerTypeAbbr::Quote,
			],
			'FILTER_FIELDS' => [
				'OWNER_TYPE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'PR.OWNER_TYPE',
					'FIELD_TYPE' => 'string',
				],
			],
			'FIELDS' => [
				//ID INT (18) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'PR.ID',
					'FIELD_TYPE' => 'int',
				],
				//OWNER_ID INT(1) NOT NULL,
				'QUOTE_ID' => [
					'FIELD_NAME' => 'PR.OWNER_ID',
					'FIELD_TYPE' => 'int',
				],
				//b_crm_quote.DATE_CREATE DATETIME NULL,
				'QUOTE_DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'Q',
					'JOIN' => 'INNER JOIN b_crm_quote Q ON Q.ID = PR.OWNER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_quote Q ON Q.ID = PR.OWNER_ID',
				],
				//b_crm_quote.CLOSEDATE DATETIME DEFAULT NULL,
				'QUOTE_CLOSEDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.CLOSEDATE',
					'FIELD_TYPE' => 'datetime',
					'TABLE_ALIAS' => 'Q',
					'JOIN' => 'INNER JOIN b_crm_quote Q ON Q.ID = PR.OWNER_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_quote Q ON Q.ID = PR.OWNER_ID',
				],
				// OWNER_TYPE CHAR(3) NOT NULL,
				//PRODUCT_ID INT(1) NOT NULL,
				'PRODUCT' => [
					'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.PRODUCT_ID', '\']\'') . ', nullif(PR.PRODUCT_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
				],
				'PRODUCT_ID' => [
					'FIELD_NAME' => 'PR.PRODUCT_ID',
					'FIELD_TYPE' => 'int',
				],
				//PRODUCT_NAME VARCHAR(256) NULL,
				'PRODUCT_NAME' => [
					'FIELD_NAME' => 'PR.PRODUCT_NAME',
					'FIELD_TYPE' => 'string',
				],
				//PRICE DECIMAL(18,2) NOT NULL,
				'PRICE' => [
					'FIELD_NAME' => 'PR.PRICE',
					'FIELD_TYPE' => 'double',
				],
				//PRICE_ACCOUNT DECIMAL(18,2) NOT NULL DEFAULT 0,
				//PRICE_EXCLUSIVE DECIMAL(18,2) NULL,
				'PRICE_EXCLUSIVE' => [
					'FIELD_NAME' => 'PR.PRICE_EXCLUSIVE',
					'FIELD_TYPE' => 'double',
				],
				//PRICE_NETTO DECIMAL(18,2) NULL,
				'PRICE_NETTO' => [
					'FIELD_NAME' => 'PR.PRICE_NETTO',
					'FIELD_TYPE' => 'double',
				],
				//PRICE_BRUTTO DECIMAL(18,2) NULL,
				'PRICE_BRUTTO' => [
					'FIELD_NAME' => 'PR.PRICE_BRUTTO',
					'FIELD_TYPE' => 'double',
				],
				//QUANTITY DECIMAL(18,4) NOT NULL,
				'QUANTITY' => [
					'FIELD_NAME' => 'PR.QUANTITY',
					'FIELD_TYPE' => 'double',
				],
				//DISCOUNT_TYPE_ID TINYINT(1) UNSIGNED NULL,
				'DISCOUNT_TYPE' => [
					'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.DISCOUNT_TYPE_ID', '\']\'') . ', ' . str_replace('#FIELD_NAME#', 'PR.DISCOUNT_TYPE_ID', $discountSql) . ')',
					'FIELD_TYPE' => 'string',
				],
				'DISCOUNT_TYPE_ID' => [
					'FIELD_NAME' => 'PR.DISCOUNT_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				'DISCOUNT_TYPE_NAME' => [
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'PR.DISCOUNT_TYPE_ID', $discountSql),
					'FIELD_TYPE' => 'string',
				],
				//DISCOUNT_RATE DECIMAL(18,2) NULL,
				'DISCOUNT_RATE' => [
					'FIELD_NAME' => 'PR.DISCOUNT_RATE',
					'FIELD_TYPE' => 'double',
				],
				//DISCOUNT_SUM DECIMAL(18,2) NULL,
				'DISCOUNT_SUM' => [
					'FIELD_NAME' => 'PR.DISCOUNT_SUM',
					'FIELD_TYPE' => 'double',
				],
				//TAX_RATE DECIMAL(18,2) NULL,
				'TAX_RATE' => [
					'FIELD_NAME' => 'PR.TAX_RATE',
					'FIELD_TYPE' => 'double',
				],
				//TAX_INCLUDED CHAR(1) NULL,
				'TAX_INCLUDED' => [
					'FIELD_NAME' => 'PR.TAX_INCLUDED',
					'FIELD_TYPE' => 'string',
				],
				//CUSTOMIZED CHAR(1) NULL,
				'CUSTOMIZED' => [
					'FIELD_NAME' => 'PR.CUSTOMIZED',
					'FIELD_TYPE' => 'string',
				],
				//MEASURE_CODE INT(1) UNSIGNED NULL,
				'MEASURE' => [
					'FIELD_NAME' => 'concat_ws(\' \', ' . $helper->getConcatFunction('\'[\'', 'PR.MEASURE_CODE', '\']\'') . ', nullif(PR.MEASURE_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
				],
				'MEASURE_CODE' => [
					'FIELD_NAME' => 'PR.MEASURE_CODE',
					'FIELD_TYPE' => 'int',
				],
				//MEASURE_NAME VARCHAR(50) NULL,
				'MEASURE_NAME' => [
					'FIELD_NAME' => 'PR.MEASURE_NAME',
					'FIELD_TYPE' => 'string',
				],
				//SORT INT(1) NULL,
				'SORT' => [
					'FIELD_NAME' => 'PR.SORT',
					'FIELD_TYPE' => 'int',
				],
				// XML_ID varchar(255) DEFAULT NULL,
				'PARENT' => self::getParentDescription($helper),
				'SUPERPARENT' => self::getSuperParentDescription($helper),
				'SUPERSUPERPARENT' => self::getSuperSuperParentDescription($helper),
			],
		];
	}
}