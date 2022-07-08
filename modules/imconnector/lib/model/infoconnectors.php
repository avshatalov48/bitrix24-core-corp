<?php
namespace Bitrix\ImConnector\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;


/**
 * Class ConnectorsInfoTable
 * @package Bitrix\ImOpenLines\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_InfoConnectors_Query query()
 * @method static EO_InfoConnectors_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_InfoConnectors_Result getById($id)
 * @method static EO_InfoConnectors_Result getList(array $parameters = array())
 * @method static EO_InfoConnectors_Entity getEntity()
 * @method static \Bitrix\ImConnector\Model\EO_InfoConnectors createObject($setDefaultValues = true)
 * @method static \Bitrix\ImConnector\Model\EO_InfoConnectors_Collection createCollection()
 * @method static \Bitrix\ImConnector\Model\EO_InfoConnectors wakeUpObject($row)
 * @method static \Bitrix\ImConnector\Model\EO_InfoConnectors_Collection wakeUpCollection($rows)
 */
class InfoConnectorsTable extends Entity\DataManager
{
	/**
	 * Return DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imconnectors_info_connectors';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('LINE_ID', [
				'primary' => true
			]),
			new Entity\TextField('DATA', [
				'serialized' => true,
			]),
			new Entity\DatetimeField('EXPIRES', [
				'default_value' => new Type\DateTime,
			]),
			new Entity\StringField('DATA_HASH', [
				'size' => 32
			]),
		];
	}
}