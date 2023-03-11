<?php
namespace Bitrix\ImConnector\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class DeliveryMarkTable
 *
 * Fields:
 * <ul>
 * <li> MESSAGE_ID int mandatory
 * <li> CHAT_ID int mandatory
 * </ul>
 *
 * @package Bitrix\ImConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ChatLastMessage_Query query()
 * @method static EO_ChatLastMessage_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ChatLastMessage_Result getById($id)
 * @method static EO_ChatLastMessage_Result getList(array $parameters = array())
 * @method static EO_ChatLastMessage_Entity getEntity()
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage createObject($setDefaultValues = true)
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection createCollection()
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage wakeUpObject($row)
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection wakeUpCollection($rows)
 */

class DeliveryMarkTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imconnectors_delivery_mark';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'MESSAGE_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'CHAT_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => new DateTime(),
			],
		];
	}
}
