<?php
namespace Bitrix\Crm\Reservation\Internals;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Crm\Reservation\ProductRowReservation;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Query\Result;

/**
 * Class ProductRowReservationTable
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
 * @method static EO_ProductRowReservation_Query query()
 * @method static EO_ProductRowReservation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ProductRowReservation_Result getById($id)
 * @method static EO_ProductRowReservation_Result getList(array $parameters = [])
 * @method static EO_ProductRowReservation_Entity getEntity()
 * @method static \Bitrix\Crm\Reservation\ProductRowReservation createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Reservation\Internals\EO_ProductRowReservation_Collection createCollection()
 * @method static \Bitrix\Crm\Reservation\ProductRowReservation wakeUpObject($row)
 * @method static \Bitrix\Crm\Reservation\Internals\EO_ProductRowReservation_Collection wakeUpCollection($rows)
 */
class ProductRowReservationTable extends DataManager
{
	use DeleteByFilterTrait;

	public const PRODUCT_ROW_RESERVATION_NAME = 'PRODUCT_ROW_RESERVATION';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_product_row_reservation';
	}

	public static function getObjectClass(): string
	{
		return ProductRowReservation::class;
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
					'title' => Loc::getMessage('PRODUCT_ROW_RESERVE_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'ROW_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('PRODUCT_ROW_RESERVE_ENTITY_PRODUCT_ROW_ID_FIELD'),
				]
			),
			new FloatField(
				'RESERVE_QUANTITY',
				[
					'title' => Loc::getMessage('PRODUCT_ROW_RESERVE_ENTITY_BASKET_RESERVATION_ID_FIELD'),
				]
			),
			new DateField(
				'DATE_RESERVE_END',
				[
					'title' => Loc::getMessage('PRODUCT_ROW_RESERVE_ENTITY_DATE_RESERVE_END'),
				]
			),
			new IntegerField(
				'STORE_ID',
				[
					'title' => Loc::getMessage('PRODUCT_ROW_RESERVE_ENTITY_BASKET_RESERVATION_ID_FIELD'),
				]
			),
			new BooleanField(
				'IS_AUTO',
				[
					'default_value' => null,
					'values' => ['N', 'Y'],
				]
			),
			//
			new Reference(
				ProductRowReservation::PRODUCT_ROW_NAME,
				ProductRowTable::class,
				Join::on('this.ROW_ID', 'ref.ID')
			),
			new Reference(
				'PRODUCT_RESERVATION_MAP',
				ProductReservationMapTable::class,
				Join::on('this.ROW_ID', 'ref.PRODUCT_ROW_ID')
			),
		];
	}

	/**
	 * Get reserve by product row id.
	 *
	 * @param int $rowId
	 *
	 * @return Result
	 */
	public static function getByRowId(int $rowId): Result
	{
		return
			static::getList([
				'filter' => [
					'=ROW_ID' => $rowId,
				],
				'limit' => 1,
			])
		;
	}

	/**
	 * Get reserve by product row id.
	 *
	 * @param int $rowId
	 *
	 * @return array|null
	 */
	public static function getRowByRowId(int $rowId): ?array
	{
		return static::getByRowId($rowId)->fetch();
	}

	public static function deleteByRowId($rowId)
	{
		$sql = new \Bitrix\Main\DB\SqlExpression(
			"DELETE FROM ?# WHERE ROW_ID = ?i",
			static::getTableName(),
			$rowId
		);
		Application::getConnection(static::getConnectionName())->query($sql);
	}
}
