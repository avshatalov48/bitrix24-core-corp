<?php
namespace Bitrix\Imopenlines\Model;

use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Entity\DataManager,
	\Bitrix\Main\Entity\Validator\Length;

use \Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Entity\TextField,
	\Bitrix\Main\Entity\StringField,
	\Bitrix\Main\Entity\IntegerField,
	\Bitrix\Main\Entity\DatetimeField;
Loc::loadMessages(__FILE__);

/**
 * Class LogTable
 *
 * @package Bitrix\Imopenlines
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Log_Query query()
 * @method static EO_Log_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Log_Result getById($id)
 * @method static EO_Log_Result getList(array $parameters = array())
 * @method static EO_Log_Entity getEntity()
 * @method static \Bitrix\Imopenlines\Model\EO_Log createObject($setDefaultValues = true)
 * @method static \Bitrix\Imopenlines\Model\EO_Log_Collection createCollection()
 * @method static \Bitrix\Imopenlines\Model\EO_Log wakeUpObject($row)
 * @method static \Bitrix\Imopenlines\Model\EO_Log_Collection wakeUpCollection($rows)
 */

class LogTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_log';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return array(
			new IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			new DatetimeField('DATA_TIME', array(
				'default_value' => [__CLASS__, 'getCurrentDate'],
			)),
			new StringField('LINE_ID', array(
				'validation' => array(__CLASS__, 'validateString')
			)),
			new StringField('CONNECTOR_ID', array(
				'validation' => array(__CLASS__, 'validateString')
			)),
			new IntegerField('SESSION_ID'),
			new StringField('TYPE', array(
				'validation' => array(__CLASS__, 'validateString')
			)),
			new TextField('DATA', array(
				'serialized' => true,
			)),
			new TextField('TRACE', array(
				'serialized' => true,
			)),
		);
	}

	public static function validateString()
	{
		return array(
			new Length(null, 255),
		);
	}

	public static function getCurrentDate()
	{
		return new DateTime();
	}
}