<?php
namespace Bitrix\Crm\Order\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class ShipmentRealizationTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SHIPMENT_ID int mandatory
 * <li> IS_REALIZATION bool ('N', 'Y') optional default 'Y'
 * </ul>
 *
 * @package Bitrix\Crm
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ShipmentRealization_Query query()
 * @method static EO_ShipmentRealization_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ShipmentRealization_Result getById($id)
 * @method static EO_ShipmentRealization_Result getList(array $parameters = [])
 * @method static EO_ShipmentRealization_Entity getEntity()
 * @method static \Bitrix\Crm\Order\Internals\EO_ShipmentRealization createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Order\Internals\EO_ShipmentRealization_Collection createCollection()
 * @method static \Bitrix\Crm\Order\Internals\EO_ShipmentRealization wakeUpObject($row)
 * @method static \Bitrix\Crm\Order\Internals\EO_ShipmentRealization_Collection wakeUpCollection($rows)
 */

class ShipmentRealizationTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_shipment_realization';
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
					'title' => Loc::getMessage('SHIPMENT_REALIZATION_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'SHIPMENT_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIPMENT_REALIZATION_ENTITY_SHIPMENT_ID_FIELD'),
				]
			),
			new BooleanField(
				'IS_REALIZATION',
				[
					'values' => array('N', 'Y'),
					'default' => 'Y',
					'title' => Loc::getMessage('SHIPMENT_REALIZATION_ENTITY_IS_REALIZATION_FIELD'),
				]
			),
		];
	}
}