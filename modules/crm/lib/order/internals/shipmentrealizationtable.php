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
 **/

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