<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 * 
 * @ignore
 * @see \Bitrix\Catalog\ProductTable
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('iblock'))
{
	return;
}

/**
 * Class ProductTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Product_Query query()
 * @method static EO_Product_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Product_Result getById($id)
 * @method static EO_Product_Result getList(array $parameters = [])
 * @method static EO_Product_Entity getEntity()
 * @method static \Bitrix\Sale\Internals\EO_Product createObject($setDefaultValues = true)
 * @method static \Bitrix\Sale\Internals\EO_Product_Collection createCollection()
 * @method static \Bitrix\Sale\Internals\EO_Product wakeUpObject($row)
 * @method static \Bitrix\Sale\Internals\EO_Product_Collection wakeUpCollection($rows)
 */
class ProductTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_catalog_product';
	}

	public static function getMap()
	{
		// Get weight factor
		$siteId = '';
		$weight_koef = 0;
		$site_currency = '';
		if (class_exists('\CBaseSaleReportHelper'))
		{
			if (\CBaseSaleReportHelper::isInitialized())
			{
				$siteId = \CBaseSaleReportHelper::getDefaultSiteId();
				if ($siteId !== null)
				{
					$weight_koef = intval(\CBaseSaleReportHelper::getDefaultSiteWeightDivider());
				}

				// Get site currency
				$site_currency = \CBaseSaleReportHelper::getSiteCurrencyId();
			}
		}
		if ($weight_koef <= 0) $weight_koef = 1;

		global $DB;

		if (function_exists('___dbCastIntToChar') !== true)
		{
			eval(
				'function ___dbCastIntToChar($dbtype, $param)'.
				'{'.
				'   $result = $param;'.
				'   if (ToLower($dbtype) === "mssql")'.
				'   {'.
				'       $result = "CAST(".$param." AS VARCHAR)";'.
				'   }'.
				'   return $result;'.
				'}'
			);
		}

		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			/*'IBLOCK_ID' => array(
				'data_type' => 'integer'
			),*/
			'TIMESTAMP_X' => array(
				'data_type' => 'integer'
			),
			'DATE_UPDATED' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'TIMESTAMP_X',
				)
			),
			'QUANTITY' => array(
				'data_type' => 'float'
			),
			'MEASURE' => array(
				'data_type' => 'integer'
			),
			'PURCHASING_PRICE' => array(
				'data_type' => 'float'
			),
			'PURCHASING_CURRENCY' => array(
				'data_type' => 'string'
			),
			'IBLOCK' => array(
				'data_type' => 'Bitrix\Iblock\Element',
				'reference' => array('=this.ID' => 'ref.ID')
			),
			'NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					'%s', 'IBLOCK.NAME'
				)
			),
			'NAME_WITH_IDENT' => array(
				'data_type' => 'string',
				'expression' => array(
					$DB->concat('%s', '\' [\'', ___dbCastIntToChar('mysql', '%s'), '\']\''), 'NAME', 'ID'
				)
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'%s', 'IBLOCK.ACTIVE'
				),
				'values' => array('N','Y')
			),
			'WEIGHT' => array(
				'data_type' => 'float'
			),
			'WEIGHT_IN_SITE_UNITS' => array(
				'data_type' => 'float',
				'expression' => array(
					'%s / '.$DB->forSql($weight_koef), 'WEIGHT'
				)
			),
			'PRICE' => array(
				'data_type' => 'float',
				'expression' => array(
					'(SELECT b_catalog_price.PRICE FROM b_catalog_price
						LEFT JOIN b_catalog_group ON b_catalog_group.ID = b_catalog_price.CATALOG_GROUP_ID
					WHERE
						b_catalog_price.PRODUCT_ID = %s
						AND
						b_catalog_group.base = \'Y\'
						AND
						( b_catalog_price.quantity_from <= 1 OR b_catalog_price.quantity_from IS NULL )
						AND
						( b_catalog_price.quantity_to >= 1 OR b_catalog_price.quantity_to IS NULL)
					LIMIT 1)', 'ID'
				)
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'expression' => array(
					'(SELECT b_catalog_price.CURRENCY FROM b_catalog_price
						LEFT JOIN b_catalog_group ON b_catalog_group.ID = b_catalog_price.CATALOG_GROUP_ID
					WHERE
						b_catalog_price.PRODUCT_ID = %s
						AND
						b_catalog_group.base = \'Y\'
						AND
						( b_catalog_price.quantity_from <= 1 OR b_catalog_price.quantity_from IS NULL )
						AND
						( b_catalog_price.quantity_to >= 1 OR b_catalog_price.quantity_to IS NULL)
					LIMIT 1)', 'ID'
				)
			),
			'SUMMARY_PRICE' => array(
				'data_type' => 'float',
				'expression' => array(
					'%s * %s', 'QUANTITY', 'PRICE'
				),
			),



			'CURRENT_CURRENCY_RATE' => array(
				'data_type' => 'float',
				'expression' => array(
					'('.$DB->topSql('SELECT (CASE WHEN b_catalog_currency_rate.RATE IS NOT NULL THEN b_catalog_currency_rate.RATE ELSE b_catalog_currency.AMOUNT END)
					FROM b_catalog_product INNER JOIN b_catalog_currency ON 1=1
						LEFT JOIN b_catalog_currency_rate ON (b_catalog_currency.CURRENCY = b_catalog_currency_rate.CURRENCY AND b_catalog_currency_rate.DATE_RATE <= '.$DB->datetimeToDateFunction('b_catalog_product.TIMESTAMP_X').')
					WHERE b_catalog_product.ID = %s AND b_catalog_currency.CURRENCY = %s
					ORDER BY DATE_RATE DESC', 1).')', 'ID', 'CURRENCY'
				)
			),
			'CURRENT_CURRENCY_RATE_CNT' => array(
				'data_type' => 'float',
				'expression' => array(
					'('.$DB->topSql('SELECT (CASE WHEN b_catalog_currency_rate.RATE_CNT IS NOT NULL THEN b_catalog_currency_rate.RATE_CNT ELSE b_catalog_currency.AMOUNT_CNT END)
					FROM b_catalog_product INNER JOIN b_catalog_currency ON 1=1
						LEFT JOIN b_catalog_currency_rate ON (b_catalog_currency.CURRENCY = b_catalog_currency_rate.CURRENCY AND b_catalog_currency_rate.DATE_RATE <= '.$DB->datetimeToDateFunction('b_catalog_product.TIMESTAMP_X').')
					WHERE b_catalog_product.ID = %s AND b_catalog_currency.CURRENCY = %s
					ORDER BY DATE_RATE DESC', 1).')', 'ID', 'CURRENCY'
				)
			),

			'CURRENT_SITE_CURRENCY_RATE' => array(
				'data_type' => 'float',
				'expression' => array(
					'('.$DB->topSql('SELECT (CASE WHEN b_catalog_currency_rate.RATE IS NOT NULL THEN b_catalog_currency_rate.RATE ELSE b_catalog_currency.AMOUNT END)
					FROM b_catalog_product INNER JOIN b_catalog_currency ON 1=1
						LEFT JOIN b_catalog_currency_rate ON (b_catalog_currency.CURRENCY = b_catalog_currency_rate.CURRENCY AND b_catalog_currency_rate.DATE_RATE <= '.$DB->datetimeToDateFunction('b_catalog_product.TIMESTAMP_X').')
					WHERE b_catalog_product.ID = %s AND b_catalog_currency.CURRENCY = \''.$DB->forSql($site_currency).'\'
					ORDER BY DATE_RATE DESC', 1).')', 'ID'
				)
			),

			'CURRENT_SITE_CURRENCY_RATE_CNT' => array(
				'data_type' => 'float',
				'expression' => array(
					'('.$DB->topSql('SELECT (CASE WHEN b_catalog_currency_rate.RATE_CNT IS NOT NULL THEN b_catalog_currency_rate.RATE_CNT ELSE b_catalog_currency.AMOUNT_CNT END)
					FROM b_catalog_product INNER JOIN b_catalog_currency ON 1=1
						LEFT JOIN b_catalog_currency_rate ON (b_catalog_currency.CURRENCY = b_catalog_currency_rate.CURRENCY AND b_catalog_currency_rate.DATE_RATE <= '.$DB->datetimeToDateFunction('b_catalog_product.TIMESTAMP_X').')
					WHERE b_catalog_product.ID = %s AND b_catalog_currency.CURRENCY = \''.$DB->forSql($site_currency).'\'
					ORDER BY DATE_RATE DESC', 1).')', 'ID'
				)
			),



			'PURCHASING_CURRENCY_RATE' => array(
				'data_type' => 'float',
				'expression' => array(
					'('.$DB->topSql('SELECT (CASE WHEN b_catalog_currency_rate.RATE IS NOT NULL THEN b_catalog_currency_rate.RATE ELSE b_catalog_currency.AMOUNT END)
					FROM b_catalog_product INNER JOIN b_catalog_currency ON 1=1
						LEFT JOIN b_catalog_currency_rate ON (b_catalog_currency.CURRENCY = b_catalog_currency_rate.CURRENCY AND b_catalog_currency_rate.DATE_RATE <= '.$DB->datetimeToDateFunction('b_catalog_product.TIMESTAMP_X').')
					WHERE b_catalog_product.ID = %s AND b_catalog_currency.CURRENCY = %s
					ORDER BY DATE_RATE DESC', 1).')', 'ID', 'PURCHASING_CURRENCY'
				)
			),
			'PURCHASING_CURRENCY_RATE_CNT' => array(
				'data_type' => 'float',
				'expression' => array(
					'('.$DB->topSql('SELECT (CASE WHEN b_catalog_currency_rate.RATE_CNT IS NOT NULL THEN b_catalog_currency_rate.RATE_CNT ELSE b_catalog_currency.AMOUNT_CNT END)
					FROM b_catalog_product INNER JOIN b_catalog_currency ON 1=1
						LEFT JOIN b_catalog_currency_rate ON (b_catalog_currency.CURRENCY = b_catalog_currency_rate.CURRENCY AND b_catalog_currency_rate.DATE_RATE <= '.$DB->datetimeToDateFunction('b_catalog_product.TIMESTAMP_X').')
					WHERE b_catalog_product.ID = %s AND b_catalog_currency.CURRENCY = %s
					ORDER BY DATE_RATE DESC', 1).')', 'ID', 'PURCHASING_CURRENCY'
				)
			),



			'PRICE_IN_SITE_CURRENCY' => array(
				'data_type' => 'float',
				'expression' => array(
					'%s * (%s * %s / %s / %s)',
					'PRICE', 'CURRENT_CURRENCY_RATE', 'CURRENT_SITE_CURRENCY_RATE_CNT', 'CURRENT_SITE_CURRENCY_RATE', 'CURRENT_CURRENCY_RATE_CNT'
				)
			),

			'PURCHASING_PRICE_IN_SITE_CURRENCY' => array(
				'data_type' => 'float',
				'expression' => array(
					'%s * (%s * %s / %s / %s)',
					'PURCHASING_PRICE', 'PURCHASING_CURRENCY_RATE', 'CURRENT_SITE_CURRENCY_RATE_CNT', 'CURRENT_SITE_CURRENCY_RATE', 'PURCHASING_CURRENCY_RATE_CNT'
				)
			),

			'SUMMARY_PRICE_IN_SITE_CURRENCY' => array(
				'data_type' => 'float',
				'expression' => array(
					'%s * (%s * %s / %s / %s)',
					'SUMMARY_PRICE', 'CURRENT_CURRENCY_RATE', 'CURRENT_SITE_CURRENCY_RATE_CNT', 'CURRENT_SITE_CURRENCY_RATE', 'CURRENT_CURRENCY_RATE_CNT'
				)
			)
		);

		return $fieldsMap;
	}
}