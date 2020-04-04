<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BasketTable extends Main\Entity\DataManager
{

	/**
	 * @param $id
	 * @return Main\Entity\DeleteResult
	 * @throws Main\ArgumentException
	 */
	public static function deleteBundle($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new Main\ArgumentNullException("id");

		$itemsFromDbList = BasketTable::getList(
			array(
				"filter" => array(
					'SET_PARENT_ID' => $id,
				),
				"select" => array("ID")
			)
		);
		while ($itemsFromDbItem = $itemsFromDbList->fetch())
			BasketTable::deleteWithItems($itemsFromDbItem['ID']);

		return BasketTable::deleteWithItems($id);
	}

	/**
	 * @param $id
	 * @return Main\Entity\DeleteResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	public static function deleteWithItems($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new Main\ArgumentNullException("id");

		$itemsList = BasketPropertyTable::getList(
			array(
				"select" => array("ID"),
				"filter" => array("BASKET_ID" => $id),
			)
		);
		while ($item = $itemsList->fetch())
			BasketPropertyTable::delete($item["ID"]);

		return BasketTable::delete($id);
	}

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_basket';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		global $DB;

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'LID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateLid'),
			),
			'FUSER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),

			new Main\Entity\ReferenceField(
				'FUSER',
				'Bitrix\Sale\Internals\Fuser',
				array('=this.FUSER_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),

			new Main\Entity\ReferenceField(
				'USER',
				'Bitrix\Main\User',
				array('=ref.ID' => 'this.FUSER.USER_ID')
			),

			'ORDER_ID' => array(
				'data_type' => 'integer'
			),

			new Main\Entity\ReferenceField(
				'ORDER',
				'Bitrix\Sale\Internals\Order',
				array('=this.ORDER_ID' => 'ref.ID')
			),

			'PRODUCT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'PRODUCT' => array(
				'data_type' => 'Product',
				'reference' => array(
					'=this.PRODUCT_ID' => 'ref.ID'
				)
			),
			'PRODUCT_PRICE_ID' => array(
				'data_type' => 'integer'
			),
			'PRICE_TYPE_ID' => array(
				'data_type' => 'integer'
			),
			'NAME' => array(
				'data_type' => 'string'
			),

			new Main\Entity\ExpressionField(
				'NAME_WITH_IDENT',
				$helper->getConcatFunction("%s", "' ['", "%s", "']'"),
				array('NAME', 'PRODUCT_ID')
			),

			new Main\Entity\FloatField(
				'PRICE'
			),

			'CURRENCY' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
			),

			new Main\Entity\FloatField(
				'BASE_PRICE'
			),

			'VAT_INCLUDED' => array(
				'data_type' => 'boolean',
				'values' => array('Y','N')
			),

			'DATE_INSERT' => array(
				'data_type' => 'datetime'
			),
			new Main\Entity\ExpressionField(
					'DATE_INS',
					$DB->datetimeToDateFunction('%s'),
					array('DATE_INSERT'),
					array('data_type' => 'datetime')
			),

			'DATE_UPDATE' => array(
				'data_type' => 'datetime'
			),
			new Main\Entity\ExpressionField(
					'DATE_UPD',
					$DB->datetimeToDateFunction('%s'),
					array('DATE_UPDATE'),
					array('data_type' => 'datetime')
			),

			'DATE_REFRESH' => array(
				'data_type' => 'datetime'
			),
			new Main\Entity\ExpressionField(
					'DATE_REF',
					$DB->datetimeToDateFunction('%s'),
					array('DATE_REFRESH'),
					array('data_type' => 'datetime')
			),

			new Main\Entity\FloatField(
				'WEIGHT'
			),

			new Main\Entity\FloatField(
				'QUANTITY',
				array(
					'required' => true
				)
			),

			'DELAY' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),

			'SUMMARY_PRICE' => array(
				'data_type' => 'float',
				'expression' => array(
					'(%s * %s)', 'QUANTITY', 'PRICE'
				)
			),

			'CAN_BUY' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),

			'MARKING_CODE_GROUP' => array(
				'data_type' => 'string',
			),

			'MODULE' => array(
				'data_type' => 'string'
			),

			'PRODUCT_PROVIDER_CLASS' => array(
				'data_type' => 'string'
			),

			'NOTES' => array(
				'data_type' => 'string'
			),

			'DETAIL_PAGE_URL' => array(
				'data_type' => 'string'
			),

			new Main\Entity\FloatField(
				'DISCOUNT_PRICE',
				array(
					'default_value' => '0.00'
				)
			),

			'CATALOG_XML_ID' => array(
				'data_type' => 'string'
			),

			'PRODUCT_XML_ID' => array(
				'data_type' => 'string'
			),

			'DISCOUNT_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDiscountName'),
			),

			'DISCOUNT_VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDiscountValue'),
			),

			'DISCOUNT_COUPON' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDiscountCoupon'),
			),

			new Main\Entity\FloatField(
				'VAT_RATE'
			),

			new Main\Entity\ExpressionField(
				'VAT_RATE_PRC',
				'100 * %s',
				array('VAT_RATE')
			),

			'SUBSCRIBE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'N_SUBSCRIBE' => array(
				'data_type' => 'integer',
				'expression' => array(
					'CASE WHEN %s = \'Y\' THEN 1 ELSE 0 END', 'SUBSCRIBE'
				)
			),

			'RESERVED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),

			new Main\Entity\FloatField(
				'RESERVE_QUANTITY'
			),

			'BARCODE_MULTI' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),

			'CUSTOM_PRICE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),

			'DIMENSIONS' => array(
				'serialized' => true,
				'data_type' => 'string'
			),

			new Main\Entity\IntegerField(
				'TYPE'
			),
			new Main\Entity\IntegerField(
				'SET_PARENT_ID'
			),
			new Main\Entity\IntegerField(
				'MEASURE_CODE'
			),

			'MEASURE_NAME' => array(
				'data_type' => 'string'
			),

			'CALLBACK_FUNC' => array(
				'data_type' => 'string'
			),

			'ORDER_CALLBACK_FUNC' => array(
				'data_type' => 'string'
			),

			'CANCEL_CALLBACK_FUNC' => array(
				'data_type' => 'string'
			),

			'PAY_CALLBACK_FUNC' => array(
				'data_type' => 'string'
			),

			'RECOMMENDATION' => array(
				'data_type' => 'string'
			),


			'ALL_PRICE' => array(
				'data_type' => 'float',
				'expression' => array(
					'(%s + %s)', 'PRICE', 'DISCOUNT_PRICE'
				)
			),

			'SUMMARY_PURCHASING_PRICE' => array(
				'data_type' => 'float',
				'expression' => array(
					'(%s) * %s', 'PRODUCT.PURCHASING_PRICE_IN_SITE_CURRENCY', 'QUANTITY'
				)
			),
			'GROSS_PROFIT' => array(
				'data_type' => 'float',
				'expression' => array(
					'(%s) - (%s)', 'SUMMARY_PRICE', 'SUMMARY_PURCHASING_PRICE'
				)
			),
			'PROFITABILITY' => array(
				'data_type' => 'float',
				'expression' => array(
					'CASE WHEN %s is NULL OR %s=0 THEN NULL ELSE (%s) * 100 / (%s) END',
					'SUMMARY_PURCHASING_PRICE', 'SUMMARY_PURCHASING_PRICE', 'GROSS_PROFIT', 'SUMMARY_PURCHASING_PRICE'
				)
			),

			'SHIPMENT_ITEM' => array(
				'data_type' => 'ShipmentItem',
				'reference' => array(
					'=ref.BASKET_ID' => 'this.ID',
				)
			),
			'SHIPMENT' => array(
				'data_type' => 'Shipment',
				'reference' => array(
					'=ref.ID' => 'this.SHIPMENT_ITEM.ORDER_DELIVERY_ID',
				)
			),

			'PAYMENT' => array(
				'data_type' => 'Payment',
				'reference' => array(
					'=ref.ORDER_ID' => 'this.ORDER_ID',
				)
			),

			new Main\Entity\IntegerField(
				'SORT',
				array(
					'default' => '100'
				)
			),

			'XML_ID' => array(
				'data_type' => 'string'
			),
		);
	}

	/**
	 * Returns validators for CURRENCY field.
	 *
	 * @return array
	 */
	public static function validateCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}

	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	public static function validateLid()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}

	/**
	 * Returns validators for DISCOUNT_NAME field.
	 *
	 * @return array
	 */
	public static function validateDiscountName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for DISCOUNT_VALUE field.
	 *
	 * @return array
	 */
	public static function validateDiscountValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 32),
		);
	}

	/**
	 * Returns validators for DISCOUNT_COUPON field.
	 *
	 * @return array
	 */
	public static function validateDiscountCoupon()
	{
		return array(
			new Main\Entity\Validator\Length(null, 32),
		);
	}


}
