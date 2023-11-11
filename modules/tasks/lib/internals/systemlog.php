<?
namespace Bitrix\Tasks\Internals;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class SystemLogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SystemLog_Query query()
 * @method static EO_SystemLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SystemLog_Result getById($id)
 * @method static EO_SystemLog_Result getList(array $parameters = [])
 * @method static EO_SystemLog_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\EO_SystemLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\EO_SystemLog_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\EO_SystemLog wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\EO_SystemLog_Collection wakeUpCollection($rows)
 */
class SystemLogTable extends Main\Entity\DataManager
{
	public const ENTITY_TYPE_TEMPLATE = 1;
	public const TYPE_MESSAGE = 1;
	public const TYPE_ERROR = 3;

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