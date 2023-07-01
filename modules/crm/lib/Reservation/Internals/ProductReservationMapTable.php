<?php
namespace Bitrix\Crm\Reservation\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class ProductReservationMapTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> PRODUCT_ROW_ID int mandatory
 * <li> BASKET_RESERVATION_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Crm\Reservation\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ProductReservationMap_Query query()
 * @method static EO_ProductReservationMap_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ProductReservationMap_Result getById($id)
 * @method static EO_ProductReservationMap_Result getList(array $parameters = [])
 * @method static EO_ProductReservationMap_Entity getEntity()
 * @method static \Bitrix\Crm\Reservation\Internals\EO_ProductReservationMap createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Reservation\Internals\EO_ProductReservationMap_Collection createCollection()
 * @method static \Bitrix\Crm\Reservation\Internals\EO_ProductReservationMap wakeUpObject($row)
 * @method static \Bitrix\Crm\Reservation\Internals\EO_ProductReservationMap_Collection wakeUpCollection($rows)
 */
class ProductReservationMapTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_product_reservation_map';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('PRODUCT_RESERVATION_MAP_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'PRODUCT_ROW_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('PRODUCT_RESERVATION_MAP_ENTITY_PRODUCT_ROW_ID_FIELD'),
				]
			),
			new IntegerField(
				'BASKET_RESERVATION_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('PRODUCT_RESERVATION_MAP_ENTITY_BASKET_RESERVATION_ID_FIELD'),
				]
			),
		];
	}
}
