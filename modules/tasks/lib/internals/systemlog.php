<?
namespace Bitrix\Tasks\Internals;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class SystemLogTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_syslog';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				//'title' => Loc::getMessage('SYSLOG_ENTITY_ID_FIELD'),
			),
			'TYPE' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('SYSLOG_ENTITY_TYPE_FIELD'),
			),
			'CREATED_DATE' => array(
				'data_type' => 'datetime',
				//'title' => Loc::getMessage('SYSLOG_ENTITY_CREATED_DATE_FIELD'),
			),
			'MESSAGE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMessage'),
				//'title' => Loc::getMessage('SYSLOG_ENTITY_MESSAGE_FIELD'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('SYSLOG_ENTITY_ENTITY_ID_FIELD'),
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('SYSLOG_ENTITY_ENTITY_TYPE_FIELD'),
			),
			'PARAM_A' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('SYSLOG_ENTITY_PARAM_A_FIELD'),
			),
			'ERROR' => array(
				'data_type' => 'text',
				//'title' => Loc::getMessage('SYSLOG_ENTITY_ERROR_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for MESSAGE field.
	 *
	 * @return array
	 */
	public static function validateMessage()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}