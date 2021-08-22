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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExecLog_Query query()
 * @method static EO_ExecLog_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ExecLog_Result getById($id)
 * @method static EO_ExecLog_Result getList(array $parameters = array())
 * @method static EO_ExecLog_Entity getEntity()
 * @method static \Bitrix\Imopenlines\Model\EO_ExecLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Imopenlines\Model\EO_ExecLog_Collection createCollection()
 * @method static \Bitrix\Imopenlines\Model\EO_ExecLog wakeUpObject($row)
 * @method static \Bitrix\Imopenlines\Model\EO_ExecLog_Collection wakeUpCollection($rows)
 */
class ExecLogTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_exec_log';
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
			new IntegerField( 'ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new StringField('EXEC_FUNCTION', array(
				'validation' => array(__CLASS__, 'validateString'),
				'required' => true
			)),
			new DatetimeField('LAST_EXEC_TIME', array(
				'required' => true,
				'default_value' => new DateTime,
			)),
			new BooleanField('IS_SUCCESS', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y'
			))
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