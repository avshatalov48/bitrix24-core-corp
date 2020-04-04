<?php
namespace Bitrix\Imopenlines\Model;

use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Entity\DataManager,
	\Bitrix\Main\Entity\Validator\Length;

use \Bitrix\Main\Entity\TextField,
	\Bitrix\Main\Entity\StringField,
	\Bitrix\Main\Entity\IntegerField,
	\Bitrix\Main\Entity\DatetimeField,
	\Bitrix\Main\Entity\BooleanField;

use \Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class EventLogTable
 * @package Bitrix\Imopenlines\Model
 *    Fields:
 *	<ul>
 * <li> ID int mandatory,
 * <li> EVENT_TYPE string(255) NOT NULL
 * <li> DATE_TIME datetime NOT NULL
 * <li> MESSAGE text optional
 * <li> SESSION_ID int optional
 * <li> MESSAGE_ID int optional
 * <li> ADDITIONAL_FIELDS longtext optional
 *	</ul>
 */
class EventLogTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_event_log';
	}

	/**
	 * Entity fields map
	 *
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return array(
			new IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new IntegerField('LINE_ID', array(
				'required' => true
			)),
			new StringField('EVENT_TYPE', array(
				'validation' => array(__CLASS__, 'validateString'),
				'required' => true
			)),
			new DatetimeField('DATE_TIME', array(
				'required' => true,
				'default_value' => new DateTime,
			)),
			new BooleanField('IS_ERROR', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			new TextField('EVENT_MESSAGE', array()),
			new IntegerField('SESSION_ID', array()),
			new IntegerField('MESSAGE_ID', array()),
			new TextField('ADDITIONAL_FIELDS', array(
				'serialized' => true
			)),
		);
	}

	/**
	 * Validate varchar(255) fields
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function validateString()
	{
		return array(
			new Length(null, 255),
		);
	}
}