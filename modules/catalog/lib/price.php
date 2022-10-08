<?php
namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class PriceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> PRODUCT_ID int mandatory
 * <li> EXTRA_ID int optional
 * <li> CATALOG_GROUP_ID int mandatory
 * <li> PRICE double mandatory
 * <li> CURRENCY string(3) mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> QUANTITY_FROM int optional
 * <li> QUANTITY_TO int optional
 * <li> TMP_ID string(40) optional
 * <li> PRICE_SCALE double optional
 * </ul>
 *
 * @package Bitrix\Catalog
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Price_Query query()
 * @method static EO_Price_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Price_Result getById($id)
 * @method static EO_Price_Result getList(array $parameters = [])
 * @method static EO_Price_Entity getEntity()
 * @method static \Bitrix\Catalog\EO_Price createObject($setDefaultValues = true)
 * @method static \Bitrix\Catalog\EO_Price_Collection createCollection()
 * @method static \Bitrix\Catalog\EO_Price wakeUpObject($row)
 * @method static \Bitrix\Catalog\EO_Price_Collection wakeUpCollection($rows)
 */

class PriceTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_price';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_ID_FIELD')
			)),
			'PRODUCT_ID' => new Main\Entity\IntegerField('PRODUCT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_PRODUCT_ID_FIELD')
			)),
			'EXTRA_ID' => new Main\Entity\IntegerField('EXTRA_ID', array(
				'title' => Loc::getMessage('PRICE_ENTITY_EXTRA_ID_FIELD')
			)),
			'CATALOG_GROUP_ID' => new Main\Entity\IntegerField('CATALOG_GROUP_ID', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_CATALOG_GROUP_ID_FIELD')
			)),
			'PRICE' => new Main\Entity\FloatField('PRICE', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_PRICE_FIELD')
			)),
			'CURRENCY' => new Main\Entity\StringField('CURRENCY', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('PRICE_ENTITY_CURRENCY_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => function(){ return new Main\Type\DateTime(); },
				'title' => Loc::getMessage('PRICE_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'QUANTITY_FROM' => new Main\Entity\IntegerField('QUANTITY_FROM', array(
				'title' => Loc::getMessage('PRICE_ENTITY_QUANTITY_FROM_FIELD')
			)),
			'QUANTITY_TO' => new Main\Entity\IntegerField('QUANTITY_TO', array(
				'title' => Loc::getMessage('PRICE_ENTITY_QUANTITY_TO_FIELD')
			)),
			'TMP_ID' => new Main\Entity\StringField('TMP_ID', array(
				'validation' => array(__CLASS__, 'validateTmpId'),
				'title' => Loc::getMessage('PRICE_ENTITY_TMP_ID_FIELD')
			)),
			'PRICE_SCALE' => new Main\Entity\FloatField('PRICE_SCALE', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_PRICE_SCALE_FIELD')
			)),
			'CATALOG_GROUP' => new Main\Entity\ReferenceField(
				'CATALOG_GROUP',
				'\Bitrix\Catalog\Group',
				array('=this.CATALOG_GROUP_ID' => 'ref.ID')
			),
			'ELEMENT' => new Main\Entity\ReferenceField(
				'ELEMENT',
				'\Bitrix\Iblock\Element',
				array('=this.PRODUCT_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'PRODUCT' => new Main\Entity\ReferenceField(
				'PRODUCT',
				'\Bitrix\Catalog\Product',
				array('=this.PRODUCT_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
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
	 * Returns validators for TMP_ID field.
	 *
	 * @return array
	 */
	public static function validateTmpId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 40),
		);
	}

	/**
	 * Delete all rows for product.
	 * @internal
	 *
	 * @param int $id       Product id.
	 * @return void
	 */
	public static function deleteByProduct($id)
	{
		$id = (int)$id;
		if ($id <= 0)
			return;

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('PRODUCT_ID').' = '.$id
		);
		unset($helper, $conn);
	}
}